<?php
define('ALLOW_ACCESS', true);

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('database.php');
require_once('UserPref.php');
require_once('Debug.php');
$db = Database::connexionBD();

// Récupère la route
$requestid = substr($_SERVER['PATH_INFO'], 1);
$requestid = explode('/', $requestid);
$requesttype = array_shift($requestid);

header('Content-Type: application/json');
$request = null;

if ($requesttype == "inscription") {
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $request = dbInsertNewUser(
            $db,
            $_POST['nom'],
            $_POST['prenom'],
            $_POST['date_naissance'],
            $_POST['email'],
            $_POST['mdp']
        );
    }
} elseif ($requesttype == "login") {
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $request = dbGetUser($db, $_POST['email'], $_POST['mdp']);
    }
} elseif ($requesttype == "events") {
    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        $date = $_GET['date'] ?? date('Y-m-d');
        $id_user = $_GET['id_user'] ?? null;

        // Récupérer les événements avec l'organisateur
        $stmt = $db->prepare("
            SELECT e.*, u.nom AS org_nom, u.prenom AS org_prenom
            FROM events e
            JOIN users u ON e.created_by = u.id_user
            WHERE e.event_date = :date
            ORDER BY e.event_time ASC
        ");
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filteredEvents = [];

        foreach ($events as $event) {
            $isPrivate = isset($event['is_private']) && $event['is_private'];

            // Si l'événement est privé, on vérifie si l'utilisateur est autorisé
            if ($isPrivate) {
                // Si pas d'utilisateur connecté => ignorer
                if (!$id_user) {
                    continue;
                }

                $stmtPriv = $db->prepare("
                    SELECT email
                    FROM users
                    WHERE id_user = :id_user
                ");
                $stmtPriv->bindParam(':id_user', $id_user);
                $stmtPriv->execute();
                $email = $stmtPriv->fetchColumn();

                $stmtPriv = $db->prepare("
                    SELECT COUNT(*) 
                    FROM event_allowed_users 
                    WHERE id_event = :id_event AND email = :email
                ");
                $stmtPriv->bindParam(':id_event', $event['id_event']);
                $stmtPriv->bindParam(':email', $email);
                $stmtPriv->execute();

                $isAllowed = $stmtPriv->fetchColumn() > 0;

                if (!$isAllowed) {
                    // L'utilisateur n'est pas autorisé → on ignore l'événement
                    continue;
                }
            }

            // Vérifier pour chaque event si l'utilisateur est inscrit
            if ($id_user) {
                $stmt2 = $db->prepare("SELECT COUNT(*) FROM registrations WHERE id_event=:id_event AND id_user=:id_user");
                $stmt2->bindParam(':id_event', $event['id_event']);
                $stmt2->bindParam(':id_user', $id_user);
                $stmt2->execute();
                $event['registered'] = $stmt2->fetchColumn() > 0;
            } else {
                $event['registered'] = false;
            }

            $filteredEvents[] = $event;
        }

        $request = $filteredEvents;
    }
} elseif ($requesttype == "unregister_event") {
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $stmt = $db->prepare("DELETE FROM registrations WHERE id_event=:id_event AND id_user=:id_user");
        $stmt->bindParam(':id_event', $_POST['id_event']);
        $stmt->bindParam(':id_user', $_POST['id_user']);
        $request = $stmt->execute();
    }
} elseif ($requesttype == "register_event") {
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Vérifie si l'utilisateur n'est pas déjà inscrit
        $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE id_event=:id_event AND id_user=:id_user");
        $stmt->bindParam(':id_event', $_POST['id_event']);
        $stmt->bindParam(':id_user', $_POST['id_user']);
        $stmt->execute();
        if($stmt->fetchColumn() > 0){
            $request = false; // déjà inscrit
        } else {
            $request = dbRegisterEvent($db, $_POST['id_user'], $_POST['id_event']);
        }
    }
} elseif ($requesttype == "event") {
    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        $id_event = $_GET['id'] ?? null;

        // Récupérer les événements avec l'organisateur
        $stmt = $db->prepare("
            SELECT e.*, u.nom AS org_nom, u.prenom AS org_prenom
            FROM events e
            JOIN users u ON e.created_by = u.id_user
            WHERE e.id_event = :id
            ORDER BY e.event_time ASC
        ");
        $stmt->bindParam(':id', $id_event);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        $request = $event;
    }
} elseif($requesttype == "create_event" && $_SERVER['REQUEST_METHOD'] == "POST") {
    // Gestion de l'image
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/'; // absolute path depuis le fichier PHP
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
        $imagePath = 'uploads/' . $fileName;
    }
    $stmt = $db->prepare("
        INSERT INTO events (title, description, event_date, event_time, location, created_by, is_private, image_path)
        VALUES (:title, :description, :event_date, :event_time, :location, :created_by, :is_private, :image_path)
    ");
    $stmt->bindParam(':title', $_POST['title']);
    $stmt->bindParam(':description', $_POST['description']);
    $stmt->bindParam(':event_date', $_POST['event_date']);
    $stmt->bindParam(':event_time', $_POST['event_time']);
    $stmt->bindParam(':location', $_POST['location']);
    $stmt->bindParam(':created_by', $_POST['id_user']);
    $stmt->bindParam(':is_private', $_POST['is_private']);
    $stmt->bindValue(':image_path', $imagePath);
    if ($stmt->execute()) {
        $request = true;
    } else {
        $error = $stmt->errorInfo();
        $request = ["error" => $error[2]]; // message d'erreur exact
    }

    $id_event = $db->lastInsertId();
    $emails = isset($_POST['emails']) ? array_map('trim', explode(',', $_POST['emails'])) : [];


    if ($_POST['is_private']) {
        $added = [];

        // Vérifie que l'événement existe et est bien privé
        $stmtCheck = $db->prepare("SELECT is_private FROM events WHERE id_event = :id_event");
        $stmtCheck->bindParam(':id_event', $id_event);
        $stmtCheck->execute();
        $event = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            $request = ["error" => "Event not found"];
        } elseif (!$event['is_private']) {
            $request = ["error" => "Event is not private"];
        } else {
            foreach ($emails as $email) {
                // Vérifie si déjà présent
                $stmtCheck = $db->prepare("
                        SELECT COUNT(*) FROM event_allowed_users 
                        WHERE id_event = :id_event AND email = :email
                    ");
                $stmtCheck->bindParam(':id_event', $id_event);
                $stmtCheck->bindParam(':email', $email);
                $stmtCheck->execute();

                if ($stmtCheck->fetchColumn() == 0) {
                    // Ajoute dans la table des autorisations
                    $stmtInsert = $db->prepare("
                            INSERT INTO event_allowed_users (id_event, email) 
                            VALUES (:id_event, :email)
                        ");
                    $stmtInsert->bindParam(':id_event', $id_event);
                    $stmtInsert->bindParam(':email', $email);
                    $stmtInsert->execute();

                    $added[] = $email;
                }
            }
        }
    }

} elseif($requesttype == "my_events" && $_SERVER['REQUEST_METHOD'] == "GET"){
    $id_user = $_GET['id_user'];
    $stmt = $db->prepare("SELECT * FROM events WHERE created_by=:id_user ORDER BY event_date, event_time");
    $stmt->bindParam(':id_user', $id_user);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque événement privé, on ajoute la liste des invités
    foreach ($events as &$event) {
        if (!empty($event['is_private']) && $event['is_private']) {
            $stmt2 = $db->prepare("
                SELECT email 
                FROM event_allowed_users
                WHERE id_event = :id_event
            ");
            $stmt2->bindParam(':id_event', $event['id_event']);
            $stmt2->execute();
            $event['invited_users'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $event['invited_users'] = [];
        }
    }

    $request = $events;
} elseif($requesttype == "edit_event" && $_SERVER['REQUEST_METHOD'] == "POST"){
    $stmt = $db->prepare("UPDATE events SET title=:title, description=:description, event_date=:event_date, event_time=:event_time, location=:location, is_private=:is_private WHERE id_event=:id_event");
    $stmt->bindParam(':title', $_POST['title']);
    $stmt->bindParam(':description', $_POST['description']);
    $stmt->bindParam(':event_date', $_POST['event_date']);
    $stmt->bindParam(':event_time', $_POST['event_time']);
    $stmt->bindParam(':location', $_POST['location']);
    $stmt->bindParam(':id_event', $_POST['id_event']);
    $stmt->bindParam(':is_private', $_POST['is_private']);
    $request = $stmt->execute();

    // Maintenant, on gère les invités
    $id_event = $_POST['id_event'];
    $emails = isset($_POST['emails']) ? array_map('trim', explode(',', $_POST['emails'])) : [];

    // On supprime d'abord les anciennes autorisations pour cet event
    $stmtDel = $db->prepare("DELETE FROM event_allowed_users WHERE id_event = :id_event");
    $stmtDel->bindParam(':id_event', $id_event);
    $stmtDel->execute();
    if ($_POST['is_private']) {
        foreach ($emails as $email) {
            // Ajoute dans event_allowed_users
            $stmtInsert = $db->prepare("
                INSERT INTO event_allowed_users (id_event, email)
                VALUES (:id_event, :id_user)
            ");
            $stmtInsert->bindParam(':id_event', $id_event);
            $stmtInsert->bindParam(':id_user', $email);
            $stmtInsert->execute();
        }
    } else {
        $request = [
            "success" => true,
            "updated" => true,
            "message" => "Event updated and invitations cleared (not private)"
        ];
    }
} elseif($requesttype == "delete_event" && $_SERVER['REQUEST_METHOD'] == "POST"){
    $stmt = $db->prepare("DELETE FROM events WHERE id_event=:id_event");
    $stmt->bindParam(':id_event', $_POST['id_event']);
    $request = $stmt->execute();
} elseif($requesttype == "user_preferences" && $_SERVER['REQUEST_METHOD'] == "GET"){
    // vérifier existence puis lire
    if (isset($_COOKIE['user_preferences'])) {
        $cookie = $_COOKIE['user_preferences'];
        $serialized = urldecode($cookie);
        $user_pref = unserialize($serialized);
        $request = (string)$user_pref;
    } else {
        $request = "Pas de préférences utilisateur"; // valeur par défaut
    }
} else {
    $request = ["error" => "Invalid endpoint"];
}

echo json_encode($request);
?>
