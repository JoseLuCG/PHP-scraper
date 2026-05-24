<?php

function validateVividSeatsUrl(string $url): bool {
    $host = parse_url($url, PHP_URL_HOST);
    return $host !== null && str_contains($host, 'vividseats.com');
}

function respondError(string $msg, bool $isAjax, string $class = 'error-msg'): void {
  if ($isAjax) {
    echo $msg;
  } else {
    echo '<p class="' . $class . '">' . htmlspecialchars($msg) . '</p>';
  }
  exit;
}

function validateRequest(bool $isAjax): string {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['link'])) {
    respondError('No link provided.', $isAjax);
  }

  $url = filter_var($_POST['link'], FILTER_VALIDATE_URL);

  if ($url === false) {
    respondError('Invalid URL format.', $isAjax);
  }

  if (!validateVividSeatsUrl($url)) {
    respondError('Please provide a valid VividSeats URL.', $isAjax);
  }

  return $url;
}

function validateScrapeResult(array $result, bool $isAjax): array {
  if (!$result['success']) {
    respondError('Error: ' . ($result['error'] ?? 'Unknown error'), $isAjax, 'error-msg-danger');
  }
  return $result;
}
