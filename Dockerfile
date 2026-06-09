FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mysqli curl \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/start-web.sh /usr/local/bin/start-web
COPY . /var/www/html

RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod +x /usr/local/bin/start-web

EXPOSE 80

CMD ["start-web"]
