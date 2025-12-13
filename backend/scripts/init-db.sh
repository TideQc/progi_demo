#!/bin/bash
# Database initialization script
# Run this to initialize the database schema and seed data

set -e

DB_HOST="${DB_HOST:-db}"
DB_USER="${DB_USER:-user}"
DB_PASS="${DB_PASS:-pass}"
DB_NAME="${DB_NAME:-bidcalc}"

echo "Waiting for database to be ready..."
for i in {1..30}; do
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1; then
        echo "Database is ready!"
        break
    fi
    echo "Attempt $i/30 - waiting for database..."
    sleep 1
done

echo "Initializing database schema..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /app/sql/schema.sql

echo "Database initialization complete!"
