FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libssl-dev \
    gettext \
    locales \
    && docker-php-ext-install pdo pdo_mysql gettext \
    && docker-php-ext-enable pdo_mysql gettext \
    && rm -rf /var/lib/apt/lists/*

# Install PEAR::Mail (optional, used if USE_PEAR_MAIL=true)
RUN pear install Mail Mail_Mime Net_SMTP || true

# Enable Apache modules
RUN a2enmod rewrite remoteip

# Configure locales
RUN sed -i 's/# de_DE.UTF-8/de_DE.UTF-8/' /etc/locale.gen && \
    sed -i 's/# en_US.UTF-8/en_US.UTF-8/' /etc/locale.gen && \
    locale-gen

COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY remoteip.conf /etc/apache2/conf-available/remoteip.conf
RUN a2enconf remoteip

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]