# ReSource | TIP Campus Marketplace — PHP + MySQL Backend

This backend is designed around the existing ReSource frontend (`index.html`, `style.css`, `script.js`).

## 1. Folder placement

Put the complete ReSource project in:

`C:\xampp\htdocs\ReSource\`

The structure should include:

- your existing `index.html`
- your existing `style.css`
- your existing `script.js`
- `php/`
- `database/`
- `uploads/`

Open:

`http://localhost/ReSource/`

Do not open `index.html` directly with `file://`.

## 2. Start XAMPP

Start:

- Apache
- MySQL

## 3. Create the database

Open phpMyAdmin and import:

`database/resource_marketplace.sql`

The database name is:

`resource_marketplace`

Default XAMPP credentials used by `php/config/database.php`:

- MySQL host: localhost
- user: root
- password: empty

If your MySQL password is different, edit `php/config/database.php`.

## 4. Create an admin

The imported SQL creates this local development administrator:

- Email: `admin@tip.edu.ph`
- Password: `admin123`

For a different administrator, register normally through ReSource first. Then open phpMyAdmin and run:

`UPDATE users SET role='admin' WHERE email='YOUR_TIP_EMAIL@tip.edu.ph';`

Never allow the public registration form to choose the admin role.

## 5. Image uploads

The backend stores listing images in:

`uploads/listings/`

Profile photos go in:

`uploads/profiles/`

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

## 7. Important frontend integration

The supplied backend uses real PHP sessions and MySQL. The existing frontend currently uses localStorage as its data layer. Therefore, the existing `script.js` must be integrated with these endpoints.

Use the included `backend-integration.js` as the bridge if you want to keep most of the existing UI code. It synchronizes persistent server data into the frontend's existing rendering model and intercepts the important forms/actions.

For a production-style implementation, the long-term goal should be to remove localStorage persistence entirely and make API responses the sole source of truth.

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
