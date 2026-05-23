<?php

require_once __DIR__ . '/helpers.php';

// ─── Handle request ─────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['link'])) {
    echo '<p style="color:#fff;">No link provided.</p>';
    exit;
}

$url = filter_var($_POST['link'], FILTER_VALIDATE_URL);

if ($url === false) {
    echo '<p style="color:#fff;">Invalid URL format.</p>';
    exit;
}

if (!validateVividSeatsUrl($url)) {
    echo '<p style="color:#fff;">Please provide a valid VividSeats URL.</p>';
    exit;
}

// ─── Fetch ──────────────────────────────────────────────────────────────

try {
    $html = fetchUrl($url);
} catch (Exception $e) {
    echo '<p style="color:#f88;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// ─── TODO: Parse with DOMDocument + XPath ───────────────────────────────
// Next step: extract ticket data from $html and display it.

echo '<pre style="color:#fff;font-size:12px;overflow:auto;max-height:400px;">';
echo htmlspecialchars($html);
echo '</pre>';
