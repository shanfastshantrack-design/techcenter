<?php
// public/delete_product.php
// Delete a product (admin only). GET = show confirmation, POST = perform delete.

require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// Helper: redirect with message if set_flash() exists; otherwise use ?msg=
function redirect_with_message($url, $msg = '') {
    if (function_exists('set_flash')) {
        if ($msg !== '') set_flash($msg);
        header("Location: {$url}");
    } else {
        $url .= ($msg !== '') ? (strpos($url, '?') === false ? "?msg=" . urlencode($msg) : "&msg=" . urlencode($msg)) : '';
        header("Location: {$url}");
    }
    exit;
}

// Admin guard
if (empty($_SESSION['user_id']) || (int)($_SESSION['is_admin'] ?? 0) !== 1) {
    redirect_with_message('/techcenter/public/login.php', 'You must be an admin to perform that action.');
}

// Validate ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($id <= 0) {
    redirect_with_message('/techcenter/public/admin_products.php', 'Invalid product id.');
}

// If POST -> perform deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // fetch the product first so we can remove image files
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        if (!$product) {
            redirect_with_message('/techcenter/public/admin_products.php', 'Product not found.');
        }

        // delete DB row
        $del = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $del->execute(['id' => $id]);

        // delete image files (original + thumbs) if exist
        if (!empty($product['image'])) {
            $uploadsDir = __DIR__ . '/uploads/';
            $original = $uploadsDir . $product['image'];
            $thumb    = $uploadsDir . 'thumbs/' . $product['image'];

            if (file_exists($original) && is_file($original)) {
                @unlink($original);
            }
            if (file_exists($thumb) && is_file($thumb)) {
                @unlink($thumb);
            }
        }

        redirect_with_message('/techcenter/public/admin_products.php', 'Product deleted successfully.');

    } catch (Exception $e) {
        // on error return with message (avoid exposing raw SQL error to users)
        redirect_with_message('/techcenter/public/admin_products.php', 'Failed to delete product.');
    }
}

// If GET -> show confirmation form
// Fetch product summary for display
$stmt = $pdo->prepare("SELECT id, title, price, image FROM products WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    redirect_with_message('/techcenter/public/admin_products.php', 'Product not found.');
}

include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="max-width:700px;margin:0 auto">
  <h2>Delete Product</h2>

  <div style="background: rgba(255,0,0,0.03); padding:12px; border-radius:8px; margin-bottom:12px;">
    <strong style="color:#ffd400">Warning:</strong>
    You are about to permanently delete this product. This action cannot be undone.
  </div>

  <div style="display:flex;gap:14px;align-items:center;margin-bottom:12px;">
    <?php if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])): ?>
      <img src="/techcenter/public/uploads/<?= e($product['image']) ?>" alt="" style="width:120px;height:90px;object-fit:cover;border-radius:8px;">
    <?php endif; ?>
    <div>
      <div style="font-weight:700;font-size:18px;"><?= e($product['title']) ?></div>
      <div style="color:#ddd;">Price: ₹<?= isset($product['price']) ? number_format($product['price'],2) : '—' ?></div>
    </div>
  </div>

  <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this product?');">
    <input type="hidden" name="id" value="<?= e($product['id']) ?>">
    <div style="display:flex;gap:10px">
      <button type="submit" class="btn primary">Yes, delete</button>
      <a href="/techcenter/public/admin_products.php" class="btn ghost">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
