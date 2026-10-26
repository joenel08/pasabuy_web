# Firebase FCM Notification Library (PHP)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/open-php/firebase-fcm-notification.svg?style=flat-square)](https://packagist.org/packages/open-php/firebase-fcm-notification)
[![Total Downloads](https://img.shields.io/packagist/dt/open-php/firebase-fcm-notification.svg?style=flat-square)](https://packagist.org/packages/open-php/firebase-fcm-notification)
[![License](https://img.shields.io/packagist/l/open-php/firebase-fcm-notification.svg?style=flat-square)](https://packagist.org/packages/open-php/firebase-fcm-notification)

A robust and easy-to-use PHP library for sending Firebase Cloud Messaging (FCM) notifications using the Google HTTP v1 API.

This library encapsulates the complexity of generating JWT tokens for Google Auth and making requests to the FCM v1 API.

## Features

- **HTTP v1 API Support**: Uses the latest Firebase v1 API.
- **Service Account Authentication**: handled automatically via `service_account.json`.
- **Flexible Targeting**: Support for sending to specific device tokens or topics.
- **Rich Notifications**: Support for title, body, and image.
- **PSR-4 Autoloading**: Easy integration with Composer.

## Folder Structure

```
firebase-fcm-notification/
├── src/
│   └── FcmNotification.php  <-- Main Library Class
├── composer.json            <-- Dependency Management
├── index.php                <-- Example Usage (HTTP)
└── service_account.json     <-- Your Firebase Credentials (NOT INCLUDED)
```

## Installation

1. **Install dependencies via Composer:**

   ```bash
   composer require open-php/firebase-fcm-notification
   ```
   *(Or running `composer install` if you cloned this repo)*

2. **Setup Credentials:**
   - Go to the [Firebase Console](https://console.firebase.google.com/).
   - Navigate to Project Settings > Service accounts.
   - Generate a new private key and save the JSON file as `service_account.json` in your project root (or anywhere secure).

## Usage

### Basic Usage

```php
require 'vendor/autoload.php';

use OpenPhp\FirebaseFcm\FcmNotification;

try {
    // 1. Initialize with path to service account JSON
    $fcm = new FcmNotification('./service_account.json');

    // 2. Send a notification
    $title = "Hello World";
    $body = "This is a test notification";
    $token = "DEVICE_FCM_TOKEN"; 

    $result = $fcm->send($title, $body, $token);

    print_r($result);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Advanced Usage (Topics & Images)

```php
// Send to a topic
$isTopic = true;
$target = "news"; // Topic name
$imageUrl = "https://example.com/image.png";

$result = $fcm->send($title, $body, $target, $isTopic, $imageUrl);
```

## Class & Methods

### `OpenPhp\FirebaseFcm\FcmNotification`

#### `__construct($serviceAccount)`
Initializes the FCM client.
- **$serviceAccount** *(string|array)*: Path to the `service_account.json` file OR an associative array containing the JSON data.

#### `send($title, $body, $target, $isTopic = false, $imageUrl = null)`
Sends a notification.
- **$title** *(string)*: Notification title.
- **$body** *(string)*: Notification body text.
- **$target** *(string)*: Device registration token OR topic name.
- **$isTopic** *(bool)*: Set to `true` if `$target` is a topic name. Default `false`.
- **$imageUrl** *(string|null)*: Optional URL to an image to display in the notification.
- **Returns**: `array` (FCM API response) or `['error' => string]` on failure.

## Support

- **Issues**: [GitHub Issues](https://github.com/mrsandipmandal/firebaseNotification/issues)
- **Source**: [GitHub Repository](https://github.com/mrsandipmandal/firebaseNotification)

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) file for details.

## Author

- **Sandip Mandal** - [GitHub](https://github.com/mrsandipmandal)
