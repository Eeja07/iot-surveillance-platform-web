    #!/bin/sh

    # Jalankan PHP-FPM di background
    php-fpm &

    # Jalankan Nginx di foreground
    nginx -g "daemon off;"
    