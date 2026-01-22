

# Sistema de Alquiler de Disfraces

Proyecto web desarrollado en Laravel y Filament PHP para la gestión de alquiler de disfraces.

## Requisitos

- **PHP** >= 8.2
- **MySQL**
- **Composer**
- **Node.js y npm** (para assets frontend)
- **Extensiones recomendadas:** Filament, DomPDF, Spatie Laravel Permission

## Instalación

1. Clona el repositorio y entra a la carpeta del proyecto.
2. Instala dependencias backend:
	```
	composer install
	```
3. Instala dependencias frontend:
	```
	npm install && npm run build
	```
4. Copia el archivo `.env.example` a `.env` y configura tu base de datos.
5. Ejecuta las migraciones y seeders:
	```
	php artisan migrate --seed
	```
6. Inicia el servidor:
	```
	php artisan serve
	```

## Acceso

- Ingresa a: [http://127.0.0.1:8000/disfraces](http://127.0.0.1:8000/disfraces)
- El sistema cuenta con autenticación (login).
- Se incluyen datos de prueba con el comando `php artisan db:seed`.

## Funcionalidades principales

- Gestión de clientes y usuarios.
- Registro y administración de disfraces y piezas.
- Alquiler y devolución de disfraces.
- Control de stock y estado de piezas.
- Reportes y generación de documentos PDF.

## Tecnologías y paquetes usados

- Laravel 11.x
- Filament PHP
- Spatie Laravel Permission
- DomPDF
- Laravel Boost
