// Requête AJAX GET
function ajaxGet(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(err => console.error(err));
}

// Requête AJAX POST
function ajaxPost(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(data => callback(data))
    .catch(err => console.error(err));
}
