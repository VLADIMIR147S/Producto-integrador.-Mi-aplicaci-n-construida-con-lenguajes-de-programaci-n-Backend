FROM php:8.2-apache

# Dependencias de compilación para las extensiones PHP utilizadas.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libsqlite3-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libonig-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_sqlite pdo_mysql mysqli gd mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \\*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

RUN printf '%s\n' \
    'file_uploads=On' \
    'upload_max_filesize=6M' \
    'post_max_size=32M' \
    'max_file_uploads=20' \
    'memory_limit=256M' \
    'max_execution_time=120' \
    > /usr/local/etc/php/conf.d/app.ini

COPY . /var/www/html/

RUN mkdir -p /var/data/uploads /var/www/html/data /var/www/html/uploads \
    && chown -R www-data:www-data /var/data /var/www/html \
    && chmod -R 775 /var/data

EXPOSE 10000
CMD ["apache2-foreground"]
