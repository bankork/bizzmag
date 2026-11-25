# ----------------------------------------------------------------------------
# A cPanel-like host for deployment verification in CI (Stage 4 §10.1).
#
# Apache 2.4 with .htaccess (AllowOverride All), mod_rewrite and mod_headers,
# PHP 8.3 as on a cPanel MultiPHP site, two virtual hosts on subdomains of
# one domain, and a non-root "salon" user with a home directory like a
# cPanel account. The real deploy scripts are run against it unchanged.
# Not used in production.
# ----------------------------------------------------------------------------
FROM php:8.3-apache

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd zip intl opcache \
    && apt-get update && apt-get install -y --no-install-recommends rsync \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers deflate mime \
    && useradd --create-home --shell /bin/bash salon

COPY vhosts.conf /etc/apache2/sites-available/000-default.conf

# Apache runs as the account user, like suEXEC/PHP-FPM on cPanel.
ENV APACHE_RUN_USER=salon APACHE_RUN_GROUP=salon
