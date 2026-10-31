FROM php:8.2-apache

# Install PHP extensions commonly used by PHP/MySQL applications
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache rewrite rules
RUN a2enmod rewrite

# Configure Apache to listen on Render's expected port
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \\*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

# Copy application files into Apache's document root
COPY . /var/www/html/

# Set ownership for Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["apache2-foreground"]
