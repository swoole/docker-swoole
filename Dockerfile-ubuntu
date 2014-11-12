FROM ubuntu:14.04

#disable debian interactive
ENV DEBIAN_FRONTEND noninteractive

#update debian
RUN apt-get update

#insatll php
RUN apt-get install -qy php5-dev php5-fpm libghc-pcre-light-dev

RUN pecl install swoole

#add swoole.ini
RUN echo 'extension=swoole.so' > /etc/php5/mods-available/swoole.ini

RUN php5enmod swoole
