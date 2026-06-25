<?php

// სატესტო ფაილი — public/alta_test.php
// გაუშვი: https://iapi.ge/alta_test.php

$token = getenv('ALTA_ACCESS_TOKEN') ?: 'u87xozMQwBf8VXOvjJ63qCn4EZWRxmsqJot9BDncIHenc2fRVRMo0NpOIAlwTOyeQQT6QLKDUOp9GNQJICV8mYY57zOZCx%2BKeMkTRrQWxW2IR5MZGqhb%2FHBCQB%2F5vMVwTBVcJeZzc%2Fz3N2QY6E4D5rMs4a1YLtcvyiEtyYCmhuB5%2FiKMO8scKHjCW%2BoRDT1aYPPmiIQEbEN9ugtWYNSib0B843XJ3NYbUm2Gr1FYpFOnDnCgazsuZsadZurxIFI7fVzx5JXN1iU8ByNZ9skHbjO%2BN5thu660Zk4pIgToUBhgsUn5QLrTVwOAbysQYmMvNVY6InOrkMtgn3yEFxK%2F0BSi9uXWeRPG9IyzzfDeh6x1GC3b5h7RP4BMTc6F9vhjrHNp4hxOAgXsHbiyl8XycYY8TcyugnizUbv8P%2BRzwR5q%2FNdKn%2FIJ7zPTkskmswbSEDxPOSB36n41l4IFasWO7to%2FYG2jt2yyCQSE45OxiQBIC143bHy2Gh%2BYx8ZKVKX4GQQYRSswRwkWtgpzaEFv2YI3sDlv1TW3cSY5diczj1bdFh7j0IECdVTlkdYhtvv4EuYg5%2FBCGYr9S4RC3j%2FhG0UaDK3yXjEXHC3xOok2Seh%2BfcAcVft0VKS%2FNkagcSg4ZBY9rKnk6GW2ilsEpS8f4tRixNMHgzqDNkDOlShSvC13C3ClH6cxcqLpooIij38DhMyqdMBrDLdMOOLPshTRwH%2FyoGnLCc%2FS8O01lzUP9kgH9G9h5i9qsZ7aIcfDLxA8ekXPmzMSF23E90n4GHg23cElXAD9MjgXXMH9voGSVkhW%2Bie9HZU3SyiYdU%2BXPjMNbkKF8%2BKDFGMWGwV3mV8D%2B069vJEx2hZw2otcrO1mc%2F8hu32qi89n5I5EDhLQoStEdcM%2FnGbWmxuSz0Jo3nX9A3K0%2FZdvtduRcqS29UliALIfDfPTxL92JIBec2pRDSYRaS5KQ%2FL5jPXYhmKlZdNlzX%2Bo0dVje5QPLGWkf0CAD5Mz06Q7rLq7GbZ3HOQ2PuYbnDuJ';

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL            => 'https://alta.ge/api/proxy/v1/Products/details?productId=49525',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_HTTPHEADER     => [
        'Cookie: alta-access_token=' . $token,
        'Accept: application/json, text/plain, */*',
        'Accept-Language: ka',
        'Referer: https://alta.ge/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
        'os: web',
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error    = curl_error($curl);
curl_close($curl);

header('Content-Type: application/json');
echo json_encode([
    'http_code' => $httpCode,
    'curl_error' => $error ?: null,
    'response'  => json_decode($response, true) ?? $response,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);