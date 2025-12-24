#!/bin/bash
#
# Copyright (c) 2025 Back Labidi Netzwerktechnik GbR. All rights reserved.
#

# This script must be run in the root of the repository.

set -e
set -o pipefail
set -x

function installComposerDependenciesInDir() {
    local dir="$1"
    if [ -f "$dir/composer.json" ]; then
        echo "Installing composer dependencies in $dir"
        (cd "$dir" && composer install --ignore-platform-reqs)
    else
        echo "No composer.json found in $dir, skipping."
    fi
}

# Get the directory of the script

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CRISPCMS_DIR="${REPO_DIR}/crispcms"
CRISPY_DIR="${REPO_DIR}/crispy"

if [ ! -d "$CRISPCMS_DIR/.git" ]; then
    git clone git@gitlab.jrbit.de:crispcms/core.git "$CRISPCMS_DIR"

else
    git -C "$CRISPCMS_DIR" pull
fi



if [ ! -d "$CRISPY_DIR/.git" ]; then
    git clone git@gitlab.jrbit.de:jrb-it/crispy.git "$CRISPY_DIR"

else
    git -C "$CRISPY_DIR" pull
fi


installComposerDependenciesInDir "$CRISPCMS_DIR"
installComposerDependenciesInDir "$CRISPY_DIR"
installComposerDependenciesInDir "$REPO_DIR/plugin"
installComposerDependenciesInDir "$REPO_DIR"
touch "$REPO_DIR/.env"  || true