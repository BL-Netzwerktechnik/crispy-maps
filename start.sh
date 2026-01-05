#!/bin/bash

# Take host:port from first parameter if provided
if [ -n "$1" ]; then
  HOST="$1"
else
  read -p "Enter host with port (e.g., localhost:8080 or example.com:443): " HOST
fi

# Extract port (default 80 if none provided)
PORT=$(echo "$HOST" | cut -s -d: -f2)
PORT=${PORT:-80}

HOST_WITHOUT_PORT=$(echo "$HOST" | cut -d: -f1)

# Choose protocol
if [[ "$HOST_WITHOUT_PORT" == "localhost" ]]; then
  PROTO="http"
else
  PROTO="https"

  # Ask user if they want to use http instead

  read -p "Use HTTP instead of HTTPS? (y/N): " use_http
  if [[ "$use_http" == "y" || "$use_http" == "Y" ]]; then
    PROTO="http"
  fi
  
fi

# Build redirect URI
REDIRECT_URI="$PROTO://$HOST:$PORT"

# Export variables
export HOST
export REDIRECT_URI
export PROTO

echo "HOST=$HOST"
echo "REDIRECT_URI=$REDIRECT_URI"
echo "PROTO=$PROTO"

# Build Docker Image
docker compose -f docker-compose.dev.yml build

# Run Docker Container
docker compose -f docker-compose.dev.yml up --force-recreate
docker compose -f docker-compose.dev.yml down
