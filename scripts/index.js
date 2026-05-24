document.getElementById('linkForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const resultsEl = document.getElementById('results');
  const container = document.querySelector('.container');

  container.classList.add('shift-up');

  resultsEl.innerHTML = `
    <div class="waiting-template">
      <div class="spinner"></div>
      <p>Fetching ticket listings...</p>
    </div>
  `;

  const formData = new FormData(e.target);

  try {
    const res = await fetch('scripts/index.php', { method: 'POST', body: formData });
    const html = await res.text();

    const doc = new DOMParser().parseFromString(html, 'text/html');
    const resultsContent = doc.querySelector('.results-container');

    container.style.transition = 'none';
    container.classList.remove('shift-up');
    void container.offsetHeight;
    document.body.classList.add('results-active');
    container.style.transition = '';

    if (resultsContent) {
      resultsEl.innerHTML = resultsContent.outerHTML;
    } else {
      const errorText = doc.body.textContent.trim();
      resultsEl.innerHTML = `<p style="color:#f88;text-align:center;padding:2rem;">${errorText || 'No results found.'}</p>`;
    }
  } catch (err) {
    resultsEl.innerHTML = `<p style="color:#f88;text-align:center;padding:2rem;">Error: ${err.message}</p>`;
  }
});

document.getElementById('clearBtn').addEventListener('click', () => {
  document.querySelector('.container').classList.remove('shift-up');
  document.getElementById('linkInput').value = '';
  document.getElementById('results').innerHTML = '';
  document.body.classList.remove('results-active');
});
