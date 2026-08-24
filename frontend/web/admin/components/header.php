<?php
// ATOM Web Admin — Shared Header Component
if (!isset($workspaceRoot)) {
    require_once dirname(__DIR__, 2) . '/bootstrap.php';
}
$pageTitle = $pageTitle ?? 'Control Center';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — <?= htmlspecialchars($pageTitle) ?></title>
  
  <!-- CSS Frameworks -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <style>
    body {
      background-color: #080a0d !important;
      color: #f0f4f8 !important;
      font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .card {
      background-color: #11151c !important;
      border-color: #1e2838 !important;
    }
    .card-header {
      background-color: #0c0f14 !important;
      border-bottom-color: #1e2838 !important;
    }
    .form-control, .form-select {
      background-color: #080a0d !important;
      border-color: #1e2838 !important;
      color: #f0f4f8 !important;
    }
    .form-control:focus, .form-select:focus {
      background-color: #0c0f14 !important;
      border-color: #10b981 !important;
      color: #fff !important;
      box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25) !important;
    }
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #080a0d;
    }
    ::-webkit-scrollbar-thumb {
      background: #1e2838;
      border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #374151;
    }
  </style>
</head>
<body class="text-[#f0f4f8] h-screen flex overflow-hidden bg-[#080a0d]">

  <!-- COLLAPSIBLE SIDEBAR -->
  <?php include_once __DIR__ . '/sidebar.php'; ?>

  <!-- MAIN WORKSPACE CONTAINER -->
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- TOP BAR -->
    <?php include_once __DIR__ . '/topbar.php'; ?>

    <!-- CONTENT BODY -->
    <main class="flex-1 overflow-y-auto p-8 space-y-8">
