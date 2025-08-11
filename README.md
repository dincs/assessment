# Laravel Products API

A Laravel 12 application that provides a **Products Management API** with authentication, role-based access control (admin/non-admin), filtering + pagination, and Excel export functionality.  
Includes authentication (login, logout, email verification) and a full PHPUnit test suite.

---

## 📌 Table of Contents
1. Requirements
2. Setup Instructions
3. Running the Application
4. Authentication
5. API Endpoints
6. Seeder Accounts
7. Testing & Development
8. Assumptions & Design Choices
9. Tech Stack
10. Production Deployment
11. Future Enhancements
12. License

---

## Requirements
- PHP 8.3+
- Composer
- MySQL 8+ (or SQLite for testing)
- Node.js 20+ (if building front-end assets)
- Git

---

## Setup Instructions

```bash
# Clone repository
git clone <your-repo-url>
cd <your-project-folder>

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your .env for DB connection and Swagger
# Example values:
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_api
DB_USERNAME=root
DB_PASSWORD=

# Swagger constants
L5_SWAGGER_CONST_HOST=http://localhost:8000
L5_SWAGGER_CONST_FRONTEND_URL=http://localhost:3000
L5_SWAGGER_CONST_API_VERSION=v1

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost

# Run migrations & seeders
php artisan migrate --seed

# Link storage (if needed)
php artisan storage:link

# (Optional) Build front-end assets
npm install && npm run build
```

---

## Running the Application

```bash
php artisan serve
```

Default URL: **http://localhost:8000**

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

## Testing & Development

### Run All Tests
```bash
php artisan test
```

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

### Get a Bearer Token for Swagger/Postman
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('email', 'admin@example.com')->first();
$token = $user->createToken('API Token')->plainTextToken;
$token;
```
Copy the token and use it in Swagger/Postman:
```
Authorization: Bearer <your-token-here>
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
