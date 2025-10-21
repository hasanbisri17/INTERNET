@echo off
echo ========================================
echo  QUEUE WORKER - WhatsApp Broadcast
echo ========================================
echo.
echo Starting queue worker...
echo Keep this window open to process broadcasts!
echo.
echo Press Ctrl+C to stop
echo ========================================
echo.

cd /d C:\xampp\htdocs\INTERNET\INTERNET
php artisan queue:work --tries=3 --timeout=300

pause

