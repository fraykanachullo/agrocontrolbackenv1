FROM dunglas/frankenphp:php8.4

# Instalar extensiones PHP necesarias
RUN install-php-extensions pdo_pgsql pgsql bcmath zip intl

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Config PHP optimizada (opcache sin revalidación + max_execution_time)
COPY docker/php-conf/99-app.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /app
EXPOSE 80 443
