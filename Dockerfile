FROM php:8.4.2-fpm-alpine3.21

ARG COMPOSER_VERSION=2.9.7
ARG XDEBUG_VERSION=3.5.1
ARG NODE_VERSION=25.9.0

ARG INSTALL_XDEBUG=false
ARG UID=1000
ARG GID=1000

ARG TZ='America/Sao_Paulo'

ENV COMPOSER_VERSION=${COMPOSER_VERSION} \
  XDEBUG_VERSION=${XDEBUG_VERSION} \
  NODE_VERSION=${NODE_VERSION} \
  INSTALL_XDEBUG=${INSTALL_XDEBUG} \
  TZ=${TZ}

RUN apk add --no-cache \
  bash \
  git \
  unzip \
  curl \
  tzdata \
  icu-dev \
  libzip-dev \
  postgresql-dev \
  oniguruma-dev \
  linux-headers \
  $PHPIZE_DEPS

RUN docker-php-ext-install \
  pdo \
  pdo_pgsql \
  opcache \
  zip \
  intl \
  mbstring \
  bcmath

RUN pecl install redis \
  && docker-php-ext-enable redis

RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
    pecl install xdebug-${XDEBUG_VERSION} \
    && docker-php-ext-enable xdebug; \
  else \
    echo "Xdebug installation skipped"; \
  fi