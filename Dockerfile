FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    fonts-noto-core \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        mbstring \
        xml \
        gd \
        zip \
    && mkdir -p /var/www/fonts \
    && cp /usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf /var/www/fonts/ \
    && cp /usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf /var/www/fonts/ \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json /var/www/composer.json
RUN cd /var/www \
    && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-progress

COPY public/ /var/www/html/
COPY src/ /var/www/src/
COPY sql/ /var/www/sql/
COPY fonts/ /var/www/fonts/

# Fail the image build early if the application structure is incomplete.
RUN test -f /var/www/src/bootstrap.php \
    && test -f /var/www/src/partials.php \
    && test -f /var/www/sql/schema.sql \
    && test -f /var/www/html/dashboard.php \
    && test -f /var/www/fonts/NotoSansThai-Regular.ttf \
    && test -f /var/www/fonts/NotoSansThai-Bold.ttf \
    && chown -R www-data:www-data /var/www/html /var/www/src /var/www/sql /var/www/vendor

EXPOSE 80
