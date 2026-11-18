<?php
require_once __DIR__ . '/functions.php';
$storeName = setting('store_name', 'LUXE Clothing');
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . $storeName : $storeName;
$metaDescription = $metaDescription ?? setting('meta_description', 'Premium clothing, shoes and accessories for men and women.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:type" content="website">
<?php if (!empty($canonicalUrl)): ?>
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</head>
<body>
<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080"></div>
<?php include __DIR__ . '/navbar.php'; ?>
