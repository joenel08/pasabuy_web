<?php
/**
 * @author Sandip Mandal
 */

require 'vendor/autoload.php';

use OpenPhp\FirebaseFcm\FcmNotification;

// Allow CORS if needed, or keep simple
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

/* Example Usage */

$token = $_POST['token'] ?? '';
$title = $_POST['title'] ?? '';
$body  = $_POST['body'] ?? '';
$isTopic = filter_var($_POST['topic'] ?? false, FILTER_VALIDATE_BOOLEAN);
$imageUrl = $_POST['image_url'] ?? '';

if (!$token || !$title || !$body) {
    echo json_encode(['error' => 'Missing required parameters (token, title, body)']);
    exit;
}

try {
    // Initialize the library with path to service account
    $fcm = new FcmNotification('./service_account.json');

    // Send notification
    $result = $fcm->send($title, $body, $token, $isTopic, $imageUrl);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
