<?php

define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
    'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
define('TIMEOUT', 30);
define('COOKIE_FILE', __DIR__ . '/cookie.txt');

function getBrowserHeaders(): array {
    return [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
    ];
}

function fetchUrl(string $url): string {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => TIMEOUT,
        CURLOPT_USERAGENT      => USER_AGENT,
        CURLOPT_HTTPHEADER     => getBrowserHeaders(),
        CURLOPT_COOKIEJAR      => COOKIE_FILE,
        CURLOPT_COOKIEFILE     => COOKIE_FILE,
        CURLOPT_ENCODING       => '',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $html    = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error   = curl_error($ch);

    curl_close($ch);

    if ($html === false) {
        throw new RuntimeException("cURL error: $error");
    }

    if ($httpCode !== 200) {
        throw new RuntimeException("HTTP $httpCode received — page could not be fetched");
    }

    return $html;
}

function validateVividSeatsUrl(string $url): bool
{
    $host = parse_url($url, PHP_URL_HOST);
    return $host !== null && str_contains($host, 'vividseats.com');
}
