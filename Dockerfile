FROM php:8.2-cli

# Instalar extensiones de PHP necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar archivos de la app
WORKDIR /app
COPY . /app

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader 2>/dev/null || true

# Crear directorio de logs
RUN mkdir -p /app/logs && chmod 777 /app/logs

# Puerto
EXPOSE 8080

# Comando de inicio
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
