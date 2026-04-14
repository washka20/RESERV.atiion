# syntax=docker/dockerfile:1.7
FROM node:22-alpine

RUN apk add --no-cache bash curl git \
    && addgroup -g 1000 app 2>/dev/null || true \
    && adduser -u 1000 -G node -s /bin/sh -D -H app 2>/dev/null || true

WORKDIR /app

# В dev код и package.json монтируются через bind mount,
# node_modules — анонимный volume (см. compose.dev.yml)
EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
