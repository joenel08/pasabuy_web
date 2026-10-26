<?php
header('Content-Type: application/json');
echo json_encode([
    'upload_max_filesize'    => ini_get('upload_max_filesize'),
    'post_max_size'          => ini_get('post_max_size'),
    'file_uploads'           => ini_get('file_uploads'),
    'max_file_uploads'       => ini_get('max_file_uploads'),
    'upload_tmp_dir'         => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
    'tmp_writable'           => is_writable(sys_get_temp_dir()),
    'storage_posts_exists'   => is_dir(__DIR__ . '/storage/posts'),
    'storage_posts_writable' => is_writable(__DIR__ . '/storage/posts'),
    'php_version'            => PHP_VERSION,
], JSON_PRETTY_PRINT);