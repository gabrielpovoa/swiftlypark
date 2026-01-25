# Usamos a imagem oficial do PHP com Apache
FROM php:8.2-apache

# 1. Instala dependências do sistema e extensões do PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo pdo_mysql

# 2. Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# --- AJUSTE AQUI: Mudar o DocumentRoot para a pasta public ---
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Garantir que o diretório public tenha permissão de sobrescrita (AllowOverride)
RUN echo '<Directory "/var/www/html/public">\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf
# -----------------------------------------------------------

# 3. Configura o diretório de trabalho
WORKDIR /var/www/html

# 4. Copia os arquivos do projeto para dentro do container
COPY . /var/www/html

# 5. Ajusta as permissões
# www-data precisa ser dono de TUDO para o Apache ler o index e gravar uploads
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public

# Habilita o Output Buffering para evitar erros de headers already sent por causa de Notices
RUN echo "output_buffering = On" >> /usr/local/etc/php/conf.d/docker-php-config.ini

# 6. Expõe a porta 80
EXPOSE 80