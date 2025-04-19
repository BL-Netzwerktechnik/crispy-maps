#!/bin/bash

# Iterate over directories in the current directory
for dir in */; do
    # Check if composer.json exists in the directory
    if [[ -f "$dir/composer.json" ]]; then
        echo "Running composer install in $dir..."
        (cd "$dir" && composer install)
    else
        echo "Skipping $dir (no composer.json found)"
    fi
done

echo "Done!"
