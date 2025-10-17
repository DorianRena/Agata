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
                    if (errorMsg) errorMsg.textContent = "Email ou mot de passe incorrect.";
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
                if (errorMsg)
                    errorMsg.textContent = "Mot de passe trop faible (min 8 caractères, 1 majuscule, 1 chiffre).";
                return;
            }
            if (mdp !== confirmMdp) {
                if (errorMsg) errorMsg.textContent = "Les mots de passe ne correspondent pas.";
                return;
            }
            if (errorMsg) errorMsg.textContent = "";

            const data = { nom, prenom, date_naissance, email, mdp };
            ajaxPost("../php/request.php/inscription", data, (response) => {
                if (response === true) {
                    alert("Inscription réussie ! Vous pouvez maintenant vous connecter.");
                    window.location.href = "login.html";
                } else if (response === "Already") {
                    if (errorMsg) errorMsg.textContent = "Un compte existe déjà avec cet email.";
                } else {
                    if (errorMsg) errorMsg.textContent = "Erreur lors de l'inscription.";
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

        const page = window.location.pathname.split("/").pop();

        if (userId) {
            nav.innerHTML = `
                <a href="index.html" ${page === "index.html" ? "class='active'" : ""}>Accueil</a>
                <a href="events.html" ${page === "events.html" ? "class='active'" : ""}>Événements</a>
                <a href="create_event.html" ${page === "create_event.html" ? "class='active'" : ""}>Créer un événement</a>
                <span class="user-info">${prenom} ${nom}</span>
                <button id="logoutBtn" class="logout-btn">Déconnexion</button>
            `;
            const logoutBtn = document.getElementById("logoutBtn");
            logoutBtn.addEventListener("click", () => {
                localStorage.clear();
                window.location.href = "index.html";
            });
        } else {
            nav.innerHTML = `
                <a href="index.html" ${page === "index.html" ? "class='active'" : ""}>Accueil</a>
                <a href="events.html" ${page === "events.html" ? "class='active'" : ""}>Événements</a>
                <a href="login.html" ${page === "login.html" ? "class='active'" : ""}>Connexion</a>
                <a href="register.html" ${page === "register.html" ? "class='active'" : ""}>Inscription</a>
            `;
        }
    }

    // --- ÉVÉNEMENTS ---
    const currentDateSpan = document.getElementById("currentDate");
    const eventContainer = document.getElementById("eventContainer");
    const createBtn = document.getElementById("createEventBtn");

    if (currentDateSpan && eventContainer) {
        loadEvents();
        const prevBtn = document.getElementById("prevDay");
        const nextBtn = document.getElementById("nextDay");

        if (prevBtn)
            prevBtn.addEventListener("click", () => {
                currentDate.setDate(currentDate.getDate() - 1);
                loadEvents();
            });
        if (nextBtn)
            nextBtn.addEventListener("click", () => {
                currentDate.setDate(currentDate.getDate() + 1);
                loadEvents();
            });
    }

    if (createBtn) {
        const userId = localStorage.getItem("userId");
        if (userId) createBtn.style.display = "block";
        createBtn.addEventListener("click", () => {
            window.location.href = "create_event.html";
        });
    }
});

// --- Chargement des événements ---
function loadEvents() {
    const eventContainer = document.getElementById("eventContainer");
    const formattedDate = formatDate(currentDate);
    displayDate(currentDate);

    const userId = localStorage.getItem("userId");

    ajaxGet(`../php/request.php/events?date=${formattedDate}&id_user=${userId || ""}`, (events) => {
        if (eventContainer) {
            if (events.length === 0) {
                eventContainer.innerHTML = "<p>Aucun événement pour cette journée.</p>";
                return;
            }

            eventContainer.innerHTML = events.map(event => {
                const isPrivate = event.is_private ? true : false;
                const privateLabel = isPrivate ? "Événement privé" : "Événement public";

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
                        ${event.image_path ? `<img src="../${event.image_path}" class="event-img">` : ""}
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

// --- Inscription / désinscription à un événement ---
function registerEvent(eventId, btn) {
    const id_user = localStorage.getItem("userId");
    if (!id_user) {
        alert("Veuillez vous connecter.");
        return;
    }

    ajaxPost("../php/request.php/register_event", { id_user, id_event: eventId }, (res) => {
        if (res === true) {
            btn.textContent = "Se désinscrire";
            btn.classList.add("btn-unregister");
            btn.onclick = () => unregisterEvent(eventId, btn);
        } else {
            alert("Vous êtes déjà inscrit.");
        }
    });
}

function unregisterEvent(eventId, btn) {
    const id_user = localStorage.getItem("userId");
    if (!id_user) {
        alert("Veuillez vous connecter.");
        return;
    }

    ajaxPost("../php/request.php/unregister_event", { id_user, id_event: eventId }, (res) => {
        if (res === true) {
            btn.textContent = "S'inscrire";
            btn.classList.remove("btn-unregister");
            btn.onclick = () => registerEvent(eventId, btn);
        } else {
            alert("Erreur lors de la désinscription.");
        }
    });
}

// --- Affichage de la date ---
function displayDate(date) {
    const options = { weekday: "long", year: "numeric", month: "long", day: "numeric" };
    const currentDateSpan = document.getElementById("currentDate");
    if (currentDateSpan) currentDateSpan.textContent = date.toLocaleDateString("fr-FR", options);
}

function formatDate(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, "0");
    const dd = String(date.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
}

// --- PAGE CREATE_EVENT ---
document.addEventListener("DOMContentLoaded", () => {
    const createForm = document.getElementById("createEventForm");
    const myEventsContainer = document.getElementById("myEventsContainer");
    const userId = localStorage.getItem("userId");

    // Vérifie si l'utilisateur est connecté
    if (window.location.pathname.endsWith("create_event.html")) {
        if (!userId) {
            alert("Vous devez être connecté pour accéder à cette page.");
            window.location.href = "login.html";
            return;
        }
    }

    // Charger les événements
    function loadMyEvents() {
        ajaxGet(`../php/request.php/my_events?id_user=${userId}`, (events) => {
            if(events.length === 0){
                myEventsContainer.innerHTML = "<p>Vous n'avez créé aucun événement.</p>";
                return;
            }

            myEventsContainer.innerHTML = events.map(ev => {
                const isPrivate = ev.is_private ? true : false;
                const privateLabel = isPrivate ? "Événement privé" : "Événement public";

                // Gestion de la liste des invités
                let inviteListHTML = "";
                if (isPrivate && ev.invited_users && ev.invited_users.length > 0) {
                    inviteListHTML = `
                        <p><strong>Invités :</strong> ${ev.invited_users.join(', ')}</p>
                    `;
                } else if (isPrivate) {
                    inviteListHTML = `
                        <p><strong>Invités :</strong> Aucun invité</p>
                    `;
                }

                return `
                    <div class="event-card" data-id="${ev.id_event}">
                        ${ev.image_path ? `<img src="../${ev.image_path}" class="event-img">` : ""}
                        <h3>${ev.title}</h3>
                        <p><strong>Date :</strong> ${ev.event_date} ${ev.event_time}</p>
                        <p><strong>Lieu :</strong> ${ev.location || 'Non précisé'}</p>
                        <p>${ev.description}</p>
                        <p><em>${privateLabel}</em></p>
                        ${inviteListHTML}
                        <button onclick="editEvent(${ev.id_event})">Modifier</button>
                        <button onclick="deleteEvent(${ev.id_event})" class="btn-outline">Supprimer</button>
                    </div>
                `;
            }).join('');
        });
    }

    // Initial load
    loadMyEvents();

    // --- Création d'un événement ---
    // Création
    const checkbox = document.getElementById('is_private');
    const invitationInput = document.getElementById('invitation_list');

    // Fonction pour activer/désactiver le champ
    const toggleInvitationInput = () => {
        invitationInput.disabled = !checkbox.checked;

        // Optionnel : on vide le champ si décoché
        if (!checkbox.checked) {
            invitationInput.value = '';
        }
    };
    // Sur changement de la checkbox
    checkbox.addEventListener('change', toggleInvitationInput);

    createForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const formData = new FormData(createForm);
        formData.set('is_private', document.getElementById('is_private').checked ? 'true' : 'false');
        formData.append("id_user", userId); // Ajoute l'id_user au formulaire

        if (invitationInput) {
            invitationEmails = invitationInput.value
                .split(',')
                .map(e => e.trim())
                .filter(e => e.includes('@'));
            formData.set("emails", invitationEmails);
        }

        fetch("../php/request.php/create_event", {
            method: "POST",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
            body: formData,
        })
            .then((res) => res.json())
            .then((res) => {
                if (res === true) {
                    alert("Événement créé !");
                    createForm.reset();
                    loadMyEvents(); // Mise à jour dynamique
                } else {
                    console.error(res);
                    alert("Erreur lors de la création.");
                }
            })
            .catch((err) => console.error(err));
    });

    // Modifier un événement
    window.editEvent = function(id_event){
        const card = document.querySelector(`.event-card[data-id='${id_event}']`);
        const title = prompt("Nouveau titre :", card.querySelector("h3").textContent);
        const desc = prompt("Nouvelle description :", card.querySelector("p:nth-of-type(3)").textContent);
        const date = prompt("Nouvelle date (YYYY-MM-DD) :", card.querySelector("p:nth-of-type(1)").textContent.split(' ')[2]);
        const time = prompt("Nouvelle heure (HH:MM) :", card.querySelector("p:nth-of-type(1)").textContent.split(' ')[3]);
        const location = prompt("Nouveau lieu :", card.querySelector("p:nth-of-type(2)").textContent.replace("Lieu : ", ""));

        // --- Ajout des champs pour un event privé ---
        const isPrivate = confirm("Cet événement est-il sur invitation ?");
        let invitationEmails = [];

        if (isPrivate) {
            const emailsInput = prompt("Entrez les emails invités (séparés par des virgules) :", "");
            if (emailsInput) {
                invitationEmails = emailsInput
                    .split(',')
                    .map(e => e.trim())
                    .filter(e => e.includes('@'));
            }
        }

        ajaxPost("../php/request.php/edit_event", { id_event, title, description: desc, event_date: date, event_time: time, location, is_private: isPrivate, emails: invitationEmails }, (res)=>{
            if(res === true) loadMyEvents();
            else alert("Erreur lors de la modification.");
        });
    }

    // --- Suppression d'un événement ---
    window.deleteEvent = function (id_event) {
        if (confirm("Voulez-vous vraiment supprimer cet événement ?")) {
            ajaxPost("../php/request.php/delete_event", { id_event }, (res) => {
                if (res === true) loadMyEvents();
                else alert("Erreur lors de la suppression.");
            });
        }
    }
});
