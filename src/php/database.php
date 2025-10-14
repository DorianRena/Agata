<?php
// Connexion à la base de données
class Database {
    static $db = null;

    static function connexionBD() {
        if (self::$db != null) {
            return self::$db;
        }
        require_once("config.php"); // Contient DB_SERVER, DB_PORT, DB_NAME, DB_USER, DB_PWD
        try {
            self::$db = new PDO('pgsql:host='.DB_SERVER.';port='.DB_PORT.';dbname='.DB_NAME, DB_USER, DB_PWD);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            error_log('Connection error: ' . $exception->getMessage());
            return false;
        }
        return self::$db;
    }
}

/* ----------------------- */
/*   UTILISATEURS          */
/* ----------------------- */

// Vérifie email/mdp et retourne infos utilisateur
function dbGetUser($db, $email, $mdp) {
    try {
        $stmt = $db->prepare('SELECT * FROM users WHERE email=:email');
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($result) && password_verify($mdp, $result['mdp'])) {
            return array($result['id_user'], $result['nom'], $result['prenom']);
        } else {
            return "error";
        }
    } catch (PDOException $e) {
        error_log('Request error: ' . $e->getMessage());
        return false;
    }
}

// Vérifie si un utilisateur existe déjà
function AlreadyUser($db, $email) {
    try {
        $stmt = $db->prepare('SELECT * FROM users WHERE email=:email');
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($user);
    } catch (PDOException $e) {
        error_log('Request error: ' . $e->getMessage());
        return false;
    }
}

// Crée un nouvel utilisateur
function dbInsertNewUser($db, $nom, $prenom, $date_naissance, $email, $mdp) {
    try {
        if (!AlreadyUser($db, $email)) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (nom, prenom, date_naissance, email, mdp) VALUES (:nom, :prenom, :date_naissance, :email, :mdp)");
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':date_naissance', $date_naissance);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':mdp', $hash);
            $stmt->execute();
            return true;
        } else {
            return "Already";
        }
    } catch (PDOException $e) {
        error_log('Request error: ' . $e->getMessage());
        return false;
    }
}

/* ----------------------- */
/*   EVENEMENTS            */
/* ----------------------- */

// Récupère les événements pour une date précise
function dbGetEventsByDate($db, $date) {
    try {
        $stmt = $db->prepare("SELECT * FROM events WHERE event_date = :date ORDER BY event_time ASC");
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Request error: ' . $e->getMessage());
        return false;
    }
}

// Crée un nouvel événement
function dbCreateEvent($db, $title, $description, $event_date, $event_time, $id_user) {
    try {
        $stmt = $db->prepare("INSERT INTO events (title, description, event_date, event_time, created_by) VALUES (:title, :description, :event_date, :event_time, :id_user)");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':event_date', $event_date);
        $stmt->bindParam(':event_time', $event_time);
        $stmt->bindParam(':id_user', $id_user);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log('Request error: ' . $e->getMessage());
        return false;
    }
}

// Inscrire un utilisateur à un événement
function dbRegisterEvent($db, $id_user, $id_event) {
    try {
        $stmt = $db->prepare("INSERT INTO registrations (id_user, id_event) VALUES (:id_user, :id_event)");
        $stmt->bindParam(':id_user', $id_user);
        $stmt->bindParam(':id_event', $id_event);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log('Request error: ' . $e->getMessage());
        return false;
    }
}
?>
