# i3 Time Tracker

i3 Time Tracker is a web app designed to help teams track time spent on various projects. Users can log their work shifts, associate them with projects, and mark shifts as billed or entered in external systems. Administrators can view project overviews, manage users, and monitor unbilled work for accurate reporting and billing.

## Features

- Log and manage work shifts for users and projects
- Mark shifts as billed or entered in external systems
- Admin dashboard to assign users to projects, and mark shifts as billed and/or entered in external systems
- User and project management
- Entra authentication upon landing

## Features in Progress

- Analytics for users and projects

## Requirements

- PHP 8.3+
- Composer
- SQLite (default)

## Getting Started

### 1. Clone the Repository

```bash
git clone https://github.com/uconndxlab/i3-timetracker.git
cd i3-timetracker
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Copy and Configure Environment

(Edit `.env` and set your database, mail, and Entra settings as needed)

```bash
cp .env.example .env
```

For the development server, keep `APP_URL=http://localhost:8000`. Set
`ENTRA_CLIENT_ID`, `ENTRA_TENANT_ID`, and `ENTRA_CLIENT_SECRET` for your Microsoft
Entra application, and register `http://localhost:8000/signin-oidc` as a Web
redirect URI in that application. `ENTRA_REDIRECT_URI` uses `${APP_URL}/signin-oidc`
by default; omitting port `8000` sends the sign-in callback to the wrong server.
After changing an existing `.env`, run `php artisan config:clear` and restart
`php artisan serve`.

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations & Start Development Server

```bash
php artisan migrate

php artisan serve
```

Visit [http://localhost:8000](http://localhost:8000) in your browser.
