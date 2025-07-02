# Use an official PHP-FPM image as a base
FROM php:8.2-fpm-alpine

# Install system dependencies: Nginx and Git
RUN apk --no-cache add nginx git

# Install the PHP cURL extension
RUN docker-php-ext-install curl

# Set the working directory
WORKDIR /var/www/html

# Copy your application code and the Nginx config
COPY . .
COPY nginx.conf /etc/nginx/nginx.conf

# Grant permissions for the web server to write the cookie.txt file
RUN chown -R www-data:www-data .

# Expose the port Nginx will run on
EXPOSE 8080

# Command to start PHP-FPM and Nginx when the container launches
CMD sh -c "php-fpm & nginx -g 'daemon off;'"
