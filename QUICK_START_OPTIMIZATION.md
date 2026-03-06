# 🚀 Quick Start: Optimasi untuk 500+ Users

Panduan cepat untuk mengoptimasi sistem CBT agar dapat menangani 500+ concurrent users.

---

## ⚡ Quick Installation (Ubuntu/Debian)

### Option 1: Automatic Installation (Recommended)

```bash
# 1. Download dan jalankan script instalasi
sudo bash install-optimization.sh

# 2. Ikuti instruksi di layar
# Script akan otomatis:
# - Install Redis
# - Install PHP Redis extension
# - Install Supervisor
# - Optimize MySQL
# - Optimize PHP-FPM
# - Optimize Nginx
# - Configure Laravel
# - Run migrations
# - Start queue workers
```

### Option 2: Manual Installation

```bash
# 1. Install Redis
sudo apt update
sudo apt install redis-server -y
sudo systemctl start redis-server
sudo systemctl enable redis-server

# 2. Install PHP Redis
sudo apt install php-redis php-igbinary -y
sudo systemctl restart php8.1-fpm

# 3. Configure Laravel
cp .env.production .env
nano .env  # Edit database & Redis settings

# 4. Run migrations
php artisan migrate

# 5. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload -o

# 6. Install Supervisor
sudo apt install supervisor -y
sudo cp supervisor-queue-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cbt-queue-worker:*

# 7. Apply MySQL optimization
sudo cp mysql-optimization.cnf /etc/mysql/conf.d/
sudo systemctl restart mysql

# 8. Apply PHP-FPM optimization
sudo cp php-fpm-optimization.conf /etc/php/8.1/fpm/pool.d/www.conf
sudo systemctl restart php8.1-fpm

# 9. Apply Nginx optimization
sudo cp nginx-optimization.conf /etc/nginx/nginx.conf
sudo nginx -t
sudo systemctl reload nginx
```

---

## ✅ Verification Checklist

Setelah instalasi, verifikasi dengan checklist ini:

```bash
# 1. Check Redis
redis-cli ping
# Expected: PONG

# 2. Check PHP Redis extension
php -m | grep redis
# Expected: redis

# 3. Check Laravel cache
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
# Expected: "value"

# 4. Check Queue workers
sudo supervisorctl status
# Expected: cbt-queue-worker:* RUNNING

# 5. Check MySQL
mysql -u root -p -e "SHOW VARIABLES LIKE 'max_connections';"
# Expected: 500

# 6. Check PHP-FPM
curl http://localhost/status
# Expected: JSON with pool stats

# 7. Check Nginx
sudo nginx -t
# Expected: syntax is ok

# 8. Check database indexes
php artisan tinker
>>> DB::select("SHOW INDEX FROM jawaban_siswas");
# Expected: Multiple indexes listed
```

---

## 🧪 Load Testing

### Quick Test (100 users)

```bash
# Make script executable
chmod +x load-test.sh

# Run load test
./load-test.sh

# Select option 7 (Run All Tests)
```

### Manual Test with Apache Bench

```bash
# Test homepage (100 concurrent, 1000 requests)
ab -n 1000 -c 100 http://127.0.0.1:8000/

# Expected results:
# - Requests per second: > 100
# - Time per request: < 1000ms
# - Failed requests: 0
```

### Stress Test (500 users)

```bash
# Install siege
sudo apt install siege -y

# Run stress test
siege -c 500 -t 60S http://127.0.0.1:8000/

# Expected results:
# - Availability: > 99%
# - Response time: < 2s
# - Transaction rate: > 50 trans/sec
```

---

## 📊 Monitoring

### Real-time Monitoring

```bash
# Start system monitor
php artisan system:monitor

# Output akan menampilkan:
# - Database connections
# - Redis metrics
# - Queue status
# - Active exams
# - Students online
```

### Check Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/queue-worker.log

# Nginx access logs
sudo tail -f /var/log/nginx/access.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# MySQL slow query log
sudo tail -f /var/log/mysql/slow-query.log

# Redis logs
sudo tail -f /var/log/redis/redis-server.log
```

### Performance Metrics

```bash
# Check Redis stats
redis-cli info stats

# Check MySQL connections
mysql -u root -p -e "SHOW PROCESSLIST;"

# Check PHP-FPM status
curl http://localhost/status?full

# Check queue size
php artisan queue:monitor
```

---

## 🔧 Common Issues & Quick Fixes

### Issue 1: Redis Connection Failed

```bash
# Check Redis status
sudo systemctl status redis-server

# Restart Redis
sudo systemctl restart redis-server

# Test connection
redis-cli ping
```

### Issue 2: Queue Workers Not Running

```bash
# Check supervisor
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart cbt-queue-worker:*

# Check logs
tail -f storage/logs/queue-worker.log
```

### Issue 3: High Database Load

```bash
# Check slow queries
mysql -u root -p -e "SELECT * FROM mysql.slow_log LIMIT 10;"

# Optimize tables
php artisan db:optimize

# Restart MySQL
sudo systemctl restart mysql
```

### Issue 4: High Memory Usage

```bash
# Check memory
free -h

# Clear Laravel cache
php artisan cache:clear

# Clear Redis cache
redis-cli FLUSHALL

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Issue 5: Slow Response Time

```bash
# Warm up cache
php artisan cache:warmup

# Clear all cache and rebuild
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php8.1-fpm nginx
```

---

## 🎯 Performance Targets

Setelah optimasi, sistem harus mencapai target ini:

```
✅ Concurrent Users: 500+
✅ Response Time (Save Answer): < 200ms
✅ Response Time (Load Exam): < 1s
✅ Response Time (Submit): < 2s
✅ Database Connections: 50-100
✅ Redis Hit Rate: > 90%
✅ Queue Processing: < 5s
✅ Failed Requests: < 0.1%
✅ Uptime: > 99.9%
```

---

## 📈 Scaling Tips

### For 1000+ Users

```bash
# 1. Increase database connections
# Edit: /etc/mysql/conf.d/optimization.cnf
max_connections = 1000

# 2. Increase PHP-FPM workers
# Edit: /etc/php/8.1/fpm/pool.d/www.conf
pm.max_children = 300

# 3. Increase Redis memory
# Edit: /etc/redis/redis.conf
maxmemory 4gb

# 4. Add more queue workers
# Edit: /etc/supervisor/conf.d/cbt-queue-worker.conf
numprocs=16

# 5. Restart all services
sudo systemctl restart mysql redis-server php8.1-fpm nginx
sudo supervisorctl restart cbt-queue-worker:*
```

### For 2000+ Users (Load Balancing)

```
1. Setup multiple application servers
2. Use Nginx as load balancer
3. Shared Redis cluster
4. MySQL master-slave replication
5. CDN for static assets
6. Separate queue server
```

---

## 🆘 Support

Jika mengalami masalah:

1. Check logs: `tail -f storage/logs/laravel.log`
2. Run diagnostics: `php artisan system:monitor`
3. Check documentation: `OPTIMASI_500_USER.md`
4. Review troubleshooting: Section 🚨 in main docs

---

## 📝 Next Steps

Setelah optimasi berhasil:

1. ✅ Run load testing
2. ✅ Monitor system for 24 hours
3. ✅ Adjust configuration based on metrics
4. ✅ Setup automated backups
5. ✅ Configure monitoring alerts
6. ✅ Document your specific configuration
7. ✅ Train team on monitoring tools

---

**Sistem siap untuk 500+ concurrent users! 🎉**
