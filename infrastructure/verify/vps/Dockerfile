# ----------------------------------------------------------------------------
# PHP-FPM 8.3 for the VPS-like verification stack (CI only). Nginx runs in
# its own container and shares /srv/bizzmag with this one.
# ----------------------------------------------------------------------------
FROM php:8.3-fpm

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd zip intl opcache \
    && apt-get update && apt-get install -y --no-install-recommends rsync \
    && rm -rf /var/lib/apt/lists/*
