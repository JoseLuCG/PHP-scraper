<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/scraper.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['link'])) {
  $msg = 'No link provided.';
  if ($isAjax) { echo $msg; exit; }
  echo '<p style="color:#fff;text-align:center;padding:2rem;">' . $msg . '</p>';
  exit;
}

$url = filter_var($_POST['link'], FILTER_VALIDATE_URL);

if ($url === false) {
  $msg = 'Invalid URL format.';
  if ($isAjax) { echo $msg; exit; }
  echo '<p style="color:#fff;text-align:center;padding:2rem;">' . $msg . '</p>';
  exit;
}

if (!validateVividSeatsUrl($url)) {
  $msg = 'Please provide a valid VividSeats URL.';
  if ($isAjax) { echo $msg; exit; }
  echo '<p style="color:#fff;text-align:center;padding:2rem;">' . $msg . '</p>';
  exit;
}

$scraper = new VividSeatsScraper($url);
$result = $scraper->scrape();

if (!$result['success']) {
  $msg = 'Error: ' . htmlspecialchars($result['error'] ?? 'Unknown error');
  if ($isAjax) { echo $msg; exit; }
  echo '<p style="color:#f88;text-align:center;padding:2rem;">' . $msg . '</p>';
  exit;
}

$event = $result['event'];
$listings = $result['listings'];

if ($isAjax) {
  require __DIR__ . '/../templates/results.php';
  exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($event['name']) ?> — Tickets</title>
  <link rel="stylesheet" href="../styles/index.css">
  <link rel="stylesheet" href="../styles/results.css">
</head>

<body>
  <div class="bg-notes">
    <?php require __DIR__ . '/../partials/notes.php'; ?>
  </div>

  <?php require __DIR__ . '/../templates/results.php'; ?>
</body>

</html>
