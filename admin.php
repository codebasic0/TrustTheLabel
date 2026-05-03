<?php
$host   = 'localhost';
$dbname = 'expose_the_label';
$user   = 'root';
$pass   = '';

function getDB($host, $dbname, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die('<div class="alert error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>');
    }
}

$pdo     = getDB($host, $dbname, $user, $pass);
$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';

    if ($action === 'delete') {
        $id   = (int) $_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Product deleted successfully.';
        $msgType = 'success';
    }

    if ($action === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $brand    = trim($_POST['brand'] ?? '');
        $image    = trim($_POST['image'] ?? '');
        $barcode  = trim($_POST['barcode'] ?? '');
        $category = trim($_POST['category_select'] ?? '');
        if ($category === '__other__') {
            $category = trim($_POST['category_custom'] ?? '');
        }
        $calories  = (float) ($_POST['calories'] ?? 0);
        $sugar_g   = (float) ($_POST['sugar_g'] ?? 0);
        $fat_g     = (float) ($_POST['fat_g'] ?? 0);
        $sodium_mg = (float) ($_POST['sodium_mg'] ?? 0);
        $risk      = $_POST['risk_level'] ?? 'Medium';
        $source    = trim($_POST['source'] ?? '');

        $rawIngredients = array_values(array_filter(array_map('trim', $_POST['ingredients'] ?? [])));
        $rawAllergens   = array_values(array_filter(array_map('trim', $_POST['allergens'] ?? [])));

        $hsNames   = $_POST['hs_name'] ?? [];
        $hsEffects = $_POST['hs_effect'] ?? [];
        $saNames   = $_POST['sa_name'] ?? [];
        $saBrands  = $_POST['sa_brand'] ?? [];

        if (!$name || !$brand || !$barcode || !$category) {
            $message = 'Name, Brand, Barcode and Category are required.';
            $msgType = 'error';
        } else {
            try {
                $pdo->beginTransaction();

                $ins = $pdo->prepare("
                    INSERT INTO products (name, brand, image, barcode, category, ingredients, allergens, calories, sugar_g, fat_g, sodium_mg, risk_level, source)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([
                    $name, $brand, $image, $barcode, $category,
                    json_encode($rawIngredients),
                    json_encode($rawAllergens),
                    $calories, $sugar_g, $fat_g, $sodium_mg, $risk, $source
                ]);
                $productId = $pdo->lastInsertId();

                $hsStmt = $pdo->prepare("INSERT INTO harmful_substances (product_id, name, effect) VALUES (?, ?, ?)");
                foreach ($hsNames as $i => $hsName) {
                    $hsName   = trim($hsName);
                    $hsEffect = trim($hsEffects[$i] ?? '');
                    if ($hsName && $hsEffect) {
                        $hsStmt->execute([$productId, $hsName, $hsEffect]);
                    }
                }

                $saStmt = $pdo->prepare("INSERT INTO safer_alternatives (product_id, name, brand) VALUES (?, ?, ?)");
                foreach ($saNames as $i => $saName) {
                    $saName  = trim($saName);
                    $saBrand = trim($saBrands[$i] ?? '');
                    if ($saName && $saBrand) {
                        $saStmt->execute([$productId, $saName, $saBrand]);
                    }
                }

                $pdo->commit();
                $message = 'Product "' . htmlspecialchars($name) . '" added successfully!';
                $msgType = 'success';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = 'Error: ' . $e->getMessage();
                $msgType = 'error';
            }
        }
    }
}

$products   = $pdo->query("SELECT p.id, p.name, p.brand, p.category, p.risk_level, p.barcode FROM products p ORDER BY p.category, p.name")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$knownCats  = array_unique(array_merge($categories, [
    'Biscuits','Snacks','Potato Chips','Soft Drinks','Energy Drinks',
    'Instant Noodles','Breakfast Cereals','Chocolates','Ice Creams','Packaged Juices'
]));
sort($knownCats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Expose The Label</title>
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --bg: #0d0f14;
        --surface: #161a22;
        --surface2: #1e2330;
        --border: #2a3040;
        --accent: #00e5a0;
        --accent2: #00b87a;
        --danger: #ff4f4f;
        --warn: #f5a623;
        --text: #e8ecf4;
        --muted: #7a8499;
        --low: #00e5a0;
        --medium: #f5a623;
        --high: #ff4f4f;
        --radius: 10px;
    }
    body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; }
    a { color: var(--accent); text-decoration: none; }
    a:hover { text-decoration: underline; }

    .topbar {
        background: var(--surface);
        border-bottom: 1px solid var(--border);
        padding: 14px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .topbar .logo { font-size: 1.3rem; font-weight: 700; letter-spacing: -0.5px; }
    .topbar .logo span { color: var(--accent); }
    .topbar .back-link { font-size: 0.85rem; color: var(--muted); }

    .page { max-width: 1200px; margin: 0 auto; padding: 32px 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start; }

    .panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    .panel-header { padding: 18px 24px; border-bottom: 1px solid var(--border); }
    .panel-header h2 { font-size: 1.05rem; font-weight: 600; }
    .panel-header p { font-size: 0.8rem; color: var(--muted); margin-top: 2px; }
    .panel-body { padding: 24px; }

    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; }
    .alert.success { background: rgba(0,229,160,0.12); border: 1px solid var(--accent); color: var(--accent); }
    .alert.error   { background: rgba(255,79,79,0.12); border: 1px solid var(--danger); color: var(--danger); }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-group { margin-bottom: 16px; }
    .form-group.full { grid-column: 1 / -1; }
    label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    input[type=text], input[type=number], input[type=url], select, textarea {
        width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 7px;
        color: var(--text); padding: 9px 12px; font-size: 0.9rem; outline: none; font-family: inherit;
        transition: border-color 0.2s;
    }
    input:focus, select:focus, textarea:focus { border-color: var(--accent); }
    select option { background: var(--surface2); }
    textarea { resize: vertical; min-height: 60px; }

    .section-label {
        font-size: 0.82rem; font-weight: 700; color: var(--accent); text-transform: uppercase;
        letter-spacing: 0.6px; border-bottom: 1px solid var(--border); padding-bottom: 8px; margin: 22px 0 14px;
    }

    .dynamic-list { display: flex; flex-direction: column; gap: 8px; }
    .dynamic-row { display: flex; gap: 8px; align-items: flex-start; }
    .dynamic-row input, .dynamic-row textarea { flex: 1; }
    .dynamic-row textarea { min-height: 52px; }
    .btn-remove {
        background: rgba(255,79,79,0.12); border: 1px solid rgba(255,79,79,0.3); color: var(--danger);
        border-radius: 6px; padding: 6px 10px; cursor: pointer; font-size: 0.85rem; white-space: nowrap;
        transition: background 0.2s; flex-shrink: 0; margin-top: 2px;
    }
    .btn-remove:hover { background: rgba(255,79,79,0.25); }
    .btn-add {
        background: rgba(0,229,160,0.08); border: 1px dashed var(--accent2); color: var(--accent);
        border-radius: 7px; padding: 8px 14px; cursor: pointer; font-size: 0.85rem; font-weight: 600;
        width: 100%; transition: background 0.2s; margin-top: 6px;
    }
    .btn-add:hover { background: rgba(0,229,160,0.15); }

    .hs-pair { display: grid; grid-template-columns: 1fr; gap: 6px; flex: 1; }
    .hs-pair input { width: 100%; }
    .hs-pair textarea { width: 100%; }

    .btn-submit {
        background: var(--accent); color: #0d0f14; border: none; border-radius: 8px;
        padding: 12px 28px; font-size: 1rem; font-weight: 700; cursor: pointer; width: 100%; margin-top: 8px;
        transition: background 0.2s;
    }
    .btn-submit:hover { background: var(--accent2); }

    .products-table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    thead th { text-align: left; padding: 10px 12px; font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
    tbody td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: var(--surface2); }
    .badge {
        display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 0.72rem; font-weight: 700;
    }
    .badge-Low    { background: rgba(0,229,160,0.15); color: var(--low); }
    .badge-Medium { background: rgba(245,166,35,0.15); color: var(--medium); }
    .badge-High   { background: rgba(255,79,79,0.15); color: var(--high); }
    .btn-del {
        background: rgba(255,79,79,0.1); border: 1px solid rgba(255,79,79,0.3); color: var(--danger);
        border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 0.8rem;
        transition: background 0.2s;
    }
    .btn-del:hover { background: rgba(255,79,79,0.25); }
    .cat-tag { font-size: 0.72rem; color: var(--muted); }
    .count-bar { padding: 10px 24px; font-size: 0.82rem; color: var(--muted); border-bottom: 1px solid var(--border); }

    #customCategoryWrap { display: none; margin-top: 8px; }
    @media (max-width: 900px) { .page { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="topbar">
    <div class="logo">Expose<span>TheLabel</span> <span style="font-weight:400;color:var(--muted);font-size:0.85rem;">Admin</span></div>
    <a class="back-link" href="index.html">← Back to site</a>
</div>

<div class="page">

    <div class="panel">
        <div class="panel-header">
            <h2>Add New Product</h2>
            <p>Fill in all fields. Harmful substances and safer alternatives can be added dynamically.</p>
        </div>
        <div class="panel-body">
            <?php if ($message): ?>
                <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" action="admin.php">
                <input type="hidden" name="_action" value="add">

                <div class="section-label">Basic Info</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" placeholder="e.g. Honey Rings" required>
                    </div>
                    <div class="form-group">
                        <label>Brand *</label>
                        <input type="text" name="brand" placeholder="e.g. MorningGrain" required>
                    </div>
                    <div class="form-group">
                        <label>Barcode *</label>
                        <input type="text" name="barcode" placeholder="e.g. 8901234560099" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_select" id="categorySelect" onchange="toggleCustomCat(this)">
                            <?php foreach ($knownCats as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="__other__">+ New Category...</option>
                        </select>
                        <div id="customCategoryWrap">
                            <input type="text" name="category_custom" placeholder="Enter new category name">
                        </div>
                    </div>
                    <div class="form-group full">
                        <label>Image Path (relative or URL)</label>
                        <input type="text" name="image" placeholder="e.g. images/product-name.jpg">
                    </div>
                    <div class="form-group full">
                        <label>Source URL</label>
                        <input type="text" name="source" placeholder="https://example.com/product">
                    </div>
                </div>

                <div class="section-label">Risk Level</div>
                <div class="form-group">
                    <label>Health Risk *</label>
                    <select name="risk_level">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <div class="section-label">Nutrition (per 100g)</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Calories (kcal)</label>
                        <input type="number" name="calories" step="0.01" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Sugar (g)</label>
                        <input type="number" name="sugar_g" step="0.01" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Fat (g)</label>
                        <input type="number" name="fat_g" step="0.01" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Sodium (mg)</label>
                        <input type="number" name="sodium_mg" step="0.01" placeholder="0">
                    </div>
                </div>

                <div class="section-label">Ingredients</div>
                <div class="dynamic-list" id="ingredientsList">
                    <div class="dynamic-row">
                        <input type="text" name="ingredients[]" placeholder="e.g. Wheat Flour">
                        <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addRow('ingredientsList','ingredients[]','Ingredient')">+ Add Ingredient</button>

                <div class="section-label">Allergens</div>
                <div class="dynamic-list" id="allergensList">
                    <div class="dynamic-row">
                        <input type="text" name="allergens[]" placeholder="e.g. Wheat">
                        <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addRow('allergensList','allergens[]','Allergen')">+ Add Allergen</button>

                <div class="section-label">⚠️ Harmful Substances</div>
                <div class="dynamic-list" id="hsList">
                    <div class="dynamic-row">
                        <div class="hs-pair">
                            <input type="text" name="hs_name[]" placeholder="Substance name (e.g. High sodium)">
                            <textarea name="hs_effect[]" placeholder="Describe the health effect..."></textarea>
                        </div>
                        <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addHSRow()">+ Add Harmful Substance</button>

                <div class="section-label">✅ Safer Alternatives</div>
                <div class="dynamic-list" id="saList">
                    <div class="dynamic-row">
                        <input type="text" name="sa_name[]" placeholder="Alternative name">
                        <input type="text" name="sa_brand[]" placeholder="Brand">
                        <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addSARow()">+ Add Alternative</button>

                <button type="submit" class="btn-submit" style="margin-top:28px;">Add Product →</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Existing Products</h2>
            <p>All products currently in the database.</p>
        </div>
        <div class="count-bar"><?= count($products) ?> products across <?= count($knownCats) ?> categories</div>
        <div class="products-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Risk</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($p['brand']) ?></td>
                        <td><span class="cat-tag"><?= htmlspecialchars($p['category']) ?></span></td>
                        <td><span class="badge badge-<?= $p['risk_level'] ?>"><?= $p['risk_level'] ?></span></td>
                        <td>
                            <form method="POST" action="admin.php" onsubmit="return confirm('Delete this product?')">
                                <input type="hidden" name="_action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-del">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function removeRow(btn) {
    const row = btn.closest('.dynamic-row');
    const list = row.parentElement;
    if (list.children.length > 1) {
        row.remove();
    }
}

function addRow(listId, fieldName, placeholder) {
    const list = document.getElementById(listId);
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `<input type="text" name="${fieldName}" placeholder="${placeholder}"><button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>`;
    list.appendChild(row);
}

function addHSRow() {
    const list = document.getElementById('hsList');
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `<div class="hs-pair"><input type="text" name="hs_name[]" placeholder="Substance name (e.g. High sodium)"><textarea name="hs_effect[]" placeholder="Describe the health effect..."></textarea></div><button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>`;
    list.appendChild(row);
}

function addSARow() {
    const list = document.getElementById('saList');
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `<input type="text" name="sa_name[]" placeholder="Alternative name"><input type="text" name="sa_brand[]" placeholder="Brand"><button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>`;
    list.appendChild(row);
}

function toggleCustomCat(sel) {
    const wrap = document.getElementById('customCategoryWrap');
    wrap.style.display = sel.value === '__other__' ? 'block' : 'none';
}
</script>
</body>
</html>
