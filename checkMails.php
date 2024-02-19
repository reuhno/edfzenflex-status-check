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


// Paramètres de connexion IMAP
$hostname = '{mail.customprovider.com:993/imap/ssl}';
$inbox_folder = "INBOX";
$username = 'custommail@customdomain.fr';
$password = 'xxxxxxxxxxxxx';


// Créer un objet DateTime pour la date et l'heure actuelles
$date = new DateTime("now", new DateTimeZone("Europe/Paris"));
// Formater la date
$formattedDateLog = $date->format('[d-M-Y H:i:s e]') ." ";

// Connexion à la boîte de réception IMAP
$inbox = imap_open($hostname.$inbox_folder, $username, $password) or die('Impossible de se connecter au serveur de messagerie: ' . imap_last_error());




// Recherche de messages non lus avec un sujet spécifique
$emails = imap_search($inbox, 'UNSEEN SUBJECT "Votre contrat Zen Flex"', SE_UID);

if ($emails) {
foreach ($emails as $email_uid) {
	// Utilisation de imap_fetch_overview avec l'UID du message
	$overview = imap_fetch_overview($inbox, $email_uid, FT_UID);

	if ($overview && is_array($overview) && isset($overview[0])) {
		$subject = $overview[0]->subject;
		
		$subject = iconv_mime_decode($subject, 0, "UTF-8");

	

		// Analyse du sujet du message
		$type = 'NON_DEFINI';
		if (strpos($subject, 'demain Jour Sobriété') !== false) {
			$type = 'SOBRIETE';
		} elseif (strpos($subject, 'demain Jour Bonus') !== false) {
			$type = 'BONUS';
		}
		elseif (strpos($subject, 'demain Jour Eco') !== false) {
			$type = 'ECO';
		}


		
		if (preg_match('/(\d{1,2})\s(janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre)\s(\d{4})/u', $subject, $matches)) {
			$day = $matches[1];
			$monthName = $matches[2];
			$year = $matches[3];
		
			// Tableau de correspondance des mois en français vers leur numéro
			$monthNames = [
				'janvier' => '01', 'février' => '02', 'fevrier' => '02', 'mars' => '03',
				'avril' => '04', 'mai' => '05', 'juin' => '06',
				'juillet' => '07', 'août' => '08', 'aout' => '08',
				'septembre' => '09', 'octobre' => '10',
				'novembre' => '11', 'décembre' => '12', 'decembre' => '12'
			];
		
			// Récupérer le numéro du mois
			$month = $monthNames[strtolower($monthName)];
		
			// Création de l'objet DateTime et formatage
			$dateObject = DateTime::createFromFormat('Y-m-d', "$year-$month-$day");
			$formattedDate = $dateObject->format('Y-m-d');
		
			// Appel à setSobriete.php
			$url = "https://CUSTOMDOMAIN.fr/setSobriete.php?date=$formattedDate&type=$type";
			$res = file_get_contents($url);
			
			if (strpos($res, "OK -") === 0) {
				// Marquer le mail comme lu
				imap_setflag_full($inbox, $email_uid, "\\Seen", ST_UID);
			
				// Déplacer le mail dans le dossier "EN_BDD"
				$mailBox = 'EN-BDD'; // Assurez-vous que le chemin du dossier est correct
				if (imap_mail_move($inbox, $email_uid, $mailBox, CP_UID)) {
					imap_expunge($inbox);
				} else {
					echo($formattedDateLog."KO - Impossible de déplacer l'email UID: $email_uid dans $mailBox");
				}
			}
			else{
				echo ($formattedDateLog.$res);
				echo ("\n");
			}
			
			
			
		}

		
	}	else {
		echo($formattedDateLog."KO - Aucun aperçu disponible pour l'UID: $email_uid");
	}
	
}

}

// Fermeture de la connexion IMAP
imap_close($inbox);
?>