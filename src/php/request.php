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
        $request = dbGetEventsByDate($db, $date);
    } elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
        $request = dbCreateEvent(
            $db,
            $_POST['title'],
            $_POST['description'],
            $_POST['event_date'],
            $_POST['event_time'],
            $_POST['id_user']
        );
    }
} elseif ($requesttype == "register_event") {
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $request = dbRegisterEvent($db, $_POST['id_user'], $_POST['id_event']);
    }
} else {
    $request = ["error" => "Invalid endpoint"];
}

echo json_encode($request);
?>
