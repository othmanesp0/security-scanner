#!/bin/bash

# Security Scanner Dashboard Uninstall Script
# Educational Use Only

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_NAME="security-scanner"
WEB_DIR="/var/www/$PROJECT_NAME"
DB_NAME="security_scanner"
DB_USER="scanner_user"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Security Scanner Dashboard Uninstaller${NC}"
echo -e "${BLUE}========================================${NC}"
echo

echo -e "${YELLOW}⚠️  WARNING ⚠️${NC}"
echo -e "${YELLOW}This will completely remove the Security Scanner Dashboard${NC}"
echo -e "${YELLOW}including all scan results and user data.${NC}"
echo
read -p "Are you sure you want to continue? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${GREEN}Uninstall cancelled.${NC}"
    exit 0
fi

echo
read -p "Do you want to backup the database before removal? (Y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
    echo -e "${BLUE}Creating database backup...${NC}"
    BACKUP_FILE="security_scanner_backup_$(date +%Y%m%d_%H%M%S).sql"
    mysqldump -u $DB_USER -p $DB_NAME > "$HOME/$BACKUP_FILE" 2>/dev/null || echo -e "${YELLOW}Backup failed or skipped${NC}"
    if [[ -f "$HOME/$BACKUP_FILE" ]]; then
        echo -e "${GREEN}Database backed up to: $HOME/$BACKUP_FILE${NC}"
    fi
fi

# Function to print status
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

# Stop and disable services if needed
print_status "Stopping services..."
sudo systemctl stop apache2 2>/dev/null || true
sudo systemctl stop httpd 2>/dev/null || true

# Remove Apache virtual host
print_status "Removing Apache configuration..."
sudo a2dissite $PROJECT_NAME.conf 2>/dev/null || true
sudo rm -f /etc/apache2/sites-available/$PROJECT_NAME.conf
sudo rm -f /etc/httpd/conf.d/$PROJECT_NAME.conf 2>/dev/null || true

# Remove web directory
print_status "Removing web directory..."
sudo rm -rf $WEB_DIR

# Remove database and user
print_status "Removing database..."
mysql -u root -p -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>/dev/null || echo -e "${YELLOW}Database removal failed or skipped${NC}"
mysql -u root -p -e "DROP USER IF EXISTS '$DB_USER'@'localhost';" 2>/dev/null || echo -e "${YELLOW}User removal failed or skipped${NC}"

# Remove sudo configuration
print_status "Removing sudo configuration..."
sudo rm -f /etc/sudoers.d/$PROJECT_NAME

# Remove firewall rules (optional)
read -p "Remove firewall rules for HTTP/HTTPS? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if command -v ufw >/dev/null 2>&1; then
        sudo ufw delete allow 80/tcp 2>/dev/null || true
        sudo ufw delete allow 443/tcp 2>/dev/null || true
    elif command -v firewall-cmd >/dev/null 2>&1; then
        sudo firewall-cmd --permanent --remove-service=http 2>/dev/null || true
        sudo firewall-cmd --permanent --remove-service=https 2>/dev/null || true
        sudo firewall-cmd --reload 2>/dev/null || true
    fi
    print_status "Firewall rules removed"
fi

# Restart Apache
print_status "Restarting web server..."
sudo systemctl restart apache2 2>/dev/null || sudo systemctl restart httpd 2>/dev/null || true

# Docker cleanup (if applicable)
if [[ -f "docker-compose.yml" ]]; then
    read -p "Remove Docker containers and volumes? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker-compose down -v 2>/dev/null || true
        docker rmi security-scanner_security-scanner 2>/dev/null || true
        print_status "Docker containers and volumes removed"
    fi
fi

echo
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Uninstall Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo
echo -e "${BLUE}What was removed:${NC}"
echo "- Web application files ($WEB_DIR)"
echo "- Database ($DB_NAME) and user ($DB_USER)"
echo "- Apache virtual host configuration"
echo "- Sudo permissions for security tools"
echo
if [[ -f "$HOME/$BACKUP_FILE" ]]; then
    echo -e "${BLUE}Database backup saved to: $HOME/$BACKUP_FILE${NC}"
fi
echo
echo -e "${GREEN}Thank you for using Security Scanner Dashboard responsibly!${NC}"
