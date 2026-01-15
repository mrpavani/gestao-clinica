#!/bin/bash

# Create dist directory
rm -rf production_build
mkdir production_build

echo "Preparing Production Build..."

# Copy Core Files
cp index.php production_build/
cp setup_production.php production_build/
cp production_db.sql production_build/

# Copy Directories
cp -r src production_build/
cp -r templates production_build/
cp -r public production_build/
cp -r config production_build/

# Clean up any potential junk (like .DS_Store)
find production_build -name ".DS_Store" -delete

echo "Build created in 'production_build/' directory."
echo "Zip this folder and upload to your server."
