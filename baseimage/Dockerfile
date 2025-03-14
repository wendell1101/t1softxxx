FROM php:5.6.40-fpm-stretch

ENV CODE tripleonetech
ENV DOCKER_RUN php-fpm
ENV OG_USER vagrant
ENV OG_UID 1010

RUN cp /usr/share/zoneinfo/Asia/Hong_Kong /etc/localtime

RUN sed -i '/DST_Root_CA_X3.crt/d' /etc/ca-certificates.conf
RUN echo '!mozilla\/DST_Root_CA_X3.crt' >> /etc/ca-certificates.conf
RUN update-ca-certificates

# set language environment
# RUN (apt-get update && apt-get install -y locales) || (find /etc/apt/ -type f -exec sed -i 's/deb.debian.org/ftp.tw.debian.org/g' {} \; && apt-get update && apt-get install -y locales)
# use tw mirror and remove old repo
# RUN (find /etc/apt/ -type f -exec sed -i '/jessie-updates/d' {} \; && find /etc/apt/ -type f -exec sed -i 's/deb.debian.org/ftp.tw.debian.org/g' {} \; && apt-get update && apt-get install -y locales)
# ENV LANG en_US.UTF-8
# ENV LANGUAGE en_US.UTF-8
# RUN locale-gen en_US.UTF-8
# ADD docker/php.ini /usr/local/etc/php/php.ini
ADD sources.list /etc/apt/sources.list
RUN apt-get update && apt-get upgrade -y  --force-yes && apt-get install -y --force-yes \
        locales \
        apt-utils \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
        libxslt1-dev \
        libzip-dev \
        # libgearman-dev \
        libmemcached-dev \
        libv8-dev \
        make \
        gcc \
        libssl-dev \
        libperl4-corelibs-perl \
        libpcre3-dev \
        dh-autoreconf \
        libgeoip-dev \
        net-tools \
        vim \
        sudo \
        # gearman-job-server \
        # gearman-tools \
        cron \
        # logrotate \
        # git \
        ntp \
        wget \
        openssh-client \
        supervisor \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/*

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US.UTF-8
RUN locale-gen en_US.UTF-8

#install php mod
RUN docker-php-ext-install -j$(nproc) mcrypt \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd mysql mysqli curl xmlrpc xsl soap zip xml mbstring opcache bcmath \
    && rm -rf /tmp/*

#install from pecl
RUN chmod a+w /var/log \
    # && pecl install gearman && docker-php-ext-enable gearman \
    && pecl install memcache-3.0.8 && docker-php-ext-enable memcache \
    && pecl install memcached-2.2.0 && docker-php-ext-enable memcached \
    # && pecl install apcu-4.0.11 && docker-php-ext-enable apcu \
    && pecl install redis-4.3.0 && docker-php-ext-enable redis \
    && pecl install timezonedb && docker-php-ext-enable timezonedb \
    # && pecl install v8js-0.1.3 && docker-php-ext-enable v8js \
    # && pecl install swoole && docker-php-ext-enable swoole
    && rm -rf /tmp/*

# Create vagrant User
# RUN apt-get update && \
#  apt-get install -y \
#     sudo \
#     gearman-job-server \
#     gearman-tools \
#     cron \
#     logrotate \
#     git \
#     wget \
#     supervisor
    # useradd -ms /bin/bash $OG_USER -u $OG_UID && \
    # echo "vagrant ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers.d/vagrant && \
    # mkdir -p /home/vagrant/Code && \
    # chown -R vagrant.vagrant /home/vagrant
##### openresty

# RUN awk -v cmd='openssl x509 -noout -subject' '/BEGIN/{close(cmd)};{print | cmd}' < /etc/ssl/certs/ca-certificates.crt | grep DST

# install openrestry
ADD libmaxminddb /tmp/libmaxminddb
ADD ngx_http_geoip2_module /usr/src/ngx_http_geoip2_module
RUN wget https://www.gamegateway.t1t.in/download/openresty-1.17.8.2.tar.gz -O /tmp/openresty-1.17.8.2.tar.gz && \
    cd /tmp/libmaxminddb && chmod +x bootstrap t/compile_c++_t.pl t/mmdblookup_t.pl && ./bootstrap && ./configure && make check && make install && ldconfig && \
    cd /tmp && \
    tar xvf openresty-1.17.8.2.tar.gz && \
    cd openresty-1.17.8.2 && \
    ./configure --with-luajit --with-http_geoip_module --add-module=/usr/src/ngx_http_geoip2_module --with-http_stub_status_module --with-http_v2_module --with-http_realip_module --with-ipv6 && \
    make -j$(nproc) && \
    make install && \
    rm -rf /tmp/*
# ADD docker/nginx/ /usr/local/openresty/nginx/
RUN mkdir -p /var/log/nginx/ && \
    mkdir -p /cache/nginx/www /cache/nginx/m
RUN ln -sf /dev/stdout /var/log/nginx/access.log && ln -sf /dev/stderr /var/log/nginx/error.log
WORKDIR /usr/local/openresty/nginx

ENV PATH="/usr/local/openresty/nginx/sbin:${PATH}"
#####
RUN ln -s /usr/local/bin/php /usr/bin/php

CMD ["php-fpm"]
