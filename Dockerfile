FROM php:8.2-cli

# Install needed PHP extensions (like mysqli)
RUN docker-php-ext-install mysqli

# Copy app code
COPY . /app
WORKDIR /app

# Expose port
EXPOSE 10000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
