#!/bin/bash
set -e


# Active SSL si demandé, de façon idempotente
if [ "$ENABLE_HTTPS" = "true" ]; then
  echo "[entrypoint] Activation du module SSL et de la conf SSL (HTTPS)"
  # Active le module SSL si ce n'est pas déjà fait
  if ! grep -q '^LoadModule ssl_module' /usr/local/apache2/conf/httpd.conf; then
    sed -i '/^#LoadModule ssl_module/s/^#//' /usr/local/apache2/conf/httpd.conf
  fi
  # Ajoute l'include une seule fois
  if ! grep -q 'Include conf/extra/httpd-vhosts-ssl.conf' /usr/local/apache2/conf/httpd.conf; then
    echo "Include conf/extra/httpd-vhosts-ssl.conf" >> /usr/local/apache2/conf/httpd.conf
  fi
  # S'assurer qu'Apache écoute sur le port 443
  if ! grep -q '^Listen 443' /usr/local/apache2/conf/httpd.conf; then
    echo "Listen 443" >> /usr/local/apache2/conf/httpd.conf
  fi
else
  echo "[entrypoint] HTTPS désactivé, serveur en HTTP seulement."
fi

# Lancer Apache en mode foreground
httpd-foreground
