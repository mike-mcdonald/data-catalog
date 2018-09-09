FROM php:7.2-fpm

# Version information
ENV WKHTMLTOPDF_VERSION 0.12.3
ENV PHANTOMJS_VERSION 2.1.1
ENV TERMINUS_VERSION 1.8.1
ENV MAVEN_VERSION 3.5.4

RUN apt-get update && apt-get install -my gnupg
# Get the repository set up for postgresql
RUN { \
  echo 'deb http://apt.postgresql.org/pub/repos/apt/ stretch-pgdg main'; \
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
  msmtp \
  mysql-client \
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
  && rm -rf /var/lib/apt/lists/* && rm -rf && rm -rf /var/lib/cache/* && rm -rf /var/lib/log/* && rm -rf /tmp/*

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
  && php -r "unlink('composer-setup.php');" \
  && mkdir -p /var/www/.composer

RUN echo 'deb http://deb.debian.org/debian stretch-backports main' > /etc/apt/sources.list.d/backports.list

# Install the additional things that make the pantheon
RUN mkdir -p /usr/share/man/man1 \
  && apt-get update && apt-get install -y -t stretch-backports \
  ca-certificates-java \
  openjdk-8-jre-headless \
  openjdk-8-jdk \
  && rm -f /usr/local/etc/php/conf.d/*-memcached.ini \
  && mkdir -p /var/www/.drush \
  && mkdir -p /var/www/.backdrush \
  && mkdir -p /var/www/.composer \
  && mkdir -p /var/www/.drupal \
  && mkdir -p /srv/bin/terminus \
  && chown -R www-data:www-data /var/www /srv/bin \
  && curl -O "https://raw.githubusercontent.com/pantheon-systems/terminus-installer/master/builds/installer.phar" \
  && php installer.phar install --install-dir=/srv/bin/terminus --install-version=$TERMINUS_VERSION \
  && cd /tmp && curl -OL "https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/${WKHTMLTOPDF_VERSION}/wkhtmltox-${WKHTMLTOPDF_VERSION}_linux-generic-amd64.tar.xz" \
  && tar xJfv "wkhtmltox-${WKHTMLTOPDF_VERSION}_linux-generic-amd64.tar.xz" && cp -rf /tmp/wkhtmltox/bin/* /srv/bin \
  && cd /srv/bin \
  && curl -fsSL "https://github.com/Medium/phantomjs/releases/download/v${PHANTOMJS_VERSION}/phantomjs-${PHANTOMJS_VERSION}-linux-x86_64.tar.bz2" | tar -xjv \
  && mv phantomjs-${PHANTOMJS_VERSION}-linux-x86_64/bin/phantomjs /srv/bin/phantomjs \
  && rm -rf phantomjs-${PHANTOMJS_VERSION}-linux-x86_64 && rm -f phantomjs-${PHANTOMJS_VERSION}-linux-x86_64.tar.bz2 \
  && chmod +x /srv/bin/phantomjs \
  && curl -fsSL "http://www-us.apache.org/dist/maven/maven-3/${MAVEN_VERSION}/binaries/apache-maven-${MAVEN_VERSION}-bin.tar.gz" | tar -xz -C /tmp \
  && cd /tmp && curl -OL "http://archive.apache.org/dist/tika/apache-tika-1.1-src.zip" \
  && unzip /tmp/apache-tika-1.1-src.zip \
  && rm /tmp/apache-tika-1.1-src.zip \
  && cd /tmp/apache-tika-1.1 && /tmp/apache-maven-${MAVEN_VERSION}/bin/mvn install \
  && cp -rf /tmp/apache-tika-1.1/tika-app/target/tika-app-1.1.jar /srv/bin/tika-app-1.1.jar \
  && apt-get -y remove openjdk-8-jdk \
  && apt-get -y clean \
  && apt-get -y autoclean \
  && apt-get -y autoremove \
  && rm -rf /var/lib/apt/lists/* && rm -rf && rm -rf /var/lib/cache/* && rm -rf /var/lib/log/* && rm -rf /tmp/*

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
