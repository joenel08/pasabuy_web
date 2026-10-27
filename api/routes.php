<?php

use App\Controllers\AuthController;
use App\Controllers\ConversationController;
use App\Controllers\HealthController;
use App\Controllers\KycController;
use App\Controllers\NotificationController;
use App\Controllers\PostController;
use App\Controllers\ProfileController;
use App\Controllers\TransactionController;
use App\Controllers\UserController;
use App\Controllers\FavoriteController;
use App\Controllers\ReportController;
use App\Controllers\ReviewController;


/** @var App\Core\Router $router */

// Health
$router->get('/health', [HealthController::class, 'index']);

// ---------- Auth ----------
$router->post('/auth/register',     [AuthController::class, 'register']);
$router->post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
$router->post('/auth/resend-code',  [AuthController::class, 'resendCode']);
$router->post('/auth/login',        [AuthController::class, 'login']);
$router->post('/auth/logout',       [AuthController::class, 'logout']);

// ---------- Me ----------
$router->get('/me', [AuthController::class, 'me']);

// ---------- Profile ----------
$router->get ('/me/profile', [ProfileController::class, 'show']);
$router->put ('/me/profile', [ProfileController::class, 'save']);
$router->post('/me/avatar',  [ProfileController::class, 'uploadAvatar']);

// ---------- KYC ----------
$router->post('/me/kyc',        [KycController::class, 'submit']);
$router->get ('/me/kyc/status', [KycController::class, 'status']);

// ---------- Posts ----------
$router->get   ('/posts',           [PostController::class, 'index']);
$router->get   ('/posts/{id}',      [PostController::class, 'show']);
$router->post  ('/posts',           [PostController::class, 'store']);
$router->put   ('/posts/{id}',      [PostController::class, 'update']);
$router->delete('/posts/{id}',      [PostController::class, 'destroy']);
$router->post  ('/posts/{id}/close', [PostController::class, 'close']);

// ---------- Conversations & Messages ----------
$router->get ('/conversations',                [ConversationController::class, 'index']);
$router->post('/conversations',                [ConversationController::class, 'start']);
$router->get ('/conversations/{id}/messages',  [ConversationController::class, 'messages']);
$router->post('/conversations/{id}/messages',  [ConversationController::class, 'send']);

// ---------- Transactions (Deals) ----------
$router->post('/conversations/{id}/deals', [TransactionController::class, 'create']);
$router->post('/deals/{id}/accept',        [TransactionController::class, 'accept']);
$router->post('/deals/{id}/decline',       [TransactionController::class, 'decline']);
$router->post('/deals/{id}/done',          [TransactionController::class, 'done']);
$router->post('/deals/{id}/dispute',       [TransactionController::class, 'dispute']);
$router->get ('/me/deals',                 [TransactionController::class, 'myDeals']);
$router->get ('/me/stats',                 [TransactionController::class, 'myStats']);

// ---------- Notifications ----------
$router->get ('/notifications',              [NotificationController::class, 'index']);
$router->get ('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
$router->post('/notifications/read-all',     [NotificationController::class, 'markAllRead']);
$router->post('/notifications/{id}/read',    [NotificationController::class, 'markRead']);

$router->get('/users/{id}', [ProfileController::class, 'publicProfile']);



$router->post('/me/fcm-token',          [UserController::class, 'saveFcmToken']);
$router->post('/me/fcm-token/remove',   [UserController::class, 'removeFcmToken']);

$router->get('/me/stats/trend', [TransactionController::class, 'myTrend']);

// Reports
$router->post('/reports', [ReportController::class, 'store']);

// Reviews
$router->get ('/users/{id}/reviews',       [ReviewController::class, 'forUser']);
$router->post('/transactions/{id}/review', [ReviewController::class, 'store']);
$router->get ('/me/reviews/pending',       [ReviewController::class, 'pending']);

// Favorites
$router->get ('/me/favorites',        [FavoriteController::class, 'index']);
$router->post('/me/favorites/toggle', [FavoriteController::class, 'toggle']);

// Post full update (with images)
$router->put ('/posts/{id}/full', [PostController::class, 'updateFull']);
$router->post('/posts/{id}/full', [PostController::class, 'updateFull']);
// Account deletion request
$router->post('/me/delete-request', [ProfileController::class, 'requestDeletion']);

$router->get('/users/{id}/review-eligibility', [ReviewController::class, 'eligibility']);

$router->post('/me/password', [ProfileController::class, 'changePassword']);

$router->get('/me/stats/by-item', [TransactionController::class, 'myItemsBreakdown']);

$router->post  ('/conversations/{id}/pin',   [ConversationController::class, 'pin']);
$router->post  ('/conversations/{id}/unpin', [ConversationController::class, 'unpin']);
$router->delete('/conversations/{id}',       [ConversationController::class, 'destroy']);

$router->get('/me/fees', [TransactionController::class, 'myFees']);