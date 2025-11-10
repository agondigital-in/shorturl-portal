# URL Shortener

A simple PHP-based URL shortening service that allows you to create short URLs that redirect to longer ones.

## Quick Installation

1. Place all files in your web server directory (e.g., XAMPP's htdocs folder).
2. Visit `http://your-domain/install.php` to automatically create the database and table.
3. Delete `install.php` after installation for security.

## Manual Installation

## Features

- Shorten long URLs into compact links
- Track click counts for each shortened URL
- View statistics of all shortened URLs
- Simple and clean interface

## Setup Instructions

1. Create a MySQL database and run the SQL commands in `database.sql` to create the required table.

2. Update the database credentials in `config.php` if needed:
   ```php
   $host = 'localhost';
   $dbname = 'shorturl';
   $username = 'root';
   $password = '';
   ```

3. Place all files in your web server directory (e.g., XAMPP's htdocs folder).

4. Make sure mod_rewrite is enabled in Apache for URL rewriting to work.

## Usage

1. Visit the main page to shorten URLs.
2. Enter a long URL and click "Shorten URL".
3. Copy the generated short URL and share it.
4. When someone visits the short URL, they will be redirected to the original URL.
5. Visit `/admin.php` to view statistics for all shortened URLs.

## How It Works

1. When a user submits a URL to shorten:
   - A unique short code is generated
   - The original URL and short code are stored in the database
   - The short URL is displayed to the user

2. When someone visits a short URL:
   - The system looks up the short code in the database
   - Increments the click counter for that URL
   - Redirects the user to the original URL

## File Structure

- `index.php` - Main page for shortening URLs
- `redirect.php` - Handles redirection from short URLs to original URLs
- `admin.php` - Displays statistics for all shortened URLs
- `config.php` - Database configuration
- `database.sql` - SQL schema for creating the database table
- `.htaccess` - Apache rewrite rules for clean URLs