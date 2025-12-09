<?php
// public/checkout.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// require login to checkout
if (!is_logged_in()) {
    // store redirect after login
    $_SESSION['after_login_redirect'] = '/techcenter/public/checkout.php';
    header('Location: /techcenter/public/login.php');
    exit;
}

$errors = [];
$cart = $_SESSION['cart'] ?? [];

// If cart is empty, redirect back to cart page
if (empty($cart)) {
    header('Location: /techcenter/public/cart.php');
    exit;
}

// Fetch product details and compute total & validate stock
$ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, title, price, stock FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$rows = $stmt->fetchAll();
$map = [];
$total = 0;
foreach ($rows as $r) { $map[$r['id']] = $r; }
foreach ($cart as $pid => $qty) {
    if (!isset($map[$pid])) { $errors[] = "Product ID $pid not found."; continue; }
    if ($qty > $map[$pid]['stock']) $errors[] = "Not enough stock for " . e($map[$pid]['title']);
    $total += $map[$pid]['price'] * $qty;
}

// If user submitted the checkout form and there are no validation errors, persist the order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {

    // recompute total server-side (defense-in-depth)
    $total = 0;
    foreach ($cart as $pid => $qty) {
        $total += ($map[$pid]['price'] ?? 0) * $qty;
    }

    try {
        // begin transaction
        $pdo->beginTransaction();

        // insert order
        $ins = $pdo->prepare('INSERT INTO orders (user_id,total_amount,shipping_name,shipping_address) VALUES (:u,:total,:name,:addr)');
        $ins->execute([
            'u' => $_SESSION['user_id'],
            'total' => $total,
            'name' => trim($_POST['ship_name'] ?? ''),
            'addr' => trim($_POST['ship_address'] ?? '')
        ]);
        $orderId = $pdo->lastInsertId();

        // insert order items and decrement stock
        $oi = $pdo->prepare('INSERT INTO order_items (order_id,product_id,qty,price) VALUES (:oid,:pid,:q,:price)');
        $up = $pdo->prepare('UPDATE products SET stock = GREATEST(stock - :q, 0) WHERE id=:id');

        foreach ($cart as $pid => $qty) {
            $price = $map[$pid]['price'] ?? 0;
            $oi->execute(['oid' => $orderId, 'pid' => $pid, 'q' => $qty, 'price' => $price]);
            // decrement stock (safe)
            $up->execute(['q' => $qty, 'id' => $pid]);
        }

        $pdo->commit();

        // clear cart and redirect to success
        unset($_SESSION['cart']);
        header('Location: /techcenter/public/order_success.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = 'Could not place order: ' . $e->getMessage();
    }
}

// Render page
include __DIR__ . '/../src/views/header.php';
?>
<div class="panel" style="max-width:760px;margin:0 auto">
  <h2>Checkout</h2>

  <?php if (!empty($errors)): ?>
    <div style="color:#ff7474;margin-bottom:12px"><ul><?php foreach($errors as $err) echo '<li>'.e($err).'</li>'; ?></ul></div>
    <div><a class="btn ghost" href="/techcenter/public/cart.php">Return to cart</a></div>
  <?php else: ?>
    <div style="margin-bottom:12px">
      <div class="muted">Review your order</div>
      <div style="font-weight:800;color:var(--accent);font-size:20px">Total: ₹<?= number_format($total) ?></div>
    </div>

    <form method="post" action="">
      <div style="margin-bottom:12px">
        <label>Shipping name <input type="text" name="ship_name" required style="width:100%;padding:10px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.04)"></label>
        <label>Shipping address <textarea name="ship_address" rows="4" required style="width:100%;padding:10px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.04)"></textarea></label>
      </div>

      <div style="display:flex;gap:10px">
        <button class="btn primary" type="submit">Place order</button>
        <a class="btn ghost" href="/techcenter/public/cart.php">Cancel</a>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
