FROM tutum/apache-php
MAINTAINER Christian Hotz-Behofsits <chris.hotz.behofsits@gmail.com>

RUN apt-get update && \
    apt-get -y install php5-mcrypt php5-sqlite git &&\
    rm -rf /var/lib/apt/lists/* &&\
    a2enmod rewrite &&\
    php5enmod mcrypt

RUN rm -rf /app
ADD . /app

# set webdir
RUN rm -rf /etc/apache2/sites-available/* /etc/apache2/sites-enabled/*
ADD config/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN ln /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf


RUN mkdir -p src/domain && composer install &&\
    vendor/bin/propel config:convert --output-dir=config --config-dir=config &&\
    vendor/bin/propel model:build --output-dir=src/domain --config-dir=config &&\
    vendor/bin/propel sql:build --output-dir=src/sql --config-dir=config &&\
    vendor/bin/propel sql:insert --sql-dir=src/sql --config-dir=config &&\
    composer dump-autoload