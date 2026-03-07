#!/bin/sh
set -e

# Render mediamtx.yml from template using sed (envsubst not available in this image)
sed \
    -e "s|\${MEDIAMTX_ADMIN_USER}|${MEDIAMTX_ADMIN_USER}|g" \
    -e "s|\${MEDIAMTX_ADMIN_PASSWORD}|${MEDIAMTX_ADMIN_PASSWORD}|g" \
    -e "s|\${MEDIAMTX_VIEWER_USER}|${MEDIAMTX_VIEWER_USER}|g" \
    -e "s|\${MEDIAMTX_VIEWER_PASSWORD}|${MEDIAMTX_VIEWER_PASSWORD}|g" \
    /mediamtx.yml.template > /mediamtx.yml

exec /mediamtx
