#!/bin/bash

# Test runner script for Carone Laravel Media package

echo "🧪 Carone Laravel Media - Test Runner"
echo "===================================="

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed. Please install Composer first."
    exit 1
fi

# Install dependencies
echo ""
echo "📦 Installing dependencies..."
if composer install --no-interaction --prefer-dist --optimize-autoloader; then
    print_status "Dependencies installed successfully"
else
    print_error "Failed to install dependencies"
    exit 1
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    print_error "Vendor directory not found. Run 'composer install' first."
    exit 1
fi

# Run tests based on argument
case "${1:-all}" in
    "all")
        echo ""
        echo "🧪 Running all tests..."
        vendor/bin/phpunit
        ;;
    "actions")
        echo ""
        echo "🎯 Running Action tests..."
        vendor/bin/phpunit tests/Actions/
        ;;
    "strategies")
        echo ""
        echo "📋 Running Strategy tests..."
        vendor/bin/phpunit tests/Strategies/
        ;;
    "coverage")
        echo ""
        echo "📊 Running tests with coverage..."
        vendor/bin/phpunit --coverage-html coverage --coverage-text
        print_status "Coverage report generated in 'coverage/' directory"
        ;;
    "store")
        echo ""
        echo "💾 Running StoreMediaAction tests..."
        vendor/bin/phpunit tests/Actions/StoreMediaActionTest.php
        ;;
    "get")
        echo ""
        echo "📤 Running GetMediaAction tests..."
        vendor/bin/phpunit tests/Actions/GetMediaActionTest.php
        ;;
    "delete")
        echo ""
        echo "🗑️  Running DeleteMediaAction tests..."
        vendor/bin/phpunit tests/Actions/DeleteMediaActionTest.php
        ;;
    "help")
        echo ""
        echo "Available commands:"
        echo "  all        - Run all tests (default)"
        echo "  actions    - Run only Action tests"
        echo "  strategies - Run only Strategy tests"
        echo "  coverage   - Run tests with coverage report"
        echo "  store      - Run StoreMediaAction tests"
        echo "  get        - Run GetMediaAction tests"
        echo "  delete     - Run DeleteMediaAction tests"
        echo "  help       - Show this help message"
        echo ""
        echo "Examples:"
        echo "  ./test.sh"
        echo "  ./test.sh actions"
        echo "  ./test.sh coverage"
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Run './test.sh help' for available commands"
        exit 1
        ;;
esac

exit_code=$?

echo ""
if [ $exit_code -eq 0 ]; then
    print_status "All tests completed successfully! 🎉"
else
    print_error "Some tests failed. Please check the output above."
fi

exit $exit_code