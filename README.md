# Laravel Products API

A Laravel 12 application that provides a **Products Management API** with authentication, role-based access control (admin/non‑admin), filtering + pagination, and Excel export. Includes login/logout and a full PHPUnit test suite. Ships with **Docker** and **Swagger UI**.

---

## 📌 Table of Contents
1. Requirements
2. Local Setup
3. Docker Setup (recommended)
4. Running the App
5. Authentication
6. API Endpoints
7. Seeder Accounts
8. Swagger Documentation
9. Testing
10. Assumptions & Design Choices
11. Tech Stack
12. Production Deployment
13. Troubleshooting
14. Future Enhancements
15. License

---

## 1) Requirements
- PHP 8.3+
- Composer 2.7+
- MySQL 8+ (or SQLite for tests)
- Node.js 20+ (only if you build front-end assets)
- Git
- Docker (Desktop) 4.30+

> **Note:** The API is backend-only. Node/Vite is not strictly required to use the API.

---

## 2) Local Setup
```bash
git clone <your-repo-url>
cd <your-project-folder>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
# (Optional) If you have front-end assets
npm install && npm run build
```

Run locally:
```bash
php artisan serve
```
- App: **http://localhost:8000**
- Swagger: **http://localhost:8000/api/documentation**

---

## 3) Docker Setup (recommended)

### Project Structure
```
my-laravel-app/
├── app/
├── bootstrap/
├── config/
├── public/
├── routes/
├── storage/
├── artisan
├── composer.json
├── Dockerfile
├── docker-compose.yml
└── .env.docker
```

### 3.1 `.env.docker`
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=appdb
DB_USERNAME=appuser
DB_PASSWORD=apppass

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

# Swagger + Sanctum
L5_SWAGGER_CONST_HOST=http://localhost:8080
L5_SWAGGER_CONST_FRONTEND_URL=http://localhost:3000
L5_SWAGGER_CONST_API_VERSION=v1
SANCTUM_STATEFUL_DOMAINS=localhost
```

### 3.2 `Dockerfile`
```dockerfile
FROM php:8.2-apache

# System deps
RUN apt-get update && apt-get install -y \
    libzip-dev zip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    git curl unzip \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql zip gd

# Apache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# App
COPY . /var/www/html
COPY .env.docker /var/www/html/.env

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Install PHP deps (no dev on prod images; dev is fine for local)
RUN composer install --no-interaction --prefer-dist

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache
```

### 3.3 `docker-compose.yml`
```yaml
services:
  app:
    build: .
    container_name: myapp
    ports:
      - "8080:80"
    depends_on:
      mysql:
        condition: service_healthy

  mysql:
    image: mysql:8.4
    container_name: mysql
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: appdb
      MYSQL_USER: appuser
      MYSQL_PASSWORD: apppass
    ports:
      - "3307:3306"
    volumes:
      - dbdata:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-uroot", "-prootpass"]
      interval: 5s
      timeout: 3s
      retries: 20

volumes:
  dbdata:
```

### 3.4 Build & Start (Unix/macOS bash)
```bash
docker compose down -v
docker compose up -d --build
docker compose exec app sh -lc "php artisan key:generate && php artisan storage:link && php artisan migrate --force && php artisan db:seed || true"
docker compose exec app sh -lc "php artisan route:clear && php artisan route:cache && php artisan config:clear && php artisan l5-swagger:generate"
```

**Windows PowerShell note:** `|| true` is not valid in PowerShell. Run it as separate commands instead:
```powershell
docker compose down -v
docker compose up -d --build
docker compose exec app sh -lc "php artisan key:generate"
docker compose exec app sh -lc "php artisan storage:link"
docker compose exec app sh -lc "php artisan migrate --force"
docker compose exec app sh -lc "php artisan db:seed"
docker compose exec app sh -lc "php artisan route:clear && php artisan route:cache && php artisan config:clear && php artisan l5-swagger:generate"
```

**URLs (Docker):**
- App: **http://localhost:8080**
- Swagger: **http://localhost:8080/api/documentation**
- MySQL: **127.0.0.1:3307** (DB: `appdb` / User: `appuser` / Pass: `apppass`)

---

## 4) Running the Application
- **Local:** `php artisan serve` → http://localhost:8000
- **Docker:** `docker compose up -d` → http://localhost:8080

---

## 5) Authentication

- **Login** (`POST /login`)
  - If the request **expects HTML**, it redirects to `/admin` (session login).
  - If the request **expects JSON**, it returns **204 No Content** (you should use a **token** for API calls).

- **Logout** (`POST /logout`)
  - HTML: redirects to `/login`
  - JSON: returns **204 No Content**

### Getting a Bearer Token (Local)
```bash
php artisan tinker
>>> $user = App\Models\User::where('email', 'admin@example.com')->first();
>>> $token = $user->createToken('API Token')->plainTextToken;
>>> $token
```

### Getting a Bearer Token (Docker)
```bash
docker compose exec app php artisan tinker
>>> $user = App\Models\User::where('email', 'admin@example.com')->first();
>>> $token = $user->createToken('API Token')->plainTextToken;
>>> $token
```

Use it in the **Authorization** header:
```
Authorization: Bearer <your-token>
```

---

## 6) API Endpoints

Base path: `/api`

| Method  | Path                        | Auth        | Role   | Description                                   |
|--------:|-----------------------------|-------------|--------|-----------------------------------------------|
|  GET    | `/products`                 | Sanctum     | Admin  | List products (filters & pagination)          |
|  GET    | `/products/export`          | Sanctum     | Admin  | Download Excel export                          |
|  GET    | `/products/{product}`       | Sanctum     | Admin  | Show a single product                          |
|  POST   | `/products`                 | Sanctum     | Admin  | Create product                                 |
|  PUT    | `/products/{product}`       | Sanctum     | Admin  | Update product                                 |
|  PATCH  | `/products/{product}`       | Sanctum     | Admin  | Partial update                                 |
|  DELETE | `/products/{product}`       | Sanctum     | Admin  | Delete product                                 |
|  POST   | `/products/bulk-delete`     | Sanctum     | Admin  | Bulk delete                                    |

### Query Parameters for `/products`
- `enabled` → `true|false|1|0`
- `category_id` → integer
- `per_page` → integer (default: 15)
- `page` → integer (default: 1)

### Export `/products/export`
- Accepts the same filters as `/products`
- Returns an Excel file: **products.xlsx**

**cURL examples**
```bash
# list (docker)
curl -H "Authorization: Bearer <TOKEN>" "http://localhost:8080/api/products?enabled=1&per_page=10"

# export (docker)
curl -H "Authorization: Bearer <TOKEN>" -L -o products.xlsx "http://localhost:8080/api/products/export?enabled=1"
```

---

## 7) Seeder Accounts
**Admin**
```
Email: admin@example.com
Password: password
```
**User**
```
Email: user@example.com
Password: password
```

---

## 8) Swagger Documentation

Open **Swagger UI**:
- Local:  **http://localhost:8000/api/documentation**
- Docker: **http://localhost:8080/api/documentation**

Ensure `.env` contains (adjust for local vs docker):
```env
# Local
L5_SWAGGER_CONST_HOST=http://localhost:8000
L5_SWAGGER_CONST_FRONTEND_URL=http://localhost:3000
L5_SWAGGER_CONST_API_VERSION=v1
SANCTUM_STATEFUL_DOMAINS=localhost

# Docker
L5_SWAGGER_CONST_HOST=http://localhost:8080
L5_SWAGGER_CONST_FRONTEND_URL=http://localhost:3000
L5_SWAGGER_CONST_API_VERSION=v1
SANCTUM_STATEFUL_DOMAINS=localhost
```

### Authorizing in Swagger
1. Generate a token (see section 5).
2. Click **Authorize** and paste exactly:
   ```
   Bearer <your-token-here>
   ```

If you change any Swagger-related env values, re-generate:
```bash
php artisan l5-swagger:generate
# or (Docker)
docker compose exec app php artisan l5-swagger:generate
```

---

## 9) Testing

Run the whole suite:
```bash
php artisan test
```

Only Product API tests:
```bash
php artisan test --filter=ProductApiTest
```

Single test method:
```bash
php artisan test --filter=ProductApiTest::admin_can_list_products_with_filters_and_pagination
```

Rebuild DB with seeds:
```bash
php artisan migrate:fresh --seed
```

Useful:
```bash
php artisan route:list
php artisan optimize:clear
```

---

## 10) Assumptions & Design Choices
- **Role-based access**: Only admins manage products.
- **Route ordering**: `/products/export` is declared **before** `/products/{product}` with a numeric constraint to avoid collisions.
- **Separated concerns**: API controllers and web controllers are separated.
- **Guards**: `auth:sanctum` for API, session guard for web.
- **Validation**: Strict request validation (including query params).
- **Exports**: Implemented via Maatwebsite/Excel and verified in tests.
- **Tests**: Major flows covered (success/failure paths).

---

## 11) Tech Stack
- **Framework**: Laravel 12
- **Language**: PHP 8.3
- **Database**: MySQL 8 (SQLite for tests supported)
- **Auth**: Laravel Sanctum
- **Exports**: Maatwebsite/Excel
- **Docs**: L5‑Swagger
- **Testing**: PHPUnit + Laravel Test Helpers
- **CI/CD**: GitHub Actions

---

## 12) Production Deployment
1. Configure `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   L5_SWAGGER_CONST_HOST=https://yourdomain.com
   L5_SWAGGER_CONST_FRONTEND_URL=https://frontend.yourdomain.com
   L5_SWAGGER_CONST_API_VERSION=v1
   SANCTUM_STATEFUL_DOMAINS=yourdomain.com
   ```
2. Deploy & run:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan l5-swagger:generate
   ```
3. (Optional) Start a queue worker:
   ```bash
   php artisan queue:work --daemon
   ```

---

## 13) Troubleshooting

**Swagger calls port 8000 inside Docker (should be 8080)**  
Set `L5_SWAGGER_CONST_HOST=http://localhost:8080` in `.env.docker`, then:
```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan l5-swagger:generate
docker compose restart app
```
Open `http://localhost:8080/api/documentation` again.

**Swagger 404 for endpoints**
- Make sure routes exist under `routes/api.php`.
- Clear/refresh caches: `php artisan optimize:clear` (or via `docker compose exec app ...`).
- Re-generate docs: `php artisan l5-swagger:generate`.

**PowerShell shows `'||' is not a valid statement separator`**
- Split commands onto separate lines (see Windows note in §3.4).

**Composer not found during Docker build**
- This Dockerfile installs composer at `/usr/local/bin/composer`. Ensure the `RUN curl ...` line remains.

**MySQL port already in use**
- Change the host port (`"3308:3306"`) in `docker-compose.yml` and update your DB client accordingly.

---

## 14) Future Enhancements
- Role & permission management UI
- Advanced product search & sorting
- Soft delete & restore
- Response caching for listings

---

## 15) License
This project is open-sourced under the [MIT license](LICENSE).
