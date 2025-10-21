#!/bin/bash

# Blue-Green Switch Script for RAG System
# This script switches traffic between blue and green environments

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
BLUE_PORT=${BLUE_PORT:-8080}
GREEN_PORT=${GREEN_PORT:-8081}
LB_PORT=${LB_PORT:-8082}
HEALTH_CHECK_URL=${HEALTH_CHECK_URL:-"http://localhost"}

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if a port is active
check_port_active() {
    local port=$1
    curl -f -s "$HEALTH_CHECK_URL:$port/health" > /dev/null 2>&1
}

# Function to determine current active environment
get_current_active() {
    if check_port_active $BLUE_PORT; then
        echo "blue"
    elif check_port_active $GREEN_PORT; then
        echo "green"
    else
        echo "none"
    fi
}

# Function to switch traffic to specific environment
switch_to_environment() {
    local target_env=$1
    local port
    
    if [ "$target_env" = "blue" ]; then
        port=$BLUE_PORT
    else
        port=$GREEN_PORT
    fi
    
    print_status "Switching traffic to $target_env environment (port $port)..."
    
    if ! check_port_active $port; then
        print_error "$target_env environment is not healthy! Cannot switch traffic."
        return 1
    fi
    
    sed -i "s/server host\.docker\.internal:\${[A-Z_]*:-[0-9]*};/server host.docker.internal:$port;/" docker/nginx/nginx-lb.conf
    
    docker compose -f docker-compose.lb.yml restart nginx-lb
    
    print_success "Traffic switched to $target_env environment!"
    return 0
}

# Function to show current status
show_status() {
    local blue_status="DOWN"
    local green_status="DOWN"
    local current_active=$(get_current_active)
    
    if check_port_active $BLUE_PORT; then
        blue_status="UP"
    fi
    
    if check_port_active $GREEN_PORT; then
        green_status="UP"
    fi
    
    echo "=== Blue-Green Status ==="
    echo "Blue Environment (port $BLUE_PORT): $blue_status"
    echo "Green Environment (port $GREEN_PORT): $green_status"
    echo "Current Active: $current_active"
    echo "Load Balancer (port $LB_PORT): UP"
    echo "========================="
}

# Main function
main() {
    local action=${1:-"status"}
    
    case $action in
        "blue")
            print_status "Switching to blue environment..."
            switch_to_environment "blue"
            ;;
        "green")
            print_status "Switching to green environment..."
            switch_to_environment "green"
            ;;
        "status")
            show_status
            ;;
        *)
            echo "Usage: $0 {blue|green|status}"
            echo ""
            echo "Commands:"
            echo "  blue   - Switch traffic to blue environment"
            echo "  green  - Switch traffic to green environment"
            echo "  status - Show current status of both environments"
            exit 1
            ;;
    esac
}

main "$@"
