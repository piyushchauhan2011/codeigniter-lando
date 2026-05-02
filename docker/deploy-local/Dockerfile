FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    openssh-server \
    curl \
    ca-certificates \
    sudo \
 && docker-php-ext-install intl mysqli pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

RUN useradd -m -s /bin/bash deploy \
 && mkdir -p /home/deploy/.ssh \
 && chmod 700 /home/deploy/.ssh \
 && chown deploy:deploy /home/deploy/.ssh \
 && printf '%s\n' 'deploy ALL=(ALL) NOPASSWD: /usr/sbin/apachectl' > /etc/sudoers.d/deploy-apache \
 && chmod 440 /etc/sudoers.d/deploy-apache

COPY apache-site.conf /etc/apache2/sites-available/deploy.conf
RUN a2dissite 000-default.conf 2>/dev/null || true \
 && a2ensite deploy.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint-deploy-local.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-deploy-local.sh

EXPOSE 22 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-deploy-local.sh"]
CMD ["apache2-foreground"]
