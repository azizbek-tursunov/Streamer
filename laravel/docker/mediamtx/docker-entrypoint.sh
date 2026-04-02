#!/bin/sh
set -e

# Render mediamtx.yml from template using sed (envsubst not available in this image)
sed \
    -e "s|\${MEDIAMTX_ADMIN_USER}|${MEDIAMTX_ADMIN_USER}|g" \
    -e "s|\${MEDIAMTX_ADMIN_PASSWORD}|${MEDIAMTX_ADMIN_PASSWORD}|g" \
    -e "s|\${MEDIAMTX_VIEWER_USER}|${MEDIAMTX_VIEWER_USER}|g" \
    -e "s|\${MEDIAMTX_VIEWER_PASSWORD}|${MEDIAMTX_VIEWER_PASSWORD}|g" \
    -e "s|\${MEDIAMTX_PUBLIC_IP}|${MEDIAMTX_PUBLIC_IP}|g" \
    -e "s|\${MEDIAMTX_INTERNAL_IP}|${MEDIAMTX_INTERNAL_IP}|g" \
    /mediamtx.yml.template > /mediamtx.yml

# Inject TURN server into webrtcICEServers2 (needed when WebRTC UDP port is blocked by NAT/firewall)
if [ -n "${TURN_URL}" ]; then
    sed -i "s|# TURN_SERVER_PLACEHOLDER|  - url: \"${TURN_URL}\"\n    username: \"${TURN_USERNAME}\"\n    password: \"${TURN_PASSWORD}\"|" /mediamtx.yml
else
    sed -i "/# TURN_SERVER_PLACEHOLDER/d" /mediamtx.yml
fi

exec /mediamtx
