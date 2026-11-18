<?php
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();

$totalSales = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE order_status != 'cancelled'")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status = 'active'")->fetchColumn();

// Sales by day (last 14 days)
$salesByDay = $pdo->query(
    "SELECT DATE(created_at) AS d, COALESCE(SUM(total),0) AS total FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND order_status != 'cancelled'
     GROUP BY DATE(created_at) ORDER BY d"
)->fetchAll();
$salesMap = [];
foreach ($salesByDay as $row) $salesMap[$row['d']] = (float)$row['total'];
$chartLabels = []; $chartData = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($date));
    $chartData[] = $salesMap[$date] ?? 0;
}

// Orders by month (last 6 months)
$ordersByMonth = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym"
)->fetchAll();
$monthMap = [];
foreach ($ordersByMonth as $row) $monthMap[$row['ym']] = (int)$row['c'];
$monthLabels = []; $monthData = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $monthLabels[] = date('M', strtotime("-$i months"));
    $monthData[] = $monthMap[$ym] ?? 0;
}

// Best selling products
$bestSellers = $pdo->query(
    "SELECT p.name, p.featured_image, SUM(oi.quantity) AS units_sold
     FROM order_items oi JOIN products p ON p.id = oi.product_id
     GROUP BY oi.product_id ORDER BY units_sold DESC LIMIT 5"
)->fetchAll();

// Recent orders
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8")->fetchAll();

$statusColors = ['pending'=>'secondary','confirmed'=>'info','processing'=>'primary','shipped'=>'warning','delivered'=>'success','cancelled'=>'danger'];

$pageTitle = 'Dashboard';
$pageHeading = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon mb-2" style="background:#2e7d32"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-value"><?= formatPrice($totalSales) ?></div>
      <div class="text-muted small">Total Sales</div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon mb-2" style="background:#1565c0"><i class="bi bi-bag-check"></i></div>
      <div class="stat-value"><?= $totalOrders ?></div>
      <div class="text-muted small">Total Orders</div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon mb-2" style="background:#ef6c00"><i class="bi bi-hourglass-split"></i></div>
      <div class="stat-value"><?= $pendingOrders ?></div>
      <div class="text-muted small">Pending Orders</div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon mb-2" style="background:#6a1b9a"><i class="bi bi-people"></i></div>
      <div class="stat-value"><?= $totalCustomers ?></div>
      <div class="text-muted small">Customers</div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon mb-2" style="background:#00838f"><i class="bi bi-tshirt"></i></div>
      <div class="stat-value"><?= $totalProducts ?></div>
      <div class="text-muted small">Products</div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card">
      <div class="stat-icon mb-2" style="background:#c62828"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="stat-value"><?= $lowStock ?></div>
      <div class="text-muted small">Low Stock</div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="admin-card">
      <h6 class="mb-3">Sales - Last 14 Days</h6>
      <canvas id="salesChart" height="90"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="admin-card">
      <h6 class="mb-3">Orders by Month</h6>
      <canvas id="ordersChart" height="90"></canvas>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="admin-card">
      <h6 class="mb-3">Recent Orders</h6>
      <div class="table-responsive">
        <table class="table table-admin">
          <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td><a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $o['id'] ?>"><?= e($o['order_number']) ?></a></td>
              <td><?= e($o['customer_name']) ?></td>
              <td><?= formatPrice((float)$o['total']) ?></td>
              <td><span class="badge badge-status bg-<?= $statusColors[$o['order_status']] ?? 'secondary' ?>"><?= e($o['order_status']) ?></span></td>
              <td><?= date('M j', strtotime($o['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($recentOrders)): ?><tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="admin-card">
      <h6 class="mb-3">Best-Selling Products</h6>
      <?php if (empty($bestSellers)): ?>
        <p class="text-muted small">No sales data yet.</p>
      <?php endif; ?>
      <?php foreach ($bestSellers as $b): ?>
      <div class="d-flex align-items-center gap-3 mb-3">
        <img src="<?= e(productImageUrl($b['featured_image'])) ?>" class="thumb">
        <div class="flex-grow-1"><?= e($b['name']) ?></div>
        <strong><?= (int)$b['units_sold'] ?> sold</strong>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('salesChart'), {
  type: 'line',
  data: { labels: <?= json_encode($chartLabels) ?>, datasets: [{ label: 'Sales', data: <?= json_encode($chartData) ?>, borderColor: '#1a1a1a', backgroundColor: 'rgba(184,147,95,0.15)', fill: true, tension: 0.35 }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('ordersChart'), {
  type: 'bar',
  data: { labels: <?= json_encode($monthLabels) ?>, datasets: [{ label: 'Orders', data: <?= json_encode($monthData) ?>, backgroundColor: '#b8935f' }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
