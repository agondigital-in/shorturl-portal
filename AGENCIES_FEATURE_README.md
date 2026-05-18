# Agencies Feature Installation Guide

## Overview
Yeh feature Super Admin ko agencies manage karne ki facility deta hai. Agencies ko campaigns ke saath link kiya ja sakta hai.

## Installation Steps

### Step 1: Database Setup
Browser mein yeh URL open karein:
```
http://your-domain/install_agencies.php
```

Yeh automatically:
- `agencies` table create karega
- `campaigns` table mein `agency_id` column add karega

### Step 2: Verify Installation
Installation ke baad check karein:
1. Super Admin Dashboard pe jaayein
2. Sidebar mein "Agencies" link dikhna chahiye
3. Dashboard stats mein "Agencies" count dikhna chahiye

## Features Added

### 1. Agencies Management (`super_admin/agencies.php`)
- Add new agencies
- View all agencies
- Delete agencies
- Agency details: Name, Email, Company, Phone

### 2. Campaign Creation (`super_admin/add_campaign.php`)
- Agency select dropdown added
- Optional field - campaign bina agency ke bhi create ho sakta hai

### 3. Daily Leads Entry (`super_admin/daily_leads_entry.php`)
- Agency column added in campaigns table
- Agency name show hota hai har campaign ke saath

### 4. Dashboard Updates
- Agencies count stat box added
- Sidebar mein Agencies link added

## Database Schema

### Agencies Table
```sql
CREATE TABLE `agencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
)
```

### Campaigns Table Update
```sql
ALTER TABLE `campaigns` 
ADD COLUMN `agency_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `agency_id` (`agency_id`);
```

## Usage

### Add Agency
1. Super Admin Dashboard → Agencies
2. Click "Add Agency" button
3. Fill form: Name*, Email*, Company, Phone
4. Click "Create Agency"

### Assign Agency to Campaign
1. Super Admin Dashboard → Add Campaign
2. Fill campaign details
3. Select agency from "Select Agency" dropdown (optional)
4. Create campaign

### View Agency in Campaigns
- Daily Leads Entry page mein har campaign ke saath agency name show hoga
- Agar agency assign nahi hai to "N/A" show hoga

## Files Modified

1. `super_admin/dashboard.php` - Agencies count added
2. `super_admin/includes/header.php` - Agencies link in sidebar
3. `super_admin/add_campaign.php` - Agency select field added
4. `super_admin/daily_leads_entry.php` - Agency column in table
5. `super_admin/campaigns.php` - Agency join in query

## Files Created

1. `super_admin/agencies.php` - Agencies management page
2. `install_agencies.php` - Installation script
3. `create_agencies_table.sql` - SQL schema file
4. `AGENCIES_FEATURE_README.md` - This file

## Troubleshooting

### Error: Table 'agencies' doesn't exist
Solution: Run `install_agencies.php` again

### Error: Column 'agency_id' doesn't exist
Solution: Run this SQL manually:
```sql
ALTER TABLE `campaigns` ADD COLUMN `agency_id` int(11) DEFAULT NULL AFTER `id`;
```

### Agencies link not showing in sidebar
Solution: Clear browser cache and refresh page

## Support
Agar koi problem ho to:
1. Check database connection
2. Verify all files are uploaded
3. Check file permissions
4. Review error logs

---
**Installation Date:** May 18, 2026
**Version:** 1.0
