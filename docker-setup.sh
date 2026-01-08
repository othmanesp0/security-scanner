#!/bin/bash

# Docker Setup Script for Security Scanner Dashboard
# Educational Use Only

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Security Scanner Dashboard - Docker Setup${NC}"
echo -e "${BLUE}Educational Use Only${NC}"
echo -e "${BLUE}========================================${NC}"
echo

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is not installed. Please install Docker first.${NC}"
    echo "Visit: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose is not installed. Please install Docker Compose first.${NC}"
    echo "Visit: https://docs.docker.com/compose/install/"
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
    echo -e "${RED}Setup cancelled.${NC}"
    exit 1
fi

# Build and start containers
echo -e "${GREEN}Building and starting containers...${NC}"
docker-compose up -d --build

# Wait for services to be ready
echo -e "${GREEN}Waiting for services to start...${NC}"
sleep 30

# Check if containers are running
if docker-compose ps | grep -q "Up"; then
    echo -e "${GREEN}✓ Containers are running successfully${NC}"
else
    echo -e "${RED}✗ Some containers failed to start${NC}"
    docker-compose logs
    exit 1
fi

# Get container IP
CONTAINER_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' security-scanner-dashboard)

echo
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Docker Setup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo
echo -e "${BLUE}Access Information:${NC}"
echo -e "URL: http://localhost:8080"
echo -e "Container IP: http://$CONTAINER_IP"
echo
echo -e "${BLUE}Default Admin Login:${NC}"
echo -e "Username: admin"
echo -e "Password: admin"
echo
echo -e "${YELLOW}⚠️  IMPORTANT NEXT STEPS:${NC}"
echo -e "1. Change the default admin password immediately"
echo -e "2. Test all security tools are working"
echo -e "3. Review container security settings"
echo
echo -e "${BLUE}Docker Commands:${NC}"
echo -e "Stop containers: docker-compose down"
echo -e "View logs: docker-compose logs -f"
echo -e "Restart: docker-compose restart"
echo
echo -e "${RED}Remember: Use this tool only on systems you own or have explicit permission to test!${NC}"
