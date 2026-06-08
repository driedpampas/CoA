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