<?php
// public/order_success.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';
include __DIR__ . '/../src/views/header.php';
?>
<div class="panel" style="max-width:720px;margin:0 auto;text-align:center">
  <h2>Order placed</h2>
  <p class="muted">Thank you — your order has been received (demo). We didn't create a real orders table in this demo; implement orders if you need persistent orders.</p>
  <div style="margin-top:12px">
    <a class="btn primary" href="/techcenter/public/products.php">Continue shopping</a>
  </div>
</div>
<?php include __DIR__ . '/../src/views/footer.php'; ?>
