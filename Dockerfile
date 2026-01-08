# Security Scanner Dashboard - Docker Configuration
# Educational Use Only

FROM ubuntu:20.04

# Prevent interactive prompts during installation
ENV DEBIAN_FRONTEND=noninteractive

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    php-mysql \
    php-curl \
    php-json \
    php-mbstring \
    mysql-server \
    nmap \
    nikto \
    dnsutils \
    wget \
    python3 \
    python3-pip \
    golang-go \
    curl \
    sudo \
    && rm -rf /var/lib/apt/lists/*

# Install Python security tools
RUN pip3 install dirsearch sslyze

# Install Go security tools
ENV GOPATH=/root/go
ENV PATH=$PATH:$GOPATH/bin
RUN go install github.com/ffuf/ffuf@latest

# Configure Apache
RUN a2enmod rewrite headers
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN mkdir -p /var/www/html/{results,logs,wordlists}
RUN chmod -R 777 /var/www/html/{results,logs,wordlists}

# Configure sudo for security tools
RUN echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/nmap, /usr/bin/nikto, /usr/local/bin/ffuf, /usr/local/bin/dirsearch, /usr/bin/sslyze" > /etc/sudoers.d/security-scanner

# Create database initialization script
RUN echo '#!/bin/bash\n\
service mysql start\n\
mysql -e "CREATE DATABASE IF NOT EXISTS security_scanner;"\n\
mysql -e "CREATE USER IF NOT EXISTS '\''scanner_user'\''@'\''localhost'\'' IDENTIFIED BY '\''scanner_pass'\'';"\n\
mysql -e "GRANT ALL PRIVILEGES ON security_scanner.* TO '\''scanner_user'\''@'\''localhost'\'';"\n\
mysql -e "FLUSH PRIVILEGES;"\n\
mysql security_scanner < /var/www/html/database/schema.sql\n\
service apache2 start\n\
tail -f /var/log/apache2/access.log' > /start.sh && chmod +x /start.sh

# Update database configuration for Docker
RUN sed -i "s/private \$password = '';/private \$password = 'scanner_pass';/" /var/www/html/config/database.php

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start services
CMD ["/start.sh"]
