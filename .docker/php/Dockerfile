FROM php:8-fpm

ARG INSTALL_XDEBUG="false"
ARG INSTALL_COMPOSER="true"

ENV COMPOSER_HOME=/var/www/.composer

COPY rootfs /

# Configure certificates, OpenSSL, and apt repository for Postgres
RUN apt-get update \
    && apt-get install -my gnupg2 \
    && update-ca-certificates \
    && sed -i 's/CipherString = DEFAULT@SECLEVEL=2/CipherString = DEFAULT@SECLEVEL=1/' /etc/ssl/openssl.cnf \
    && echo 'deb http://apt.postgresql.org/pub/repos/apt/ bullseye-pgdg main' > /etc/apt/sources.list.d/pgdg.list \
    # Add postgresql GPG
    && curl --output /tmp/postgres.asc https://www.postgresql.org/media/keys/ACCC4CF8.asc \
    && apt-key add /tmp/postgres.asc \
    && rm /tmp/postgres.asc \
    && if [ "${INSTALL_XDEBUG}" = "false" ]; \
    then \
    rm -rf /usr/local/etc/php/conf.d/xdebug.ini; \
    fi

RUN apt-get update \
    && apt-get install -y \
    bzip2 \
    exiftool \
    git-core \
    imagemagick \
    libbz2-dev \
    libc-client2007e-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libkrb5-dev \
    libldap2-dev \
    libmagickwand-dev \
    libmemcached-dev \
    libpng-dev \
    libpq-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    msmtp \
    openssl \
    postgresql-client \
    pv \
    unzip \
    xfonts-base \
    xfonts-75dpi \
    zlib1g-dev \
    && pecl install apcu \
    && pecl install imagick \
    && pecl install memcached \
    && pecl install oauth \
    && pecl install redis \
    && if [ "${INSTALL_XDEBUG}" = "true" ]; then pecl install xdebug; fi \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure imap --with-imap-ssl --with-kerberos \
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable imagick \
    && docker-php-ext-enable memcached \
    && docker-php-ext-enable oauth \
    && docker-php-ext-enable redis \
    && docker-php-ext-install \
    bcmath \
    bz2 \
    calendar \
    exif \
    gd \
    imap \
    opcache \
    pdo \
    pdo_pgsql \
    zip \
    intl \
    gettext \
    # Install composer
    && if [ "${INSTALL_COMPOSER}" = "true" ]; \
    then \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
    php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"; \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer; \
    php -r "unlink('composer-setup.php');"; \
    mkdir -p ${COMPOSER_HOME}; \
    chown -R www-data:www-data ${COMPOSER_HOME}; \
    fi \
    # Create drush and drupal console symlink so it can be accessed from anywhere in the container
    && ln -s /var/www/html/vendor/drush/drush/drush /usr/local/bin/drush \
    # Change the shell for login as www-data user
    && chsh -s /bin/bash www-data \
    # Clean up
    && apt-get -y clean \
    && apt-get -y autoclean \
    && apt-get -y autoremove \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /var/lib/cache/* \
    && rm -rf /var/lib/log/* \
    && rm -rf /tmp/*

# when you run bash, this is where you'll start, and where drush will be able to run from.
WORKDIR /var/www/html

USER www-data
