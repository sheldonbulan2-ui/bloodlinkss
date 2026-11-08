FROM php:8.2-apache

# Install CA certificates, libpq for Supabase (PostgreSQL) & enable Apache mod_rewrite
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates libpq-dev \
    && update-ca-certificates \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install PDO MySQL & PostgreSQL drivers (Universal TiDB & Supabase support)
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql

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
