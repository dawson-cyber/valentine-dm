<?php
// config.php
$host = 'localhost';
$dbname = 'valentine_stats';
$username = 'root'; // À changer selon ton hébergement
$password = ''; // À changer selon ton hébergement

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}

// Fonction pour enregistrer une réponse
function enregistrerReponse($prenom, $reponse, $tentatives = 1) {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $page = $_SERVER['HTTP_REFERER'] ?? 'direct';
    
    $sql = "INSERT INTO reponses (prenom, reponse, tentatives, ip_address, user_agent, page_source) 
            VALUES (:prenom, :reponse, :tentatives, :ip, :ua, :page)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':prenom' => $prenom,
        ':reponse' => $reponse,
        ':tentatives' => $tentatives,
        ':ip' => $ip,
        ':ua' => $user_agent,
        ':page' => $page
    ]);
}

// Si on reçoit des données POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = $_POST['prenom'] ?? 'Anonyme';
    $reponse = $_POST['reponse'] ?? '';
    $tentatives = $_POST['tentatives'] ?? 1;
    
    if ($reponse === 'oui' || $reponse === 'non') {
        if (enregistrerReponse($prenom, $reponse, $tentatives)) {
            echo json_encode(['success' => true, 'message' => 'Réponse enregistrée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur d\'enregistrement']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Réponse invalide']);
    }
    exit;
}
?>