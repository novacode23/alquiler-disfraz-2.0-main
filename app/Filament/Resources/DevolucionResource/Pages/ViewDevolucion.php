<?php

namespace App\Filament\Resources\DevolucionResource\Pages;

use App\Enums\DevolucionPiezasEnum;
use App\Filament\Resources\DevolucionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Dom\Text;
use Illuminate\Support\Facades\Blade;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;

class ViewDevolucion extends ViewRecord
{
    protected static string $resource = DevolucionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargar_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('secondary')
                ->action(function () {
                    $record = $this->record;

                    return response()->streamDownload(
                        function () use ($record) {
                            echo Pdf::loadHtml(
                                Blade::render('pdf.devolucion-alquiler', ['record' => $record])
                            )->output();
                        },
                        'devolucion_' . $record->id . '.pdf',
                        ['Content-Type' => 'application/pdf']
                    );
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Ver Devolución';
    }

    public function getInfolist(string $name): ?\Filament\Infolists\Infolist
    {
        $record = $this->record;

        return \Filament\Infolists\Infolist::make()
            ->record($record)
            ->schema([
                Section::make('Resumen de Devolución')->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('alquiler.cliente.name')->label('Nombre del Cliente'),
                        TextEntry::make('alquiler.fecha_alquiler')->label('Fecha de Alquiler')->date(),

                        TextEntry::make('fecha_devolucion_real')->label('Fecha de Devolución Real')->dateTime(),
                        TextEntry::make('multa')->label('Multa por Retraso')->prefix('Bs ')->numeric(),
                    ]),
                ]),
                /*Section::make('Piezas Devueltas en Buen Estado')->schema([
                    RepeatableEntry::make('alquiler.alquilerDisfrazs')
                        ->label(false)
                        ->schema([
                            TextEntry::make('disfraz.nombre')->label(false)->columnSpanFull(),
                            RepeatableEntry::make('alquilerPiezas')
                                ->label(false)
                                ->schema([
                                    TextEntry::make('pieza.name')->label(false),
                                    TextEntry::make('devolucionPiezas.cantidad')->label(false),
                                ])
                                ->columns(2),
                        ])

                        ->columns(1),
                ]),*/
                Section::make('Piezas Dañadas o Perdidas')->schema([
                    RepeatableEntry::make('piezas_danadas_por_disfraz')
                        ->label(false)
                        ->schema([
                            TextEntry::make('disfraz')->label(false)->columnSpanFull(),
                            RepeatableEntry::make('piezas')
                                ->label(false)
                                ->schema([
                                    TextEntry::make('alquilerDisfrazPieza.pieza.name')->label('Pieza'),
                                    TextEntry::make('cantidad')->label('Cantidad'),
                                    TextEntry::make('estado_pieza')
                                        ->label('Estado')
                                        ->formatStateUsing(
                                            fn($state) => \App\Enums\DevolucionPiezasEnum::tryFrom($state)?->getLabel()
                                        )
                                        ->color(
                                            fn($state) => \App\Enums\DevolucionPiezasEnum::tryFrom($state)?->getColor()
                                        ),
                                ])
                                ->columns(3),
                        ]),
                ]),
            ]);
    }
}
