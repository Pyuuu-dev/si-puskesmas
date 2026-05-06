#!/bin/bash
# Cron Setup for SI Puskesmas - Telegram Auto Backup
# Run this script once to set up the Laravel scheduler cron job
# Usage: bash cron-setup.sh

PUSKESMAS_DIR="/var/www/puskesmas"
PHP_BIN=$(which php)

if [ -z "$PHP_BIN" ]; then
    echo "ERROR: PHP not found in PATH"
    exit 1
fi

echo "Setting up Laravel Scheduler cron job..."
echo "PHP: $PHP_BIN"
echo "Project: $PUSKESMAS_DIR"
echo ""

# Create the cron entry
CRON_ENTRY="* * * * * cd $PUSKESMAS_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"

# Check if cron entry already exists
EXISTING=$(crontab -l 2>/dev/null | grep -F "puskesmas" | grep -F "schedule:run")

if [ -n "$EXISTING" ]; then
    echo "Cron job already exists:"
    echo "  $EXISTING"
    echo ""
    echo "To remove and re-add, run: crontab -e"
else
    # Add to crontab
    (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
    echo "Cron job added successfully!"
    echo "  $CRON_ENTRY"
fi

echo ""
echo "Current crontab:"
crontab -l 2>/dev/null
echo ""
echo "Done! The Laravel scheduler will now run every minute."
echo "Backup will be sent to Telegram at the configured times."
echo ""
echo "To test manually: cd $PUSKESMAS_DIR && php artisan schedule:run"
echo "To verify scheduler: cd $PUSKESMAS_DIR && php artisan schedule:list"
