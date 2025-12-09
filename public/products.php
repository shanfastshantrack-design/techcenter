<?php
// public/products.php
$page_title = "Shop";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// Check admin
$isAdmin = !empty($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;

// Category filter
$category = $_GET['category'] ?? 'all';

// Fetch products
try {
    if ($category === 'all') {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category = :cat ORDER BY id DESC");
        $stmt->execute(['cat' => $category]);
    }
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $products = [];
    $db_error = $e->getMessage();
}

include __DIR__ . '/../src/views/header.php';
?>

<style>
/* ==== MINIMAL MODERN STYLING ==== */

.page-wrapper {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px 10px;
    color:#fff;
}

/* Category tabs */
.tab-row {
    display: flex;
    gap: 12px;
    margin-bottom: 18px;
}
.tab {
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    color: #ffd400;
    border: 1px solid rgba(255,212,0,0.12);
    transition: 0.2s;
}
.tab.active {
    background: #ffd400;
    color: #111;
    border-color: #ffd400;
}
.tab:hover {
    border-color: rgba(255,212,0,0.4);
}

/* Admin button */
.btn-admin {
    padding: 8px 12px;
    background: #ffd400;
    color: #111;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 700;
}

/* User admin-block */
.btn-admin-ghost {
    padding: 8px 12px;
    border-radius: 8px;
    background: transparent;
    border: 1px solid rgba(255,212,0,0.2);
    color: #ffd400;
    font-weight: 700;
}

/* Product grid */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(240px,1fr));
    gap: 20px;
}

/* Product card */
.card {
    background: rgba(255,255,255,0.03);
    padding: 14px;
    border-radius: 14px;
    backdrop-filter: blur(6px);
    transition: 0.25s ease;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 26px rgba(0,0,0,0.35);
}

/* IMAGE CROPPING 5:5 (tall phone shape) */
.img-wrap {
    width: 100%;
    aspect-ratio: 5/5; /* ★ NEW ASPECT RATIO 5:5 ★ */
    overflow: hidden;
    border-radius: 12px;
    background: rgba(255,255,255,0.06);
}
.img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

/* Buttons */
.card .actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
}
.btn {
    padding: 8px 10px;
    border-radius: 8px;
    text-decoration:none;
    font-weight:600;
    color:#ffd400;
    border:1px solid rgba(255,212,0,0.18);
}
.btn:hover {
    border-color: rgba(255,212,0,0.4);
}
.btn.buy {
    background:#ffd400;
    color:#111;
    font-weight:800;
    border:none;
}

/* Modal */
#adminModal {
    position:fixed;
    inset:0;
    backdrop-filter: blur(3px);
    background:rgba(0,0,0,0.6);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}
#adminModal .box {
    background:rgba(255,255,255,0.08);
    padding:20px;
    border-radius:12px;
    max-width:380px;
    width:90%;
    color:#fff;
    text-align:left;
}
</style>

<div class="page-wrapper">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h2 style="margin:0;font-weight:700;">Shop</h2>

        <?php if ($isAdmin): ?>
            <a href="/techcenter/public/admin_products.php" class="btn-admin">← Back to Admin</a>
        <?php else: ?>
            <button id="adminOnlyBtn" class="btn-admin-ghost">Admin Panel</button>
        <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div class="tab-row">
        <a class="tab <?= $category=='all'?'active':'' ?>" href="/techcenter/public/products.php">All</a>
        <a class="tab <?= $category=='phones'?'active':'' ?>" href="/techcenter/public/products.php?category=phones">Phones</a>
        <a class="tab <?= $category=='accessories'?'active':'' ?>" href="/techcenter/public/products.php?category=accessories">Accessories</a>
    </div>

    <?php if (!empty($db_error)): ?>
        <p style="color:#ff7777;">Error: <?= e($db_error) ?></p>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <p style="opacity:0.6;">No products found.</p>
    <?php else: ?>
    <div class="product-grid">

        <?php foreach ($products as $p): ?>
        <div class="card">

            <div class="img-wrap">
                <?php if (!empty($p['image']) && file_exists(__DIR__."/uploads/".$p['image'])): ?>
                    <img src="/techcenter/public/uploads/<?= e($p['image']) ?>">
                <?php else: ?>
                    <img src="/techcenter/public/css/placeholder.png">
                <?php endif; ?>
            </div>

            <h3 style="margin:10px 0 6px;font-size:18px;font-weight:700;"><?= e($p['title']) ?></h3>

            <div style="display:flex;justify-content:space-between;color:#ddd;">
                <span><?= e($p['category']) ?></span>
                <strong style="color:#ffd400;">₹<?= number_format($p['price']) ?></strong>
            </div>

            <div class="actions">
                <a class="btn" href="/techcenter/public/view_product.php?id=<?= e($p['id']) ?>">View</a>
                <a class="btn buy" href="/techcenter/public/view_product.php?id=<?= e($p['id']) ?>">BUY</a>

                <?php if ($isAdmin): ?>
                <a class="btn" href="/techcenter/public/edit_product.php?id=<?= e($p['id']) ?>">Edit</a>
                <a class="btn" onclick="return confirm('Delete this product?')" 
                   href="/techcenter/public/delete_product.php?id=<?= e($p['id']) ?>">Delete</a>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>

    </div>
    <?php endif; ?>
</div>

<!-- MODAL -->
<div id="adminModal">
  <div class="box">
    <h3 style="color:#ffd400;margin-top:0;">Admin Access Only</h3>
    <p>You must be logged in as an administrator to access this area.</p>
    <div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">
        <button id="closeModal" class="btn">Close</button>
        <a href="/techcenter/public/login.php" class="btn-admin">Login as Admin</a>
    </div>
  </div>
</div>

<script>
let adminBtn = document.getElementById("adminOnlyBtn");
let modal = document.getElementById("adminModal");
let closeModal = document.getElementById("closeModal");

if (adminBtn) { adminBtn.onclick = () => modal.style.display = "flex"; }
closeModal.onclick = () => modal.style.display = "none";
modal.onclick = e => { if (e.target === modal) modal.style.display = "none"; }
</script>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
