# Agata
Agata est une application web intentionnelement vulnérable qui permet de  créer, gérer et s’inscrire à des événements. 
Le backend est en PHP/MySQL et le frontend en HTML/CSS/JS.
Le tout est conteneurisé avec Docker et Docker Compose.

## Fonctionnalités de l'application
### Gestion des utilisateurs
- Les utilisateurs peuvent créer un compte avec nom, prénom, date de naissance, email et mot de passe.
- Les utilisateurs non-connecté n'ont accès qu'à la page d'accueil et la liste des events
###  Gestion des events
#### Voir les events
- Les utilisateurs peuvent consulter les événements par date.
- Les utilisateurs connectés peuvent s'inscrire à des événements ou se désinscrire 
#### Créer un événement
- Les utilisateurs connectés peuvent créer un événement et voir tous ceux dont ils sont l'organisateur
- Les utilisateurs peuvent créer des événements avec :
Titre / Description / Date / Heure / Lieu / Image (optionnelle) / Sur invitation (optionnelle)
- Il est possible de mettre autant d'utilisateurs (avec l'adresse mail séparé par des ",") que voulu sur la liste d'invitations 
#### Supprimer/modifier un événement
- Les events créés par un utilisateur peuvent être modifiés ou supprimés

## Structure du projet
```
agata/
├─ src/
│  ├─ index.html        # Pages HTML  
│  ├─ events.html        
│  ├─ login.html        
│  ├─ register.html        
│  ├─ create_event.html        
│  ├─ style.css        
│  ├─ js/               # Scripts JS
│  ├─ php/              # Scripts PHP
│  └─ uploads/          # Images uploadées
├─ docker-compose.yml
├─ Dockerfile
├─ README.md
└─ init.sql             # Scripts pour initialiser la BDD
```

## Installation 
### Prérequis
- Docker
- DockerCompose

### Étapes d'installation
#### 1 - Cloner le projet
```bash
git clone https://github.com/DorianRena/Agata agata
cd agata
```
#### 2 - Construire les conteneurs et les lancés
```bash
docker-compose up --build
```
Accéder à la base de données (si nécessaire)
```bash
docker exec -it agata_db bash 
psql -U agata -d agata_db
```
#### 3 - Accéder à l’application
Ouvrir le navigateur à l'adresse :
 **http://localhost:8080/**

## Fonctionnalités de l'application
### Gestion des utilisateurs
- Les utilisateurs peuvent créer un compte avec nom, prénom, date de naissance, email et mot de passe.
- Les utilisateurs non-connecté n'ont accès qu'à la page d'accueil et la liste des events
###  Gestion des events
#### Voir les events
- Les utilisateurs peuvent consulter les événements par date.
- Les utilisateurs connectés peuvent s'inscrire à des événements ou se désinscrire 
#### Créer un événement
- Les utilisateurs connectés peuvent créer un événement et voir tous ceux dont ils sont l'organisateur
- Les utilisateurs peuvent créer des événements avec :
Titre / Description / Date / Heure / Lieu / Image (optionnelle) / Sur invitation (optionnelle)
- Il est possible de mettre autant d'utilisateurs (avec l'adresse mail séparé par des ",") que voulu sur la liste d'invitations 
#### Supprimer/modifier un événement
- Les events créés par un utilisateur peuvent être modifiés ou supprimés
