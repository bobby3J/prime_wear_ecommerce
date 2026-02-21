<?php
$kpis = $kpis ?? [
    'products' => 0,
    'categories' => 0,
    'customers' => 0,
    'carts' => 0,
    'orders' => 0,
    'payments' => 0,
];

$trends = $trends ?? [
    'active_carts_24h' => 0,
    'new_customers_7d' => 0,
    'orders_7d' => 0,
];

$alerts = $alerts ?? [
    'low_stock_products' => 0,
    'abandoned_carts' => 0,
];

$chartData = $chartData ?? [
    'orders_vs_payments_7d' => ['labels' => [], 'orders' => [], 'payments' => []],
    'customers_7d' => ['labels' => [], 'values' => []],
    'order_status' => ['labels' => [], 'values' => []],
    'payment_status' => ['labels' => [], 'values' => []],
    'cart_health' => ['labels' => [], 'values' => []],
];
?>

<section class="dashboard-shell">
  <div class="dashboard-hero mb-3">
    <h2 class="mb-1">Business Dashboard</h2>
    <p class="mb-0">Compact analytics snapshot.</p>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-icon bg-soft-blue"><i class="fa fa-box"></i></div>
        <div>
          <div class="kpi-label">Products</div>
          <div class="kpi-value"><?= (int) $kpis['products'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-icon bg-soft-cyan"><i class="fa fa-layer-group"></i></div>
        <div>
          <div class="kpi-label">Categories</div>
          <div class="kpi-value"><?= (int) $kpis['categories'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-icon bg-soft-violet"><i class="fa fa-users"></i></div>
        <div>
          <div class="kpi-label">Customers</div>
          <div class="kpi-value"><?= (int) $kpis['customers'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-icon bg-soft-teal"><i class="fa fa-shopping-cart"></i></div>
        <div>
          <div class="kpi-label">Carts</div>
          <div class="kpi-value"><?= (int) $kpis['carts'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-icon bg-soft-green"><i class="fa fa-bag-shopping"></i></div>
        <div>
          <div class="kpi-label">Orders</div>
          <div class="kpi-value"><?= (int) $kpis['orders'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="kpi-tile">
        <div class="kpi-icon bg-soft-orange"><i class="fa fa-credit-card"></i></div>
        <div>
          <div class="kpi-label">Payments</div>
          <div class="kpi-value"><?= (int) $kpis['payments'] ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-lg-8">
      <div class="chart-card chart-primary h-100">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Orders vs Payments (Last 7 Days)</h5>
          <span class="text-muted small">Trend</span>
        </div>
        <canvas id="ordersPaymentsChart" height="78"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="insight-card h-100">
        <h6 class="mb-2">Quick Stats</h6>
        <ul class="mb-0 small">
          <li>Active carts (24h): <strong><?= (int) $trends['active_carts_24h'] ?></strong></li>
          <li>New customers (7d): <strong><?= (int) $trends['new_customers_7d'] ?></strong></li>
          <li>Orders (7d): <strong><?= (int) $trends['orders_7d'] ?></strong></li>
          <li>Low stock products: <strong><?= (int) $alerts['low_stock_products'] ?></strong></li>
          <li>Abandoned carts: <strong><?= (int) $alerts['abandoned_carts'] ?></strong></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="row g-2">
    <div class="col-lg-4">
      <div class="chart-card compact h-100">
        <h5 class="mb-2">Order Status Mix</h5>
        <canvas id="orderStatusChart" height="105"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="chart-card compact h-100">
        <h5 class="mb-2">Payment Status Mix</h5>
        <canvas id="paymentStatusChart" height="105"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="chart-card compact h-100">
        <h5 class="mb-2">Cart Health</h5>
        <canvas id="cartHealthChart" height="105"></canvas>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
(() => {
  const chartData = <?= json_encode($chartData, JSON_UNESCAPED_SLASHES) ?>;
  if (!window.Chart) return;

  const palette = {
    blue: "#0d6efd",
    cyan: "#0dcaf0",
    green: "#20c997",
    amber: "#ffb703",
    violet: "#7c4dff",
    red: "#dc3545",
    slate: "#6c757d"
  };

  new Chart(document.getElementById("ordersPaymentsChart"), {
    type: "line",
    data: {
      labels: chartData.orders_vs_payments_7d.labels || [],
      datasets: [
        {
          label: "Orders",
          data: chartData.orders_vs_payments_7d.orders || [],
          borderColor: palette.blue,
          backgroundColor: "rgba(13,110,253,0.16)",
          tension: 0.35,
          fill: true
        },
        {
          label: "Payments",
          data: chartData.orders_vs_payments_7d.payments || [],
          borderColor: palette.green,
          backgroundColor: "rgba(32,201,151,0.10)",
          tension: 0.35,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: "bottom", labels: { boxWidth: 10 } } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });

  new Chart(document.getElementById("orderStatusChart"), {
    type: "doughnut",
    data: {
      labels: chartData.order_status.labels || [],
      datasets: [{
        data: chartData.order_status.values || [],
        backgroundColor: [palette.blue, palette.green, palette.amber, palette.violet, palette.red, palette.slate]
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom", labels: { boxWidth: 10 } } } }
  });

  new Chart(document.getElementById("paymentStatusChart"), {
    type: "doughnut",
    data: {
      labels: chartData.payment_status.labels || [],
      datasets: [{
        data: chartData.payment_status.values || [],
        backgroundColor: [palette.amber, palette.green, palette.red, palette.slate]
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom", labels: { boxWidth: 10 } } } }
  });

  new Chart(document.getElementById("cartHealthChart"), {
    type: "bar",
    data: {
      labels: chartData.cart_health.labels || [],
      datasets: [{
        label: "Carts",
        data: chartData.cart_health.values || [],
        backgroundColor: [palette.cyan, palette.amber, palette.blue],
        borderRadius: 10
      }]
    },
    options: {
      indexAxis: "y",
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
})();
</script>
