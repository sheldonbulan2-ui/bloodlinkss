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

# Suppress ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Expose standard web port and Render dynamic port
EXPOSE 80 10000

# Dynamically bind Apache to Render's dynamic $PORT (defaults to 80 if not set)
CMD ["sh", "-c", "sed -i \"s/80/${PORT:-80}/g\" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground"]
