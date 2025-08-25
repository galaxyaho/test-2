#!/bin/bash

# Auto Checkout Cron Setup Script
# This script helps you set up the cron job for auto checkout

echo "=== Auto Checkout Cron Setup ==="
echo ""

# Get the current directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CRON_SCRIPT="$SCRIPT_DIR/cron/auto_checkout_cron.php"

echo "Script directory: $SCRIPT_DIR"
echo "Cron script path: $CRON_SCRIPT"
echo ""

# Check if the cron script exists
if [ ! -f "$CRON_SCRIPT" ]; then
    echo "❌ Error: Cron script not found at $CRON_SCRIPT"
    exit 1
fi

echo "✅ Cron script found"
echo ""

# Get PHP path
PHP_PATH=$(which php)
if [ -z "$PHP_PATH" ]; then
    echo "❌ Error: PHP not found in PATH"
    echo "Please install PHP or add it to your PATH"
    exit 1
fi

echo "✅ PHP found at: $PHP_PATH"
echo ""

# Default time is 10:00 AM (10 00)
HOUR="10"
MINUTE="00"

echo "Current cron job configuration:"
echo "Time: ${HOUR}:${MINUTE} (24-hour format)"
echo "Command: $PHP_PATH $CRON_SCRIPT"
echo ""

# Create the cron job entry
CRON_ENTRY="$MINUTE $HOUR * * * $PHP_PATH $CRON_SCRIPT >> $SCRIPT_DIR/logs/cron.log 2>&1"

echo "Cron job entry:"
echo "$CRON_ENTRY"
echo ""

# Ask user if they want to install the cron job
read -p "Do you want to install this cron job? (y/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Create logs directory if it doesn't exist
    mkdir -p "$SCRIPT_DIR/logs"
    
    # Add the cron job
    (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
    
    if [ $? -eq 0 ]; then
        echo "✅ Cron job installed successfully!"
        echo ""
        echo "The auto checkout will run daily at ${HOUR}:${MINUTE}"
        echo "Logs will be saved to: $SCRIPT_DIR/logs/cron.log"
        echo ""
        echo "To view current cron jobs: crontab -l"
        echo "To edit cron jobs: crontab -e"
        echo "To remove this cron job, run: crontab -e and delete the line"
    else
        echo "❌ Error: Failed to install cron job"
        exit 1
    fi
else
    echo "Cron job installation cancelled."
    echo ""
    echo "To manually install the cron job:"
    echo "1. Run: crontab -e"
    echo "2. Add this line:"
    echo "   $CRON_ENTRY"
    echo "3. Save and exit"
fi

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "1. Test the auto checkout system using the admin panel"
echo "2. Check the logs regularly: $SCRIPT_DIR/logs/"
echo "3. Configure the auto checkout time in the admin dashboard"
echo ""
echo "Admin panel URL: http://yourdomain.com/admin/auto_checkout_settings.php"
echo "Test URL: http://yourdomain.com/admin/test_auto_checkout.php"