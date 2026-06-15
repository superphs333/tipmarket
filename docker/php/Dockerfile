FROM php:8.4-fpm

# Laravel 실행에 필요한 시스템 패키지와 PHP 확장 도구를 설치함
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    gnupg \
    libfreetype6-dev \
    libjpeg-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip bcmath \
    && pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer 최신 버전 설치함
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
