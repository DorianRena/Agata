<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('database.php');
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

        // Vérifier pour chaque event si l'utilisateur est inscrit
        if ($id_user) {
            foreach ($events as &$event) {
                $stmt2 = $db->prepare("SELECT COUNT(*) FROM registrations WHERE id_event=:id_event AND id_user=:id_user");
                $stmt2->bindParam(':id_event', $event['id_event']);
                $stmt2->bindParam(':id_user', $id_user);
                $stmt2->execute();
                $event['registered'] = $stmt2->fetchColumn() > 0;
            }
        } else {
            foreach ($events as &$event) {
                $event['registered'] = false;
            }
        }

        $request = $events;
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
    $stmt = $db->prepare("
        INSERT INTO events (title, description, event_date, event_time, location, created_by)
        VALUES (:title, :description, :event_date, :event_time, :location, :created_by)
    ");
    $stmt->bindParam(':title', $_POST['title']);
    $stmt->bindParam(':description', $_POST['description']);
    $stmt->bindParam(':event_date', $_POST['event_date']);
    $stmt->bindParam(':event_time', $_POST['event_time']);
    $stmt->bindParam(':location', $_POST['location']);
    $stmt->bindParam(':created_by', $_POST['id_user']);
    $request = $stmt->execute();
} elseif($requesttype == "my_events" && $_SERVER['REQUEST_METHOD'] == "GET"){
    $id_user = $_GET['id_user'];
    $stmt = $db->prepare("SELECT * FROM events WHERE created_by=:id_user ORDER BY event_date, event_time");
    $stmt->bindParam(':id_user', $id_user);
    $stmt->execute();
    $request = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif($requesttype == "edit_event" && $_SERVER['REQUEST_METHOD'] == "POST"){
    $stmt = $db->prepare("UPDATE events SET title=:title, description=:description, event_date=:event_date, event_time=:event_time, location=:location WHERE id_event=:id_event");
    $stmt->bindParam(':title', $_POST['title']);
    $stmt->bindParam(':description', $_POST['description']);
    $stmt->bindParam(':event_date', $_POST['event_date']);
    $stmt->bindParam(':event_time', $_POST['event_time']);
    $stmt->bindParam(':location', $_POST['location']);
    $stmt->bindParam(':id_event', $_POST['id_event']);
    $request = $stmt->execute();
} elseif($requesttype == "delete_event" && $_SERVER['REQUEST_METHOD'] == "POST"){
    $stmt = $db->prepare("DELETE FROM events WHERE id_event=:id_event");
    $stmt->bindParam(':id_event', $_POST['id_event']);
    $request = $stmt->execute();
} else {
    $request = ["error" => "Invalid endpoint"];
}

echo json_encode($request);

