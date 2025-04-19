#!/bin/bash

export HOST="$(gp url 80 | sed -E 's_^https?://__')"
export REDIRECT_URI="$(gp url 80)"

# Build Docker Image

docker compose -f docker-compose.dev.yml build

# Run Docker Container

docker compose -f docker-compose.dev.yml up