#!/bin/sh
set -e

# Render nginx config from template, substituting only our custom variables
# (preserving nginx's own $variables like $uri, $host, etc.)
envsubst '${MEDIAMTX_VIEWER_AUTH}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

exec nginx -g 'daemon off;'
