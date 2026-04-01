FROM php:8.2-apache

RUN apt-get update \
  && apt-get install -y --no-install-recommends gettext-base libpq-dev \
  && docker-php-ext-install pdo_pgsql pgsql \
  && a2enmod rewrite headers expires \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default-template.conf
COPY docker/start-apache.sh /usr/local/bin/start-apache.sh

RUN chmod +x /usr/local/bin/start-apache.sh \
  && mkdir -p /var/www/html/form_submissions /data \
  && chown -R www-data:www-data /var/www/html/form_submissions /data

ENV APACHE_DOCUMENT_ROOT=/var/www/html

EXPOSE 8080

CMD ["/usr/local/bin/start-apache.sh"]
