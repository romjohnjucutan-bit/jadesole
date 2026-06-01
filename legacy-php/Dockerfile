# Simple Dockerfile to run the PHP app with Apache
FROM php:8.1-apache

# System packages for building and runtime
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    curl \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Build frontend assets (optional - can be built locally instead)
RUN if [ -f package.json ]; then \
    npm install --no-audit --no-fund && npm run build || true; \
    fi

# Ensure built files are in assets/js/react (Vite output)
# (If your build produces `dist`, copy into expected path)
RUN if [ -d dist ]; then mkdir -p assets/js/react && cp -r dist/* assets/js/react/ || true; fi

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
