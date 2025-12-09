<?php
// public/edit_product.php
$page_title = "Edit Product";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// Admin-only guard
if (empty($_SESSION['user_id']) || (int)($_SESSION['is_admin'] ?? 0) !== 1) {
    set_flash('Admin access only.');
    header('Location: /techcenter/public/login.php');
    exit;
}

// Allowed image mime types and extension map (same as add_product)
$ALLOWED = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp'
];

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// categories list (edit as needed)
$categories = ['phones','accessories','cases','chargers','cables','general'];

// get product id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../src/views/header.php';
    echo '<div class="panel" style="max-width:700px;margin:40px auto;color:#fff">Invalid product id.</div>';
    include __DIR__ . '/../src/views/footer.php';
    exit;
}

// fetch product
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $product = false;
    $db_err = $e->getMessage();
}

if (!$product) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../src/views/header.php';
    echo '<div class="panel" style="max-width:700px;margin:40px auto;color:#fff">Product not found.</div>';
    include __DIR__ . '/../src/views/footer.php';
    exit;
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read & trim
    $title  = trim($_POST['title'] ?? '');
    $brand  = trim($_POST['brand'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $color  = trim($_POST['color'] ?? '');
    $price  = trim($_POST['price'] ?? '');
    $stock  = trim($_POST['stock'] ?? '');
    $short_desc = trim($_POST['short_desc'] ?? '');
    $long_desc  = trim($_POST['long_desc'] ?? '');

    // validation (similar to add_product)
    if ($title === '') $errors[] = 'Title is required.';
    if ($brand === '') $errors[] = 'Brand is required.';
    if ($category === '') $errors[] = 'Category is required.';
    if ($color === '') $errors[] = 'Color is required.';
    if ($price === '' || !is_numeric($price)) $errors[] = 'Price is required and must be a number.';
    if ($stock === '' || !ctype_digit((string)$stock)) $errors[] = 'Stock is required and must be an integer.';
    if ($short_desc === '') $errors[] = 'Short description is required.';
    if ($long_desc === '') $errors[] = 'Long description is required.';

    // handle image replacement (URL or file)
    $new_image_filename = null;
    $image_url = trim($_POST['image_url'] ?? '');
    $image_file = $_FILES['image_file'] ?? null;

    if ($image_url !== '') {
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Image URL is not valid.';
        } else {
            $headers = @get_headers($image_url, 1);
            if (!$headers || stripos($headers[0], '200') === false) {
                $errors[] = 'Image URL not reachable or returned non-200.';
            } else {
                $contentType = null;
                if (!empty($headers['Content-Type'])) {
                    $contentType = is_array($headers['Content-Type']) ? $headers['Content-Type'][0] : $headers['Content-Type'];
                } else {
                    $info = @getimagesize($image_url);
                    $contentType = $info['mime'] ?? null;
                }

                if (!$contentType || !isset($ALLOWED[$contentType])) {
                    $errors[] = 'Remote image type not supported: ' . ($contentType ?? 'unknown');
                } else {
                    $imgData = @file_get_contents($image_url);
                    if ($imgData === false || strlen($imgData) < 20) {
                        // cURL fallback
                        $ch = curl_init($image_url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                        $imgData = curl_exec($ch);
                        $curlErr = curl_error($ch);
                        curl_close($ch);
                        if ($imgData === false || strlen($imgData) < 20) {
                            $errors[] = 'Could not download image from URL. ' . ($curlErr ?: '');
                        }
                    }

                    if (empty($errors) && !empty($imgData)) {
                        $ext = $ALLOWED[$contentType];
                        $new_image_filename = bin2hex(random_bytes(8)) . '.' . $ext;
                        $fullPath = $uploadDir . $new_image_filename;
                        if (@file_put_contents($fullPath, $imgData) === false) {
                            $errors[] = 'Failed to save remote image to server.';
                            $new_image_filename = null;
                        } else {
                            @chmod($fullPath, 0644);
                        }
                    }
                }
            }
        }
    }

    // file upload fallback
    if (empty($new_image_filename) && $image_file && isset($image_file['tmp_name']) && $image_file['tmp_name'] !== '') {
        if ($image_file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload error: ' . $image_file['error'];
        } else {
            $info = @getimagesize($image_file['tmp_name']);
            $mime = $info['mime'] ?? null;
            if (!$mime || !isset($ALLOWED[$mime])) {
                $errors[] = 'Uploaded file is not a supported image type.';
            } else {
                $ext = $ALLOWED[$mime];
                $new_image_filename = bin2hex(random_bytes(8)) . '.' . $ext;
                $dest = $uploadDir . $new_image_filename;
                if (!move_uploaded_file($image_file['tmp_name'], $dest)) {
                    $errors[] = 'Failed to move uploaded image.';
                    $new_image_filename = null;
                } else {
                    @chmod($dest, 0644);
                }
            }
        }
    }

    // If there are no errors, perform update
    if (empty($errors)) {
        try {
            // prepare update query
            $sql = "UPDATE products SET
                      title = :title,
                      brand = :brand,
                      category = :category,
                      color = :color,
                      price = :price,
                      stock = :stock,
                      short_desc = :short_desc,
                      long_desc = :long_desc,
                      updated_at = NOW()";

            $params = [
                'title' => $title,
                'brand' => $brand,
                'category' => $category,
                'color' => $color,
                'price' => $price,
                'stock' => $stock,
                'short_desc' => $short_desc,
                'long_desc' => $long_desc,
                'id' => $id
            ];

            if (!empty($new_image_filename)) {
                $sql .= ", image = :image";
                $params['image'] = $new_image_filename;
            }

            $sql .= " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // if image replaced, delete old file (safe)
            if (!empty($new_image_filename) && !empty($product['image'])) {
                $oldPath = $uploadDir . $product['image'];
                if (file_exists($oldPath) && is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // reload product for form
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            $success = 'Product updated successfully.';
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            // clean up newly uploaded image on failure
            if (!empty($new_image_filename) && file_exists($uploadDir . $new_image_filename)) {
                @unlink($uploadDir . $new_image_filename);
            }
        }
    }
}

include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="max-width:920px;margin:0 auto">
  <h2>Edit Product — <?= e($product['title']) ?></h2>

  <?php if ($success): ?>
    <div style="background:#d4ffb2;color:#111;padding:10px;border-radius:8px;margin-bottom:12px;"><?= e($success) ?></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div style="background:#ffdddd;color:#900;padding:10px;border-radius:8px;margin-bottom:12px;">
      <ul style="margin:0 0 0 18px;">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="display:grid;gap:12px">
    <label>Title
      <input type="text" name="title" value="<?= e($_POST['title'] ?? $product['title']) ?>" required>
    </label>

    <label>Brand
      <input type="text" name="brand" value="<?= e($_POST['brand'] ?? $product['brand']) ?>" required>
    </label>

    <label>Category
      <select name="category" required>
        <?php foreach ($categories as $c): ?>
          <option value="<?= e($c) ?>" <?= (($_POST['category'] ?? $product['category']) == $c) ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Color
      <input type="text" name="color" value="<?= e($_POST['color'] ?? $product['color']) ?>" required>
    </label>

    <label>Price
      <input type="text" name="price" value="<?= e($_POST['price'] ?? $product['price']) ?>" required>
    </label>

    <label>Stock
      <input type="number" name="stock" value="<?= e($_POST['stock'] ?? $product['stock']) ?>" min="0" required>
    </label>

    <label>Short Description
      <input type="text" name="short_desc" value="<?= e($_POST['short_desc'] ?? $product['short_desc']) ?>" maxlength="255" required>
    </label>

    <label>Long Description
      <textarea name="long_desc" rows="6" required><?= e($_POST['long_desc'] ?? $product['long_desc']) ?></textarea>
    </label>

    <hr style="border:none;border-top:1px solid rgba(255,255,255,0.04);margin:12px 0">

    <h4>Current Image</h4>
    <div style="margin-bottom:8px;">
      <?php if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])): ?>
        <img src="/techcenter/public/uploads/<?= e($product['image']) ?>" alt="" style="width:120px;height:auto;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.4);">
      <?php else: ?>
        <div style="width:120px;height:160px;border-radius:8px;background:rgba(255,255,255,0.03);display:flex;align-items:center;justify-content:center;color:#aaa;">No image</div>
      <?php endif; ?>
    </div>

    <h4>Replace Image (optional)</h4>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start;">
      <div style="flex:1;min-width:220px">
        <label>Upload file
          <input type="file" name="image_file" accept="image/*">
        </label>
      </div>

      <div style="flex:2;min-width:260px">
        <label>Image URL (paste link)
          <input type="url" name="image_url" placeholder="https://example.com/image.jpg" value="<?= e($_POST['image_url'] ?? '') ?>">
        </label>
        <div class="muted" style="font-size:13px">If you supply a URL or upload a file the image will be replaced.</div>
      </div>
    </div>

    <div style="margin-top:12px;display:flex;gap:10px">
      <button class="btn primary" type="submit">Save Changes</button>
      <a class="btn ghost" href="/techcenter/public/admin_products.php">Back to Admin</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
