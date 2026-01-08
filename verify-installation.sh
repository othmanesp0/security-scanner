#!/bin/bash

# Security Scanner Dashboard Installation Verification Script
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

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Security Scanner Installation Verification${NC}"
echo -e "${BLUE}========================================${NC}"
echo

# Function to print status
print_check() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
    fi
}

print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo -e "${RED}Please run this script as a regular user, not root.${NC}"
   exit 1
fi

echo -e "${BLUE}System Checks:${NC}"

# Check Apache/HTTP server
if systemctl is-active --quiet apache2 2>/dev/null; then
    print_check 0 "Apache2 is running"
elif systemctl is-active --quiet httpd 2>/dev/null; then
    print_check 0 "Apache/httpd is running"
else
    print_check 1 "Web server is not running"
fi

# Check MySQL/MariaDB
if systemctl is-active --quiet mysql 2>/dev/null; then
    print_check 0 "MySQL is running"
elif systemctl is-active --quiet mariadb 2>/dev/null; then
    print_check 0 "MariaDB is running"
else
    print_check 1 "Database server is not running"
fi

# Check PHP
if command -v php >/dev/null 2>&1; then
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2)
    print_check 0 "PHP is installed (version $PHP_VERSION)"
else
    print_check 1 "PHP is not installed"
fi

echo
echo -e "${BLUE}Application Checks:${NC}"

# Check web directory
if [ -d "$WEB_DIR" ]; then
    print_check 0 "Web directory exists ($WEB_DIR)"
else
    print_check 1 "Web directory not found ($WEB_DIR)"
fi

# Check key files
FILES=("index.php" "login.php" "user_dashboard.php" "admin_dashboard.php" "config/database.php")
for file in "${FILES[@]}"; do
    if [ -f "$WEB_DIR/$file" ]; then
        print_check 0 "File exists: $file"
    else
        print_check 1 "File missing: $file"
    fi
done

# Check directories
DIRS=("api" "components" "admin" "includes" "results" "logs" "wordlists")
for dir in "${DIRS[@]}"; do
    if [ -d "$WEB_DIR/$dir" ]; then
        print_check 0 "Directory exists: $dir"
    else
        print_check 1 "Directory missing: $dir"
    fi
done

# Check permissions
WEB_USER="www-data"
if id "apache" >/dev/null 2>&1; then
    WEB_USER="apache"
fi

OWNER=$(stat -c '%U' "$WEB_DIR" 2>/dev/null || echo "unknown")
if [ "$OWNER" = "$WEB_USER" ]; then
    print_check 0 "Web directory has correct ownership ($WEB_USER)"
else
    print_check 1 "Web directory ownership incorrect (expected: $WEB_USER, actual: $OWNER)"
fi

echo
echo -e "${BLUE}Security Tools Checks:${NC}"

# Check security tools
TOOLS=("nmap" "nikto" "dig" "wget")
for tool in "${TOOLS[@]}"; do
    if command -v $tool >/dev/null 2>&1; then
        print_check 0 "$tool is installed"
    else
        print_check 1 "$tool is not installed"
    fi
done

# Check Python tools
if python3 -c "import dirsearch" 2>/dev/null; then
    print_check 0 "dirsearch is installed"
else
    print_check 1 "dirsearch is not installed"
fi

if python3 -c "import sslyze" 2>/dev/null; then
    print_check 0 "sslyze is installed"
else
    print_check 1 "sslyze is not installed"
fi

# Check Go tools
if command -v ffuf >/dev/null 2>&1; then
    print_check 0 "ffuf is installed"
else
    print_check 1 "ffuf is not installed"
fi

echo
echo -e "${BLUE}Network Checks:${NC}"

# Check if web server is responding
SERVER_IP=$(hostname -I | awk '{print $1}')
if curl -s -o /dev/null -w "%{http_code}" "http://$SERVER_IP" | grep -q "200\|302\|301"; then
    print_check 0 "Web server is responding on http://$SERVER_IP"
else
    print_check 1 "Web server is not responding on http://$SERVER_IP"
fi

# Check database connection
if [ -f "$WEB_DIR/config/database.php" ]; then
    # Extract database credentials (basic parsing)
    DB_NAME=$(grep "db_name" "$WEB_DIR/config/database.php" | cut -d"'" -f4 2>/dev/null || echo "")
    DB_USER=$(grep "username" "$WEB_DIR/config/database.php" | cut -d"'" -f4 2>/dev/null || echo "")
    
    if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
        print_info "Database configuration found (DB: $DB_NAME, User: $DB_USER)"
    else
        print_warning "Could not parse database configuration"
    fi
fi

echo
echo -e "${BLUE}Security Checks:${NC}"

# Check firewall status
if command -v ufw >/dev/null 2>&1; then
    if ufw status | grep -q "Status: active"; then
        print_check 0 "UFW firewall is active"
    else
        print_check 1 "UFW firewall is not active"
    fi
elif command -v firewall-cmd >/dev/null 2>&1; then
    if firewall-cmd --state 2>/dev/null | grep -q "running"; then
        print_check 0 "Firewalld is running"
    else
        print_check 1 "Firewalld is not running"
    fi
else
    print_warning "No firewall detected"
fi

# Check sudo configuration
if [ -f "/etc/sudoers.d/$PROJECT_NAME" ]; then
    print_check 0 "Sudo configuration exists for security tools"
else
    print_check 1 "Sudo configuration not found (some tools may not work)"
fi

# Check .htaccess
if [ -f "$WEB_DIR/.htaccess" ]; then
    print_check 0 ".htaccess security configuration exists"
else
    print_check 1 ".htaccess security configuration missing"
fi

echo
echo -e "${BLUE}Recommendations:${NC}"

# Provide recommendations
echo -e "${YELLOW}Security Recommendations:${NC}"
echo "1. Change the default admin password (admin/admin)"
echo "2. Configure SSL/HTTPS certificates"
echo "3. Review and test all security tools"
echo "4. Ensure firewall is properly configured"
echo "5. Regular security updates"
echo

echo -e "${YELLOW}Testing Recommendations:${NC}"
echo "1. Test login functionality"
echo "2. Verify each scanning tool works"
echo "3. Check real-time monitoring"
echo "4. Test admin dashboard functions"
echo "5. Verify file permissions and access controls"
echo

echo -e "${YELLOW}Legal Reminders:${NC}"
echo "1. Only scan systems you own or have explicit permission to test"
echo "2. Follow all applicable laws and regulations"
echo "3. Use responsibly in educational environments"
echo "4. Document all authorized testing activities"
echo

# Final status
echo -e "${GREEN}Verification completed!${NC}"
echo -e "${BLUE}Access your installation at: http://$SERVER_IP${NC}"
echo -e "${BLUE}Default login: admin / admin (change immediately!)${NC}"
