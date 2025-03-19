#!/bin/bash
# Mata o servidor PHP se já estiver em execução
pkill php || true

# Inicia o servidor PHP
php -S 0.0.0.0:8080 -t /var/www/html/public &

# Monitora alterações no diretório /var/www/html
while inotifywait -r -e modify,create,delete /var/www/html; do
    echo "Alteração detectada. Reiniciando o servidor PHP..."
    pkill php
    php -S 0.0.0.0:8080 -t /var/www/html/public &
done



