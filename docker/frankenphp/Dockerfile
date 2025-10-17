FROM dunglas/frankenphp:1-php8.4

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    librabbitmq-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip sockets \
    && pecl install redis amqp \
    && docker-php-ext-enable redis amqp

RUN echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/llm.ini && \
    echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/llm.ini && \
    echo "default_socket_timeout = 120" >> /usr/local/etc/php/conf.d/llm.ini

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader

COPY . ./

RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app

EXPOSE 80 443

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"] 