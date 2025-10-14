-- Création des tables
CREATE TABLE users (
    id_user SERIAL PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    date_naissance DATE,
    email VARCHAR(100) UNIQUE NOT NULL,
    mdp VARCHAR(255) NOT NULL
);

CREATE TABLE events (
    id_event SERIAL PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    created_by INT REFERENCES users(id_user)
);

CREATE TABLE registrations (
    id_registration SERIAL PRIMARY KEY,
    id_user INT REFERENCES users(id_user),
    id_event INT REFERENCES events(id_event)
);

-- Quelques exemples d'événements
INSERT INTO events (title, description, event_date, event_time, created_by)
VALUES 
('Meetup Agata', 'Présentation du projet et discussion.', CURRENT_DATE, '18:00', NULL),
('Meetup Agata2', 'Présentation du projet et discussion.2', CURRENT_DATE, '19:00', NULL),
('Meetup Agata3', 'Présentation du projet et discussion.3', CURRENT_DATE, '17:00', NULL),
('Hackathon PHP', 'Créer une mini app en 4h !', CURRENT_DATE + INTERVAL '1 day', '14:00', NULL),
('Afterwork Dev', 'Apéro et discussions tech.', CURRENT_DATE + INTERVAL '2 day', '19:00', NULL);
