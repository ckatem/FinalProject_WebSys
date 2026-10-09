# ReSource | TIP Campus Marketplace

The project keeps the browser-facing application in `frontend/` and the PHP API, database schema, dependencies, and uploads in `backend/`.

## 1. Folder placement

Put the complete ReSource project in:

`C:\xampp\htdocs\ReSource\`

The project is organized as:

- `frontend/` — `index.php`, `admin-dashboard.php`, CSS, JavaScript, and the logo
- `backend/php/` — PHP API endpoints and database configuration
- `backend/database/` — SQL schema
- `backend/vendor/` — Composer dependencies
- `backend/uploads/` — listing images and profile photos

Open:

`http://localhost/ReSource/`

The root `index.php` forwards to the frontend entry point. Do not open PHP pages directly with `file://`.

## 2. Start XAMPP

Start:

- Apache
- MySQL

## 3. Create the database

Open phpMyAdmin and import:

`backend/database/resource_marketplace.sql`

The database name is:

`resource_marketplace`

Default XAMPP credentials used by `backend/php/config/database.php`:

- MySQL host: localhost
- user: root
- password: empty

If your MySQL password is different, edit `backend/php/config/database.php`.

## 4. Create an admin

The imported SQL creates this local development administrator:

- Email: `admin@tip.edu.ph`
- Password: `admin123`

For a different administrator, register normally through ReSource first. Then open phpMyAdmin and run:

`UPDATE users SET role='admin' WHERE email='YOUR_TIP_EMAIL@tip.edu.ph';`

Never allow the public registration form to choose the admin role.

## 5. Image uploads

The backend stores listing images in:

`backend/uploads/listings/`

Profile photos go in:

`backend/uploads/profiles/`

Only the image path is stored in MySQL.

## 6. Main backend endpoints

Authentication:

- `php/auth/register.php`
- `php/auth/login.php`
- `php/auth/logout.php`
- `php/auth/session.php`

Listings:

- `php/listings/get.php`
- `php/listings/detail.php`
- `php/listings/create.php`
- `php/listings/delete.php`
- `php/listings/mark-sold.php`

Favorites:

- `php/favorites/toggle.php`
- `php/favorites/get.php`

Cart:

- `php/cart/toggle.php`
- `php/cart/get.php`
- `php/cart/update.php`

Purchases:

- `php/purchases/checkout.php`
- `php/purchases/history.php`
- `php/purchases/sold.php`

Messaging:

- `php/messages/send.php`
- `php/messages/get.php`
- `php/messages/conversations.php`

Users:

- `php/users/profile.php`
- `php/users/update.php`
- `php/users/upload-photo.php`
- `php/users/change-password.php`

Reports:

- `php/reports/create.php`

Admin:

- `php/admin/dashboard.php`
- `php/admin/users.php`
- `php/admin/listings.php`
- `php/admin/reports.php`
- `php/admin/delete-listing.php`

## 7. Frontend integration

The frontend is served from `frontend/` and calls the PHP endpoints in `backend/php/`. Some UI state remains in localStorage; account and administrative actions use the PHP/MySQL backend.

## 8. Security

The backend includes:

- PDO prepared statements
- password_hash/password_verify
- PHP sessions
- ownership checks
- admin authorization
- server-side validation
- MIME validation for uploads
- file-size limits
- unique upload names
- database transactions for checkout/sales
