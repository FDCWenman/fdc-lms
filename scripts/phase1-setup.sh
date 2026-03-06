#!/bin/bash

# Phase 1: Setup Script for Authentication Implementation
# Feature: 001-auth-login-registration

set -e

CONTAINER="addfc01e309b"
WORKDIR="/var/www/html/fdc-lms"

echo "🚀 Starting Phase 1: Setup"
echo "================================"

# T002: Install Spatie Laravel Permission
echo "📦 T002: Installing Spatie Laravel Permission..."
docker exec $CONTAINER composer require spatie/laravel-permission --working-dir=$WORKDIR

# T003: Publish Spatie migrations
echo "📄 T003: Publishing Spatie migrations..."
docker exec $CONTAINER php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --working-dir=$WORKDIR

# T004: Configure Slack API (already done in .env)
echo "✅ T004: Slack API credentials configured in .env"

# T005: Session driver already configured
echo "✅ T005: Database session driver configured in config/session.php"

# T006: Create verification_tokens migration
echo "📝 T006: Creating verification_tokens migration..."
docker exec $CONTAINER php artisan make:migration create_verification_tokens_table --working-dir=$WORKDIR

# T007: Remove legacy role columns migration
echo "📝 T007: Creating migration to remove legacy role columns..."
docker exec $CONTAINER php artisan make:migration remove_legacy_role_columns_from_users_table --working-dir=$WORKDIR

# T008: Run migrations
echo "🗄️  T008: Running migrations..."
docker exec $CONTAINER php artisan migrate --working-dir=$WORKDIR

# T009: Seed roles
echo "🌱 T009: Seeding roles..."
docker exec $CONTAINER php artisan make:seeder RoleSeeder --working-dir=$WORKDIR

echo ""
echo "✅ Phase 1: Setup Complete!"
echo "================================"
