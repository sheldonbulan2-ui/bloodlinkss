FROM php:8.2-apache

# Install CA certificates for secure cloud database TLS/SSL & enable Apache mod_rewrite
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates \
    && update-ca-certificates \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install PDO MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy all application files
COPY . /var/www/html/

# Ensure uploads directory exists with write permissions for prescription files
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads

# Expose standard web port
EXPOSE 80

CMD ["apache2-foreground"]
