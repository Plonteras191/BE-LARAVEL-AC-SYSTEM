# Air-Conditioning Appointment System SETUP
---

### README_BACKEND.md

```markdown
# Backend (Laravel 10) – Setup & Instructions

This README describes how to set up and run the **Laravel 10** backend for the Air-Conditioning Service Appointment System.

---

## Table of Contents

- [Prerequisites](#prerequisites)  
- [Installing Dependencies](#installing-dependencies)  
- [Environment Configuration](#environment-configuration)   
- [Database Setup & Migrations](#database-setup--migrations)  
- [Running the Development Server](#running-the-development-server)  
- [Common Artisan Commands](#common-artisan-commands)
- [Github REPO](# GITHUB-REPO)  

 

---

## Prerequisites

Before you begin, confirm that your system has the following installed:

- **PHP ≥ 8.1** with extensions:  
  `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd` (for image support)  
- **Composer ≥ 2.0**  
- **MySQL (or MariaDB) ≥ 5.7 / 10.2**  
- **Git** (for cloning the repository)  
- **XAMPP** <= PREFER
---

## Installing Dependencies

1. Open a terminal and navigate to the `AC-SYSTEM-BACKEND/` folder:
   ```bash
#  NO CD NEEDED DIRECT

2. composer install
# If you run into permission or proxy issues, try:
composer self-update
composer install --no-interaction

## Environment Configuration

3. Copy the example environment file to create a local .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=system_ac  <= YOUR OWN MYSQL DATABASE NAME USING XAMPP
DB_USERNAME=root
DB_PASSWORD=

## Database Setup & Migrations
4. RUN THIS COMMAND 
# php artisan migrate
# php artisan db:seed --class=Database\Seeders\BookingStatusSeeder

## Common Artisan Commands

5. START THE LARAVEL development server
# php artisan serve
# you should see something like this --host=127.0.0.1 --port=8000

## GITHUB REPO
# https://github.com/Plonteras191/BE-LARAVEL-AC-SYSTEM.git

# THATS IT ENJOY

# PlonterasJKC(FE/BE DEV)





