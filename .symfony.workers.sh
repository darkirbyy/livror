#!/bin/bash

DB_DEFAULT_PORT=3306
WP_DEFAULT_PORT=8080

# Function to find the next available port
find_next_available_port() {
    local port=$1
    while ss -ltn | awk '{print $4}' | grep -q ":$port$"; do
        ((port++))
    done
    echo $port
}

echo "Starting custom workers..."

# Find first available wp port and run webpack dev server with the selected port
export WP_PORT=$(find_next_available_port $WP_DEFAULT_PORT)
echo "Starting WebPack dev server on port $WP_PORT"
./node_modules/.bin/encore dev-server --hot --port=$WP_PORT &
WP_PID=$!  # Capture the Webpack process PID

# Find first available db port and run docker compose with the selected port
export DB_PORT=$(find_next_available_port $DB_DEFAULT_PORT)
echo "Starting MariaDB container on port $DB_PORT"
docker compose up -d

# Function to stop processes on exit
cleanup() {
    echo "Stopping WebPack dev server..."
    kill $WP_PID 2>/dev/null
    echo "Stopping MariaDB container..."
    docker compose down
}

# Trap termination signals to execute cleanup
trap cleanup SIGINT SIGTERM

# Wait for child processes to finish
wait $WP_PID
