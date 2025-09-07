<?php
$data = [
    'txnid' => 'CL' . uniqid('TXN_'),
    'amount' => '1.0',
    'firstname' => 'John Doe',
    'email' => 'john.doe@example.com',
    'phone' => '1234567890',
    'productinfo' => 'Sample Product',
    'surl' => 'https://creditlab.in/payeasebuzz/response.php',
    'furl' => 'https://creditlab.in/payeasebuzz/response.php'
];
echo '<html><body>';
echo '<form id="postForm" action="https://creditlab.in/payeasebuzz/easebuzz.php?api_name=initiate_payment" method="POST">';
foreach ($data as $key => $value) {
    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
}

echo '<script>';
echo 'document.getElementById("postForm").submit();'; // Automatically submit the form
echo '</script>';
echo '</body></html>';
?>