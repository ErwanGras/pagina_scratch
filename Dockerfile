FROM php:8.2-apache

# Instalar la extensión PDO MySQL necesaria para conectarse a bases de datos MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar el módulo de reescritura de Apache
RUN a2enmod rewrite

# Copiar el código del proyecto al directorio web de Apache
COPY . /var/www/html/

# Ajustar los permisos para que Apache pueda acceder
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80
EXPOSE 80
