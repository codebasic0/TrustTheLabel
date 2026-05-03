<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$dbname = 'expose_the_label';
$user = 'root';
$pass = '';

function getConnection($host, $dbname, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}

function buildProduct($pdo, $row) {
    $hs = $pdo->prepare("SELECT name, effect FROM harmful_substances WHERE product_id = ?");
    $hs->execute([$row['id']]);

    $sa = $pdo->prepare("SELECT name, brand FROM safer_alternatives WHERE product_id = ?");
    $sa->execute([$row['id']]);

    return [
        'name'               => $row['name'],
        'brand'              => $row['brand'],
        'image'              => $row['image'],
        'barcode'            => $row['barcode'],
        'category'           => $row['category'],
        'ingredients'        => json_decode($row['ingredients'], true) ?? [],
        'allergens'          => json_decode($row['allergens'], true) ?? [],
        'harmfulSubstances'  => $hs->fetchAll(),
        'nutrition'          => [
            'calories'   => (float) $row['calories'],
            'sugar_g'    => (float) $row['sugar_g'],
            'fat_g'      => (float) $row['fat_g'],
            'sodium_mg'  => (float) $row['sodium_mg'],
        ],
        'riskLevel'          => $row['risk_level'],
        'saferAlternatives'  => $sa->fetchAll(),
        'source'             => $row['source'],
    ];
}

$pdo    = getConnection($host, $dbname, $user, $pass);
$action = $_GET['action'] ?? 'all';

if ($action === 'all') {
    $rows   = $pdo->query("SELECT * FROM products ORDER BY category, name")->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[$row['category']][] = buildProduct($pdo, $row);
    }
    echo json_encode($result);

} elseif ($action === 'product') {
    $barcode = $_GET['id'] ?? '';
    $stmt    = $pdo->prepare("SELECT * FROM products WHERE barcode = ?");
    $stmt->execute([$barcode]);
    $row = $stmt->fetch();
    echo $row ? json_encode(buildProduct($pdo, $row)) : json_encode(null);

} elseif ($action === 'categories') {
    $rows = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll();
    echo json_encode(array_column($rows, 'category'));

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
