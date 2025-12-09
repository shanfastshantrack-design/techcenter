<?php
$page_title = "Admin – Products";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// Admin protection
if (empty($_SESSION['user_id']) || (int)($_SESSION['is_admin'] ?? 0) !== 1) {
    set_flash("Admin access only.");
    header("Location: /techcenter/public/login.php");
    exit;
}

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../src/views/header.php';
?>

<style>
/* Container Card */
.admin-panel {
    max-width: 1050px;
    margin: 40px auto;
    background: #0c0c0c;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 0 25px rgba(0,0,0,0.5);
}

/* Admin Navigation */
.admin-nav {
    display: flex;
    gap: 15px;
    margin-bottom: 18px;
}

.admin-nav a {
    padding: 8px 18px;
    border-radius: 8px;
    background: #111;
    color: #f1c40f;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #2a2a2a;
}

.admin-nav a.active {
    background: #f1c40f;
    color: #000;
}

/* Add Product Dropdown */
.dropdown-box {
    position: relative;
    display: inline-block;
}

.dropdown-btn {
    padding: 8px 18px;
    background: #f1c40f;
    color: #000;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 42px;
    right: 0;
    width: 180px;
    background: #000;                /* SOLID BLACK */
    border: 1px solid #222;
    border-radius: 10px;
    padding: 10px;
    z-index: 9999;
    box-shadow: 0 8px 20px rgba(0,0,0,0.6);
}

.dropdown-menu a {
    display: block;
    padding: 10px;
    background: #111;
    border-radius: 6px;
    margin-bottom: 6px;
    color: #f1c40f;
    text-decoration: none;
    font-size: 15px;
}

.dropdown-menu a:hover {
    background: #222;
}

.dropdown-box:hover .dropdown-menu {
    display: block;
}

/* Product Table */
.admin-table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
    color: white;
}

.admin-table th, 
.admin-table td {
    padding: 10px;
    border-bottom: 1px solid #222;
}

.admin-table th {
    background: #111;
    color: #f1c40f;
}

.admin-table img {
    width: 60px;
    height: 80px;
    object-fit: cover;       /* NO CROPPING */
    border-radius: 6px;
    box-shadow: 0 0 10px rgba(0,0,0,0.5);
}

.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 14px;
    border: 1px solid #f1c40f;
    background: transparent;
    color: #f1c40f;
    text-decoration: none;
    font-weight: 600;
}

.action-btn:hover {
    background: #f1c40f;
    color: #000;
}
</style>

<div class="admin-panel">

    <h2 style="margin-bottom:15px;text-align:left;color:#fff;">Products</h2>

    <!-- ADMIN NAV -->
    <div class="admin-nav">
        <a href="/techcenter/public/admin_dashboard.php">Dashboard</a>
        <a href="/techcenter/public/admin_products.php" class="active">Products</a>
        <a href="/techcenter/public/admin_users.php">Users</a>
        <a href="/techcenter/public/admin_orders.php">Orders</a>
        <a href="/techcenter/public/logout.php">Logout</a>

        <!-- ADD PRODUCT DROPDOWN -->
        <div class="dropdown-box" style="margin-left:auto;">
            <div class="dropdown-btn">Add product ▼</div>
            <div class="dropdown-menu">
                <a href="add_product.php?category=phones">Phones</a>
                <a href="add_product.php?category=accessories">Accessories</a>
                <a href="add_product.php?category=cases">Cases</a>
                <a href="add_product.php?category=chargers">Chargers</a>
                <a href="add_product.php?category=earbuds">Earbuds</a>
                <a href="add_product.php?category=powerbanks">Powerbanks</a>
            </div>
        </div>
    </div>

    <!-- PRODUCT TABLE -->
    <table class="admin-table">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Brand</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Image</th>
            <th>Added</th>
            <th>Actions</th>
        </tr>

        <?php foreach ($products as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= e($p['title']) ?></td>
            <td><?= e($p['brand']) ?></td>
            <td><?= ucfirst($p['category']) ?></td>
            <td>₹<?= number_format($p['price']) ?></td>
            <td><?= $p['stock'] ?></td>

            <td>
                <img src="/techcenter/public/uploads/<?= e($p['image']) ?>" alt="">
            </td>

            <td><?= $p['created_at'] ?></td>

            <td>
                <a class="action-btn" href="edit_product.php?id=<?= $p['id'] ?>">Edit</a>
                <a class="action-btn" href="delete_product.php?id=<?= $p['id'] ?>" 
                   onclick="return confirm('Are you sure you want to delete this product?');">
                   Delete
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
