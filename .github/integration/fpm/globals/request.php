<?php

header('Content-Type: application/json');
$initial = [
    'server_array' => is_array($_SERVER),
    'env_array' => is_array($_ENV),
    'request_array' => is_array($_REQUEST),
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'uri' => $_SERVER['REQUEST_URI'] ?? null,
    'query' => $_GET['request'] ?? null,
    'request' => $_REQUEST['request'] ?? null,
    'env' => $_ENV['TYPEPHP_INTEGRATION_ENV'] ?? null,
    'previous' => $_SERVER['typephp_test_marker'] ?? null,
];
$_SERVER['typephp_test_marker'] = 'server-preserved';
$_ENV['typephp_test_marker'] = 'env-preserved';
$_REQUEST['typephp_test_marker'] = 'request-preserved';
$later = require __DIR__ . '/later.php';
echo json_encode(['pid' => integration_worker_pid(), 'initial' => $initial, 'later' => $later]);
