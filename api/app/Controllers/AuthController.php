<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Models\EmailVerification;
use App\Models\IdVerification;
use App\Models\Profile;
use App\Models\User;
use App\Core\AdminNotifier;

class AuthController
{
    public function register(Request $request, array $params = []): void
    {
        $email    = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        // $role     = (string) $request->input('role', 'buyer');

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors['email']    = 'Invalid email';
        if (strlen($password) < 8)                          $errors['password'] = 'Password must be at least 8 characters';
        // if (!in_array($role, ['buyer', 'pasabuyer'], true))  $errors['role']     = 'Invalid role';
        if ($errors) Response::error('Validation failed', 422, $errors);

        if (User::findBy('email', $email)) {
            Response::error('Email already registered', 422, ['email' => 'Email already in use']);
        }

        $userId = User::insert([
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => 'user',
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        EmailVerification::create($userId, $code);
        \App\Core\AdminNotifier::push([
            'type'        => 'user_registered',
            'category'    => 'users',
            'title'       => 'New user registered',
            'body'        => $email,
            'link'        => '/pasabuy/users/' . $userId,
            'target_type' => 'user',
            'target_id'   => $userId,
        ]);
        $sent = Mailer::sendVerification($email, $code);

        Response::ok(
            [
                'user_id'    => $userId,
                'email_sent' => $sent,
            ],
            $sent
                ? 'Registered. Check your email for the code.'
                : 'Registered, but we could not send the email. Please use "Resend code".'
        );
    }

    public function verifyEmail(Request $request, array $params = []): void
    {
        $email = trim((string) $request->input('email', ''));
        $code  = trim((string) $request->input('code', ''));

        $user = User::findBy('email', $email);
        if (!$user) Response::error('User not found', 404);
        if (!EmailVerification::verify((int) $user['id'], $code)) {
            // Find out exactly why
            $stmt = \App\Core\Database::pdo()->prepare(
                "SELECT code, expires_at, NOW() AS db_now FROM email_verifications
         WHERE user_id = ? ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([(int) $user['id']]);
            $row = $stmt->fetch();

            if (!$row) {
                Response::error('No verification code on file. Request a new one.', 422);
            } elseif ((string) $row['code'] !== $code) {
                Response::error('Code does not match. Check the email and try again.', 422);
            } else {
                Response::error('Code expired. Request a new one.', 422);
            }
        }

        User::update((int) $user['id'], ['email_verified_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        Response::ok(null, 'Email verified. You can now log in.');
    }

    public function resendCode(Request $request, array $params = []): void
    {
        $email = trim((string) $request->input('email', ''));
        $user = User::findBy('email', $email);
        if (!$user) Response::error('User not found', 404);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        EmailVerification::create((int) $user['id'], $code);
        Mailer::sendVerification($email, $code);
        Response::ok(null, 'Verification code sent.');
    }

    public function login(Request $request, array $params = []): void
    {
        $email    = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        $user = User::findBy('email', $email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }
        if (!$user['email_verified_at']) {
            Response::error('Email not verified', 403, ['email' => 'Verify your email first']);
        }

        $token = Auth::issueToken((int) $user['id']);

        Response::ok([
            'token' => $token,
            'user'  => $this->userPayload((int) $user['id']),
        ], 'Logged in');
    }

    public function me(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        Response::ok($this->userPayload((int) $auth['id']));
    }

    public function logout(Request $request, array $params = []): void
    {
        Auth::require($request);
        Auth::revokeCurrent($request);
        Response::ok(null, 'Logged out');
    }

    private function userPayload(int $userId): array
    {
        $user    = User::find($userId);
        $profile = Profile::findBy('user_id', $userId);
        $kyc     = IdVerification::findBy('user_id', $userId);

        return [
            'id'                => (int) $user['id'],
            'email'             => $user['email'],
            'role'              => $user['role'],
            'status'            => $user['status'],
            'email_verified'    => (bool) $user['email_verified_at'],
            'profile_completed' => (bool) $profile,
            'kyc_status'        => $kyc['status'] ?? 'none',
        ];
    }
}
