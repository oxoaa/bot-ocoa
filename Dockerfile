FROM php:8.4-cli

RUN apt-get update -qq && apt-get install -y -qq git unzip curl > /dev/null 2>&1 \
    && pecl install swoole > /dev/null 2>&1 \
    && docker-php-ext-enable swoole \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /bot
COPY . .
RUN composer install --no-dev --quiet 2>/dev/null || true

CMD ["php", "server.php"]
