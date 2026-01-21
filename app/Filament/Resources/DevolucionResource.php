<?php

namespace App\Filament\Resources;

use App\Enums\DevolucionPiezasEnum;
use App\Filament\Resources\DevolucionResource\Pages;
use App\Filament\Resources\DevolucionResource\RelationManagers;
use App\Models\Alquiler;
use App\Models\AlquilerDisfraz;
use App\Models\AlquilerDisfrazPieza;
use App\Models\Devolucion;
use App\Models\Disfraz;
use App\Models\DisfrazPieza;
use App\Models\Pieza;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;

class DevolucionResource extends Resource
{
    protected static ?string $model = Devolucion::class;
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';
    public static function getModelLabel(): string
    {
        return 'Devolución';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Devoluciones';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de la Devolución')->schema([
                Forms\Components\Hidden::make('alquiler_id')
                    ->default(function () {
                        // Obtén el alquiler_id desde la consulta de la URL
                        return request()->query('alquiler_id');
                    })
                    ->required(),

                Forms\Components\TextInput::make('cliente_name')
                    ->label('Nombre del Cliente')
                    ->default(function ($get) {
                        $alquilerId = $get('alquiler_id');

                        return Alquiler::find($alquilerId)?->cliente->name ?? 'No disponible';
                    })
                    ->disabled(),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\DatePicker::make('fecha_alquiler')
                        ->label('Fecha de Alquiler')
                        ->default(function ($get) {
                            $alquilerId = $get('alquiler_id');
                            return Alquiler::find($alquilerId)?->fecha_alquiler ?? 'No disponible';
                        })
                        ->disabled(),
                    Forms\Components\DatePicker::make('fecha_devolucion')
                        ->label('Fecha de Devolución')
                        ->default(function ($get) {
                            $alquilerId = $get('alquiler_id');

                            return Alquiler::find($alquilerId)?->fecha_devolucion ?? 'No disponible';
                        })
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('fecha_devolucion_real')
                        ->label('Fecha Actual')
                        ->default(now())
                        ->displayFormat('d/m/Y H:i')
                        ->required(),
                    Forms\Components\TextInput::make('multa')
                        ->prefix('Bs')
                        ->reactive()
                        ->dehydrated(true)
                        ->afterStateHydrated(function ($set, $get) {
                            self::actualizarMulta($set, $get);
                        })
                        ->afterStateUpdated(function ($set, $get) {
                            self::actualizarMulta($set, $get);
                        })
                        ->required(),
                ]),
            ]),
            Forms\Components\Section::make('Piezas Dañadas y/o Perdidas')->schema([
                Repeater::make('devolucionPiezas') //este modelo lo cree para usar repeater  con una tabla pivote
                    ->relationship()
                    ->label(false)
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Grid::make(15)->schema([
                            Select::make('alquiler_disfraz_id')
                                ->label('Disfraces Alquilados')
                                ->options(function (callable $get) {
                                    // Necesitas saber el alquiler_id para filtrar
                                    $alquilerId = $get('../../alquiler_id');
                                    // Filtrar solo las piezas que pertenecen a ese alquiler
                                    return AlquilerDisfraz::where('alquiler_id', $alquilerId)
                                        ->get()
                                        ->mapWithKeys(function ($item) {
                                            $nombreDisfraz = $item->disfraz->nombre ?? 'Disfraz';
                                            $genero = $item->disfraz->genero ?? 'Disfraz';
                                            return [
                                                $item->id => "$nombreDisfraz - $genero",
                                            ];
                                        });
                                })
                                ->live()
                                ->searchable()
                                ->columnSpan(5)
                                ->reactive()
                                ->required(),
                            Select::make('alquiler_disfraz_pieza_id')
                                ->label('Piezas Alquiladas')
                                ->placeholder('selecciona...')
                                ->options(function (Get $get): Collection {
                                    // Necesitas saber el alquiler_id para filtrar
                                    $disfrazId = $get('alquiler_disfraz_id');
                                    $piezaIds =
                                        AlquilerDisfraz::where('id', $disfrazId)->value('piezas_seleccionadas') ?? [];
                                    return AlquilerDisfrazPieza::whereIn('pieza_id', $piezaIds)->where('alquiler_disfraz_id', $disfrazId)
                                        ->with('pieza.tipo')
                                        ->get()
                                        ->mapWithKeys(function ($item) {
                                            $nombrePieza = $item->pieza->tipo->name ?? 'Pieza';
                                            return [
                                                $item->id => "{$nombrePieza}",
                                            ];
                                        });
                                    // Filtrar solo las piezas que pertenecen a ese alquiler
                                })
                                ->searchable()
                                ->columnSpan(3)
                                ->reactive()
                                ->required(),

                            TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->numeric()
                                ->minValue(1)
                                ->rules([
                                    fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use (
                                        $get
                                    ) {
                                        $piezaIdActual = $get('alquiler_disfraz_pieza_id');
                                        $repeaterState = collect($get('../../devolucionPiezas'));
                                        if (!$piezaIdActual) {
                                            return;
                                        }
                                        $sumaPorPieza = $repeaterState
                                            ->where('alquiler_disfraz_pieza_id', $piezaIdActual)
                                            ->sum('cantidad');
                                        $pieza = \App\Models\AlquilerDisfrazPieza::find($piezaIdActual);
                                        $reservado = $pieza?->cantidad_reservada ?? 0;
                                        if ($sumaPorPieza > $reservado) {
                                            $fail(
                                                "Has superado el límite disponible: $reservado. Estás usando $sumaPorPieza."
                                            );
                                        }
                                    },
                                ])
                                ->columnSpan(2)
                                ->required(),
                            Forms\Components\Select::make('estado_pieza')
                                ->label('Estado de la pieza')
                                ->options([
                                    DevolucionPiezasEnum::LEVE->value => DevolucionPiezasEnum::LEVE->getLabel(),
                                    DevolucionPiezasEnum::MODERADO->value => DevolucionPiezasEnum::MODERADO->getLabel(),
                                    DevolucionPiezasEnum::GRAVE->value => DevolucionPiezasEnum::GRAVE->getLabel(),
                                    DevolucionPiezasEnum::PERDIDO->value => DevolucionPiezasEnum::PERDIDO->getLabel(),
                                ])
                                ->columnSpan(3)
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $alquilerPiezaId = $get('alquiler_disfraz_pieza_id');
                                    $piezaId = AlquilerDisfrazPieza::where('id', $alquilerPiezaId)->value('pieza_id');
                                    if ($state === DevolucionPiezasEnum::LEVE->value) {
                                        $set('multa_pieza', 10);
                                    } elseif ($state === DevolucionPiezasEnum::MODERADO->value) {
                                        $multa = Pieza::where('id', $piezaId)->value('valor_reposicion');
                                        $set('multa_pieza', $multa * 0.4 + 10);
                                    } else {
                                        $multa = Pieza::where('id', $piezaId)->value('valor_reposicion');
                                        $cantidad = $get('cantidad') ?? 1;
                                        $set('multa_pieza', $multa * $cantidad);
                                    }
                                }),
                            TextInput::make('multa_pieza')
                                ->label('Multa (Bs)')
                                ->numeric()
                                ->minValue(1)
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(true),
                        ]),
                    ])
                    ->itemLabel(function (array $state): string {
                        $disfraz = AlquilerDisfrazPieza::where('id', $state['alquiler_disfraz_pieza_id'])
                        ->where('alquiler_disfraz_id', $state['alquiler_disfraz_id'])
                            ->with('pieza')
                            ->first();
                        if (!$disfraz) {
                            return 'Nuevo disfraz';
                        }
    
                        return "{$disfraz->pieza->name} - Stock: {$disfraz->cantidad_reservada}";
                    })
                    ->addActionLabel('Añadir Pieza'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('alquiler_id')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('fecha_devolucion_real')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('multa')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\CreateAction::make()
                    ->form(fn(Form $form) => $this->form($form))
                    ->action(function (Model $record) {
                        return response()->streamDownload(
                            function () use ($record) {
                                echo Pdf::loadHtml(
                                    Blade::render('pdf.devolucion-alquiler', ['record' => $record])
                                )->output();
                            },
                            $record->number . '.pdf',

                            [
                                'Content-Type' => 'application/pdf',
                            ]
                        );
                    }),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
                //
            ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevolucions::route('/'),
            'create' => Pages\CreateDevolucion::route('/create'),
            'view' => Pages\ViewDevolucion::route('/{record}'),
        ];
    }
    protected static function actualizarMulta(callable $set, callable $get): void
    {
        $fechaDevolucion = Carbon::parse($get('fecha_devolucion'))->startOfDay();
        $fechaReal = Carbon::parse($get('fecha_devolucion_real'))->startOfDay();

        if ($fechaReal->lte($fechaDevolucion)) {
            $set('multa', 0);
            return;
        }

        $diasDeRetraso = $fechaDevolucion->diffInDays($fechaReal);
        $alquilerId = $get('alquiler_id');

        $alquilerDisfraces = AlquilerDisfraz::where('alquiler_id', $alquilerId)->get();

        $total = $alquilerDisfraces->sum(fn($item) => $item->precio_alquiler * $item->cantidad);

        $set('multa', round($total * $diasDeRetraso, 2));
    }
}
