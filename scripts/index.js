document.getElementById('submitBtn').addEventListener('click', () => {
  document.querySelector('.container').classList.add('shift-up');
});

document.getElementById('clearBtn').addEventListener('click', () => {
  document.querySelector('.container').classList.remove('shift-up');
  document.getElementById('linkInput').value = '';
});
