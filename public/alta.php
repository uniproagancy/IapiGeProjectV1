<?php

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL            => 'https://dry-king-29d3.royal-sunset-e1c6.workers.dev/?id=49525&token=' . urlencode(getenv('ALTA_ACCESS_TOKEN') ?: 'Q2xTWRP2yINTO0BZjC7cdZpjTKQS9A6Zy5dQWpL%2FgIpMMvvHsEm284G2XzR1yoFUvMY%2BfFm6Wub%2FI1lEf7sbs3542Iua2LG1VMU3f18U6Osh%2BBzD9AaEqb93rpMFVAmhtzXVcgKKFDNXvvKNx%2BJta%2BKYkZJ7TG9SFpz2rR48nDklyYujfKyRPBxQqh3gP1IrBX60lzAp7ebfmRmioq4LWlS8XpIBKrl%2B6jH7R%2BjIEtWvBiIufymg%2FAKRUFo6ygx97NdINHf%2FIjJaZJFCf1y8O0HZq%2BN6iwLoDGK3vdnGEJpvCbf7Uy7IjJdXS4ozR9x4Yw1V6tHa00RfMMTLzyq3X47UgyXieqHPlBx2KxwNk5qSfe9M6UZk5If489GR3ta1K7T%2FOS17PjNzXoMb4QRrCzE3g13TKF6afgRw38pjQiSNlSkBkNmDDFiyLWVWb%2BSE8fmflzBSwKeKV2VbMI0MbXuV19m22eF8yDw5R2fvL2FmAI4wm%2Fgdpq7vFTYPxtRM9AZPXploW11gkGgYOVmChD4L9%2FJJ94LY6xPl9DN9kfZW0BhRxGFXEMqrdzImWvNqT96%2FqIkXSKjE8HLnj84xMptfNe8CyVFZrhUxtUtRYDVy5OO9lgAfuy46mHz%2BmlzzfM7nxbcnjS9IN51rMRNlyb%2F7FU0Zox5avjLgIY5t6TDH3ePoFnZP7y5WysX1rd4KpvOZ2v8C%2FE6ejb5EHPgcoIiXxun4zCZGDOUJqbzBWGLleLj2jSxCSzDiqezPxtL34PkrblY%2Bso1Q3cVXbszePpRd7JFJiX2iG%2B7wN5Bs9ieeDYJGdiFav%2FF9N2UMXLf3aRYPTJpLal754mVK%2FAg727Y4Bi9aVtD7fiN5E3%2FDF%2Fp%2FUgv7Qu2HNFQhr5jy781ZyzWQeQKBaXu1yzhc6djJ%2BC%2FePxAByQwa3rOiBzyci1Go6WbcQn7bto4FerE7jGWQbJIambE0shB2r9HyTlk5kWf%2Bko3%2Fs2cM89fu5C32I5X9Os1wrfVX%2BkqZfnkRKEFefvte'),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => '',
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => 'GET',
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error    = curl_error($curl);
curl_close($curl);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'http_code'  => $httpCode,
    'curl_error' => $error ?: null,
    'response'   => json_decode($response, true) ?? $response,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);