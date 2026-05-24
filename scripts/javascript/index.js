const container = document.querySelector('.container');
const resultsEl = document.getElementById('results');
const linkInput = document.getElementById('linkInput');
const linkForm = document.getElementById('linkForm');

linkForm.addEventListener('submit', async (e) => {
  e.preventDefault();

  container.classList.add('shift-up');

  resultsEl.innerHTML = WAITING_TEMPLATE;

  const formData = new FormData(e.target);

  try {
    const res = await fetchToBackend(formData);
    const html = await res.text();

    container.style.transition = 'none';
    container.classList.remove('shift-up');
    void container.offsetHeight;
    document.body.classList.add('results-active');
    container.style.transition = '';

    if (res.ok) {
      resultsEl.innerHTML = html;
    } else {
      resultsEl.innerHTML = errorMsg(html || 'No results found.');
    }
  } catch (err) {
    resultsEl.innerHTML = errorMsg('Error: ' + err.message);
  }
});

document.getElementById('clearBtn').addEventListener('click', () => {
  container.classList.remove('shift-up');
  linkInput.value = '';
  resultsEl.innerHTML = '';
  document.body.classList.remove('results-active');
});
