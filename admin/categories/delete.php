<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);

$check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
$check->execute([$id]);
if ((int)$check->fetchColumn() > 0) {
    flash('error', 'Cannot delete this category because it still has products assigned to it. Reassign or delete those products first.');
    redirect(BASE_URL . '/admin/categories/index.php');
}

$pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
flash('success', 'Category deleted.');
redirect(BASE_URL . '/admin/categories/index.php');
