<?php
require 'vendor/autoload.php';

use OpenPhp\FirebaseFcm\FcmNotification;

echo "Class OpenPhp\FirebaseFcm\FcmNotification exists: " . (class_exists(FcmNotification::class) ? 'YES' : 'NO') . "\n";

try {
    // We expect this to fail if service_account.json is invalid or missing, but that confirms class is working.
    // Or if we don't pass anything, it might throw error.
    if (file_exists('./service_account.json')) {
        echo "Found service_account.json, attempting instantiation...\n";
        $fcm = new FcmNotification('./service_account.json');
        echo "Instantiation successful!\n";
    } else {
        echo "service_account.json not found. Creating dummy instance with array to verify logic...\n";
        $dummyConfig = [
            'project_id' => 'test-project',
            'client_email' => 'test@example.com',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvg...=\n-----END PRIVATE KEY-----\n"
        ];
        $fcm = new FcmNotification($dummyConfig);
        echo "Instantiation with dummy array successful!\n";
    }
} catch (Exception $e) {
    echo "Caught expected exception (or actual error): " . $e->getMessage() . "\n";
}
