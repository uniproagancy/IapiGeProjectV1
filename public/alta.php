<?php

// public/alta_suggestions_test.php
// გაუშვი: https://iapi.ge/alta_suggestions_test.php

// DB კონექცია
$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_DATABASE') ?: 'unipro_iapi';
$username = getenv('DB_USERNAME') ?: 'unipro_iapi';
$password = getenv('DB_PASSWORD') ?: 'TANC7RKMWaMTyuzbncXQ';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
}

// quantity > 2 პირველი 20
$stmt = $pdo->query("SELECT product_id, quantity FROM db_alta WHERE quantity > 2 LIMIT 20");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    die(json_encode(['error' => 'db_alta-ში quantity > 2 ჩანაწერი არ არის']));
}

echo "ნაპოვნია " . count($items) . " პროდუქტი quantity > 2\n\n";

$results  = [];
$success  = 0;
$failed   = 0;

foreach ($items as $item) {
    $productId = $item['product_id'];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://alta.ge/api/proxy/v1/Products/search/suggestions?query=' . $productId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => 'gzip, deflate',
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: ka',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
            'Referer: https://alta.ge/',
            'os: web',
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error    = curl_error($curl);
    curl_close($curl);

    $data = json_decode($response, true);

    $results[] = [
        'product_id' => $productId,
        'quantity'   => $item['quantity'],
        'http_code'  => $httpCode,
        'curl_error' => $error ?: null,
        'response'   => $data ?? substr($response, 0, 200),
    ];

    if ($httpCode === 200) $success++;
    else $failed++;

    // სერვერს ნუ დავაყრდობინებთ
    usleep(300000); // 0.3 წამი
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'summary' => [
        'total'   => count($items),
        'success' => $success,
        'failed'  => $failed,
    ],
    'results' => $results,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);