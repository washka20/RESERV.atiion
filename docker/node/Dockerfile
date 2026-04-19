# syntax=docker/dockerfile:1.7
FROM node:22-alpine

# Cross-platform uid/gid remap для bind-mount write compatibility.
# В node-image уже есть user `node` (uid 1000), group `node` (gid 1000) — ремапим на host.
# APP_UID/APP_GID прокидываются из Makefile (auto-detect id -u/-g) через docker-compose.dev.yml.
# Флаг -o допускает существующие uid/gid (на macOS gid 20 = dialout в alpine).
ARG APP_UID=1000
ARG APP_GID=1000

RUN apk add --no-cache bash curl git shadow \
    && groupmod -o -g ${APP_GID} node \
    && usermod  -o -u ${APP_UID} -g ${APP_GID} node \
    && mkdir -p /home/node && chown -R node:node /home/node \
    && apk del shadow

WORKDIR /app

# Без USER node процесс работает под root и пишет .vite/ / .eslintcache / node_modules
# в bind-mount от root — хост потом не может удалить без sudo.
USER node
ENV HOME=/home/node

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
