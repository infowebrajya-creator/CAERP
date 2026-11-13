#!/bin/bash
set -e

echo "==================================================================="
echo "            WebRajya ERP — Practice Management System              "
echo "==================================================================="
echo ""

# 1. Check if Docker is installed
echo "[1/3] Checking Docker installation..."
if ! command -v docker &> /dev/null; then
    echo ""
    echo "[ERROR] Docker is not installed."
    echo "Please download and install Docker Desktop: https://www.docker.com/products/docker-desktop/"
    exit 1
fi

if ! docker info &> /dev/null; then
    echo ""
    echo "[ERROR] Docker daemon is not running."
    echo "Please launch Docker Desktop and re-run this script."
    exit 1
fi
echo "[OK] Docker is active and running!"
echo ""

# 2. Build and launch containers
echo "[2/3] Building & Starting WebRajya ERP containers..."
docker compose up -d --build
echo ""

# 3. Wait for initialization
echo "[3/3] Initializing WebRajya application..."
sleep 8

echo ""
echo "==================================================================="
echo "           SUCCESS: WebRajya ERP is now running!                   "
echo "==================================================================="
echo "Application URL : http://localhost:8000"
echo "Activation URL  : http://localhost:8000/license/activate"
echo ""

# Try opening in browser
if command -v open &> /dev/null; then
    open "http://localhost:8000/license/activate"
elif command -v xdg-open &> /dev/null; then
    xdg-open "http://localhost:8000/license/activate"
fi

echo "To stop later: docker compose down"
echo "To restart later: docker compose up -d"

