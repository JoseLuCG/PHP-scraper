const container = document.querySelector('.container');
const resultsEl = document.getElementById('results');
const linkInput = document.getElementById('linkInput');
const linkForm = document.getElementById('linkForm');

linkForm.addEventListener('submit', async (e) => {
  e.preventDefault();

  container.classList.add('shift-up');

  resultsEl.innerHTML = `
    <div class="waiting-template glass">
      <div class="spinner"></div>
      <p>Fetching ticket listings...</p>
    </div>
  `;

  const formData = new FormData(e.target);

  try {
    const res = await fetch('scripts/index.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const html = await res.text();

    container.style.transition = 'none';
    container.classList.remove('shift-up');
    void container.offsetHeight;
    document.body.classList.add('results-active');
    container.style.transition = '';

    if (res.ok) {
      resultsEl.innerHTML = html;
    } else {
      resultsEl.innerHTML = `<p style="color:#f88;text-align:center;padding:2rem;">${html || 'No results found.'}</p>`;
    }
  } catch (err) {
    resultsEl.innerHTML = `<p style="color:#f88;text-align:center;padding:2rem;">Error: ${err.message}</p>`;
  }
});

document.getElementById('clearBtn').addEventListener('click', () => {
  container.classList.remove('shift-up');
  linkInput.value = '';
  resultsEl.innerHTML = '';
  document.body.classList.remove('results-active');
});
