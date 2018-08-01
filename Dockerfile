# use PHP 7.1 and Apache as a base.
FROM php:7.1-apache

# set working dir
ENV FIREFLY_PATH /var/www/firefly-iii
ENV CURL_VERSION 7.60.0
ENV OPENSSL_VERSION 1.1.1-pre6
WORKDIR $FIREFLY_PATH
ADD . $FIREFLY_PATH

# install packages
RUN apt-get update -y && \
    apt-get install -y --no-install-recommends libcurl4-openssl-dev \
                                               zlib1g-dev \
                                               libjpeg62-turbo-dev \
                                               wget \
                                               libpng-dev \
                                               libicu-dev \
                                               libedit-dev \
                                               libtidy-dev \
                                               libxml2-dev \
                                               libsqlite3-dev \
                                               libpq-dev \
                                               libbz2-dev \
                                               gettext-base \
                                               cron \
                                               locales && \
                                               apt-get clean && \
                                               rm -rf /var/lib/apt/lists/*

# Setup the Composer installer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install latest curl
RUN cd /tmp && \
    wget https://www.openssl.org/source/openssl-${OPENSSL_VERSION}.tar.gz && \
    tar -xvf openssl-${OPENSSL_VERSION}.tar.gz && \
    cd openssl-${OPENSSL_VERSION} && \
    ./config && \
    make && \
    make install

RUN cd /tmp && \
    wget https://curl.haxx.se/download/curl-${CURL_VERSION}.tar.gz && \
    tar -xvf curl-${CURL_VERSION}.tar.gz && \
    cd curl-${CURL_VERSION} && \
    ./configure --with-ssl && \
    make && \
    make install


# Create the log file to be able to run tail
RUN touch /var/log/cron.log

# Setup cron job
RUN (crontab -l ; echo "* * * * * root $FIREFLY_PATH/artisan schedule:run >> /var/log/cron.log") | crontab

# Install PHP exentions.
RUN docker-php-ext-install -j$(nproc) curl gd intl json readline tidy zip bcmath xml mbstring pdo_sqlite pdo_mysql bz2 pdo_pgsql

# Generate locales supported by Firefly III
RUN echo "en_US.UTF-8 UTF-8\nde_DE.UTF-8 UTF-8\nfr_FR.UTF-8 UTF-8\nit_IT.UTF-8 UTF-8\nnl_NL.UTF-8 UTF-8\npl_PL.UTF-8 UTF-8\npt_BR.UTF-8 UTF-8\nru_RU.UTF-8 UTF-8\ntr_TR.UTF-8 UTF-8\n\n" > /etc/locale.gen && locale-gen

# copy Apache config to correct spot.
COPY ./.deploy/docker/apache2.conf /etc/apache2/apache2.conf

# Enable apache mod rewrite..
RUN a2enmod rewrite

# Enable apache mod ssl..
RUN a2enmod ssl

# Create volumes for several directories:
VOLUME $FIREFLY_PATH/storage/export $FIREFLY_PATH/storage/upload

# Enable default site (Firefly III)
COPY ./.deploy/docker/apache-firefly.conf /etc/apache2/sites-available/000-default.conf

# Make sure we own Firefly III directory
RUN chown -R www-data:www-data /var/www && chmod -R 775 $FIREFLY_PATH/storage

# Run composer
RUN composer install --prefer-dist --no-dev --no-scripts --no-suggest

# Expose port 80
EXPOSE 80

# Run the command on container startup
CMD cron

# Run entrypoint thing
ENTRYPOINT [".deploy/docker/entrypoint.sh"]

