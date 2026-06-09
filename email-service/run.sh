#!/bin/bash
# Runner script to start both the email HTTP service and the background worker

# Ensure RESEND_API_KEY is available (warn if not)
if [ -z "$RESEND_API_KEY" ]; then
    echo "WARNING: RESEND_API_KEY environment variable is not set."
    echo "The worker will fail to send emails unless you set it."
    echo "Example: export RESEND_API_KEY=\"re_12345\""
    echo ""
fi

# Change directory to the script's directory
cd "$(dirname "$0")"

echo "Starting Email HTTP API server on http://localhost:3000..."
/opt/lampp/bin/php -S localhost:3000 index.php > /dev/null 2>&1 &
SERVER_PID=$!

# Function to clean up the background server on exit
cleanup() {
    echo ""
    echo "Stopping Email HTTP API server (PID: $SERVER_PID)..."
    kill $SERVER_PID
    exit 0
}

# Trap exit signals (like Ctrl+C) to run cleanup
trap cleanup SIGINT SIGTERM

echo "Starting Email Queue Worker..."
/opt/lampp/bin/php worker.php
