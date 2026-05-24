<?php

require_once __DIR__ . '/validators.php';
require_once __DIR__ . '/scraper.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

$url = validateRequest($isAjax);

$scraper = new VividSeatsScraper($url);
$result = validateScrapeResult($scraper->scrape(), $isAjax);

$event = $result['event'];
$listings = $result['listings'];

if ($isAjax) {
  require __DIR__ . '/../../templates/results.php';
  exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($event['name']) ?> — Tickets</title>
  <link rel="stylesheet" href="../../styles/index.css">
  <link rel="stylesheet" href="../../styles/results.css">
</head>

<body>
  <div class="bg-notes">
    <?php require __DIR__ . '/../../partials/notes.php'; ?>
  </div>

  <?php require __DIR__ . '/../../templates/results.php'; ?>
</body>

</html>
