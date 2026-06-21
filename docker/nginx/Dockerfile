FROM nginx:alpine

# Install fcgi untuk healthcheck yang lebih andal
RUN apk add --no-cache fcgi

# Salin file konfigurasi Nginx
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

# Baris 'COPY public' dihapus karena kita akan menggunakan volume bersama

EXPOSE 80
