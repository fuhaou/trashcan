php /usr/share/nginx/html/artisan migrate --force > /tmp/migration.log
service nginx start
/usr/local/sbin/php-fpm
