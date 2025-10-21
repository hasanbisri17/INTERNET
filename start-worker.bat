@echo off
title Queue Worker - WhatsApp Broadcast
color 0A

echo.
echo ========================================
echo   QUEUE WORKER - WhatsApp Broadcast
echo ========================================
echo.
echo Starting queue worker for background processing...
echo.
echo IMPORTANT: Keep this window OPEN!
echo - Broadcasts will be processed in background
echo - Messages will be sent automatically
echo.
echo To stop: Press Ctrl+C
echo ========================================
echo.

cd /d C:\xampp\htdocs\INTERNET\INTERNET
php artisan queue:work --tries=3 --timeout=300 --sleep=1

pause

