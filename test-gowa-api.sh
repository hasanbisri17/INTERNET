#!/bin/bash

# Script untuk test GOWA API endpoints
API_URL="http://43.133.137.52:3000"
API_KEY="GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww"
PHONE="6281234567890"  # Ganti dengan nomor HP Anda

echo "Testing GOWA API Endpoints..."
echo "================================"

# List of possible endpoints to try
endpoints=(
    "send/text"
    "api/send/text"
    "send/message"
    "api/send/message"
    "message/send"
    "api/message/send"
    "api/sendText"
    "sendText"
    "v1/messages"
    "api/v1/messages"
)

# Test each endpoint
for endpoint in "${endpoints[@]}"; do
    echo ""
    echo "Testing: $API_URL/$endpoint"
    echo "---"
    
    response=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "$API_URL/$endpoint" \
        -H "Content-Type: application/json" \
        -H "X-API-Key: $API_KEY" \
        -d "{\"phone\":\"$PHONE\",\"message\":\"Test API\"}" 2>&1)
    
    http_code=$(echo "$response" | grep "HTTP_CODE:" | cut -d: -f2)
    body=$(echo "$response" | sed '/HTTP_CODE:/d')
    
    echo "HTTP Code: $http_code"
    echo "Response: $body"
    
    if [ "$http_code" == "200" ] || [ "$http_code" == "201" ]; then
        echo "✅ SUCCESS! Use this endpoint: $endpoint"
        break
    fi
done

echo ""
echo "================================"
echo "Test completed!"

