<?php
/**
 * Simple script to test GOWA API endpoints
 * Run: php test-gowa-simple.php
 */

$apiUrl = 'http://43.133.137.52:3000';
$apiKey = 'GQJLPguHbA4r8bT8v4K8TB7OW7L6xzww';
$phone = '6281234567890'; // Ganti dengan nomor HP Anda

echo "Testing GOWA API Endpoints...\n";
echo "================================\n\n";

// List of possible endpoints
$endpoints = [
    // Format 1: Standard REST
    'send/text',
    'api/send/text',
    'send/message',
    'api/send/message',
    'message/send',
    'api/message/send',
    
    // Format 2: camelCase
    'sendText',
    'api/sendText',
    'sendMessage',
    'api/sendMessage',
    
    // Format 3: With version
    'v1/messages',
    'api/v1/messages',
    'v1/send/text',
    
    // Format 4: Alternative structures
    'text/send',
    'api/text/send',
];

$data = [
    'phone' => $phone,
    'message' => 'Test dari PHP script'
];

foreach ($endpoints as $endpoint) {
    $url = rtrim($apiUrl, '/') . '/' . $endpoint;
    
    echo "Testing: $url\n";
    echo "---\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    
    if ($httpCode == 200 || $httpCode == 201) {
        echo "✅ SUCCESS! Use this endpoint: $endpoint\n";
        echo "================================\n";
        break;
    }
    
    echo "\n";
}

echo "\nTest completed!\n";

