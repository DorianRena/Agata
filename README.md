# Agata
Ce projet est une application web intentionnelement vulnérable qui permet de  créer, gérer et s’inscrire à des événements. 
Le backend est en PHP/MySQL et le frontend en HTML/CSS/JS.
Le tout est conteneurisé avec Docker et Docker Compose.

## Installation 
### Prérequis
- Docker
- DockerCompose

### Structure du projet
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
### Étapes d'installation
#### Cloner le projet
```bash
git clone https://github.com/DorianRena/Agata agata
cd agata
```
#### Construire les conteneurs et les lancés
```bash
docker-compose up --build
```

