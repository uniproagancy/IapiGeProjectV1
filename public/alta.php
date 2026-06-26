<?php

// public/alta_suggestions_test.php

$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_DATABASE') ?: 'unipro_iapi';
$username = getenv('DB_USERNAME') ?: 'unipro_iapi';
$password = getenv('DB_PASSWORD') ?: 'TANC7RKMWaMTyuzbncXQ';
$token    = getenv('ALTA_ACCESS_TOKEN') ?: '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die(json_encode(['error' => 'DB: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

$stmt  = $pdo->query("SELECT product_id, quantity FROM db_alta WHERE quantity > 2 LIMIT 20");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    die(json_encode(['error' => 'quantity > 2 ჩანაწერი არ არის'], JSON_UNESCAPED_UNICODE));
}

$results = [];
$success = 0;
$failed  = 0;

foreach ($items as $item) {
    $productId = $item['product_id'];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://alta.ge/api/proxy/v1/Products/search/suggestions?query=' . $productId . '&page=1&pageSize=10',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => 'gzip, deflate',
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json, text/plain, */*',
            'accept-language: ka',
            'os: web',
            'referer: https://alta.ge/',
            'sec-ch-ua: "Google Chrome";v="149", "Chromium";v="149", "Not)A;Brand";v="24"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
            'cookie: alta-access_token=' . $token . '; alta-is_user_session=0',
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
        'items_found'=> isset($data['products']) ? count($data['products']) : 0,
        'first_item' => $data['products'][0] ?? null,
        'raw'        => $data ?? substr($response, 0, 300),
    ];

    if ($httpCode === 200) $success++;
    else $failed++;

    usleep(300000);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'summary' => ['total' => count($items), 'success' => $success, 'failed' => $failed],
    'results' => $results,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);