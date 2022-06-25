php /usr/share/nginx/html/artisan migrate --force > /tmp/migration.log
service cron start
crontab < /usr/share/nginx/html/cron-data
while true ; do
    sleep 1;
done
