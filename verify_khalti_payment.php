<?php
$data = json_decode(file_get_contents('php://input'), true);

$url = "https://khalti.com/api/v2/payment/verify/";
$args = array(
    'token' => $data['token'],
    'amount' => $data['amount']
);

$headers = [
    "Authorization: Key test_secret_key_xxxxxxxx"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$response = curl_exec($ch);
curl_close($ch);

$res = json_decode($response, true);

if(isset($res['idx'])){
    // Payment verified, update order status in DB as needed
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>