# 🚀 Optimasi Sistem untuk 500+ Concurrent Users

## 📊 Analisis Kebutuhan

Untuk menangani 500+ user concurrent yang mengerjakan ujian:
- **Request per detik**: ~50-100 RPS (auto-save setiap 3-5 detik)
- **Database queries**: ~200-500 queries/second
- **Memory usage**: ~2-4GB RAM minimum, 8GB recommended
- **CPU usage**: 4+ cores recommended, 8 cores optimal
- **Network bandwidth**: 10-20 Mbps
- **Storage**: SSD recommended untuk database & cache

---

## 🔧 Optimasi yang Diperlukan

### 1. Database Optimization (MySQL)
### 2. Caching Strategy (Redis)
### 3. Queue System (Laravel Queue + Redis)
### 4. Session Management (Redis)
### 5. PHP-FPM Optimization
### 6. Nginx/Apache Optimization
### 7. Database Indexing
### 8. Rate Limiting
### 9. Monitoring & Logging

---

## 📝 Implementasi Step-by-Step

### STEP 1: Install Redis

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install redis-server -y

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Test Redis
redis-cli ping
# Should return: PONG
```

### STEP 2: Install PHP Redis Extension

```bash
# Install PHP Redis
sudo apt install php-redis php-igbinary -y

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Verify installation
php -m | grep redis
```

### STEP 3: Configure Redis

```bash
# Copy optimized Redis config
sudo cp redis.conf /etc/redis/redis.conf

# Set Redis password (recommended)
sudo nano /etc/redis/redis.conf
# Uncomment and set: requirepass your_strong_password_here

# Restart Redis
sudo systemctl restart redis-server
```

### STEP 4: Configure Laravel for Redis

```bash
# Copy production environment
cp .env.production .env

# Edit .env
nano .env

# Set these values:
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379
```

### STEP 5: Run Database Migrations for Indexes

```bash
# Run migration to add performance indexes
php artisan migrate

# This will add indexes to:
# - jawaban_siswas
# - peserta_ujians
# - bank_soals
# - opsi_jawabans
# - ujians
# - users
# - siswa
```

### STEP 6: Optimize MySQL

```bash
# Copy MySQL optimization config
sudo cp mysql-optimization.cnf /etc/mysql/conf.d/optimization.cnf

# Edit based on your RAM
sudo nano /etc/mysql/conf.d/optimization.cnf

# Adjust innodb_buffer_pool_size:
# - 2GB RAM: set to 1G
# - 4GB RAM: set to 2G
# - 8GB RAM: set to 4G
# - 16GB RAM: set to 8G

# Restart MySQL
sudo systemctl restart mysql

# Check MySQL status
sudo systemctl status mysql
```

### STEP 7: Optimize PHP-FPM

```bash
# Copy PHP-FPM optimization
sudo cp php-fpm-optimization.conf /etc/php/8.1/fpm/pool.d/www.conf

# Adjust pm.max_children based on RAM:
# Formula: (Total RAM - OS RAM) / Average PHP Process Size
# Example: (8GB - 2GB) / 40MB = 150 processes

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Check status
sudo systemctl status php8.1-fpm
```

### STEP 8: Optimize Nginx

```bash
# Backup original config
sudo cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup

# Copy optimized config
sudo cp nginx-optimization.conf /etc/nginx/nginx.conf

# Edit server_name and SSL paths
sudo nano /etc/nginx/nginx.conf

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### STEP 9: Setup Queue Workers with Supervisor

```bash
# Install Supervisor
sudo apt install supervisor -y

# Copy queue worker config
sudo cp supervisor-queue-worker.conf /etc/supervisor/conf.d/cbt-queue-worker.conf

# Edit paths in config
sudo nano /etc/supervisor/conf.d/cbt-queue-worker.conf

# Update Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start queue workers
sudo supervisorctl start cbt-queue-worker:*

# Check status
sudo supervisorctl status
```

### STEP 10: Optimize Laravel

```bash
# Clear all cache
php artisan optimize:clear

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Generate optimized class loader
composer dump-autoload -o
```

### STEP 11: Warm Up Cache

```bash
# Create artisan command to warm up cache
php artisan make:command WarmUpCache

# Run cache warm-up
php artisan cache:warmup
```

### STEP 12: Enable OPcache

```bash
# Check if OPcache is enabled
php -i | grep opcache

# If not enabled, edit php.ini
sudo nano /etc/php/8.1/fpm/php.ini

# Add/uncomment these lines:
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

---

## 🧪 Testing & Benchmarking

### Test 1: Redis Connection

```bash
# Test Redis from Laravel
php artisan tinker

# In tinker:
Cache::put('test', 'value', 60);
Cache::get('test');
# Should return: "value"
```

### Test 2: Queue System

```bash
# Dispatch a test job
php artisan tinker

# In tinker:
dispatch(new App\Jobs\SaveJawabanJob(1, 1, 'A', false));

# Check queue
php artisan queue:work --once

# Check logs
tail -f storage/logs/laravel.log
```

### Test 3: Load Testing with Apache Bench

```bash
# Install Apache Bench
sudo apt install apache2-utils -y

# Test login page (100 requests, 10 concurrent)
ab -n 100 -c 10 https://cbt.yourschool.com/login

# Test save jawaban (with authentication)
# First, get session cookie from browser
ab -n 500 -c 50 -C "laravel_session=YOUR_SESSION_COOKIE" \
   -p post_data.json -T application/json \
   https://cbt.yourschool.com/exam/1/save-jawaban
```

### Test 4: Load Testing with Siege

```bash
# Install Siege
sudo apt install siege -y

# Test with 100 concurrent users for 1 minute
siege -c 100 -t 1M https://cbt.yourschool.com

# Test specific endpoints
siege -c 50 -r 10 -f urls.txt
```

### Test 5: Database Performance

```sql
-- Check slow queries
SELECT * FROM mysql.slow_log 
ORDER BY start_time DESC 
LIMIT 10;

-- Check index usage
SHOW INDEX FROM jawaban_siswas;
SHOW INDEX FROM peserta_ujians;

-- Analyze table
ANALYZE TABLE jawaban_siswas;
ANALYZE TABLE peserta_ujians;

-- Check table status
SHOW TABLE STATUS LIKE 'jawaban_siswas';
```

---

## 📊 Monitoring

### Monitor Redis

```bash
# Redis CLI monitor
redis-cli monitor

# Redis stats
redis-cli info stats

# Check memory usage
redis-cli info memory

# Check connected clients
redis-cli client list
```

### Monitor MySQL

```bash
# Show processlist
mysql -u root -p -e "SHOW FULL PROCESSLIST;"

# Show status
mysql -u root -p -e "SHOW STATUS LIKE '%thread%';"

# Show variables
mysql -u root -p -e "SHOW VARIABLES LIKE 'max_connections';"

# Check InnoDB status
mysql -u root -p -e "SHOW ENGINE INNODB STATUS\G"
```

### Monitor PHP-FPM

```bash
# Check PHP-FPM status
curl http://localhost/status

# Check PHP-FPM pool status
sudo systemctl status php8.1-fpm

# Monitor PHP-FPM processes
watch -n 1 'ps aux | grep php-fpm | wc -l'

# Check slow log
tail -f /var/log/php-fpm/slow.log
```

### Monitor Queue Workers

```bash
# Supervisor status
sudo supervisorctl status

# Queue stats
php artisan queue:monitor

# Failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Monitor System Resources

```bash
# CPU & Memory
htop

# Disk I/O
iotop

# Network
iftop

# All-in-one monitoring
glances
```

---

## 🔍 Performance Metrics

### Target Metrics for 500+ Users

```
✅ Response Time:
   - Login: < 500ms
   - Load Exam: < 1s
   - Save Answer: < 200ms
   - Submit Exam: < 2s

✅ Throughput:
   - 100+ requests/second
   - 500+ concurrent connections

✅ Database:
   - Query time: < 50ms average
   - Connection pool: 50-100 connections
   - Cache hit rate: > 80%

✅ Redis:
   - Memory usage: < 2GB
   - Hit rate: > 90%
   - Latency: < 1ms

✅ Server:
   - CPU usage: < 70%
   - Memory usage: < 80%
   - Disk I/O: < 80%
```

---

## 🚨 Troubleshooting

### Issue 1: Redis Connection Failed

```bash
# Check Redis status
sudo systemctl status redis-server

# Check Redis logs
sudo tail -f /var/log/redis/redis-server.log

# Test connection
redis-cli ping

# Check Laravel Redis config
php artisan tinker
Redis::ping();
```

### Issue 2: Queue Workers Not Processing

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart cbt-queue-worker:*

# Check queue connection
php artisan queue:work --once

# Clear failed jobs
php artisan queue:flush
```

### Issue 3: High Database Load

```bash
# Check slow queries
mysql -u root -p -e "SELECT * FROM mysql.slow_log LIMIT 10;"

# Check connections
mysql -u root -p -e "SHOW PROCESSLIST;"

# Kill long-running queries
mysql -u root -p -e "KILL <process_id>;"

# Optimize tables
php artisan db:optimize
```

### Issue 4: High Memory Usage

```bash
# Check memory
free -h

# Check PHP-FPM processes
ps aux | grep php-fpm

# Reduce pm.max_children in PHP-FPM config
sudo nano /etc/php/8.1/fpm/pool.d/www.conf

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Issue 5: Slow Response Time

```bash
# Enable query log
php artisan debugbar:enable

# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Nginx logs
tail -f /var/log/nginx/access.log

# Profile with Blackfire/Xdebug
```

---

## 📈 Scaling Beyond 500 Users

### Horizontal Scaling (1000+ Users)

1. **Load Balancer**
   ```
   - Use Nginx/HAProxy as load balancer
   - Multiple application servers
   - Shared Redis & MySQL
   ```

2. **Database Replication**
   ```
   - Master-Slave replication
   - Read from slaves
   - Write to master
   ```

3. **Redis Cluster**
   ```
   - Redis Sentinel for HA
   - Redis Cluster for sharding
   ```

4. **CDN for Static Assets**
   ```
   - CloudFlare
   - AWS CloudFront
   - Bunny CDN
   ```

5. **Separate Queue Server**
   ```
   - Dedicated server for queue workers
   - Separate Redis instance for queue
   ```

---

## ✅ Checklist Implementasi

```
[ ] Redis installed and configured
[ ] PHP Redis extension installed
[ ] Laravel configured for Redis (cache, session, queue)
[ ] Database indexes added (migration run)
[ ] MySQL optimized (config applied)
[ ] PHP-FPM optimized (config applied)
[ ] Nginx optimized (config applied)
[ ] Queue workers running (Supervisor)
[ ] OPcache enabled
[ ] Laravel optimized (cache config/routes/views)
[ ] Load testing completed
[ ] Monitoring setup
[ ] Backup strategy implemented
[ ] Documentation updated
```

---

## 📚 Additional Resources

- [Laravel Performance](https://laravel.com/docs/10.x/deployment#optimization)
- [Redis Best Practices](https://redis.io/docs/manual/patterns/)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [Nginx Performance](https://www.nginx.com/blog/tuning-nginx/)
- [PHP-FPM Tuning](https://www.php.net/manual/en/install.fpm.configuration.php)

---

## 🎯 Expected Results

Setelah implementasi lengkap:

✅ **500+ concurrent users** dapat mengerjakan ujian bersamaan
✅ **Response time < 200ms** untuk save jawaban
✅ **Zero downtime** selama ujian berlangsung
✅ **Auto-recovery** jika ada failure
✅ **Scalable** untuk pertumbuhan user
✅ **Monitoring** real-time untuk troubleshooting

---

**Catatan**: Sesuaikan konfigurasi dengan spesifikasi server Anda. Monitor performa secara berkala dan adjust sesuai kebutuhan.

