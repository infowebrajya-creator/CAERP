FROM php:8.4-cli-alpine

LABEL maintainer="WebRajya Technologies <support@webrajya.in>"
LABEL description="WebRajya ERP — Chartered Accountant Practice Management System"

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mysql-client \
    zip \
    unzip \
    git

# Install PHP extensions required by Laravel, DomPDF & MySQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        bcmath \
        intl \
        opcache \
        pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP composer dependencies (optimized production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy entrypoint script and make executable
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose WebRajya ERP port
EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

