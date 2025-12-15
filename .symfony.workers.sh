#!/bin/bash

# Function to find the next available port
find_next_available_port() {
    local port=$1
    while ss -ltn | awk '{print $4}' | grep -q ":$port$"; do
        ((port++))
    done
    echo $port
}

# Find first available webpack port, starting at 8080
echo "Finding available port..."
export WEBPACK_PORT=$(find_next_available_port 8080)

# Run webpack dev server with the found port
echo "Starting WebPack dev server on port $WEBPACK_PORT..."
./node_modules/.bin/encore dev-server --hot --port=$WEBPACK_PORT &
WEBPACK_PID=$!  # Capture the Webpack process PID

# Detect the database port configured in env files
echo "Getting DATABASE_PORT from .env.local or .env otherwise..."
DATABASE_URL=$(grep -E '^DATABASE_URL=' .env)
if [ -f .env.local ]; then
    DATABASE_URL=$(grep -E '^DATABASE_URL=' .env.local)
fi
export DATABASE_PORT=$(echo "$DATABASE_URL" | sed -E 's/.*:([0-9]+).*/\1/')

# Run docker compose with the found port
echo "Starting MariaDB container on port $DATABASE_PORT..."
docker compose up -d

# Function to stop processes on exit
cleanup() {
    echo "Stopping WebPack dev server..."
    kill $WEBPACK_PID 2>/dev/null
    echo "Stopping MariaDB container..."
    docker compose down
}

# Trap termination signals to execute cleanup
trap cleanup SIGINT SIGTERM

# Wait for child processes to finish
wait $WEBPACK_PID
