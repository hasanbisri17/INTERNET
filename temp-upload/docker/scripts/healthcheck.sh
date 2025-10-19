#!/bin/bash

# Health check script untuk Internet Management System

# Check if Apache is running
if ! pgrep apache2 > /dev/null; then
    echo "Apache is not running"
    exit 1
fi

# Check if application responds
if ! curl -f http://localhost/health > /dev/null 2>&1; then
    echo "Application health check failed"
    exit 1
fi

# Check if database is accessible
if ! php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo "Database connection failed"
    exit 1
fi

echo "All health checks passed"
exit 0
