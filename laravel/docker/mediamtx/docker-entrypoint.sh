#!/bin/sh
set -e

# Render mediamtx.yml from template
envsubst '${MEDIAMTX_ADMIN_USER} ${MEDIAMTX_ADMIN_PASSWORD} ${MEDIAMTX_VIEWER_USER} ${MEDIAMTX_VIEWER_PASSWORD}' \
    < /mediamtx.yml.template > /mediamtx.yml

exec /mediamtx
