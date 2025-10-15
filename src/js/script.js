let currentDate = new Date();

document.addEventListener("DOMContentLoaded", () => {
    // --- NAVBAR dynamique ---
    updateNavbar();

    // --- LOGIN ---
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const email = document.getElementById("loginEmail").value;
            const mdp = document.getElementById("loginPassword").value;
            const errorMsg = document.getElementById("loginError");

            ajaxPost("../php/request.php/login", { email, mdp }, (response) => {
                if (response !== "error" && response) {
                    localStorage.setItem("userId", response[0]);
                    localStorage.setItem("userNom", response[1]);
                    localStorage.setItem("userPrenom", response[2]);
                    window.location.href = "events.html";
                } else {
                    if(errorMsg) errorMsg.textContent = "Email ou mot de passe incorrect.";
                }
            });
        });
    }

    // --- REGISTER ---
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const nom = document.getElementById("regNom").value;
            const prenom = document.getElementById("regPrenom").value;
            const date_naissance = document.getElementById("regDate").value;
            const email = document.getElementById("regEmail").value;
            const mdp = document.getElementById("regPassword").value;
            const confirmMdp = document.getElementById("regConfirmPassword").value;
            const errorMsg = document.getElementById("passwordError");

            if (mdp.length < 8 || !/[A-Z]/.test(mdp) || !/[0-9]/.test(mdp)) {
                if(errorMsg) errorMsg.textContent = "Mot de passe trop faible (min 8 caractères, 1 majuscule, 1 chiffre).";
                return;
            }
            if (mdp !== confirmMdp) {
                if(errorMsg) errorMsg.textContent = "Les mots de passe ne correspondent pas.";
                return;
            }
            if(errorMsg) errorMsg.textContent = "";

            const data = { nom, prenom, date_naissance, email, mdp };
            ajaxPost("../php/request.php/inscription", data, (response) => {
                if (response === true) {
                    alert("Inscription réussie ! Vous pouvez maintenant vous connecter.");
                    window.location.href = "login.html";
                } else if (response === "Already") {
                    if(errorMsg) errorMsg.textContent = "Un compte existe déjà avec cet email.";
                } else {
                    if(errorMsg) errorMsg.textContent = "Erreur lors de l'inscription.";
                }
            });
        });
    }

    // --- NAVBAR + BOUTON DÉCONNEXION ---
    function updateNavbar() {
        const nav = document.getElementById("navbar");
        if (!nav) return;

        const userId = localStorage.getItem("userId");
        const nom = localStorage.getItem("userNom");
        const prenom = localStorage.getItem("userPrenom");

        // Détecte la page courante
        const page = window.location.pathname.split("/").pop();

        if (userId) {
            nav.innerHTML = `
                <a href="accueil.html" ${page === "accueil.html" ? "class='active'" : ""}>Accueil</a>
                <a href="events.html" ${page === "events.html" ? "class='active'" : ""}>Événements</a>
                <span class="user-info">${prenom} ${nom}</span>
                <button id="logoutBtn" class="logout-btn">Déconnexion</button>
            `;
            const logoutBtn = document.getElementById("logoutBtn");
            logoutBtn.addEventListener("click", () => {
                localStorage.clear();
                window.location.href = "accueil.html";
            });
        } else {
            nav.innerHTML = `
                <a href="accueil.html" ${page === "accueil.html" ? "class='active'" : ""}>Accueil</a>
                <a href="events.html" ${page === "events.html" ? "class='active'" : ""}>Événements</a>
                <a href="login.html" ${page === "login.html" ? "class='active'" : ""}>Connexion</a>
                <a href="register.html" ${page === "register.html" ? "class='active'" : ""}>Inscription</a>
            `;
        }
    }

    // --- ÉVÉNEMENTS ---
    const currentDateSpan = document.getElementById('currentDate');
    const eventContainer = document.getElementById('eventContainer');
    const createBtn = document.getElementById('createEventBtn');

    if(currentDateSpan && eventContainer) {
        loadEvents();
        const prevBtn = document.getElementById('prevDay');
        const nextBtn = document.getElementById('nextDay');

        if(prevBtn) prevBtn.addEventListener('click', () => {
            currentDate.setDate(currentDate.getDate() - 1);
            loadEvents();
        });
        if(nextBtn) nextBtn.addEventListener('click', () => {
            currentDate.setDate(currentDate.getDate() + 1);
            loadEvents();
        });
    }

    if(createBtn) {
        const userId = localStorage.getItem("userId");
        if(userId) createBtn.style.display = "block";
        createBtn.addEventListener("click", () => {
            window.location.href = "create_event.html";
        });
    }

});

// --- Chargement des événements ---
function loadEvents() {
    const currentDateSpan = document.getElementById('currentDate');
    const eventContainer = document.getElementById('eventContainer');
    const formattedDate = formatDate(currentDate);
    displayDate(currentDate);

    const userId = localStorage.getItem("userId");

    ajaxGet(`../php/request.php/events?date=${formattedDate}&id_user=${userId || ''}`, (events) => {
        if(eventContainer){
            if(events.length === 0){
                eventContainer.innerHTML = '<p>Aucun événement pour cette journée.</p>';
                return;
            }

            eventContainer.innerHTML = events.map(event => {
                let actionHtml = '';
                if(userId){
                    if(event.registered){
                        // Déjà inscrit → bouton outline pour se désinscrire
                        actionHtml = `<button class="btn-unregister" onclick="unregisterEvent(${event.id_event}, this)">Se désinscrire</button>`;
                    } else {
                        // Non inscrit → bouton classique pour s'inscrire
                        actionHtml = `<button onclick="registerEvent(${event.id_event}, this)">S'inscrire</button>`;
                    }
                }
                return `
                    <div class="event-card">
                        <h3>${event.title}</h3>
                        <p><strong>Heure :</strong> ${event.event_time}</p>
                        <p><strong>Lieu :</strong> ${event.location || 'Non précisé'}</p>
                        <p>${event.description}</p>
                        <p><strong>Organisateur :</strong> ${event.org_prenom} ${event.org_nom}</p>
                        ${actionHtml}
                    </div>
                `;
                }).join('');

        }
    });
}

// --- S'inscrire à un événement ---
function registerEvent(eventId, btnElement) {
    const id_user = localStorage.getItem("userId");
    if(!id_user) { alert("Veuillez vous connecter."); return; }

    ajaxPost('../php/request.php/register_event', { id_user, id_event: eventId }, (res) => {
        if(res === true){
            btnElement.textContent = "Se désinscrire";
            btnElement.classList.add("btn-unregister");
            btnElement.onclick = () => unregisterEvent(eventId, btnElement);
        } else {
            alert('Vous êtes déjà inscrit.');
        }
    });
}

// --- Se désinscrire d'un événement ---
function unregisterEvent(eventId, btnElement) {
    const id_user = localStorage.getItem("userId");
    if(!id_user) { alert("Veuillez vous connecter."); return; }

    ajaxPost('../php/request.php/unregister_event', { id_user, id_event: eventId }, (res) => {
        if(res === true){
            btnElement.textContent = "S'inscrire";
            btnElement.classList.remove("btn-unregister");
            btnElement.onclick = () => registerEvent(eventId, btnElement);
        } else {
            alert('Erreur lors de la désinscription.');
        }
    });
}



// --- Affichage de la date ---
function displayDate(date) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const currentDateSpan = document.getElementById('currentDate');
    if(currentDateSpan) currentDateSpan.textContent = date.toLocaleDateString('fr-FR', options);
}

function formatDate(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2,'0');
    const dd = String(date.getDate()).padStart(2,'0');
    return `${yyyy}-${mm}-${dd}`;
}
