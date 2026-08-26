FROM php:8.2-apache

# Instalar extensão PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Ativar mod_rewrite do Apache
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copiar arquivos do painel
COPY . /var/www/html/

EXPOSE 80
