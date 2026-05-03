FROM php:8.4.2-fpm-alpine3.21

ARG COMPOSER_VERSION=2.9.7
ARG XDEBUG_VERSION=3.5.1
ARG NODE_VERSION=25.9.0

ARG INSTALL_XDEBUG=false

ARG UID=1000
ARG GID=1000
ARG TZ=America/Sao_Paulo

ENV COMPOSER_VERSION=${COMPOSER_VERSION} \
  XDEBUG_VERSION=${XDEBUG_VERSION} \
  NODE_VERSION=${NODE_VERSION} \
  INSTALL_XDEBUG=${INSTALL_XDEBUG} \
  TZ=${TZ}

WORKDIR /var/www/html

RUN apk add --no-cache \
  bash \
  git \
  unzip \
  curl \
  tzdata \
  icu-libs \
  libzip \
  postgresql-libs \
  oniguruma

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    linux-headers \
  && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    zip \
    intl \
    mbstring \
    bcmath \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && if [ "$INSTALL_XDEBUG" = "true" ]; then \
    pecl install xdebug-${XDEBUG_VERSION} \
    && docker-php-ext-enable xdebug; \
  else \
    echo "Xdebug installation skipped"; \
  fi \
  && apk del .build-deps

RUN echo "date.timezone=${TZ}" > /usr/local/etc/php/conf.d/timezone.ini

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php \
  && php /tmp/composer-setup.php \
    --version=${COMPOSER_VERSION} \
    --install-dir=/usr/local/bin \
    --filename=composer \
  && rm -f /tmp/composer-setup.php \
  && composer --version

RUN curl -fsSL https://unofficial-builds.nodejs.org/download/release/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-x64-musl.tar.xz \
    | tar -xJ -C /usr/local --strip-components=1 --no-same-owner \
 && ln -s /usr/local/bin/node /usr/bin/node \
 && ln -s /usr/local/bin/npm /usr/bin/npm \
 && ln -s /usr/local/bin/npx /usr/bin/npx

RUN addgroup -g ${GID} appgroup \
  && adduser -D -u ${UID} -G appgroup appuser

RUN mkdir -p /var/www/html \
  && chown -R appuser:appgroup /var/www/html

COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]

CMD ["php-fpm"]