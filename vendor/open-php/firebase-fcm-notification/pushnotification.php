<?php
/**
 * @author Sandip Mandal
 */

require 'vendor/autoload.php';

use OpenPhp\FirebaseFcm\FcmNotification;

// Simple wrapper function for legacy support or easy usage
function notify($title, $body, $token) {
    try {
        $fcm = new FcmNotification('./service_account.json');
        return $fcm->send($title, $body, $token);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// Example usage: Get token, title, and body from the request
$token = $_REQUEST['token'] ?? null;
$title = $_REQUEST['title'] ?? null;
$body = $_REQUEST['body'] ?? null;

if ($token && $title && $body) {
    $result = notify($title, $body, $token);

    if (isset($result['name'])) { // Success response usually contains 'name'
        echo "Message sent successfully! Response: " . json_encode($result);
    } else {
        echo "Failed to send message: " . json_encode($result);
    }
} else {
    echo "Missing required parameters.";
}
