#!/bin/bash

echo "⚠️  Dropping database..."
php bin/console doctrine:database:drop --force

echo "✅ Database dropped."

echo "🛠️  Creating database..."
php bin/console doctrine:database:create
echo "✅ Database created."

echo "🚀 Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction
echo "✅ Migrations done."

echo "📦 Loading fixtures..."
php bin/console doctrine:fixtures:load --no-interaction
echo "✅ Fixtures loaded successfully."
