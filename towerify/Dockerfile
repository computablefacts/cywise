#
# PHP Dependencies
#
FROM composer:2 AS vendor

# Needed dependencies from composer.json
COPY composer.json composer.lock ./
COPY database ./database

# Install with --no-dev for production
RUN composer install \
#    --no-dev \
    --ignore-platform-reqs \
    --no-interaction \
    --no-progress \
    --no-ansi \
    --no-plugins \
    --no-scripts \
    --prefer-dist

#
# Frontend
#
FROM node:18 AS frontend

COPY --chown=www-data:www-data . /app
# Add composer dependencies
COPY --chown=www-data:www-data --from=vendor /app/vendor/ /app/vendor/

WORKDIR /app

RUN node --version
RUN npm --version
# RUN npm install -g npm && npm --version
RUN npm install --verbose
RUN npm run build

#
# Application
#
FROM php:8.3-apache AS final

LABEL maintainer="Patrick Brisacier <pbrisacier@mncc.fr>"

RUN apt-get update && apt-get install -y \
    poppler-utils \
    dnsutils \
    wget \
    jq \
    ffmpeg \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg

# Install clickhouse-client
# See: https://clickhouse.com/docs/install#setup-the-debian-repository
RUN curl -fsSL 'https://packages.clickhouse.com/rpm/lts/repodata/repomd.xml.key' | gpg --dearmor -o /usr/share/keyrings/clickhouse-keyring.gpg \
    && ARCH=$(dpkg --print-architecture) \
    && echo "deb [signed-by=/usr/share/keyrings/clickhouse-keyring.gpg arch=${ARCH}] https://packages.clickhouse.com/deb stable main" | tee /etc/apt/sources.list.d/clickhouse.list \
    && apt-get update \
    && apt-get install -y clickhouse-client \
    && clickhouse-client --version \
    && clickhouse-local --version

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# imagick/imagick@master : see https://github.com/Imagick/imagick/issues/640#issuecomment-2077206945 for details
RUN install-php-extensions \
        intl \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        exif \
        opcache \
        imagick/imagick@master \
        bcmath \
        zlib

# PHP configuration
# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Max execution time (default=30)
RUN echo "max_execution_time=120" > $PHP_INI_DIR/conf.d/max-execution-time.ini
# Memory Limit (default=128M)
RUN echo "memory_limit=6144M" > $PHP_INI_DIR/conf.d/max-limit.ini
# POST max size (default=8M)
RUN echo "post_max_size=50M" >> $PHP_INI_DIR/conf.d/max-limit.ini
# Upload max file size (default=2M)
RUN echo "upload_max_filesize=50M" >> $PHP_INI_DIR/conf.d/max-limit.ini
# Set maximum length of log_errors (default=1024)
RUN echo "log_errors_max_len=16384" >> $PHP_INI_DIR/conf.d/max-limit.ini

# Setup opcache. See https://www.php.net/manual/fr/opcache.configuration.php for details
RUN echo "opcache.enable=1" > $PHP_INI_DIR/conf.d/opcache.ini
RUN echo "opcache.revalidate_freq=0" >> $PHP_INI_DIR/conf.d/opcache.ini
RUN echo "opcache.validate_timestamps=0" >> $PHP_INI_DIR/conf.d/opcache.ini
RUN echo "opcache.max_accelerated_files=20000" >> $PHP_INI_DIR/conf.d/opcache.ini
RUN echo "opcache.memory_consumption=256" >> $PHP_INI_DIR/conf.d/opcache.ini
RUN echo "opcache.max_wasted_percentage=10" >> $PHP_INI_DIR/conf.d/opcache.ini
RUN echo "opcache.interned_strings_buffer=16" >> $PHP_INI_DIR/conf.d/opcache.ini
RUN echo "opcache.fast_shutdown=1" >> $PHP_INI_DIR/conf.d/opcache.ini

# Time Zone (default=UTC)
RUN echo "date.timezone=UTC" > $PHP_INI_DIR/conf.d/date_timezone.ini

# Disable expose PHP (default=1)
# Value 0 remove the HTTP header: "X-Powered-By : PHP/N.N.N"
RUN echo "expose_php=0" > $PHP_INI_DIR/conf.d/expose-php.ini

# Copy application files
# See .dockerignore to know which files and directories are excluded
COPY --chown=www-data:www-data . /var/www/html
# Add composer dependencies
COPY --chown=www-data:www-data --from=vendor /app/vendor/ /var/www/html/vendor/
# Add npm dependencies
COPY --chown=www-data:www-data --from=frontend /app/public/build/ /var/www/html/public/build/

RUN chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache

# Generate API docs
RUN php artisan sajya:docs 'v2.public.rpc.endpoint' --path='/public/docs/' --name 'docs.public.html'
RUN php artisan sajya:docs 'v2.private.rpc.endpoint' --path='/public/docs/' --name 'docs.private.html'

COPY <<-EOT /etc/apache2/sites-available/laravel.conf
<VirtualHost *:80>
  DocumentRoot /var/www/html/public
  <Directory /var/www/html/public>
    AllowOverride All
  </Directory>
</VirtualHost>
EOT

RUN a2dissite 000-default.conf \
    && a2enmod rewrite \
    && a2ensite laravel.conf


# Start script
COPY <<-"EOT" /usr/local/bin/start
#!/usr/bin/env bash
# See: https://laravel-news.com/laravel-scheduler-queue-docker
set -e
role=${CONTAINER_ROLE:-app}
queue_name=${QUEUE_NAME:-default}
if [[ "$role" = "app" ]]; then
    echo "artisan storage:link && artisan vendor:publish ..."
    (cd /var/www/html && php artisan storage:link && php artisan vendor:publish --tag=telescope-assets)
    echo "artisan optimize ..."
    (cd /var/www/html && php artisan optimize)
    echo "artisan filament:optimize ..."
    (cd /var/www/html && php artisan filament:optimize)
    echo "chown -R www-data:www-data ..."
    mkdir -p /var/www/html/storage/app/public/docs
    chown -R www-data:www-data /var/www/html/storage/app/public/docs
    mkdir -p /var/www/html/storage/framework/views
    chown -R www-data:www-data /var/www/html/storage/framework/views
    echo "Start apache..."
    exec apache2-foreground
elif [[ "$role" = "queue" ]]; then
    while [[ true ]]
    do
      echo "Running the queue..."
      (cd /var/www/html && php artisan queue:work --memory 1024 --queue=$queue_name --stop-when-empty --verbose --tries=3 --timeout=90)
      echo "Queue empty... wait some seconds..."
      sleep 13
    done
elif [[ "$role" = "scheduler" ]]; then
    echo "Migrate database if needed..."
    (cd /var/www/html && php artisan migrate --step --force)
    if [[ -f /var/www/html/database/seeders/CywiseSeeder.php ]]; then
        echo "Apply Seeders..."
        (cd /var/www/html && php artisan db:from-old-to-new)
        (cd /var/www/html && php artisan db:seed --class=CywiseSeeder)
    fi
    echo "Launch schedule:run every minute..."
    while [[ true ]]
    do
      (cd /var/www/html && php artisan schedule:run --verbose --no-interaction)
      sleep 60
    done
else
    echo "Could not match the container role \"$role\""
    exit 1
fi
EOT
RUN chmod u+x /usr/local/bin/start


WORKDIR /var/www/html

# Launch our start script
CMD ["/usr/local/bin/start"]