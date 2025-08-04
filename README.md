# i3 Time Tracker

i3 Time Tracker is a web app designed to help teams track time spent on various projects. Users can log their work shifts, associate them with projects, and mark shifts as billed or entered in external systems. Administrators can view project overviews, manage users, and monitor unbilled work for accurate reporting and billing.

## Features

- Log and manage work shifts for users and projects
- Mark shifts as billed or entered in external systems
- Admin dashboard to assign users to projects, and mark shifts as billed and/or entered in external systems
- User and project management
- CAS authentication upon landing

## Features in Progress

- Analytics for users and projects

## Requirements

- PHP 8.1+
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

(Edit `.env` and set your database and mail settings as needed)

```bash
cp .env.example .env
```

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