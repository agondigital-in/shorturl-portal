# Agencies Feature - हिंदी गाइड

## क्या बनाया गया है?

Super Admin में अब **Agencies** (एजेंसियां) manage करने की सुविधा add की गई है। यह बिल्कुल Advertisers की तरह काम करती है।

## Installation कैसे करें?

### Step 1: Database Setup
अपने browser में यह URL खोलें:
```
http://your-domain/install_agencies.php
```

यह automatically:
- Agencies की table बनाएगा
- Campaigns table में agency_id column add करेगा

### Step 2: Check करें
Installation के बाद:
1. Super Admin Dashboard खोलें
2. Left sidebar में "Agencies" link दिखेगा
3. Dashboard में "Agencies" का count दिखेगा

## Features

### 1. Agency Add करना
1. Super Admin Dashboard → Agencies पर click करें
2. "Add Agency" button पर click करें
3. Form भरें:
   - **Name** (जरूरी)
   - **Email** (जरूरी)
   - Company (optional)
   - Phone (optional)
4. "Create Agency" पर click करें

### 2. Campaign में Agency Select करना
1. Super Admin Dashboard → Add Campaign
2. Campaign की details भरें
3. "Select Agency" dropdown से agency चुनें (optional)
4. Campaign create करें

### 3. Agency देखना
- **Daily Leads Entry** page पर हर campaign के साथ agency का name दिखेगा
- अगर agency assign नहीं है तो "N/A" दिखेगा

## Dashboard में Changes

### Stats Cards
Dashboard में अब 6 stat boxes हैं:
1. Total Campaigns
2. Active Now
3. Advertisers
4. Publishers
5. **Agencies** (नया)
6. Total Clicks

### Sidebar Menu
"👥 USERS" section में अब:
- Advertisers
- Publishers
- **Agencies** (नया)
- Admins

## Daily Leads Entry में Changes

Table में अब columns हैं:
1. # (Number)
2. Campaign Name
3. **Agency** (नया column)
4. Shortcode
5. Leads
6. Actions

## Database Tables

### Agencies Table
```
- id (Primary Key)
- name (Agency का नाम)
- email (Email address)
- company (Company का नाम)
- phone (Phone number)
- created_at (Creation date)
```

### Campaigns Table में Addition
```
- agency_id (Agency का ID - optional)
```

## Important Points

1. **Agency optional hai** - Campaign बिना agency के भी बन सकता है
2. **Email unique होना चाहिए** - Same email से दो agencies नहीं बन सकती
3. **Delete करने से पहले सोचें** - Agency delete करने से campaigns से link टूट जाएगा

## Files जो बनाई गई हैं

1. `super_admin/agencies.php` - Agencies manage करने का page
2. `install_agencies.php` - Installation script
3. `create_agencies_table.sql` - Database schema
4. `AGENCIES_FEATURE_README.md` - English documentation
5. `AGENCIES_HINDI_GUIDE.md` - यह file

## Files जो modify की गई हैं

1. `super_admin/dashboard.php` - Agencies count added
2. `super_admin/includes/header.php` - Sidebar में link
3. `super_admin/add_campaign.php` - Agency select field
4. `super_admin/daily_leads_entry.php` - Agency column
5. `super_admin/campaigns.php` - Agency data fetch

## अगर Problem आए तो?

### Problem: Agencies link नहीं दिख रहा
**Solution:** Browser cache clear करें और page refresh करें

### Problem: Table 'agencies' doesn't exist error
**Solution:** `install_agencies.php` फिर से run करें

### Problem: Agency select dropdown खाली है
**Solution:** 
1. Agencies page पर जाएं
2. पहले कुछ agencies add करें
3. फिर campaign create करें

## Example Usage

### Example 1: Agency Add करना
```
Name: Digital Marketing Pro
Email: info@digitalmarketingpro.com
Company: DMP Solutions
Phone: +91 9876543210
```

### Example 2: Campaign में Agency Assign करना
```
Campaign Name: Summer Sale 2026
Campaign Type: CPL
Agency: Digital Marketing Pro (dropdown से select करें)
Website URL: https://example.com
```

### Example 3: Daily Leads Entry में देखना
```
# | Campaign Name    | Agency                | Shortcode  | Leads | Actions
1 | Summer Sale 2026 | Digital Marketing Pro | CAMP1234   | 150   | Enter | View
2 | Winter Offer     | N/A                   | CAMP5678   | 200   | Enter | View
```

## Testing Checklist

Installation के बाद यह check करें:

- [ ] `install_agencies.php` successfully run हुआ
- [ ] Dashboard में Agencies stat box दिख रहा है
- [ ] Sidebar में Agencies link है
- [ ] Agencies page खुल रहा है
- [ ] Agency add हो रही है
- [ ] Campaign create करते समय agency select हो रही है
- [ ] Daily Leads Entry में agency column दिख रहा है

## Support

अगर कोई doubt हो तो:
1. README files पढ़ें
2. Database connection check करें
3. File permissions verify करें
4. Browser console में errors check करें

---
**बनाया गया:** 18 May 2026
**Version:** 1.0
**Language:** Hindi + English
