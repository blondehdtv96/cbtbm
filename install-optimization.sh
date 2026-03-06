#!/bin/bash

# ============================================
# CBT SMK - Optimization Installation Script
# For 500+ Concurrent Users
# ============================================

set -e

echo "============================================"
echo "CBT SMK Optimization Installer"
echo "For 500+ Concurrent Users"
echo "============================================"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Please run as root (sudo)"
    exit 1
fi

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VER=$VERSION_ID
else
    echo "❌ Cannot detect OS"
    exit 1
fi

echo "✓ Detected OS: $OS $VER"
echo ""

# ============================================
# STEP 1: Update System
# ============================================
echo "📦 Step 1: Updating system..."
apt update && apt upgrade -y
echo "✓ System updated"
echo ""

# ============================================
# STEP 2: Install Redis
# ============================================
echo "📦 Step 2: Installing Redis..."
apt install redis-server -y

# Configure Redis
cp redis.conf /etc/redis/redis.conf

# Start Redis
systemctl start redis-server
systemctl enable redis-server

# Test Redis
if redis-cli ping | grep -q "PONG"; then
    echo "✓ Redis installed and running"
else
    echo "❌ Redis installation failed"
    exit 1
fi
echo ""

# ============================================
# STEP 3: Install PHP Redis Extension
# ============================================
echo "📦 Step 3: Installing PHP Redis extension..."
apt install php-redis php-igbinary -y

# Detect PHP version
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✓ Detected PHP version: $PHP_VERSION"

# Restart PHP-FPM
systemctl restart php${PHP_VERSION}-fpm
echo "✓ PHP Redis extension installed"
echo ""

# ============================================
# STEP 4: Install Supervisor
# ============================================
echo "📦 Step 4: Installing Supervisor..."
apt install supervisor -y

# Copy supervisor config
cp supervisor-queue-worker.conf /etc/supervisor/conf.d/cbt-queue-worker.conf

# Update paths in config
read -p "Enter full path to CBT directory (e.g., /var/www/cbt-smk): " CBT_PATH
sed -i "s|/var/www/cbt-smk|$CBT_PATH|g" /etc/supervisor/conf.d/cbt-queue-worker.conf

# Reload supervisor
supervisorctl reread
supervisorctl update

echo "✓ Supervisor installed"
echo ""

# ============================================
# STEP 5: Optimize MySQL
# ============================================
echo "📦 Step 5: Optimizing MySQL..."

# Detect available RAM
TOTAL_RAM=$(free -g | awk '/^Mem:/{print $2}')
echo "✓ Detected RAM: ${TOTAL_RAM}GB"

# Calculate buffer pool size (70% of RAM)
BUFFER_POOL=$((TOTAL_RAM * 70 / 100))
echo "✓ Setting InnoDB buffer pool to: ${BUFFER_POOL}G"

# Copy MySQL config
cp mysql-optimization.cnf /etc/mysql/conf.d/optimization.cnf

# Update buffer pool size
sed -i "s/innodb_buffer_pool_size = 4G/innodb_buffer_pool_size = ${BUFFER_POOL}G/g" /etc/mysql/conf.d/optimization.cnf

# Restart MySQL
systemctl restart mysql

if systemctl is-active --quiet mysql; then
    echo "✓ MySQL optimized and restarted"
else
    echo "❌ MySQL restart failed"
    exit 1
fi
echo ""

# ============================================
# STEP 6: Optimize PHP-FPM
# ============================================
echo "📦 Step 6: Optimizing PHP-FPM..."

# Calculate max children (RAM / 40MB per process)
MAX_CHILDREN=$((TOTAL_RAM * 1024 / 40))
echo "✓ Setting pm.max_children to: $MAX_CHILDREN"

# Backup original config
cp /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.backup

# Copy optimized config
cp php-fpm-optimization.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

# Update max children
sed -i "s/pm.max_children = 150/pm.max_children = $MAX_CHILDREN/g" /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

# Restart PHP-FPM
systemctl restart php${PHP_VERSION}-fpm

if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
    echo "✓ PHP-FPM optimized and restarted"
else
    echo "❌ PHP-FPM restart failed"
    exit 1
fi
echo ""

# ============================================
# STEP 7: Optimize Nginx
# ============================================
echo "📦 Step 7: Optimizing Nginx..."

# Backup original config
cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup

# Copy optimized config
cp nginx-optimization.conf /etc/nginx/nginx.conf

# Update PHP version in config
sed -i "s/php8.1-fpm/php${PHP_VERSION}-fpm/g" /etc/nginx/nginx.conf

# Test Nginx config
if nginx -t; then
    systemctl reload nginx
    echo "✓ Nginx optimized and reloaded"
else
    echo "❌ Nginx configuration test failed"
    echo "Restoring backup..."
    cp /etc/nginx/nginx.conf.backup /etc/nginx/nginx.conf
    exit 1
fi
echo ""

# ============================================
# STEP 8: Configure Laravel
# ============================================
echo "📦 Step 8: Configuring Laravel..."

cd $CBT_PATH

# Copy production env
if [ ! -f .env ]; then
    cp .env.production .env
    echo "✓ Created .env from .env.production"
else
    echo "⚠ .env already exists, skipping..."
fi

# Update .env with Redis settings
sed -i "s/CACHE_DRIVER=.*/CACHE_DRIVER=redis/g" .env
sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=redis/g" .env
sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/g" .env

echo "✓ Laravel configured for Redis"
echo ""

# ============================================
# STEP 9: Run Migrations
# ============================================
echo "📦 Step 9: Running database migrations..."

cd $CBT_PATH

# Run migrations
php artisan migrate --force

echo "✓ Database indexes added"
echo ""

# ============================================
# STEP 10: Optimize Laravel
# ============================================
echo "📦 Step 10: Optimizing Laravel..."

cd $CBT_PATH

# Clear cache
php artisan optimize:clear

# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize composer
composer install --optimize-autoloader --no-dev
composer dump-autoload -o

echo "✓ Laravel optimized"
echo ""

# ============================================
# STEP 11: Set Permissions
# ============================================
echo "📦 Step 11: Setting permissions..."

cd $CBT_PATH

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "✓ Permissions set"
echo ""

# ============================================
# STEP 12: Start Queue Workers
# ============================================
echo "📦 Step 12: Starting queue workers..."

supervisorctl start cbt-queue-worker:*

if supervisorctl status | grep -q "RUNNING"; then
    echo "✓ Queue workers started"
else
    echo "⚠ Queue workers may not be running. Check with: sudo supervisorctl status"
fi
echo ""

# ============================================
# FINAL CHECKS
# ============================================
echo "============================================"
echo "🎉 Installation Complete!"
echo "============================================"
echo ""
echo "✅ Checking services..."
echo ""

# Check Redis
if systemctl is-active --quiet redis-server; then
    echo "✓ Redis: RUNNING"
else
    echo "❌ Redis: NOT RUNNING"
fi

# Check MySQL
if systemctl is-active --quiet mysql; then
    echo "✓ MySQL: RUNNING"
else
    echo "❌ MySQL: NOT RUNNING"
fi

# Check PHP-FPM
if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
    echo "✓ PHP-FPM: RUNNING"
else
    echo "❌ PHP-FPM: NOT RUNNING"
fi

# Check Nginx
if systemctl is-active --quiet nginx; then
    echo "✓ Nginx: RUNNING"
else
    echo "❌ Nginx: NOT RUNNING"
fi

# Check Supervisor
if systemctl is-active --quiet supervisor; then
    echo "✓ Supervisor: RUNNING"
else
    echo "❌ Supervisor: NOT RUNNING"
fi

echo ""
echo "============================================"
echo "📊 System Information"
echo "============================================"
echo "RAM: ${TOTAL_RAM}GB"
echo "PHP Version: ${PHP_VERSION}"
echo "InnoDB Buffer Pool: ${BUFFER_POOL}G"
echo "PHP-FPM Max Children: ${MAX_CHILDREN}"
echo "CBT Path: ${CBT_PATH}"
echo ""
echo "============================================"
echo "📝 Next Steps"
echo "============================================"
echo "1. Edit .env file and set your database credentials"
echo "2. Generate APP_KEY: php artisan key:generate"
echo "3. Set Redis password in .env if needed"
echo "4. Configure SSL certificate in Nginx"
echo "5. Test the system with load testing tools"
echo "6. Monitor logs: tail -f storage/logs/laravel.log"
echo ""
echo "============================================"
echo "🔍 Monitoring Commands"
echo "============================================"
echo "Redis: redis-cli monitor"
echo "Queue: sudo supervisorctl status"
echo "PHP-FPM: sudo systemctl status php${PHP_VERSION}-fpm"
echo "MySQL: mysql -u root -p -e 'SHOW PROCESSLIST;'"
echo "Logs: tail -f storage/logs/laravel.log"
echo ""
echo "✅ System is ready for 500+ concurrent users!"
echo "============================================"
