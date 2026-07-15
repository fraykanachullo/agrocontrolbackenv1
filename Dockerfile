FROM dunglas/frankenphp:php8.4

# Instalar extensiones PHP necesarias
RUN install-php-extensions pdo_pgsql pgsql bcmath zip intl

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
EXPOSE 80 443
