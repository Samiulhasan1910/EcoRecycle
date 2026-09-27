# EcoRecycle

EcoRecycle is a PHP and MySQL web application for buying, selling, and donating reusable items. It connects community members, NGOs, and delivery personnel through separate dashboards and item-management workflows.

## Features

- User registration, login, and profile management.
- Item listings for buying, selling, and donation, with image uploads.
- Wanted posts and offers for requested items.
- NGO donation requests, accepted donations, and pickup coordination.
- Delivery job handling, availability settings, charge responses, and progress updates.
- Notifications and transaction records.
- Administrative management of users, items, requests, NGOs, and delivery personnel.

## User Roles

| Role | Main responsibilities |
| --- | --- |
| User | Browse items, create listings, buy or donate items, and manage requests and offers. |
| NGO | Manage donation requests and coordinate pickups. |
| Delivery personnel | Handle delivery jobs and update delivery progress. |
| Admin | Manage platform records and account verification. |

## Technologies

| Component | Technology |
| --- | --- |
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL / MariaDB, accessed through MySQLi |
| Local server | XAMPP (Apache and MySQL/MariaDB) |
| Database administration | phpMyAdmin |

## Local Setup

### 1. Place the project in XAMPP

Install XAMPP with PHP and MySQL/MariaDB. Clone the repository into the `htdocs` directory:

```bash
cd C:\xampp\htdocs
git clone https://github.com/Samiulhasan1910/EcoRecycle.git
```

For this cloned layout, `login.php` should be located at `C:\xampp\htdocs\EcoRecycle\login.php`.

### 2. Start the services

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### 3. Import the database

1. Open `http://localhost/phpmyadmin/`.
2. Create a database named `project` with a UTF-8-compatible collation such as `utf8mb4_general_ci`.
3. Select the database and open the **Import** tab.
4. Select `project (5).sql` from the project directory and run the import.

### 4. Check the database connection

The supplied `db.php` uses the following local development settings:

```php
$conn = mysqli_connect("localhost", "root", "", "project");
```

Update the host, username, password, or database name if your local configuration differs.

### 5. Open the application

For the cloned layout above, visit:

```text
http://localhost/EcoRecycle/login.php
```

If you extracted the ZIP and kept the nested `EcoRecycle/web/web` layout, use:

```text
http://localhost/EcoRecycle/web/web/login.php
```

Register an account through `register.php`. NGO and delivery accounts may require administrator approval before their workflows are available. Keep the `uploads` directory and allow the local web server to write uploaded files there.

## Important Files

| File or directory | Purpose |
| --- | --- |
| `login.php` / `login_process.php` | Login interface and processing |
| `register.php` / `register_process.php` | Account registration |
| `db.php` | Database connection |
| `dashboard.php` | User dashboard |
| `admin_dashboard.php` | Administrator dashboard |
| `ngo_dashboard.php` | NGO dashboard |
| `delivery_dashboard.php` | Delivery dashboard |
| `delivery_workflow.php` | Shared delivery workflow logic |
| `project (5).sql` | Supplied database dump |
| `uploads/` | Uploaded images and documents |

## Development Notes

- This repository is an academic group project intended for local demonstration.
- Some existing interface labels use the name **EcoCycle**; the repository name is **EcoRecycle**.
- PHP pages require a PHP-capable server and a database; opening the files directly in a browser will not run the application.
- The supplied SQL dump must match the application code. If a workflow reports missing tables or columns, import the current database export from the working development environment.

## Repository

[Samiulhasan1910/EcoRecycle](https://github.com/Samiulhasan1910/EcoRecycle)
