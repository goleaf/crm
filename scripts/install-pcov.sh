#!/bin/bash

# PCOV Installation Script for Relaticle CRM
# This script installs and configures PCOV for code coverage

set -e

echo "🚀 PCOV Installation Script"
echo "============================"
echo ""

# Detect OS
if [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macos"
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="linux"
else
    echo "❌ Unsupported OS: $OSTYPE"
    exit 1
fi

echo "📍 Detected OS: $OS"
echo ""

# Check if PCOV is already installed
if php -m | grep -q pcov; then
    echo "✅ PCOV is already installed!"
    php --ri pcov
    echo ""
    echo "To reconfigure PCOV, edit your php.ini file:"
    php --ini | grep "Loaded Configuration"
    exit 0
fi

echo "📦 Installing PCOV..."
echo ""

# Install PCOV based on OS
if [[ "$OS" == "macos" ]]; then
    echo "Installing via PECL..."
    pecl install pcov
elif [[ "$OS" == "linux" ]]; then
    # Check for apt-get
    if command -v apt-get &> /dev/null; then
        echo "Installing via apt-get..."
        PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        sudo apt-get update
        sudo apt-get install -y php${PHP_VERSION}-pcov
    else
        echo "Installing via PECL..."
        sudo pecl install pcov
    fi
fi

# Verify installation
if php -m | grep -q pcov; then
    echo ""
    echo "✅ PCOV installed successfully!"
    echo ""
    php --ri pcov
else
    echo ""
    echo "❌ PCOV installation failed!"
    echo ""
    echo "Please install manually:"
    echo "  macOS: pecl install pcov"
    echo "  Linux: sudo apt-get install php-pcov"
    echo ""
    exit 1
fi

# Find php.ini location
echo ""
echo "📝 Configuring PCOV..."
PHP_INI=$(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")

if [[ -z "$PHP_INI" ]]; then
    echo "⚠️  Could not find php.ini location"
    echo "Please manually add to your php.ini:"
    echo ""
    echo "extension=pcov.so"
    echo "pcov.enabled = 1"
    echo "pcov.directory = $(pwd)"
    echo 'pcov.exclude = "~vendor~"'
    echo ""
else
    echo "Found php.ini: $PHP_INI"
    
    # Check if PCOV is already configured
    if grep -q "pcov.enabled" "$PHP_INI"; then
        echo "✅ PCOV is already configured in php.ini"
    else
        echo ""
        echo "Adding PCOV configuration to php.ini..."
        
        # Backup php.ini
        sudo cp "$PHP_INI" "${PHP_INI}.backup"
        
        # Add PCOV configuration
        echo "" | sudo tee -a "$PHP_INI" > /dev/null
        echo "; PCOV Configuration" | sudo tee -a "$PHP_INI" > /dev/null
        echo "extension=pcov.so" | sudo tee -a "$PHP_INI" > /dev/null
        echo "pcov.enabled = 1" | sudo tee -a "$PHP_INI" > /dev/null
        echo "pcov.directory = $(pwd)" | sudo tee -a "$PHP_INI" > /dev/null
        echo 'pcov.exclude = "~vendor~"' | sudo tee -a "$PHP_INI" > /dev/null
        
        echo "✅ PCOV configuration added to php.ini"
        echo "   Backup saved to: ${PHP_INI}.backup"
    fi
fi

# Update .env if it exists
if [[ -f ".env" ]]; then
    echo ""
    echo "📝 Updating .env file..."
    
    if grep -q "PCOV_ENABLED" ".env"; then
        echo "✅ PCOV configuration already exists in .env"
    else
        echo "" >> .env
        echo "# PCOV Code Coverage Configuration" >> .env
        echo "PCOV_ENABLED=true" >> .env
        echo "PCOV_DIRECTORY=." >> .env
        echo 'PCOV_EXCLUDE="~vendor~"' >> .env
        echo "COVERAGE_MIN_PERCENTAGE=80" >> .env
        echo "COVERAGE_MIN_TYPE_COVERAGE=99.9" >> .env
        echo "COVERAGE_HTML_DIR=coverage-html" >> .env
        echo "COVERAGE_CLOVER_FILE=coverage.xml" >> .env
        echo "COVERAGE_CACHE_TTL=300" >> .env
        
        echo "✅ PCOV configuration added to .env"
    fi
fi

# Run migration
if [[ -f "artisan" ]]; then
    echo ""
    echo "📝 Running database migration..."
    php artisan migrate --force
    echo "✅ Migration complete"
fi

# Clear caches
if [[ -f "artisan" ]]; then
    echo ""
    echo "🧹 Clearing caches..."
    php artisan optimize:clear
    echo "✅ Caches cleared"
fi

echo ""
echo "🎉 PCOV Installation Complete!"
echo ""
echo "Next steps:"
echo "  1. Run coverage: composer test:coverage"
echo "  2. View HTML report: open coverage-html/index.html"
echo "  3. View in Filament: Navigate to System → Code Coverage"
echo ""
echo "Documentation:"
echo "  - Quick Start: docs/README-PCOV-COVERAGE.md"
echo "  - Complete Guide: docs/pcov-code-coverage-integration.md"
echo "  - Steering Rules: .kiro/steering/pcov-code-coverage.md"
echo ""
echo "Verify installation:"
echo "  php -m | grep pcov"
echo "  php --ri pcov"
echo ""
