FROM php:8.3-apache

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y cron dos2unix

WORKDIR /var/www/html

COPY . /var/www/html

RUN dos2unix /var/www/html/cronfile

RUN chown -R root:root /var/www/html
RUN chmod -R 777 /var/www/html

COPY cronfile /etc/cron.d/cron-tasks

RUN chmod 0644 /etc/cron.d/cron-tasks

ENV TZ=Europe/Kiev

RUN apt-get install -y tzdata && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "date.timezone = Europe/Kiev" > /usr/local/etc/php/conf.d/timezone.ini

RUN dos2unix /etc/cron.d/cron-tasks
RUN crontab /etc/cron.d/cron-tasks

CMD ["cron", "-f"]
