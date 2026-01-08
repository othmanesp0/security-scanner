#!/bin/bash

# Security Scanner Dashboard Installation Script
# Educational Use Only - Authorized Testing Only

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="security-scanner"
WEB_DIR="/var/www/$PROJECT_NAME"
DB_NAME="security_scanner"
DB_USER="scanner_user"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Security Scanner Dashboard Installer${NC}"
echo -e "${BLUE}Educational Use Only${NC}"
echo -e "${BLUE}========================================${NC}"
echo

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo -e "${RED}This script should not be run as root for security reasons.${NC}"
   echo -e "${YELLOW}Please run as a regular user with sudo privileges.${NC}"
   exit 1
fi

# Check if sudo is available
if ! command -v sudo &> /dev/null; then
    echo -e "${RED}sudo is required but not installed.${NC}"
    exit 1
fi

echo -e "${YELLOW}⚠️  IMPORTANT DISCLAIMER ⚠️${NC}"
echo -e "${YELLOW}This tool is for educational purposes only.${NC}"
echo -e "${YELLOW}Only use on systems you own or have explicit permission to test.${NC}"
echo -e "${YELLOW}Unauthorized scanning is illegal and unethical.${NC}"
echo
read -p "Do you agree to use this tool responsibly? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}Installation cancelled.${NC}"
    exit 1
fi

# Function to print status
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Detect OS
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    if command_exists apt-get; then
        OS="debian"
        PACKAGE_MANAGER="apt-get"
    elif command_exists yum; then
        OS="redhat"
        PACKAGE_MANAGER="yum"
    else
        print_error "Unsupported Linux distribution"
        exit 1
    fi
else
    print_error "This installer only supports Linux"
    exit 1
fi

print_status "Detected OS: $OS"

# Update system packages
print_status "Updating system packages..."
if [[ "$OS" == "debian" ]]; then
    sudo apt-get update -y
elif [[ "$OS" == "redhat" ]]; then
    sudo yum update -y
fi

# Install required packages
print_status "Installing required packages..."
if [[ "$OS" == "debian" ]]; then
    sudo apt-get install -y apache2 php php-mysql php-curl php-json php-mbstring mysql-server curl wget unzip
elif [[ "$OS" == "redhat" ]]; then
    sudo yum install -y httpd php php-mysql php-curl php-json php-mbstring mariadb-server curl wget unzip
fi

# Install security tools (optional)
print_status "Installing security tools..."
if [[ "$OS" == "debian" ]]; then
    sudo apt-get install -y nmap nikto dnsutils wget python3-pip golang-go
elif [[ "$OS" == "redhat" ]]; then
    sudo yum install -y nmap nikto bind-utils wget python3-pip golang
fi

# Install additional Python tools
print_status "Installing Python security tools..."
sudo pip3 install dirsearch sslyze 2>/dev/null || print_warning "Some Python tools may not have installed correctly"

# Install Go tools
print_status "Installing Go security tools..."
if command_exists go; then
    export GOPATH=$HOME/go
    export PATH=$PATH:$GOPATH/bin
    go install github.com/ffuf/ffuf@latest 2>/dev/null || print_warning "ffuf installation may have failed"
fi

# Configure and start services
print_status "Configuring services..."
if [[ "$OS" == "debian" ]]; then
    sudo systemctl enable apache2
    sudo systemctl start apache2
    sudo systemctl enable mysql
    sudo systemctl start mysql
elif [[ "$OS" == "redhat" ]]; then
    sudo systemctl enable httpd
    sudo systemctl start httpd
    sudo systemctl enable mariadb
    sudo systemctl start mariadb
fi

# Generate random password for database
DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

# Configure MySQL/MariaDB
print_status "Configuring database..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Create web directory
print_status "Setting up web directory..."
sudo mkdir -p $WEB_DIR
sudo mkdir -p $WEB_DIR/{results,logs,wordlists}

# Copy application files (assuming they're in current directory)
print_status "Copying application files..."
sudo cp -r ./* $WEB_DIR/ 2>/dev/null || print_warning "Make sure to copy application files to $WEB_DIR"

# Set proper permissions
print_status "Setting file permissions..."
sudo chown -R www-data:www-data $WEB_DIR 2>/dev/null || sudo chown -R apache:apache $WEB_DIR
sudo chmod -R 755 $WEB_DIR
sudo chmod -R 777 $WEB_DIR/{results,logs,wordlists}

# Update database configuration
print_status "Updating database configuration..."
sudo tee $WEB_DIR/config/database.php > /dev/null <<EOF
<?php
/**
 * Database Configuration
 * Educational Security Scanner Dashboard
 */

class Database {
    private \$host = 'localhost';
    private \$db_name = '$DB_NAME';
    private \$username = '$DB_USER';
    private \$password = '$DB_PASSWORD';
    private \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$this->conn = new PDO(
                "mysql:host=" . \$this->host . ";dbname=" . \$this->db_name,
                \$this->username,
                \$this->password
            );
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException \$exception) {
            echo "Connection error: " . \$exception->getMessage();
        }
        
        return \$this->conn;
    }
}
?>
EOF

# Import database schema
print_status "Importing database schema..."
if [[ -f "$WEB_DIR/database/schema.sql" ]]; then
    mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME < $WEB_DIR/database/schema.sql
else
    print_warning "Database schema file not found. Please import manually."
fi

# Configure Apache virtual host
print_status "Configuring Apache virtual host..."
sudo tee /etc/apache2/sites-available/$PROJECT_NAME.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $PROJECT_NAME.local
    DocumentRoot $WEB_DIR
    
    <Directory $WEB_DIR>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    ErrorLog \${APACHE_LOG_DIR}/${PROJECT_NAME}_error.log
    CustomLog \${APACHE_LOG_DIR}/${PROJECT_NAME}_access.log combined
</VirtualHost>
EOF

# Enable site and modules
if [[ "$OS" == "debian" ]]; then
    sudo a2enmod rewrite headers
    sudo a2ensite $PROJECT_NAME.conf
    sudo a2dissite 000-default.conf 2>/dev/null || true
    sudo systemctl reload apache2
fi

# Configure sudo for security tools (optional)
print_status "Configuring sudo permissions for security tools..."
WEB_USER="www-data"
[[ "$OS" == "redhat" ]] && WEB_USER="apache"

sudo tee /etc/sudoers.d/$PROJECT_NAME > /dev/null <<EOF
# Security Scanner Dashboard - Educational Use Only
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/nmap, /usr/bin/nikto, /usr/local/bin/ffuf, /usr/local/bin/dirsearch, /usr/bin/sslyze
EOF

# Create .htaccess for security
print_status "Creating security configurations..."
sudo tee $WEB_DIR/.htaccess > /dev/null <<EOF
# Security Scanner Dashboard - Security Configuration

# Deny access to sensitive files
<Files "*.sql">
    Deny from all
</Files>

<Files "*.log">
    Deny from all
</Files>

<Files "config.php">
    Deny from all
</Files>

# Prevent directory browsing
Options -Indexes

# Enable rewrite engine
RewriteEngine On

# Redirect to HTTPS (uncomment when SSL is configured)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
EOF

# Create firewall rules
print_status "Configuring firewall..."
if command_exists ufw; then
    sudo ufw --force enable
    sudo ufw allow 22/tcp
    sudo ufw allow 80/tcp
    sudo ufw allow 443/tcp
elif command_exists firewall-cmd; then
    sudo firewall-cmd --permanent --add-service=http
    sudo firewall-cmd --permanent --add-service=https
    sudo firewall-cmd --permanent --add-service=ssh
    sudo firewall-cmd --reload
fi

# Get server IP
SERVER_IP=$(hostname -I | awk '{print $1}')

# Installation complete
echo
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Installation Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo
echo -e "${BLUE}Access Information:${NC}"
echo -e "URL: http://$SERVER_IP"
echo -e "Default Admin Login:"
echo -e "  Username: admin"
echo -e "  Password: admin"
echo
echo -e "${YELLOW}⚠️  IMPORTANT NEXT STEPS:${NC}"
echo -e "1. Change the default admin password immediately"
echo -e "2. Configure SSL/HTTPS for production use"
echo -e "3. Review and adjust firewall settings"
echo -e "4. Test all security tools are working"
echo -e "5. Add /etc/hosts entry: $SERVER_IP $PROJECT_NAME.local"
echo
echo -e "${BLUE}Database Information (save securely):${NC}"
echo -e "Database: $DB_NAME"
echo -e "Username: $DB_USER"
echo -e "Password: $DB_PASSWORD"
echo
echo -e "${RED}Remember: Use this tool only on systems you own or have explicit permission to test!${NC}"
echo

# Create info file
sudo tee $WEB_DIR/INSTALLATION_INFO.txt > /dev/null <<EOF
Security Scanner Dashboard - Installation Information
Generated: $(date)

Database Configuration:
- Database: $DB_NAME
- Username: $DB_USER
- Password: $DB_PASSWORD

Default Admin Account:
- Username: admin
- Password: admin (CHANGE IMMEDIATELY)

Server Information:
- Web Directory: $WEB_DIR
- Server IP: $SERVER_IP
- OS: $OS

Security Notes:
- Change default admin password
- Configure SSL/HTTPS
- Review firewall settings
- Use only on authorized systems

For support and documentation, see README.md
EOF

sudo chmod 600 $WEB_DIR/INSTALLATION_INFO.txt

print_status "Installation information saved to $WEB_DIR/INSTALLATION_INFO.txt"
print_status "Installation completed successfully!"
