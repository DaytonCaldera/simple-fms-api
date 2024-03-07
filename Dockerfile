FROM  php:8.2-apache as php_base

# Enable header directives
RUN a2enmod rewrite

RUN apt update
# RUN apt install wget -y
# RUN wget https://dev.mysql.com/get/mysql-apt-config_0.8.22-1_all.deb
# RUN apt install ./mysql-apt-config_0.8.22-1_all.deb
# RUN apt update
RUN apt-get update
# RUN apt-get install -y gnupg2
# RUN curl -sL https://deb.nodesource.com/setup_8.x -o nodesource_setup.sh
# RUN bash nodesource_setup.sh
RUN apt-get install nodejs -y
RUN apt-get install npm -y
RUN apt install git -y
RUN apt install unzip -y
# RUN apt install mariadb-server -y
RUN apt install libmcrypt-dev -y
RUN docker-php-source extract
RUN docker-php-ext-install pdo pdo_mysql mysqli
# RUN docker-php-ext-enable xdebug
RUN docker-php-ext-enable mysqli
RUN docker-php-ext-enable pdo_mysql

# Copy from composer image, the composer bin
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME="/opt/composer"
ENV PATH="/opt/composer/vendor/bin:${PATH}"
RUN mkdir /opt/composer && chown www-data:www-data /opt/composer && chmod a+rwX /opt/composer


# Copy proyect files
COPY . /var/www/html/

# Ensure permissions are correct
RUN chown -R www-data:www-data /var/www

USER www-data

# USER www-data
# RUN apt update

RUN composer install
RUN php artisan optimize

RUN CI=true

USER root

EXPOSE 8080
EXPOSE 5173