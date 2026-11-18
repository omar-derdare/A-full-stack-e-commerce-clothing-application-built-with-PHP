<?php
$adminUser = currentUser();
$pageTitle = isset($pageTitle) ? $pageTitle . ' | Admin' : 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css">
</head>
<body>
<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080"></div>
<div class="admin-wrapper">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar d-flex justify-content-between align-items-center">
      <button class="btn d-lg-none" id="sidebarToggle"><i class="bi bi-list fs-3"></i></button>
      <h5 class="mb-0 d-none d-lg-block"><?= e($pageHeading ?? 'Dashboard') ?></h5>
      <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="small text-muted"><i class="bi bi-box-arrow-up-right"></i> View Store</a>
        <div class="dropdown">
          <a href="#" class="d-flex align-items-center gap-2 text-dark" data-bs-toggle="dropdown">
            <div class="admin-avatar"><?= e(strtoupper(substr($adminUser['first_name'],0,1))) ?></div>
            <span class="d-none d-md-inline"><?= e($adminUser['first_name']) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/profile.php">My Profile</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/logout.php">Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="admin-content">
