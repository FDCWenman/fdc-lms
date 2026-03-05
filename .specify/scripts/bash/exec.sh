#!/bin/bash

# Helper script for executing commands in the correct environment
# Reads .env file and determines if commands should run in Docker or locally

set -e

# Function to get .env value
get_env_value() {
    local key=$1
    local env_file="${2:-.env}"
    
    if [ -f "$env_file" ]; then
        grep "^${key}=" "$env_file" | cut -d'=' -f2- | tr -d '"'
    fi
}

# Get Docker container ID from .env
DOCKER_CONTAINER=$(get_env_value "LOCAL_DOCKER")
DOCKER_PATH=$(get_env_value "LOCAL_PATH")

# Function to execute command
exec_command() {
    local cmd="$@"
    
    if [ -n "$DOCKER_CONTAINER" ]; then
        echo "🐳 Executing in Docker container: $DOCKER_CONTAINER"
        docker exec -it "$DOCKER_CONTAINER" bash -c "cd $DOCKER_PATH && $cmd"
    else
        echo "💻 Executing locally"
        eval "$cmd"
    fi
}

# If no arguments provided, show usage
if [ $# -eq 0 ]; then
    echo "Usage: $0 <command>"
    echo ""
    echo "Examples:"
    echo "  $0 php artisan migrate"
    echo "  $0 php artisan test"
    echo "  $0 composer install"
    echo ""
    echo "Environment:"
    if [ -n "$DOCKER_CONTAINER" ]; then
        echo "  Docker Container: $DOCKER_CONTAINER"
        echo "  Docker Path: $DOCKER_PATH"
    else
        echo "  Execution: Local (no Docker container configured)"
    fi
    exit 0
fi

# Execute the command
exec_command "$@"
