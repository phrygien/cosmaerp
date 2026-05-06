1/ Installation Rdkafka lib


2/ Lien Apache Rdkafka
    ln -s /etc/php/8.3/cli/conf.d/20-rdkafka.ini /etc/php/8.3/apache2/conf.d/20-rdkafka.ini
    systemctl restart apache2

    ls /etc/php/8.3/apache2/conf.d/ | grep rdkafka

3/ Cas avec Nginx
    ln -s /etc/php/8.3/cli/conf.d/20-rdkafka.ini /etc/php/8.3/fpm/conf.d/20-rdkafka.ini
    systemctl restart php8.3-fpm
