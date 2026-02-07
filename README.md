# Ability Examiner - HR Application System

Managing job vacancies, applications, and CV analysis with AI support.
Built with **Laravel 12**, **Filament PHP**, and **Tailwind CSS**.

## System Requirements

- **PHP**: 8.2 or higher
- **Composer**
- **Node.js** & **NPM**
- **MySQL** or compatible database

## Installation Guide

### 1. Clone the Repository

```bash
git clone https://github.com/rantiekaa/abilityExaminer.git
cd abilityExaminer
```

### 2. Install Dependencies & Setup

We have a convenient setup script that handles:
- Composer install
- Environment (.env) creation
- Key generation
- Database migration
- NPM install & build assets

```bash
composer run setup
```

**Note:** Ensure your database configuration in `.env` is correct before running migrations (if setup fails on migration step).

### 3. Filament Admin Setup & Permissions

This project uses **Filament Shield** for roles and permissions.

#### Generate Shield Permissions
Use this command to generate all necessary permissions and policies to ensure roles work correctly:

```bash
php artisan shield:generate --all
```

#### Create a Super Admin User
Create a user with Super Admin access to manage the system:

```bash
php artisan shield:super-admin
# Follow the prompts to set name, email, and password.
```
*Alternatively, you can create a standard Filament user:*
```bash
php artisan make:filament-user
```

### 4. Custom Styling (Important)

This project uses a custom SCSS file for specific Filament styles (e.g., AI Analysis cards).
The file is located at: `resources/sass/custom/filament.scss`.

If you make changes to SCSS files or pull updates, you **must** rebuild the assets:

```bash
npm run build
```

Or for development (watch mode):

```bash
npm run dev
```

## Running the Application

Start the local development server:

```bash
php artisan serve
```

Access the admin panel at: `http://localhost:8000/admin`

## Key Features

- **Job Vacancies Management**: Create and manage job postings.
- **Application Tracking**: View applicant details, CVs, and status.
- **AI CV Analysis**: Automated scoring and executive summary of candidate CVs.
- **Email Notifications**: Automated email notifications for acceptance and rejection.

## Troubleshooting

- **Missing Styles?**: Run `npm run build` to ensure the custom Filament stylesheet is compiled.
- **Permission Errors?**: Run `php artisan shield:generate --all` to refresh permissions.
