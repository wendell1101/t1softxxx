# FROM php:5.6-fpm
FROM dockerhub.techdevteam.com/tripleonetech/ogbasephp:20230825-final

ENV CODE tripleonetech
ENV DOCKER_RUN php-fpm
ENV OG_USER vagrant
ENV OG_UID 1010

# RUN find /etc/apt/ -type f -exec sed -i 's/deb.debian.org/ftp.tw.debian.org/g' {} \;
RUN cp /usr/share/zoneinfo/Asia/Hong_Kong /etc/localtime

# set language environment
# RUN (apt-get update && apt-get install -y locales) || (find /etc/apt/ -type f -exec sed -i 's/deb.debian.org/ftp.tw.debian.org/g' {} \; && apt-get update && apt-get install -y locales)
# use tw mirror
# RUN (find /etc/apt/ -type f -exec sed -i 's/deb.debian.org/ftp.tw.debian.org/g' {} \; && apt-get update && apt-get install -y locales)
# ENV LANG en_US.UTF-8
# ENV LANGUAGE en_US.UTF-8
# RUN locale-gen en_US.UTF-8
# ADD docker/php.ini /usr/local/etc/php/php.ini
# RUN apt-get update && apt-get install -y \
#         libfreetype6-dev \
#         libjpeg62-turbo-dev \
#         libmcrypt-dev \
#         libpng12-dev \
#         libcurl4-openssl-dev \
#         libxml2-dev \
#         libxslt1-dev \
#         libzip-dev \
#         libgearman-dev \
#         libmemcached-dev \
#         libv8-dev \
#     && docker-php-ext-install -j$(nproc) mcrypt \
#     && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
#     && docker-php-ext-install -j$(nproc) gd \
#     && docker-php-ext-install -j$(nproc) mysql mysqli curl xmlrpc xsl soap zip xml mbstring opcache \
#     && chmod a+w /var/log \
#     && pecl install gearman && docker-php-ext-enable gearman \
#     && pecl install memcache && docker-php-ext-enable memcache \
#     && pecl install memcached-2.2.0 && docker-php-ext-enable memcached \
#     && pecl install apcu-4.0.11 && docker-php-ext-enable apcu \
#     && pecl install redis && docker-php-ext-enable redis \
#     && pecl install v8js-0.1.3 && docker-php-ext-enable v8js \
#     && pecl install swoole && docker-php-ext-enable swoole

# Create vagrant User
# RUN apt-get update && \
#     apt-get install -y \
#     sudo \
#     gearman-job-server \
#     gearman-tools \
#     cron \
#     logrotate \
#     git \
#     wget \
#     supervisor && \
#     useradd -ms /bin/bash $OG_USER -u $OG_UID && \
#     echo "vagrant ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers.d/vagrant && \
#     mkdir -p /home/vagrant/Code && \
#     chown -R vagrant.vagrant /home/vagrant

ADD docker/php.ini /usr/local/etc/php/php.ini

RUN useradd -ms /bin/bash $OG_USER -u $OG_UID && \
    echo "vagrant ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers.d/vagrant && \
    mkdir -p /home/vagrant/Code && \
    chown -R vagrant.vagrant /home/vagrant

##### openresty

# install openrestry
# ADD docker/libmaxminddb /tmp/libmaxminddb
# ADD docker/ngx_http_geoip2_module /usr/src/ngx_http_geoip2_module
# RUN wget https://openresty.org/download/openresty-1.11.2.3.tar.gz -O /tmp/openresty-1.11.2.3.tar.gz
# RUN apt-get update && apt-get install -y \
#     make \
#     gcc \
#     libssl-dev \
#     libperl4-corelibs-perl \
#     libpcre3-dev \
#     dh-autoreconf \
#     wget \
#     libgeoip-dev
# RUN cd /tmp/libmaxminddb && chmod +x bootstrap t/compile_c++_t.pl t/mmdblookup_t.pl && ./bootstrap && ./configure && make check && make install && ldconfig
# RUN cd /tmp && \
#     tar xvf openresty-1.11.2.3.tar.gz && \
#     cd openresty-1.11.2.3 && \
#     ./configure --with-luajit --with-http_geoip_module --add-module=/usr/src/ngx_http_geoip2_module --with-http_stub_status_module --with-http_v2_module --with-http_realip_module --with-ipv6 && \
#     make -j$(nproc) && \
#     make install
ADD docker/nginx/ /usr/local/openresty/nginx/
# RUN mkdir -p /var/log/nginx/ && \
#     mkdir -p /cache/nginx/www /cache/nginx/m
# RUN ln -sf /dev/stdout /var/log/nginx/access.log && ln -sf /dev/stderr /var/log/nginx/error.log
WORKDIR /usr/local/openresty/nginx

ENV PATH="/usr/local/openresty/nginx/sbin:${PATH}"
#####
ADD docker/supervisor/ /root/
ADD docker/config/ /usr/local/etc/php-fpm.d/
# RUN ln -s /usr/local/bin/php /usr/bin/php
ADD --chown=vagrant:vagrant . /home/vagrant/Code/og
RUN rm -f /home/vagrant/Code/og/admin/application/config/config_local.php && \
    rm -f /home/vagrant/Code/og/aff/application/config/config_local.php && \
    rm -f /home/vagrant/Code/og/agency/application/config/config_local.php && \
    rm -f /home/vagrant/Code/og/player/application/config/config_local.php && \
    ln -sf /home/vagrant/Code/config_local/admin-config-local.php /home/vagrant/Code/og/admin/application/config/config_local.php && \
    ln -sf /home/vagrant/Code/config_local/aff-config-local.php /home/vagrant/Code/og/aff/application/config/config_local.php && \
    ln -sf /home/vagrant/Code/config_local/agency-config-local.php /home/vagrant/Code/og/agency/application/config/config_local.php && \
    ln -sf /home/vagrant/Code/config_local/player-config-local.php /home/vagrant/Code/og/player/application/config/config_local.php && \
    mkdir -p /home/vagrant/Code/og/admin/application/logs && \
    # chown vagrant:vagrant -R /home/vagrant/Code/og/admin/application && \
    mkdir -p /var/game_platform && \
    chmod 777 /var/game_platform
    # ln -s /dev/null /home/vagrant/Code/og/admin/application/logs/raw_debug.log && \
    # ln -s /dev/null /home/vagrant/Code/og/admin/application/logs/raw_error.log && \
    # ln -s /dev/null /home/vagrant/Code/og/admin/application/logs/log-json.log

# RUN chown vagrant.vagrant /home/vagrant/Code -R
EXPOSE 80 443
ADD docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["og"]
