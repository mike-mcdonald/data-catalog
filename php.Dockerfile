FROM php:7.3-fpm

RUN apt-get update && apt-get install -my gnupg
# Get the repository set up for postgresql
RUN { \
  echo 'deb http://apt.postgresql.org/pub/repos/apt/ buster-pgdg main'; \
  } > /etc/apt/sources.list.d/pgdg.list
RUN curl https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -

RUN apt-get update && apt-get install -y \
  bzip2 \
  exiftool \
  git-core \
  imagemagick \
  libbz2-dev \
  libc-client2007e-dev \
  libjpeg-dev \
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
  ssh \
  unzip \
  wget \
  xfonts-base \
  xfonts-75dpi \
  zlib1g-dev \
  && pecl install apcu \
  && pecl install imagick \
  && pecl install memcached \
  && pecl install oauth-2.0.2 \
  && pecl install redis-3.1.2 \
  && pecl install xdebug \
  && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
  && docker-php-ext-configure imap --with-imap-ssl --with-kerberos \
  && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
  && docker-php-ext-enable apcu \
  && docker-php-ext-enable imagick \
  && docker-php-ext-enable memcached \
  && docker-php-ext-enable oauth \
  && docker-php-ext-enable redis \
  && docker-php-ext-enable xdebug \
  && docker-php-ext-install \
  bcmath \
  bz2 \
  calendar \
  exif \
  gd \
  imap \
  ldap \
  mbstring \
  mysqli \
  opcache \
  pdo \
  pdo_mysql \
  pdo_pgsql \
  soap \
  zip \
  intl \
  gettext \
  && apt-get -y clean \
  && apt-get -y autoclean \
  && apt-get -y autoremove \
  && rm -rf /var/lib/apt/lists/* \
  && rm -rf /var/lib/cache/* \
  && rm -rf /var/lib/log/* \
  && rm -rf /tmp/*

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php -r "if (hash_file('sha384', 'composer-setup.php') === 'e0012edf3e80b6978849f5eff0d4b4e4c79ff1609dd1e613307e16318854d24ae64f26d17af3ef0bf7cfb710ca74755a') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
  && php -r "unlink('composer-setup.php');" \
  && mkdir -p /var/www/.composer

# Install terminus
RUN mkdir -p /srv/bin/terminus \
  && curl -O "https://raw.githubusercontent.com/pantheon-systems/terminus-installer/master/builds/installer.phar" \
  && php installer.phar install --install-dir=/srv/bin/terminus --install-version=$TERMINUS_VERSION

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
  echo 'opcache.memory_consumption=128'; \
  echo 'opcache.interned_strings_buffer=8'; \
  echo 'opcache.max_accelerated_files=4000'; \
  echo 'opcache.revalidate_freq=60'; \
  echo 'opcache.fast_shutdown=1'; \
  echo 'opcache.enable_cli=1'; \
  } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# configure xdebug
RUN { \
  echo 'xdebug.remote_enable=on'; \
  echo 'xdebug.remote_autostart=on'; \
  echo 'xdebug.remote_host="host.docker.internal"'; \
  } > /usr/local/etc/php/conf.d/xdebug.ini

# install drush and drupal console symlink so it can be accessed from anywhere in the container
RUN ln -s /var/www/html/vendor/drush/drush/drush /usr/local/bin/drush
RUN ln -s /var/www/html/vendor/drupal/console/bin/drupal /usr/local/bin/drupal

# Change the shell for login as www-data user
RUN chsh -s /bin/bash www-data

# when you run bash, this is where you'll start, and where drush will be able to run from.
WORKDIR /var/www/html
