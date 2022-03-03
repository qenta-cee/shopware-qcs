FROM php:7.4-apache

ENV PHP_VERSION 7.4

RUN apt-get update && apt-get install -y \
        git \
        wget \
        unzip \
        mariadb-client \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libicu-dev \
        zlib1g-dev \
        jpegoptim \
        pngcrush \
        guetzli \ 
        optipng \
        libzip-dev \
        zip \
        libjpeg-progs \
    && docker-php-ext-install -j$(nproc) iconv pdo pdo_mysql bcmath intl \
    && docker-php-ext-install zip \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/  \
    && docker-php-ext-install -j$(nproc) gd


# install png out
RUN wget http://static.jonof.id.au/dl/kenutils/pngout-20150319-linux.tar.gz &&\
        tar -xf pngout-20150319-linux.tar.gz &&\
        rm pngout-20150319-linux.tar.gz &&\
        cp pngout-20150319-linux/x86_64/pngout /bin/pngout &&\
        rm -rf pngout-20150319-linux


RUN pecl install apcu

RUN a2enmod rewrite

# useful for local development with mounted sources
RUN sed -i 's/www-data:x:33:33/www-data:x:1000:1000/g' /etc/passwd

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY .htaccess /tmp/.htaccess
RUN rm -rf /var/www/html && ln -s /shopware /var/www/html && \
    mkdir -p /swvolumes

# Install Ioncube loader
RUN mkdir /ioncube && \
    cd /ioncube && \
    wget "http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz" && \
    tar xvf ioncube_loaders_lin_x86-64.tar.gz --strip-components=1 && \
    cd `php -i | grep extension_dir | cut -d' ' -f 5` && \
    cp /ioncube/ioncube_loader_lin_7.4.so . && \
    rm -rf /ioncube/

# some default values of php variables
ENV PHP_UPLOAD_MAX_FILESIZE "64M"
ENV PHP_POST_MAX_FILESIZE "64M"
ENV PHP_MEMORY_LIMIT "512M"
ENV PHP_MAX_EXECUTION_TIME "60"

# opcache variables
ENV PHP_OPCACHE_ENABLE 1
ENV PHP_OPCACHE_MEMORY_CONSUMPTION 128
ENV PHP_OPCACHE_INTERNED_STRINGS_BUFFER 8
ENV PHP_OPCACHE_ACCELERATED_FILES 10000
ENV PHP_OPCACHE_REVALIDATE_FREQ 5
ENV PHP_OPCACHE_FAST_SHUTDOWN 1
ENV PHP_SESSION_COOKIE_SECURE On
ENV PHP_OPCACHE_VALIDATE_ROOT 1

# other variables
ENV PHP_APCU_ENABLE 1

ENV SWPHP_IONCUBE_ENABLE 0

# use proper shell
SHELL ["/bin/bash", "-c"]

COPY v5-7-7/init.sh /usr/local/bin/init.sh
# copy ngrok script
COPY ngrok.sh /usr/local/bin/ngrok.sh

# make scripts executable
RUN chmod +x /usr/local/bin/*.sh

# override default entrypoint with ours
ENTRYPOINT [ "init.sh" ]

# Enable stderr error logging for PHP
RUN echo 'log_errors = On' > /usr/local/etc/php/conf.d/log_errors.ini
RUN echo 'error_log = /dev/stderr' >> /usr/local/etc/php/conf.d/log_errors.ini

# to avoid all too common aborts because of debian repo timeouts
RUN echo 'APT::Acquire::Retries "30";' > /etc/apt/apt.conf.d/80-retries

# upgrade package list and default packages
RUN apt-get -qq update
RUN apt-get -qq upgrade

# install npm nodesource repo
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash -

# install php extension dependencies
RUN apt-get -qq install libmemcached-dev zlib1g-dev libpng-dev libonig-dev libtidy-dev

# install dependencies for WP-CLI and tools
RUN apt-get -qq install git unzip vim bat mariadb-client zip jq nodejs

# clean up to reduce docker image size
RUN apt-get -qq autoremove

# enable apache modules
RUN a2enmod rewrite headers ext_filter expires

# create self-signed cert and enable SSL on apache
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/ssl-cert-snakeoil.key -out /etc/ssl/certs/ssl-cert-snakeoil.pem -subj "/C=AT/ST=Vienna/L=Vienna/O=Security/OU=Development/CN=example.com"
RUN a2ensite default-ssl
RUN a2enmod ssl

# get composer binary from composer docker image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# add user and dir for executing composer
# RUN useradd -u 431 -r -g www-data -s /sbin/nologin -c "Shopware user" sw
# USER sw

# install ngrok
COPY --from=ngrok/ngrok:2-debian /bin/ngrok /usr/bin/ngrok

WORKDIR /var/www/html

# # shopware 5.5.7 setup
# RUN git clone --depth 1 --branch v5.5.7 https://github.com/shopware/shopware.git .
# COPY v5-5-7/.psh.yaml /var/www/html/.psh.yaml

# # shopware 5.6.9 setup
# RUN git clone --depth 1 --branch v5.6.9 https://github.com/shopware/shopware.git .
# COPY v5-6-9/.psh.yaml /var/www/html/.psh.yaml

# shopware 5.7.7 setup
RUN git clone https://github.com/shopware/shopware.git .

RUN chmod -R 755 custom/plugins 
RUN chmod -R 755 engine/Shopware/Plugins/Community 
RUN chmod -R 755 files 
RUN chmod -R 755 media 
RUN chmod -R 755 var 
RUN chmod -R 755 web

RUN wget -O test_images.zip http://releases.s3.shopware.com/test_images_since_5.1.zip

EXPOSE 80
EXPOSE 443