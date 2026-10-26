<?php

use Admin\Controllers\AuthController;
use Admin\Controllers\DashboardController;
use Admin\Controllers\KycController;
use Admin\Controllers\PostsController;
use Admin\Controllers\UsersController;
use Admin\Controllers\AnalyticsController;
use Admin\Controllers\ReportsController;
use Admin\Controllers\LandingController;

use Admin\Controllers\SettingsController;

use Admin\Controllers\NotificationsController;

use Admin\Controllers\TransactionsController;

/** @var Admin\Core\Router $router */

// Root → dashboard or login
// Landing page
$router->get('/',      [LandingController::class, 'index']);
$router->get('/admin', [DashboardController::class, 'root']);


// Auth
$router->get('/login',   [AuthController::class, 'showLogin']);
$router->post('/login',  [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index']);

// Users
$router->get('/users',     [UsersController::class, 'index']);
$router->get('/users/{id}', [UsersController::class, 'show']);

// KYC review
$router->get('/kyc',                        [KycController::class, 'index']);
$router->get('/kyc/{id}/image/{side}',      [KycController::class, 'image']);
$router->post('/users/{id}/kyc/approve',    [KycController::class, 'approve']);
$router->post('/users/{id}/kyc/decline',    [KycController::class, 'decline']);

$router->post('/users/{id}/kyc/reset',     [KycController::class,   'reset']);
$router->post('/users/{id}/force-logout',  [UsersController::class, 'forceLogout']);
$router->post('/users/{id}/delete',        [UsersController::class, 'destroy']);

$router->get('/analytics',           [AnalyticsController::class, 'index']);
$router->get('/analytics/user/{id}', [AnalyticsController::class, 'user']);
$router->get('/analytics/data',      [AnalyticsController::class, 'dataJson']); // for AJAX



$router->get ('/reports',        [ReportsController::class, 'index']);
$router->get ('/reports/{id}',   [ReportsController::class, 'show']);
$router->post('/reports/{id}',   [ReportsController::class, 'update']);
// $router->get ('/reports',                [ReportsController::class, 'index']);
// $router->post('/reports/{id}/resolve',   [ReportsController::class, 'resolve']);
// $router->post('/reports/{id}/dismiss',   [ReportsController::class, 'dismiss']);


// System settings
$router->get ('/settings',                    [SettingsController::class, 'index']);
$router->post('/settings/password',           [SettingsController::class, 'changePassword']);
$router->post('/settings/admins',             [SettingsController::class, 'createAdmin']);
$router->post('/settings/admins/{id}/role',   [SettingsController::class, 'updateRole']);
$router->post('/settings/admins/{id}/delete', [SettingsController::class, 'deleteAdmin']);

// Posts
$router->get ('/posts',             [PostsController::class, 'index']);
$router->post('/posts/{id}/delete', [PostsController::class, 'destroy']);
$router->get('/posts/{id}',  [PostsController::class, 'show']);   // ← ADD


// Admin notifications API
$router->get ('/notifications/summary',           [NotificationsController::class, 'summary']);
$router->post('/notifications/{id}/read',         [NotificationsController::class, 'markRead']);
$router->post('/notifications/read-all',          [NotificationsController::class, 'markAllRead']);

// Reports page
$router->get ('/reports',                         [ReportsController::class, 'index']);
$router->post('/reports/{id}/resolve',            [ReportsController::class, 'resolve']);
$router->post('/reports/{id}/dismiss',            [ReportsController::class, 'dismiss']);


$router->get('/transactions', [TransactionsController::class, 'index']);
$router->get('/analytics/by-item', [AnalyticsController::class, 'itemsBreakdown']);

// Reports
$router->get ('/reports',                       [ReportsController::class, 'index']);
$router->get ('/reports/{id}',                  [ReportsController::class, 'show']);
$router->post('/reports/{id}/update',           [ReportsController::class, 'update']);         // ← ADD
$router->post('/reports/{id}/suspend-user',     [ReportsController::class, 'suspendUser']);    // ← ADD
$router->post('/reports/{id}/delete-target',    [ReportsController::class, 'deleteTarget']);   // ← ADD