FROM epsilion/php:7.4-nginx
WORKDIR /usr/share/nginx/html

RUN apt install cron -y && service cron start
COPY nginx/default /etc/nginx/sites-enabled/
COPY docker/passwd /etc/
COPY . .

RUN composer config --global bitbucket-oauth.bitbucket.org u9a2UWad9kvKABMpgZ SCjhQgxacv8q48FSjaeA6U2gMqwaHRJk
RUN composer install -d ./
COPY php-tuning/www.conf /usr/local/etc/php-fpm.d/
COPY php-tuning/php.ini-production /usr/local/etc/php/php.ini
CMD ["sh", "start.sh"]

