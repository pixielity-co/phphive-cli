#!/bin/bash

# Test Magento app creation with command-line flags
# This demonstrates the new non-interactive Magento app creation

cd /Users/akouta/Projects/mono-php/test-workspace

# Clean up any existing test app
rm -rf apps/magento-test

# Create Magento app with all flags (no prompts!)
hive make:app magento-test \
  --type=magento \
  --description="Magento test app" \
  --magento-version=2.4.7 \
  --magento-public-key=test_public_key \
  --magento-private-key=test_private_key \
  --no-docker \
  --db-host=127.0.0.1 \
  --db-port=3306 \
  --db-name=magento_test \
  --db-user=magento_user \
  --db-password=magento_pass \
  --admin-firstname=Admin \
  --admin-lastname=User \
  --admin-email=admin@example.com \
  --admin-user=admin \
  --admin-password=Admin123! \
  --base-url=http://localhost/ \
  --language=en_US \
  --currency=USD \
  --timezone=America/New_York

echo ""
echo "✓ Magento app created with flags!"
echo ""
echo "To test with Docker instead, use:"
echo "  --use-docker (instead of --no-docker)"
echo "  Remove database flags to let Docker handle it"
