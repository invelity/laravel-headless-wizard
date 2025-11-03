#!/bin/bash

# Script to add declare(strict_types=1); to all PHP files
# This script will add the declaration right after the opening <?php tag

echo "Adding declare(strict_types=1); to PHP files..."

# Find all PHP files, excluding vendor directory and some specific files
find . -name "*.php" \
    -not -path "./vendor/*" \
    -not -path "./storage/*" \
    -not -path "./bootstrap/cache/*" \
    -not -name "*.blade.php" \
    | while read -r file; do

    # Check if file already has declare(strict_types=1)
    if grep -q "declare(strict_types=1)" "$file"; then
        echo "Skipping $file (already has strict_types declaration)"
        continue
    fi

    # Check if file starts with <?php
    if ! head -1 "$file" | grep -q "<?php"; then
        echo "Skipping $file (doesn't start with <?php)"
        continue
    fi

    echo "Processing $file"

    # Create a temporary file
    temp_file=$(mktemp)

    # Read the file line by line and add declare statement after <?php
    {
        # Read first line (should be <?php)
        read -r first_line
        echo "$first_line"
        echo ""
        echo "declare(strict_types=1);"

        # Skip empty line if it exists after <?php
        read -r second_line
        if [[ -n "$second_line" ]]; then
            echo "$second_line"
        fi

        # Copy the rest of the file
        cat
    } < "$file" > "$temp_file"

    # Replace original file with modified content
    mv "$temp_file" "$file"

done

echo "Finished processing PHP files!"
echo ""
echo "Files that were modified:"
find . -name "*.php" \
    -not -path "./vendor/*" \
    -not -path "./storage/*" \
    -not -path "./bootstrap/cache/*" \
    -not -name "*.blade.php" \
    -exec grep -l "declare(strict_types=1)" {} \;
