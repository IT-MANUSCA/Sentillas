<?php
// Semaphore API Key
$api_key = "24b4949fd7a2ad804aa23f95a28e5acc";

// SMS details
$number = "09305854390"; // change to receiver's number dynamically later
$message = "Hello! This is a test SMS from Sentillas using our Registration System.";
$sendername = "Sentillas"; // ✅ Your approved sender name

// API URL
$url = "https://api.semaphore.co/api/v4/messages";

// Data to send
$fields = array(
    'apikey' => $api_key,
    'number' => $number,
    'message' => $message,
    'sendername' => $sendername
);

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute and get response
$response = curl_exec($ch);

// Close connection
curl_close($ch);

// Show raw JSON response
echo "<h3>Semaphore API Response:</h3>";
echo "<pre>$response</pre>";
?>
