# --------------------------------------------------------
# PHP + Apache (imagem oficial)
# --------------------------------------------------------
FROM php:8.2-apache

# --------------------------------------------------------
# 1. Dependências do sistema + extensões PHP
# --------------------------------------------------------
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo pdo_mysql zip

# --------------------------------------------------------
# 2. Composer (IMPORTANTE para seu erro do vendor/)
# --------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --------------------------------------------------------
# 3. Apache mod_rewrite
# --------------------------------------------------------
RUN a2enmod rewrite

# --------------------------------------------------------
# 4. DocumentRoot apontando para /public
# --------------------------------------------------------
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Permitir .htaccess
RUN echo '<Directory "/var/www/html/public">\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/public.conf \
 && a2enconf public

# --------------------------------------------------------
# 5. Diretório de trabalho
# --------------------------------------------------------
WORKDIR /var/www/html

# --------------------------------------------------------
# 6. Copiar projeto
# --------------------------------------------------------
COPY . /var/www/html

# A imagem gerada pelo pipeline deve ser executável sem depender do volume do
# workspace ou de um composer install durante a inicialização.
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

# --------------------------------------------------------
# 7. Dependências e permissões corretas
# --------------------------------------------------------
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public

# --------------------------------------------------------
# 8. PHP settings úteis (evita bugs chatos)
# --------------------------------------------------------
RUN echo "output_buffering = On" > /usr/local/etc/php/conf.d/docker.ini \
 && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/docker.ini \
 && echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/docker.ini \
 && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/docker.ini

# --------------------------------------------------------
# 9. Porta Apache
# --------------------------------------------------------
EXPOSE 80

# --------------------------------------------------------
# 10. Start do Apache
# --------------------------------------------------------
CMD ["apache2-foreground"]
