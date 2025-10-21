#!/bin/bash

# Blue-Green Deployment Script for RAG System
# This script deploys the new version to the inactive environment and switches traffic

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
MAX_HEALTH_CHECK_ATTEMPTS=30
HEALTH_CHECK_INTERVAL=2

# Function to backup and restore gitignored files
backup_gitignored_files() {
    print_status "Backing up gitignored files..."
    
    # Create backup directory
    mkdir -p /tmp/rag-backup
    
    # Backup .env.production
    if [ -f ".env.production" ]; then
        cp .env.production /tmp/rag-backup/
        print_status "Backed up .env.production"
    fi
    
    # Backup model files
    if [ -d "models" ] && [ "$(ls -A models/*.gguf 2>/dev/null)" ]; then
        cp models/*.gguf /tmp/rag-backup/ 2>/dev/null || true
        print_status "Backed up model files"
    fi
    
    # Backup any other important files
    if [ -f ".env" ]; then
        cp .env /tmp/rag-backup/
        print_status "Backed up .env"
    fi
}

# Function to restore gitignored files
restore_gitignored_files() {
    print_status "Restoring gitignored files..."
    
    # Restore .env.production
    if [ -f "/tmp/rag-backup/.env.production" ]; then
        cp /tmp/rag-backup/.env.production .
        print_status "Restored .env.production"
    fi
    
    # Restore model files
    if [ -d "/tmp/rag-backup" ] && [ "$(ls -A /tmp/rag-backup/*.gguf 2>/dev/null)" ]; then
        mkdir -p models
        cp /tmp/rag-backup/*.gguf models/ 2>/dev/null || true
        print_status "Restored model files"
    fi
    
    # Restore .env if needed
    if [ -f "/tmp/rag-backup/.env" ] && [ ! -f ".env" ]; then
        cp /tmp/rag-backup/.env .
        print_status "Restored .env"
    fi
}

# Function to update code from git
update_code_from_git() {
    print_status "Updating code from git repository..."
    
    # Backup files before git pull
    backup_gitignored_files
    
    # Pull latest changes
    git pull origin production
    
    # Restore backed up files
    restore_gitignored_files
    
    print_success "Code updated successfully!"
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

# Function to determine target environment
get_target_environment() {
    local current=$(get_current_active)
    if [ "$current" = "blue" ]; then
        echo "green"
    elif [ "$current" = "green" ]; then
        echo "blue"
    else
        echo "blue"
    fi
}

# Function to wait for health check
wait_for_health() {
    local port=$1
    local environment=$2
    local attempts=0
    
    print_status "Waiting for $environment environment to be healthy on port $port..."
    
    while [ $attempts -lt $MAX_HEALTH_CHECK_ATTEMPTS ]; do
        if check_port_active $port; then
            print_success "$environment environment is healthy!"
            return 0
        fi
        
        attempts=$((attempts + 1))
        print_status "Health check attempt $attempts/$MAX_HEALTH_CHECK_ATTEMPTS..."
        sleep $HEALTH_CHECK_INTERVAL
    done
    
    print_error "$environment environment failed health check after $MAX_HEALTH_CHECK_ATTEMPTS attempts"
    return 1
}

# Function to deploy to specific environment
deploy_to_environment() {
    local environment=$1
    local compose_file="docker-compose.$environment.yml"
    
    print_status "Deploying to $environment environment..."
    
    print_status "Stopping existing $environment containers..."
    docker compose -f $compose_file down --remove-orphans || true
    
    print_status "Pulling latest images..."
    docker compose -f $compose_file pull || true
    
    print_status "Building and starting $environment containers..."
    docker compose -f $compose_file build --pull
    docker compose -f $compose_file up -d
    
    local port
    if [ "$environment" = "blue" ]; then
        port=$BLUE_PORT
    else
        port=$GREEN_PORT
    fi
    
    if wait_for_health $port $environment; then
        print_success "$environment environment deployed successfully!"
        return 0
    else
        print_error "$environment environment deployment failed!"
        return 1
    fi
}

# Function to switch traffic
switch_traffic() {
    local target_env=$1
    local port
    
    if [ "$target_env" = "blue" ]; then
        port=$BLUE_PORT
    else
        port=$GREEN_PORT
    fi
    
    print_status "Switching traffic to $target_env environment (port $port)..."
    
    sed -i "s/server host\.docker\.internal:\${[A-Z_]*:-[0-9]*};/server host.docker.internal:$port;/" docker/nginx/nginx-lb.conf
    
    docker compose -f docker-compose.lb.yml restart nginx-lb
    
    print_success "Traffic switched to $target_env environment!"
}

# Function to cleanup old environment
cleanup_old_environment() {
    local old_env=$1
    local compose_file="docker-compose.$old_env.yml"
    
    print_status "Cleaning up old $old_env environment..."
    docker compose -f $compose_file down --remove-orphans || true
    
    print_status "Cleaning up old Docker images..."
    docker image prune -f || true
    
    print_success "Cleanup completed!"
}

# Main deployment logic
main() {
    local action=${1:-"deploy"}
    
    case $action in
        "update_code_from_git")
            update_code_from_git
            return 0
            ;;
        "deploy")
            print_status "Starting Blue-Green deployment..."
            ;;
        *)
            print_error "Unknown action: $action"
            print_status "Available actions: deploy, update_code_from_git"
            exit 1
            ;;
    esac
    
    if [ ! -f "docker-compose.blue.yml" ] || [ ! -f "docker-compose.green.yml" ]; then
        print_error "Blue-Green compose files not found. Please run this script from the project root."
        exit 1
    fi
    
    local current_active=$(get_current_active)
    local target_env=$(get_target_environment)
    
    print_status "Current active environment: $current_active"
    print_status "Target environment: $target_env"
    
    if deploy_to_environment $target_env; then
        switch_traffic $target_env
        
        print_status "Waiting 10 seconds before cleanup..."
        sleep 10
        
        if [ "$current_active" != "none" ] && [ "$current_active" != "$target_env" ]; then
            cleanup_old_environment $current_active
        fi
        
        print_success "Blue-Green deployment completed successfully!"
        print_status "Application is now running on port $LB_PORT (Load Balancer)"
        print_status "Active environment: $target_env"
        
    else
        print_error "Deployment failed! Rolling back..."
        
        if [ "$current_active" != "none" ]; then
            print_status "Attempting rollback to $current_active environment..."
            deploy_to_environment $current_active
            switch_traffic $current_active
        fi
        
        exit 1
    fi
}

main "$@"
