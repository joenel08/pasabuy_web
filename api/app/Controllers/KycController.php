<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\IdVerification;
use App\Core\AdminNotifier;

class KycController
{
    private const ID_TYPES = ['philsys', 'passport', 'drivers_license', 'umid', 'sss', 'prc', 'voters', 'postal', 'senior'];

    public function submit(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);

        $idType     = (string) $request->input('id_type', '');
        $idNumber   = trim((string) $request->input('id_number', ''));
        $dateIssued = (string) $request->input('date_issued', '');

        $errors = [];
        if (!in_array($idType, self::ID_TYPES, true))     $errors['id_type']     = 'Invalid ID type';
        if ($idNumber === '')                             $errors['id_number']   = 'ID number is required';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateIssued)) $errors['date_issued'] = 'Invalid date (YYYY-MM-DD)';
        if (empty($request->files['front']))              $errors['front']       = 'Front image is required';
        if ($errors) Response::error('Validation failed', 422, $errors);

        $config = require __DIR__ . '/../../config/config.php';
        $dir = $config['storage']['ids'];
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $frontPath = $this->saveUpload($request->files['front'], $dir, 'front');
        $backPath  = !empty($request->files['back'])
            ? $this->saveUpload($request->files['back'], $dir, 'back')
            : null;

        $payload = [
            'user_id'     => (int) $auth['id'],
            'id_type'     => $idType,
            'id_number'   => $idNumber,
            'date_issued' => $dateIssued,
            'front_path'  => $frontPath,
            'back_path'   => $backPath,
            'status'      => 'pending',
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        $existing = IdVerification::findBy('user_id', (int) $auth['id']);
        if ($existing) {
            IdVerification::update((int) $existing['id'], $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            IdVerification::insert($payload);
        }

        \App\Core\AdminNotifier::push([
            'type'        => 'kyc_submitted',
            'category'    => 'kyc',
            'title'       => 'New KYC submission',
            'body'        => 'User #' . $auth['id'] . ' · ' . $idType,
            'link'        => '/pasabuy/users/' . $auth['id'],
            'target_type' => 'user',
            'target_id'   => (int) $auth['id'],
        ]);
        Response::ok(null, 'KYC submitted for review');
    }

    public function status(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $kyc  = IdVerification::findBy('user_id', (int) $auth['id']);
        Response::ok([
            'status'  => $kyc['status']  ?? 'none',
            'remarks' => $kyc['remarks'] ?? null,
        ]);
    }

    private function saveUpload(array $file, string $dir, string $prefix): string
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $target = rtrim($dir, '/') . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            Response::error('Failed to save file', 500);
        }
        return 'storage/ids/' . $name;
    }
}
