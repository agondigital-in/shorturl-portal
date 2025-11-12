# Ad Campaign Management + URL Shortener Platform

A web-based platform for managing advertising campaigns with URL shortening and click tracking capabilities.

## Features

- **Super Admin Panel**
  - Manage Admin users
  - View all campaigns and advertisers
  - Full system control

- **Admin Panel**
  - Manage advertisers
  - Create and manage campaigns
  - Generate short URLs
  - Monitor campaign performance

- **Advertiser Management**
  - Add/edit/delete advertisers
  - Track advertiser information

- **Campaign Management**
  - Create campaigns with start/end dates
  - Set campaign types (CPR, CPL, CPC, CPM, CPS, None)
  - Automatic short URL generation (e.g., p1, p2, etc.)
  - Click tracking
  - Automatic status updates based on dates

- **URL Shortening & Redirect**
  - Short URLs like https://yourdomain.com/p1
  - Automatic redirect to original URL
  - Click counting
  - Expiration handling

## System Requirements

- PHP 7.0 or higher
- MySQL 5.0 or higher
- Apache with mod_rewrite enabled

## Installation

1. **Database Setup**
   - Create a MySQL database
   - Execute the `database.sql` file to create tables and insert the default super admin user

2. **Configuration**
   - Update `config.php` with your database credentials

3. **Default Login**
   - Username: `business@agondigital.in`
   - Password: `Agondigital@2020`
   - *Note: Change this password immediately after first login*

## Usage

1. **Super Admin**
   - Login with super admin credentials
   - Create admin users
   - Monitor overall system performance

2. **Admins**
   - Login with assigned credentials
   - Add advertisers
   - Create campaigns
   - Track clicks and campaign performance

3. **Campaign URLs**
   - When a campaign is created, a short URL is automatically generated
   - Example: https://yourdomain.com/p5
   - These URLs automatically track clicks and redirect to the original URL

## Cron Job

Set up a daily cron job to automatically update expired campaigns:

```bash
0 0 * * * /usr/bin/php /path/to/your/site/cron_update_expired.php
```

## File Structure

```
ads-platforms/
├── admin/                 # Admin panel files
│   ├── dashboard.php
│   ├── advertisers.php
│   └── campaigns.php
├── super_admin/           # Super Admin panel files
│   ├── dashboard.php
│   ├── advertisers.php
│   ├── campaigns.php
│   └── admins.php
├── .htaccess              # URL rewriting rules
├── config.php             # Database configuration
├── database.sql           # Database schema
├── login.php              # Login page
├── logout.php             # Logout functionality
├── redirect.php           # URL redirect and tracking
├── cron_update_expired.php # Cron job script
└── README.md              # This file
```

## Security Notes

- Always change the default super admin password after installation
- Use HTTPS in production environments
- Regularly update and patch your server software
- Implement additional security measures as needed for your environment