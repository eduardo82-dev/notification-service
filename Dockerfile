FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libssl-dev \
    libgmp-dev \
    libicu-dev \
    librdkafka-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip \
    intl \
    gmp \
    opcache \
    sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install AMQP extension (RabbitMQ)
RUN apt-get update && apt-get install -y librabbitmq-dev \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Create system user to run Composer and Artisan Commands
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Change current user to www
USER www

EXPOSE 9000
CMD ["php-fpm"]
