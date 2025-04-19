#!/bin/bash

export HOST="$(gp url 80 | sed -E 's_^https?://__')"
export REDIRECT_URI="$(gp url 80)"

export THEME_GIT_COMMIT="$(git rev-parse --short HEAD)"

# Build Docker Image

docker compose -f docker-compose.dev.yml build

mkdir -p generated_theme

chown 33:33 generated_theme -Rf

# Run Docker Container

docker compose -f docker-compose.dev.yml up