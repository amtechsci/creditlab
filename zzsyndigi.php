<?php
function createDigilockerUrl($adharNumber)
{
    $signzy_url = 'https://api.signzy.com/digilocker/createUrl';

    $headers = [
        'Content-Type: application/json',
        'x-api-key: 23028df6-386f-4b34-835f-f4e97f845cc4',
        'x-api-secret: jJrnSFk1jFeqbjKrCLpIn6xW7D8TIpLU'
    ];

    $data = [
        'signup' => true,
        'redirectUrl' => 'https://agent.loadingwalla.com/',
        'redirectTime' => 1,
        'callbackUrl' => 'https://creditlab.in/zzsyndigiwh.php',
        'customerId' => $adharNumber,
        'successRedirectUrl' => 'https://creditlab.in/zzsyndigiwh.php',
        'failureRedirectUrl' => 'https://creditlab.in/zzsyndigiwh.php',
        'docType' => ['ADHAR'],
        'purpose' => 'kyc',
        'getScope' => true,
    ];

    $ch = curl_init($signzy_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Debug info:
    if ($response === false) {
        echo "Curl error: " . $curlError . PHP_EOL;
        return ['success' => false, 'error' => 'Curl error: ' . $curlError];
    }

    echo "HTTP Status Code: $httpCode" . PHP_EOL;
    echo "Response: " . $response . PHP_EOL;

    $responseData = json_decode($response, true);

    if ($httpCode == 503) {
        echo "Service Unavailable: The API server might be down or overloaded." . PHP_EOL;
        return ['success' => false, 'error' => '503 Service Unavailable'];
    }

    if (isset($responseData['result']['url'])) {
        return ['success' => true, 'url' => $responseData['result']['url']];
    } else {
        echo "Error Response Data: ";
        print_r($responseData);
        return ['success' => false, 'error' => $responseData ?? 'Unknown error'];
    }
}

if (isset($_GET['ad'])) {
    $ad = $_GET['ad'];
    $result = createDigilockerUrl($ad);
    print_r($result);
} else {
    echo "Please provide 'ad' parameter in the URL";
}
