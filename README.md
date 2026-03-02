Пет проект с админ панелью Filament, частично перенесенный из https://github.com/IDEPTA/socialNetwork.
Стек:
- PHP 8.4
- Laravel 12
- Docker
- PgSQL
- Filament 5

Запуск:
- `docker compose up -d --build`
- в контейнере socialNetwork_app `composer install`
- `php artisan key:generate`
- `php artisan migrate`
- `php artisan db:seed`

Вход в админку:
`localhost:8000/admin`
Достпуы:
login: `admin@soc.local`
password: `password`
