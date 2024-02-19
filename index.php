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

date_default_timezone_set('Europe/Paris');

// Connexion à la base de données MySQL
try {
	$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASSWORD);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die('Erreur de connexion à la base de données : ' . $e->getMessage());
}


header('Content-Type: application/json');



try {
	// Vérifier si le paramètre dateRelevant est présent
	if (isset($_GET['dateRelevant']) && !empty($_GET['dateRelevant'])) {
	
		$dateRelevant = filter_input(INPUT_GET, 'dateRelevant', FILTER_SANITIZE_STRING);
		if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $dateRelevant)) {
		// Formater $dateRelevant au format 'YYYY-mm-dd'
		$dateRelevant = date('Y-m-d', strtotime($dateRelevant));
		}
		else{
			throw new Exception("Format de date invalide. Utilisez YYYY-MM-DD.");
		}
		
		$today = date('Y-m-d');
		$dateRelevantPlusUn = date('Y-m-d', strtotime($dateRelevant . ' +1 day'));
		$currentHour = date('H');

		
		
		// Récupérer le type de la dateRelevant
		$stmt = $db->prepare("SELECT type FROM sobriete WHERE date = ?");
		$stmt->execute([$dateRelevant]);
		$typeJourJ = $stmt->fetchColumn() ?: "NON_DEFINI";
		
		// Récupérer le type pour la dateRelevantPlusUn
		$stmt->execute([$dateRelevantPlusUn]);
		$typeJourJ1 = $stmt->fetchColumn() ?: ($currentHour < 20 && $dateRelevant === $today ? "EN_ATTENTE" : "NON_DEFINI");
		
		if ($dateRelevant > $today) {
			$typeJourJ = $typeJourJ1 = "EN_ATTENTE";
		}
		
		// Résultat
		$result = [
			"couleurJourJ" => $typeJourJ,
			"couleurJourJ1" => $typeJourJ1
		];
		
		
		
		//##############################################
	} else {
		
		
		
		
		$today = date('Y-m-d');
		$tomorrow = date('Y-m-d', strtotime('+1 day'));
		$currentHour = date('H');
		
		// Initialisation d'un tableau pour stocker les résultats avec une clé de date pour faciliter la vérification
		$datesResults = [];
		
		// Récupérer les dates et types des 20 dernières entrées
		$stmt = $db->query("SELECT date, type FROM sobriete ORDER BY date DESC LIMIT 20");
		$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			
		// Traiter chaque entrée
		foreach ($entries as $entry) {
			$datesResults[$entry['date']] = [
				"date" => $entry['date'],
				"zenflex" => $entry['type']
			];
		}
		
		// Initialisation d'un tableau temporaire pour stocker les résultats potentiels pour aujourd'hui et demain
		$tempResults = [];
		
		
		// Vérifier et préparer demain s'il n'est pas déjà inclus
		if (!array_key_exists($tomorrow, $datesResults)) {
			$tempResults[$tomorrow] = [
				"date" => $tomorrow,
				"zenflex" => "EN_ATTENTE"
			];
		}
		
		// Vérifier et préparer aujourd'hui s'il n'est pas déjà inclus
		if (!array_key_exists($today, $datesResults)) {
			$typeForToday = $currentHour < 20 ? "EN_ATTENTE" : "NON_DEFINI";
			$tempResults[$today] = [
				"date" => $today,
				"zenflex" => $typeForToday
			];
		}
		
		
		
		// Fusionner les entrées préparées pour aujourd'hui et demain avec les résultats existants
		$finalResults = $tempResults + $datesResults;

		
		// Convertir les résultats en tableau indexé pour le JSON
		$result = array_values($finalResults);
		
		
	}

	// Envoi des données au format JSON
	echo json_encode($result);
} catch (PDOException $e) {
	// En cas d'erreur, renvoyer un message d'erreur en JSON
	echo json_encode(['error' => $e->getMessage()]);
}


?>
