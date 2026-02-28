FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    ffmpeg \
    supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install PHP Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
COPY --chown=www:www . /var/www/html

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY start-container.sh /usr/local/bin/start-container
RUN sed -i 's/\r$//' /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

# Create supervisor log directory and permissions (must be done before switching user)
RUN mkdir -p /var/log/supervisor && chown -R www:www /var/log/supervisor /var/run

# Change current user to www
USER www

# Expose port 9000 and start container via entrypoint
EXPOSE 9000
ENTRYPOINT ["start-container"]
