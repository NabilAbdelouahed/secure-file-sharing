FROM php:8.3-apache

# Install PostgreSQL PDO driver
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Harden session cookies globally
RUN echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "session.cookie_samesite = Strict" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "session.use_strict_mode = 1" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "session.use_only_cookies = 1" >> /usr/local/etc/php/conf.d/session.ini

# Copy application files
COPY . /var/www/html/

# Ensure uploads directory exists and is writable
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
