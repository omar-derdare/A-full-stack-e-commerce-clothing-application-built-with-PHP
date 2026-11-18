<?php
require_once __DIR__ . '/../includes/auth.php';
$id = (int)($_GET['id'] ?? 0);
getDB()->prepare('DELETE FROM coupons WHERE id = ?')->execute([$id]);
flash('success', 'Coupon deleted.');
redirect(BASE_URL . '/admin/coupons/index.php');
