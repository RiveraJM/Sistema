# Base PHP con Apache
FROM php:8.2-apache

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    git zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli gd \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activar mod_rewrite de Apache
RUN a2enmod rewrite

# Cambiar DocumentRoot de /var/www/html → /var/www/public
RUN sed -i 's|/var/www/html|/var/www/public|g' /etc/apache2/sites-available/000-default.conf

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos del proyecto al contenedor
COPY . /var/www

# Instalar dependencias si existe composer.json
RUN if [ -f composer.json ]; then composer install --no-interaction; fi

# Ajustar permisos
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Exponer puerto
EXPOSE 80
