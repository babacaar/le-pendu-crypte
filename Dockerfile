FROM php:8.2-apache

# Set the Apache DocumentRoot to public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Enable mod_rewrite
RUN a2enmod rewrite

# Install necessary libraries and tools
RUN apt-get update && apt-get install -y \
    curl \
    gnupg \
    cron \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    libonig-dev \
    libxml2-dev \
    mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd zip pdo pdo_mysql \
    && docker-php-ext-install gd zip pdo pdo_mysql exif

# ✅ Install Node.js and Yarn for asset building
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && \
    npm install --global yarn

# Update Apache conf to use public/ as DocumentRoot and allow .htaccess
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf && \
    sed -ri -e 's!<Directory /var/www/>!<Directory ${APACHE_DOCUMENT_ROOT}>!g' /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the project files into the container
COPY . /var/www/html/
WORKDIR /var/www/html/

# Install JS dependencies and build Encore assets
RUN yarn install && yarn build

# Install PHP dependencies for production
RUN APP_ENV=prod composer install --no-dev --optimize-autoloader --no-interaction

# Set file permissions
RUN chown -R www-data:www-data /var/www/html

# Add cron job configuration
COPY ./docker/cronjobs /etc/cron.d/delete-unverified-users
RUN chmod 0644 /etc/cron.d/delete-unverified-users

# Allow .htaccess in public/
COPY docker/htaccess.conf /etc/apache2/conf-available/htaccess.conf
RUN a2enconf htaccess

# Start Apache and cron in the background
CMD cron && apache2-foreground