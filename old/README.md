<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

# RESERV.atiion Project

Welcome to the RESERV.atiion project. This guide will assist you in setting up the project on your local machine for development and testing purposes.

![Jokes Card](https://readme-jokes.vercel.app/api)

## Getting Started

These instructions will get you a copy of the project up and running on your local machine.

### Prerequisites

Before you begin, ensure you have the following installed:

- Git
- Composer
- PHP (8.2 or higher recommended)
- Node.js with npm

### Installation Steps

Follow these steps to set up the project:

1. **Clone the Repository**

```bash
git clone https://github.com/kaef74/RESERV.atiion.git
```

2. **Navigate to the Project Directory** 

```bash
cd RESERV.atiion
```

3. **Install PHP Dependencies**

```bash
composer install
```

4. **Install Node.js Modules**

```bash
npm install
```

5. **Set Up Environment File**

Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

6. **Configure Database in `.env`**

Open `.env` and update it with your database details:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reserv.atiion
DB_USERNAME=root
DB_PASSWORD=
```

7. **Run Database Migrations**

```bash
php artisan migrate
```

8. **Seed the Database (Optional)**

```bash
php artisan db:seed
```

9. **Generate Application Key**

```bash
php artisan key:generate
```

10. **Start the Laravel Development Server**

```bash
php artisan serve
```

11. **Compile Assets (Optional for Development)**

```bash
npm run dev
```

## Accessing the Project

After starting the Laravel development server, the project will be accessible at [http://localhost:8000](http://localhost:8000).

## Default Accounts

The project includes two predefined accounts:

- **Admin Account**
    - **Email:** `admin@gmail.com`
    - **Password:** `1234567890`
    - **Role:** `super-admin`

- **Test Account**
    - **Email:** `test@gmail.com`
    - **Password:** `1234567890`
    - **Role:** `user`

