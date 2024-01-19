FROM php:8.3-cli-alpine3.18

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions \
    opcache \
    mysqli

WORKDIR /app

CMD sh -c "tail -f /dev/null"
