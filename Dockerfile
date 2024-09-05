FROM php:8.3-apache

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y cron

COPY . /var/www/html

COPY cronfile /etc/cron.d/cron-tasks

RUN chmod 0644 /etc/cron.d/cron-tasks

RUN chmod -R 755 /var/www/html

CMD ["sh", "-c", "service cron start && apache2-foreground"]
