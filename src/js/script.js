let currentDate = new Date();

const currentDateSpan = document.getElementById('currentDate');
const eventContainer = document.getElementById('eventContainer');

/**
 * Formate une date en "Weekday, DD Month YYYY"
 * Exemple : "Tuesday, 14 October 2025"
 */
function displayDate(date) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    // 'fr-FR' pour le français
    currentDateSpan.textContent = date.toLocaleDateString('fr-FR', options);
}


/**
 * Formate une date pour l'API au format YYYY-MM-DD
 */
function formatDate(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

/**
 * Charge les événements pour la date actuelle
 */
function loadEvents() {
    const formattedDate = formatDate(currentDate);
    displayDate(currentDate);
    eventContainer.innerHTML = 'Loading...';
    
    ajaxGet(`../php/request.php/events?date=${formattedDate}`, (events) => {
        if(events.length === 0) {
            eventContainer.innerHTML = '<p>No events for this day.</p>';
            return;
        }

        eventContainer.innerHTML = events.map(event => `
            <div class="event-card">
                <h3>${event.title}</h3>
                <p><strong>Time:</strong> ${event.event_time}</p>
                <p>${event.description}</p>
                <button onclick="registerEvent(${event.id_event})">Register</button>
            </div>
        `).join('');
    });
}

/**
 * Inscrire un utilisateur à un événement
 */
function registerEvent(eventId) {
    const id_user = prompt("Enter your user ID to register:"); // À remplacer par session réelle
    ajaxPost('../php/request.php/register_event', { id_user, id_event: eventId }, (res) => {
        if(res === true) {
            alert('Successfully registered!');
        } else {
            alert('Error registering for event.');
        }
    });
}

// Navigation jours
document.getElementById('prevDay').addEventListener('click', () => {
    currentDate.setDate(currentDate.getDate() - 1);
    loadEvents();
});

document.getElementById('nextDay').addEventListener('click', () => {
    currentDate.setDate(currentDate.getDate() + 1);
    loadEvents();
});

// Chargement initial
loadEvents();
