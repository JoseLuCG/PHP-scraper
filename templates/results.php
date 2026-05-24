<div class="results-container">
  <div class="event-card glass">
    <h1><?= htmlspecialchars($event['name']) ?></h1>
    <div class="meta">
      <?= htmlspecialchars($event['venue']) ?><br>
      <?= htmlspecialchars($event['venueAddress']) ?>,
      <?= htmlspecialchars($event['venueCity']) ?>, <?= htmlspecialchars($event['venueState']) ?><br>
      <?= htmlspecialchars($event['date']) ?> at <?= htmlspecialchars($event['time']) ?>
    </div>
    <div class="stats-grid">
      <div class="stat">
        <div class="label">Listings</div>
        <div class="value"><?= number_format($event['totalListings']) ?></div>
      </div>
      <div class="stat">
        <div class="label">Tickets</div>
        <div class="value"><?= number_format($event['totalTickets']) ?></div>
      </div>
      <div class="stat">
        <div class="label">Min Price</div>
        <div class="value">$<?= number_format($event['minPrice'], 2) ?></div>
      </div>
      <div class="stat">
        <div class="label">Max Price</div>
        <div class="value">$<?= number_format($event['maxPrice'], 2) ?></div>
      </div>
      <div class="stat">
        <div class="label">Avg Price</div>
        <div class="value">$<?= number_format($event['avgPrice'], 2) ?></div>
      </div>
    </div>
  </div>

  <div class="listings-count">
    Showing <?= count($listings) ?> available listings
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Section</th>
          <th>Row</th>
          <th>Price</th>
          <th>Qty</th>
          <th>Deal Score</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($listings as $i => $l): ?>
          <tr>
            <td class="index-number"><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($l['section']) ?></td>
            <td><?= htmlspecialchars($l['row']) ?></td>
            <td class="price-cell">
              <?php if ($l['originalPrice']): ?>
                <span class="orig-price">$<?= number_format($l['originalPrice']) ?></span>
              <?php endif; ?>
              $<?= number_format($l['price']) ?>
            </td>
            <td><?= $l['quantityMin'] ?><?= $l['quantityMax'] > $l['quantityMin'] ? '–' . $l['quantityMax'] : '' ?></td>
            <td>
              <span class="score
                <?= $l['dealScore'] >= 9 ? 'score-high' : ($l['dealScore'] >= 7 ? 'score-mid' : 'score-low') ?>
              ">
                <?= $l['dealScore'] ? number_format($l['dealScore'], 1) : '—' ?>
              </span>
            </td>
            <td>
              <?php if ($l['badge'] === 'SALE!'): ?>
                <span class="badge badge-sale">SALE!</span>
              <?php elseif ($l['badge'] === 'Last Ticket in Section'): ?>
                <span class="badge badge-last">Last Ticket</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <a href="../index.html" class="back-link">&larr; Search again</a>
</div>
