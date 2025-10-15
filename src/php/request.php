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
                    SELECT COUNT(*) 
                    FROM event_allowed_users 
                    WHERE id_event = :id_event AND id_user = :id_user
                ");
                $stmtPriv->bindParam(':id_event', $event['id_event']);
                $stmtPriv->bindParam(':id_user', $id_user);
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
} else {
    $request = ["error" => "Invalid endpoint"];
}

echo json_encode($request);

