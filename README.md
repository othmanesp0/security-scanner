# Security Scanner Dashboard

An educational web-based security scanning tool dashboard built with PHP, MySQL, and JavaScript.

## ⚠️ IMPORTANT DISCLAIMER
** ADD NVIDIA API KEY IN ai_bridge.py **

**THIS TOOL IS FOR EDUCATIONAL PURPOSES ONLY**

- Only use on systems you own or have explicit written permission to test
- Unauthorized scanning of systems is illegal and unethical
- This tool is designed for learning cybersecurity concepts in controlled environments
- Users are responsible for complying with all applicable laws and regulations

## Features

### User Management
- Role-based authentication (User/Admin)
- Secure password hashing
- Session management

### Security Scanning Tools
- **Port Scanning** - Nmap integration for network discovery
- **Subdomain Discovery** - ffuf-based subdomain enumeration
- **Directory Brute Force** - dirsearch for web directory discovery
- **SSL/TLS Analysis** - sslyze for certificate and protocol analysis
- **Web Vulnerability Scanning** - Nikto for web server assessment
- **DNS Information** - dig for DNS record analysis
- **Technology Detection** - webanalyze for web technology identification
- **Web Crawling** - wget-based URL discovery

### Dashboard Features
- Real-time scan monitoring
- Scan history and results management
- Admin oversight and user management
- System statistics and reporting

### Security Features
- Input sanitization and validation
- Command injection prevention
- Role-based access control
- Secure file handling
- Session security

## System Requirements

### Software Dependencies
- PHP 7.4+ with PDO MySQL extension
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- Linux operating system (recommended: Kali Linux)

### Security Tools (Optional - for full functionality)
- nmap
- ffuf
- dirsearch
- sslyze
- nikto
- dig
- webanalyze
- wget

## Installation Guide

### Step 1: System Preparation

\`\`\`bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install web server and PHP
sudo apt install apache2 php php-mysql mysql-server -y

# Install security tools (optional)
sudo apt install nmap nikto dnsutils wget -y

# Install additional tools
pip3 install dirsearch
go install github.com/ffuf/ffuf@latest
pip3 install sslyze
\`\`\`

### Step 2: Database Setup

\`\`\`bash
# Start MySQL service
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
\`\`\`

\`\`\`sql
CREATE DATABASE security_scanner;
CREATE USER 'scanner_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON security_scanner.* TO 'scanner_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
\`\`\`

### Step 3: Web Server Configuration

\`\`\`bash
# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod php7.4  # or your PHP version

# Create virtual host
sudo nano /etc/apache2/sites-available/security-scanner.conf
\`\`\`

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName security-scanner.local
    DocumentRoot /var/www/security-scanner
    
    <Directory /var/www/security-scanner>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/security-scanner_error.log
    CustomLog ${APACHE_LOG_DIR}/security-scanner_access.log combined
</VirtualHost>
