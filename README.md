# CoA

## App Features:

- Create and manage user accounts, whom can edit certain parts about their experience, as well as receive notifications about upcoming disasters
- Display disaster events, shelters, escape routes across the globe
- Let administrator change the data of the database, as well as import and export data
- Expose API endpoints for importing, exporting and serving structured data
- Keep the public UI separate from the API layer so page rendering stays simple and data exchange stays isolated


## Technologies used:

- `PHP` - Vanilla application without frameworks, following the MVC model: `controllers`, `models` si `views`
- `MySQL` - Database for user accounts, event alerts, shelters and routes
- `mysqli` - Prepared statements + safe access to the database
- `Composer autoload` - Automatic load for `api/models` and `api/handlers`
- `JavaScript` - Interaction between the admin interface and dashboard
- `CSS` - Stylizing the pages

## Design patterns used:

- `MVC` - The main MVC flux: `public/index.php` -> `controller` -> `model` -> `view`
- `Procedural controllers` - The HTTP logic remains in different files, for a better organization and easier time reading code
- `Repository-like models` - Each model encapsulates the data access
- `Separation of concerns` - The API, Admin interface and public user interface are all separated

## Project structure:

- `public/` - Entry point for the public application
- `admin/` - The administrator interface. It allows the admin to modify the database 
- `api/` - Endpoints and handlers for data and exports
- `schema/` - SQL Scripts for the database
- `config/` - Database connection configurations

## Testing & Validation

To ensure the application works correctly and meets the expected requirements, everything has been carefully tested to work accordingly.

## Test Plan
The following aspects were validated:
- User interface responsiveness on different screen sizes (Desktop & Mobile)
- Correct handling of user input and forms
- Navigation between pages/components
- Functionality of core features
- Error handling for invalid or missing data

## Acceptance Criteria
The project is considered complete when:
- The application runs without errors
- All main features are fully functional
- The design remains responsive on desktop, tablet, and mobile devices
- The user experience is consistent and intuitive
- The final solution matches the initial requirements

## Deployment with Apache

The application is designed to run on an `Apache Web Server` with **PHP**, **MySQL**, and **Composer**.

### Requirements
- Apache 2.4+
- PHP 8.x
- MySQL / MariaDB
- Composer 2.x
- `mod_rewrite` enabled

### Deployment Steps
1. Copy the project into the Apache document root or configure a virtual host for it
2. Point the web server document root to the `public/` directory. This is the only public entry point
3. Make sure `mod_rewrite` is enabled so the `public/.htaccess` rewrite rules can route requests to `public/index.php`
4. Run `composer install` in the project root to install dependencies and generate the `vendor/` directory
5. Import the database schema from the `schema/` folder
6. Configure the database credentials in `config/`
7. If there are any issues, restart Apache

### Local Hostname
The project is intended to be accessible locally at `http://coa.local` through an Apache virtual host configuration.

### Notes
- The `admin/` and `api/` folders also use `.htaccess` and should remain protected from direct access.
- `vendor/autoload.php` is required by the application, so Composer must be installed before running the project.

### Presentation Video:

**https://drive.google.com/drive/folders/1UfU9SJdc7Gb446mMZJFLfOG4cAn-D0iL?usp=sharing**