<?php

function validateVividSeatsUrl(string $url): bool {
    $host = parse_url($url, PHP_URL_HOST);
    return $host !== null && str_contains($host, 'vividseats.com');
}
