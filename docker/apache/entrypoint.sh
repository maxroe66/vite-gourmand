#!/bin/bash
set -e

# Active SSL si demandé
if [ "$ENABLE_HTTPS" = "true" ]; then
  echo "[entrypoint] Activation du module SSL et de la conf SSL (HTTPS)"
  sed -i '/^#LoadModule ssl_module/s/^#//' /usr/local/apache2/conf/httpd.conf
  echo "Include conf/extra/httpd-vhosts-ssl.conf" >> /usr/local/apache2/conf/httpd.conf
else
  echo "[entrypoint] HTTPS désactivé, serveur en HTTP seulement."
fi

# Lancer Apache en mode foreground
httpd-foreground
