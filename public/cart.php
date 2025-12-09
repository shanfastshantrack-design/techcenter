<?php
// public/cart.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// initialize cart
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Handle POST actions: add (from products), update, remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // add via product form (product.php or products grid)
    if (isset($_POST['product_id']) && !isset($_POST['action'])) {
        $pid = (int)$_POST['product_id'];
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
        header('Location: /techcenter/public/cart.php');
        exit;
    }

    // update quantities
    if (isset($_POST['action']) && $_POST['action'] === 'update_qty' && isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $pid => $q) {
            $pid = (int)$pid;
            $q = max(0, (int)$q);
            if ($q === 0) {
                unset($_SESSION['cart'][$pid]);
            } else {
                $_SESSION['cart'][$pid] = $q;
            }
        }
        header('Location: /techcenter/public/cart.php');
        exit;
    }

    // remove single
    if (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['product_id'])) {
        $pid = (int)$_POST['product_id'];
        unset($_SESSION['cart'][$pid]);
        header('Location: /techcenter/public/cart.php');
        exit;
    }
}

// fetch product details for items in cart
$cart_items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    // prepare a query with placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, title, price, stock, image FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[$r['id']] = $r;
    foreach ($_SESSION['cart'] as $pid => $qty) {
        if (!isset($map[$pid])) continue;
        $item = $map[$pid];
        $subtotal = ((int)$item['price']) * $qty;
        $cart_items[] = ['product'=>$item, 'qty'=>$qty, 'subtotal'=>$subtotal];
        $total += $subtotal;
    }
}

include __DIR__ . '/../src/views/header.php';
?>
<div class="panel" style="max-width:1000px;margin:0 auto">
  <h2>Your cart</h2>

  <?php if (empty($cart_items)): ?>
    <div class="muted">Your cart is empty.</div>
    <div style="margin-top:12px"><a class="btn primary" href="/techcenter/public/products.php">Browse products</a></div>
  <?php else: ?>
    <form method="post" action="">
      <input type="hidden" name="action" value="update_qty">
      <table style="width:100%;border-collapse:collapse">
        <thead style="text-align:left;color:var(--muted)"><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
          <?php foreach($cart_items as $it): 
            $prod = $it['product'];
          ?>
            <tr style="border-top:1px solid rgba(255,255,255,0.03);">
              <td style="padding:12px 8px;vertical-align:middle">
                <div style="display:flex;gap:12px;align-items:center">
                  <?php if ($prod['image'] && file_exists(__DIR__ . '/uploads/' . $prod['image'])): ?>
                    <img src="/techcenter/public/uploads/<?= e($prod['image']) ?>" style="width:64px;height:64px;object-fit:cover;border-radius:8px" alt="">
                  <?php else: ?>
                    <div style="width:64px;height:64px;border-radius:8px;background:linear-gradient(180deg,#111,#222);display:flex;align-items:center;justify-content:center;color:var(--muted)"><?= e($prod['title']) ?></div>
                  <?php endif; ?>
                  <div>
                    <a href="/techcenter/public/product.php?id=<?= e($prod['id']) ?>"><?= e($prod['title']) ?></a>
                  </div>
                </div>
              </td>
              <td>₹<?= number_format((int)$prod['price']) ?></td>
              <td><input type="number" name="qty[<?= e($prod['id']) ?>]" value="<?= e($it['qty']) ?>" min="0" style="width:80px;padding:6px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.04);color:inherit"></td>
              <td>₹<?= number_format($it['subtotal']) ?></td>
              <td>
                <form method="post" style="display:inline">
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="product_id" value="<?= e($prod['id']) ?>">
                  <button class="btn ghost" type="submit">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
        <div>
          <button class="btn primary" type="submit">Update quantities</button>
          <a class="btn ghost" href="/techcenter/public/products.php">Continue shopping</a>
        </div>
        <div style="text-align:right">
          <div class="muted">Total</div>
          <div style="font-weight:800;color:var(--accent);font-size:20px">₹<?= number_format($total) ?></div>
          <div style="margin-top:8px">
            <a class="btn primary" href="/techcenter/public/checkout.php">Proceed to checkout</a>
          </div>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../src/views/footer.php'; ?>
