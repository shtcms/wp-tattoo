# Base image
FROM wordpress:latest

# Install additional dependencies
#RUN apt-get update && apt-get install -y \
    # Add any required dependencies here
    # Example: php7.4-gd php7.4-mysql

# Set working directory
WORKDIR /var/www/html/

# Copy application files
COPY . /var/www/html/

# Import MySQL database
COPY database-dump.sql /docker-entrypoint-initdb.d/

# Remove unnecessary packages and files
#RUN apt-get remove -y <package_name> && apt-get autoremove -y

# Update system packages
RUN apt-get update && apt-get upgrade -y

# Create a new user
RUN useradd -ms /bin/bash myuser

# Switch to the new user
USER myuser

# Expose necessary ports
EXPOSE 80

# Set entry point
CMD ["apache2-foreground"]