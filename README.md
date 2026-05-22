# Portal TIK Backend (Laravel + Docker)

Backend untuk sistem smart gate / gate controller berbasis IoT dengan Laravel dan Docker. Panduan ini fokus pada cara menjalankan proyek menggunakan Docker.

## Prasyarat

- Docker dan Docker Compose sudah terpasang

## Setup Pertama Kali

1. Salin file environment

```bash
cp .env.example .env
```

2. Jalankan container

```bash
docker compose up -d --build
```

3. Install dependency PHP

```bash
docker compose exec app composer install
```

4. Generate APP key

```bash
docker compose exec app php artisan key:generate
```

5. Jalankan migrasi database

```bash
docker compose exec app php artisan migrate
```

6. (Opsional) Jalankan seeder

```bash
docker compose exec app php artisan db:seed
```

## Akses Aplikasi

Nginx expose di port `8000`.

- `http://localhost:8000`
- `http://<IP-LAN>:8000`

## Perintah Berguna

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f nginx
```

## Reset Data

```bash
docker compose exec app php artisan migrate:fresh --seed
```

## Troubleshooting Singkat

- Jika response 500: cek log `storage/logs/laravel.log`
- Jika perubahan `.env` belum terbaca: jalankan `php artisan optimize:clear`

```bash
docker compose exec app php artisan optimize:clear
```
