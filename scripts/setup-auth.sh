#!/bin/bash

# Complete Setup Script for Authentication Implementation
# Run this after the files have been created

set -e

CONTAINER="addfc01e309b"
WORKDIR="/var/www/html/fdc-lms"

echo "🚀 FDCLeave Authentication Setup"
echo "=================================="
echo ""

# Check if container is running
echo "📋 Checking Docker container..."
if ! docker ps | grep -q $CONTAINER; then
    echo "❌ Error: Docker container $CONTAINER is not running"
    exit 1
fi
echo "✅ Container is running"
echo ""

# Install Spatie Permission
echo "📦 Installing Spatie Laravel Permission..."
docker exec $CONTAINER composer require spatie/laravel-permission --working-dir=$WORKDIR --no-interaction

# Publish Spatie migrations
echo "📄 Publishing Spatie migrations..."
docker exec $CONTAINER php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --working-dir=$WORKDIR --no-interaction

# Run migrations
echo "🗄️  Running database migrations..."
docker exec $CONTAINER php artisan migrate --working-dir=$WORKDIR --force

# Seed roles
echo "🌱 Seeding roles..."
docker exec $CONTAINER php artisan db:seed --class=RoleSeeder --working-dir=$WORKDIR

# Clear caches
echo "🧹 Clearing application caches..."
docker exec $CONTAINER php artisan config:clear --working-dir=$WORKDIR
docker exec $CONTAINER php artisan cache:clear --working-dir=$WORKDIR
docker exec $CONTAINER php artisan route:clear --working-dir=$WORKDIR

echo ""
echo "✅ Setup Complete!"
echo "=================================="
echo ""
echo "Next steps:"
echo "1. Verify roles were created: php artisan tinker"
echo "   > \Spatie\Permission\Models\Role::all()"
echo ""
echo "2. Create a test user with a role:"
echo "   > \$user = \App\Models\User::create([...])"
echo "   > \$user->assignRole('employee')"
echo ""
echo "3. Run tests: php artisan test"
echo ""
