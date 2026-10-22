# PHP 8.2.35+ (21 Oct 2026): parche CVE-2026-109618 / CVE-2026-109619 (RCE; crítico con Apache mod_php).
FROM php:8.2.35-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd \
    && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY composer.json composer.lock* /var/www/html/
WORKDIR /var/www/html
RUN composer install --no-dev --no-interaction --prefer-dist
COPY . /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html
WORKDIR /var/www/html
EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
