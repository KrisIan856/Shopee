<?php
// logout.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
session_destroy();
header('Location: ' . SITE_URL . '/index.php');
exit;
