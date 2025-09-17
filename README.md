cd Modules/QrGenerator/packages/qr-code-styling
yarn



chmod +x setup-qr-puppeteer.sh
sudo ./setup-qr-puppeteer.sh







# cron jobs
nano /etc/supervisor/conf.d/satify-artisan-jobs.conf

------------------------------------------------------------------------------


[program:artisan-servers-sync-traffic]
command=/bin/bash -lc 'while true; do php artisan servers:sync-traffic --no-interaction --no-ansi || true; sleep 1800; done'
directory=/var/www/satify
user=www-data
autostart=true
autorestart=true
startsecs=5
stopsignal=TERM
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/supervisor/servers-sync-traffic.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stderr_logfile=/var/log/supervisor/servers-sync-traffic.err.log
stderr_logfile_maxbytes=10MB
stderr_logfile_backups=5
environment=APP_ENV="production",APP_DEBUG="false"

[program:artisan-orders-deactivate-exceeded]
command=/bin/bash -lc 'while true; do php artisan orders:deactivate-exceeded-traffic --no-interaction --no-ansi || true; sleep 14400; done'
directory=/var/www/satify
user=www-data
autostart=true
autorestart=true
startsecs=5
stopsignal=TERM
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/supervisor/orders-deactivate-exceeded.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stderr_logfile=/var/log/supervisor/orders-deactivate-exceeded.err.log
stderr_logfile_maxbytes=10MB
stderr_logfile_backups=5
environment=APP_ENV="production",APP_DEBUG="false"

[program:artisan-servers-sync-users]
command=/bin/bash -lc 'while true; do php artisan servers:sync-users --no-interaction --no-ansi || true; sleep 86400; done'
directory=/var/www/satify
user=www-data
autostart=true
autorestart=true
startsecs=5
stopsignal=TERM
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/supervisor/servers-sync-users.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stderr_logfile=/var/log/supervisor/servers-sync-users.err.log
stderr_logfile_maxbytes=10MB
stderr_logfile_backups=5
environment=APP_ENV="production",APP_DEBUG="false"



------------------------------------------------------------------------------



sudo mkdir -p /var/log/supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start artisan-servers-sync-traffic
sudo supervisorctl start artisan-orders-deactivate-exceeded
sudo supervisorctl status
