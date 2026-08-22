# Cron Template

```bash
# Daily report generation at 6 AM
0 6 * * * /usr/bin/php /var/www/html/index.php controller method >> /var/log/cron/report.log 2>&1

# Hourly cleanup
0 * * * * /usr/bin/php /var/www/html/index.php controller cleanup >> /var/log/cron/cleanup.log 2>&1

# Database backup at midnight
0 0 * * * /usr/bin/mysqldump -u user -p'pass' dbname > /backups/db_$(date +\%Y\%m\%d).sql 2>&1
```
