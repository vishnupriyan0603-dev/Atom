<?php
// Main Admin Routing Gateway — Redirects directly to the structured control panel.
require_once __DIR__ . '/bootstrap.php';
header('Location: ' . $getAdminUrl('index'));
exit;
