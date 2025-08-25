# Auto Checkout System

This system automatically checks out hotel guests at a specified time daily.

## Features

- **Automatic Daily Checkout**: Runs at configurable time (default 10:00 AM)
- **Admin Dashboard**: Easy-to-use interface to manage settings
- **Real-time Testing**: Test the system manually anytime
- **Comprehensive Logging**: Track all checkout activities
- **Error Handling**: Robust error handling and reporting
- **Timezone Support**: Configure timezone for accurate scheduling

## Installation Steps

### 1. Database Setup

Run the SQL script to create necessary tables:

```sql
-- Run the contents of sql/create_tables.sql in your database
```

### 2. Configure Database Connection

Edit `config/database.php` with your database credentials:

```php
$host = 'localhost';
$dbname = 'u261459251_software';
$username = 'u261459251_software';
$password = 'your_actual_password';
```

### 3. Set Up Cron Job

#### Option A: Using the Setup Script (Recommended)
```bash
chmod +x setup_cron.sh
./setup_cron.sh
```

#### Option B: Manual Setup
1. Open crontab: `crontab -e`
2. Add this line (adjust path as needed):
```
0 10 * * * /usr/bin/php /home/u261459251/domains/soft.galaxytribes.in/public_html/cron/auto_checkout_cron.php >> /home/u261459251/domains/soft.galaxytribes.in/public_html/logs/cron.log 2>&1
```

### 4. Set Permissions

```bash
chmod 755 cron/auto_checkout_cron.php
chmod 755 admin/auto_checkout_settings.php
chmod 755 admin/test_auto_checkout.php
mkdir -p logs
chmod 777 logs
```

### 5. Configure Admin Access

Add authentication to `admin/auto_checkout_settings.php` by implementing your login system.

## Usage

### Admin Dashboard

Access the admin dashboard at:
- Settings: `http://yourdomain.com/admin/auto_checkout_settings.php`
- Testing: `http://yourdomain.com/admin/test_auto_checkout.php`

### Features Available:

1. **Enable/Disable Auto Checkout**
2. **Set Checkout Time** (any time of day)
3. **Configure Timezone**
4. **View Recent Logs**
5. **Test System Manually**

### Testing the System

1. Go to the admin dashboard
2. Click "Test Auto Checkout Now"
3. Check the results and logs

## How It Works

1. **Cron Job**: Runs daily at specified time
2. **Database Check**: Finds rooms with checkout date <= today
3. **Process Checkout**: Updates booking and room status
4. **Logging**: Records all activities
5. **Error Handling**: Logs any failures

## Troubleshooting

### Cron Job Not Running

1. Check if cron service is running: `service cron status`
2. Check cron logs: `tail -f /var/log/cron`
3. Verify PHP path: `which php`
4. Test script manually: `php cron/auto_checkout_cron.php`

### Database Issues

1. Check database connection in `config/database.php`
2. Verify all tables exist using the SQL script
3. Check table permissions

### Permission Issues

```bash
chmod -R 755 admin/
chmod -R 755 cron/
chmod -R 777 logs/
```

### Time Zone Issues

1. Set correct timezone in admin dashboard
2. Verify server timezone: `date`
3. Check PHP timezone: `php -r "echo date_default_timezone_get();"`

## File Structure

```
/
├── config/
│   └── database.php          # Database configuration
├── cron/
│   └── auto_checkout_cron.php # Main cron script
├── admin/
│   ├── auto_checkout_settings.php # Admin dashboard
│   └── test_auto_checkout.php     # Testing interface
├── sql/
│   └── create_tables.sql     # Database setup
├── logs/                     # Log files (auto-created)
├── setup_cron.sh            # Cron setup script
└── README.md                # This file
```

## Database Tables

- `bookings`: Hotel bookings with checkout dates
- `rooms`: Room information and status
- `auto_checkout_logs`: Checkout activity logs
- `system_settings`: Configuration settings
- `activity_logs`: General activity logging

## Security Notes

1. Protect admin files with proper authentication
2. Use HTTPS for admin access
3. Regularly backup the database
4. Monitor log files for suspicious activity
5. Keep PHP and database updated

## Support

For issues or questions:
1. Check the logs in `/logs/` directory
2. Use the test interface to debug
3. Verify cron job is properly configured
4. Check database connectivity

## Customization

You can customize:
- Checkout time (via admin dashboard)
- Email notifications (modify cron script)
- Additional logging (modify cron script)
- UI styling (modify admin CSS)