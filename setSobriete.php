<?php
/*
 * Projet : edfzenflex-status-check
 * Auteur : Renaud Pacouil
 * Site web : https://www.reuhno.fr
 * Dépôt GitHub : https://github.com/reuhno/edfzenflex-status-check
 *
 * Ce code est sous licence Apache-2.0. Pour plus d'informations, veuillez consulter le fichier LICENSE
 * ou visiter https://www.apache.org/licenses/LICENSE-2.0.
 */


// Configuration de la connexion à la base de données
define('DB_NAME', 'zenflex');
define('DB_USER', 'zenflex');
define('DB_PASSWORD', 'xxxxxxxxxxxxx');
define('DB_HOST', 'localhost');

// Connexion à la base de données MySQL
try {
	$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASSWORD);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die('KO - Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Vérification de la présence du paramètre 'date'
if (isset($_GET['date'])) {
	$date = $_GET['date'];
	
	$validTypes = ['SOBRIETE', 'BONUS', 'ECO'];
	// Nettoyage et récupération de la variable 'type'
	$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
	// Validation de la valeur
	$type = strtoupper($type);
	
	if (!in_array($type, $validTypes)) {
		//$type = 'SOBRIETE'; // Valeur par défaut si le type n'est pas valide
		echo "KO - Type SOBRIETE / BONUS / ECO attendu";
		exit;
	}

	// Vérification du format de la date
	$dateObject = DateTime::createFromFormat('Y-m-d', $date);
	if (!$dateObject || $dateObject->format('Y-m-d') != $date) {
		echo "KO - Format de date invalide. Utilisez YYYY-MM-DD.";
		exit;
	}
	
	
	// Vérification de l'unicité de la date
	$stmt = $db->prepare("SELECT COUNT(*) FROM sobriete WHERE date = :date");
	$stmt->bindValue(':date', $date, PDO::PARAM_STR);
	$stmt->execute();
	if ($stmt->fetchColumn() > 0) {
		echo "KO - Cette date est déjà enregistrée.";
		exit;
	}

	// Insertion de la date dans la base de données
	/*$stmt = $db->prepare("INSERT INTO sobriete (date) VALUES (:date)");
	$stmt->bindValue(':date', $date, PDO::PARAM_STR);
	$stmt->execute();*/
	
	$stmt = $db->prepare("INSERT INTO sobriete (date, type) VALUES (:date, :type)");
	$stmt->bindValue(':date', $date, PDO::PARAM_STR);
	$stmt->bindValue(':type', $type, PDO::PARAM_STR);
	$stmt->execute();
	
	// Sélection des 10 derniers ID
	$idsToKeep = $db->query("SELECT id FROM sobriete ORDER BY id DESC LIMIT 25");
	$idsToKeepArray = $idsToKeep->fetchAll(PDO::FETCH_COLUMN, 0);
	$idsToKeepString = implode(',', $idsToKeepArray);
	
	// Suppression des entrées qui ne sont pas dans les xx derniers
	$db->exec("DELETE FROM sobriete WHERE id NOT IN ($idsToKeepString)");



	echo "OK - Date ". $type ." enregistrée : " . $date;
} else {
	echo "Aucune date fournie.";
}
?>
