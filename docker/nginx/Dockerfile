FROM nginx:latest

LABEL maintainer="Achim Kraemer <a.kraemer@mso-digital.de>"

ADD nginx.conf /etc/nginx/
ADD symfony.conf /etc/nginx/conf.d/

RUN echo "upstream php-upstream { server php:9999; }" > /etc/nginx/conf.d/upstream.conf

EXPOSE 80
EXPOSE 443
