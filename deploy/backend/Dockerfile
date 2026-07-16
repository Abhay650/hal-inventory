FROM php:8.2-cli

# mysqli extension for MySQL access
RUN docker-php-ext-install mysqli

WORKDIR /app
COPY . /app

# Render sets $PORT at runtime and expects the service to bind to it
ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
