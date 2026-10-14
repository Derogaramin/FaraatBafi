# تصویر اجرایی سامانهٔ فرت‌بافی — PHP-FPM 8.2
FROM php:8.2-fpm-alpine

RUN apk add --no-cache icu-dev libwebp-dev libjpeg-turbo-dev libpng-dev freetype-dev \
    && docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd intl opcache

# تنظیمات PHP تولید
RUN { \
      echo 'upload_max_filesize=4M'; \
      echo 'post_max_size=8M'; \
      echo 'memory_limit=256M'; \
      echo 'max_execution_time=60'; \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/faraat.ini

WORKDIR /var/www/faraatbafi

COPY composer.json composer.lock ./
RUN apk add --no-cache --virtual .build-deps git \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
    && apk del .build-deps

COPY . .
RUN composer dump-autoload --optimize --no-dev

RUN mkdir -p storage/logs storage/cache public/assets/uploads \
    && chown -R www-data:www-data storage public/assets/uploads

EXPOSE 9000
CMD ["php-fpm"]
