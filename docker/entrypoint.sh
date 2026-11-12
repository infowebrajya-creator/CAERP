#!/bin/sh
set -e

echo "=================================================="
echo "          Starting WebRajya ERP System...         "
echo "=================================================="

cd /var/www/html

# Ensure .env exists
if [ ! -f .env ]; then
    echo "Creating production .env from .env.docker..."
    cp .env.docker .env
fi

# Ensure storage directories exist with write permissions
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache storage/app/private/documents
chmod -R 777 storage bootstrap/cache

# Generate APP_KEY if empty or unset
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application encryption key..."
    php artisan key:generate --force
fi

# Wait for MySQL to accept connections
echo "Waiting for MySQL database connection..."
MAX_TRIES=30
COUNT=0
while ! php -r "try { new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "Error: Database connection timed out after 60s."
        exit 1
    fi
    echo "Waiting for database service ($COUNT/$MAX_TRIES)..."
    sleep 2
done
echo "Database is ready!"

# Run migrations
echo "Executing database migrations..."
php artisan migrate --force

# Check if users table is empty; if so, seed demo accounts and master data
USER_COUNT=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); echo App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "First run detected: Seeding system master data and accounts..."
    php artisan db:seed --force
fi

# Cache configurations for maximum speed
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=================================================="
echo "   WebRajya ERP is LIVE at http://localhost:8000  "
echo "=================================================="

# Start PHP server
exec php artisan serve --host=0.0.0.0 --port=8000
