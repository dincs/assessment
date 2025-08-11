# Laravel Products API

A Laravel 12 application that provides a **Products Management API** with authentication, role-based access control (admin/non-admin), filtering + pagination, and Excel export functionality. Includes authentication (login, logout) and a full PHPUnit test suite.

---

## 📌 Table of Contents
1. Requirements
2. Local Setup Instructions
3. Docker Setup
4. Running the Application
5. Authentication
6. API Endpoints
7. Seeder Accounts
8. Swagger Documentation
9. Testing & Development
10. Assumptions & Design Choices
11. Tech Stack
12. Production Deployment
13. Future Enhancements
14. License

---

## Requirements
- PHP 8.3+
- Composer
- MySQL 8+ (or SQLite for testing)
- Node.js 20+ (if building front-end assets)
- Git
- Docker (for containerized setup)

---

## Local Setup Instructions
```bash
git clone <your-repo-url>
cd <your-project-folder>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
```

---

## Running the Application

```bash
php artisan serve
```

Default URL: **http://localhost:8000**

---

## Docker Setup

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

### 1) `.env.docker`
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
```

### 2) Dockerfile
```dockerfile
FROM php:8.2-apache
RUN apt-get update && apt-get install -y libzip-dev zip libpng-dev libjpeg-dev libfreetype6-dev && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite
RUN docker-php-ext-configure gd --with-freetype --with-jpeg  && docker-php-ext-install pdo_mysql zip gd
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf  && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
COPY . /var/www/html
COPY .env.docker /var/www/html/.env
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /var/www/html
RUN composer install --no-interaction --prefer-dist
RUN chown -R www-data:www-data storage bootstrap/cache
```

### 3) docker-compose.yml
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

### 4) Build & Start
```bash
docker compose down -v
docker compose up -d --build
docker compose exec app sh -lc "php artisan key:generate && php artisan storage:link && php artisan migrate --force && php artisan db:seed || true"
docker compose exec app sh -lc "php artisan route:clear && php artisan route:cache && php artisan config:clear && php artisan l5-swagger:generate"

```
### 5) Getting a Bearer Token for Swagger Authorization
```bash
docker compose exec app php artisan tinker
>>> $user = App\Models\User::where('email', 'admin@example.com')->first();
>>> $token = $user->createToken('API Token')->plainTextToken;
>>> $token
```
Copy this token and in Swagger UI click **Authorize** → paste:
```
Bearer <your-token>
```

```
App: http://localhost:8080  
MySQL: 127.0.0.1:3307 (DB: appdb / User: appuser / Pass: apppass)

---

## Running the Application
- Local: `php artisan serve`
- Docker: `docker compose up -d`

---

## Authentication

- **Login** (`POST /login`)
  - Web request → redirects to `/admin`
  - JSON request → returns **204 No Content**
- **Logout** (`POST /logout`)
  - Web request → redirects to `/login`
  - JSON request → returns **204 No Content**

---

## API Endpoints

Base path: `/api`

| Method  | Path                        | Auth        | Role   | Description                                   |
|--------:|-----------------------------|-------------|--------|-----------------------------------------------|
|  GET    | `/products`                 | Sanctum     | Admin  | List products (with filters & pagination)     |
|  GET    | `/products/export`          | Sanctum     | Admin  | Download Excel export                         |
|  GET    | `/products/{product}`       | Sanctum     | Admin  | Show a single product                         |
|  POST   | `/products`                 | Sanctum     | Admin  | Create product                                |
|  PUT    | `/products/{product}`       | Sanctum     | Admin  | Update product                                |
|  PATCH  | `/products/{product}`       | Sanctum     | Admin  | Partial update                                |
|  DELETE | `/products/{product}`       | Sanctum     | Admin  | Delete product                                |
|  POST   | `/products/bulk-delete`     | Sanctum     | Admin  | Bulk delete                                   |

### Query Parameters for `/products`
- `enabled` → `true|false|1|0`
- `category_id` → integer
- `per_page` → integer (default: 15)
- `page` → integer (default: 1)

### Export `/products/export`
- Accepts same filters as `/products`
- Returns an Excel file (`products.xlsx`)

---

## Seeder Accounts

Two accounts are created by default:

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

## Swagger Documentation

Once the app is running, open **Swagger UI** at:

```
- Local: http://localhost:8000/api/documentation
- Docker: http://localhost:8080/api/documentation
```

Make sure the following `.env` values are set for Swagger to work correctly (Local):

```env
L5_SWAGGER_CONST_HOST=http://localhost:8000
L5_SWAGGER_CONST_FRONTEND_URL=http://localhost:3000
L5_SWAGGER_CONST_API_VERSION=v1
SANCTUM_STATEFUL_DOMAINS=localhost
```

### Authorizing in Swagger
1. Generate a Bearer token using Tinker:
   ```bash
   php artisan tinker
   >>> $user = App\Models\User::where('email', 'admin@example.com')->first();
   >>> $user->createToken('API Token')->plainTextToken;
   ```
2. In Swagger UI, click **Authorize** and paste:
   ```
   Bearer <your-token-here>
   ```

---

## Testing & Development

### Run Only Product API Tests
```bash
php artisan test --filter=ProductApiTest
```

### Run a Single Test Method
```bash
php artisan test --filter=ProductApiTest::admin_can_list_products_with_filters_and_pagination
```

### Refresh the Database
Re-run migrations from scratch with seeders:
```bash
php artisan migrate:fresh --seed
```
Without seeders:
```bash
php artisan migrate:fresh
```

### Useful Artisan Commands
List all routes:
```bash
php artisan route:list
```
Clear cache (routes, config, views):
```bash
php artisan optimize:clear
```

---

## Assumptions & Design Choices

- **Role-based access**: Only admins manage products
- **Route ordering**: `/products/export` is declared before `/products/{product}` with numeric constraint to avoid collisions
- **Separated concerns**: API controllers and web controllers are separated for clarity
- **Guards**: `auth:sanctum` for API, session for web
- **Validation**: All requests validated (including query params)
- **Exports**: Built with Maatwebsite/Excel and tested for correct filename and class
- **Tests**: Written for all major flows, including failure cases

---

## Tech Stack

- **Framework**: Laravel 12
- **Language**: PHP 8.3
- **Database**: MySQL 8 (or SQLite for testing)
- **Auth**: Laravel Sanctum
- **Exports**: Maatwebsite/Excel
- **Testing**: PHPUnit + Laravel Test Helpers
- **CI/CD**: GitHub Actions

---

## Production Deployment

1. Configure `.env` with production settings:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   L5_SWAGGER_CONST_HOST=https://yourdomain.com
   L5_SWAGGER_CONST_FRONTEND_URL=https://frontend.yourdomain.com
   L5_SWAGGER_CONST_API_VERSION=v1
   SANCTUM_STATEFUL_DOMAINS=yourdomain.com
   ```
2. Run:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. Set up a queue worker if needed:
   ```bash
   php artisan queue:work
   ```

---

## Future Enhancements

- API documentation with Swagger/OpenAPI
- Role & permission management via UI
- Advanced product search & sorting
- Soft delete & restore functionality
- Response caching for product listing

---

## License

This project is open-sourced under the [MIT license](LICENSE).
