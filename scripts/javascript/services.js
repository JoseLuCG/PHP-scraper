async function fetchToBackend(formData) {
    return fetch('/scripts/php/index.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
}
