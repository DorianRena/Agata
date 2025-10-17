// Requête AJAX GET
function ajaxGet(url, callback) {
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Indique que c'est une requête AJAX
        }
    })
    .then(response => response.json())
    .then(data => callback(data))
    .catch(err => console.error('Erreur AJAX GET:', err));
}

// Requête AJAX POST
function ajaxPost(url, data, callback) {
    let fetchOptions = {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Indique que c'est une requête AJAX
        }
    };

    if (data instanceof FormData) {
        fetchOptions.body = data; 
    } else {
        fetchOptions.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        fetchOptions.body = new URLSearchParams(data);
    }

    fetch(url, fetchOptions)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(err => console.error('Erreur AJAX POST:', err));
}
