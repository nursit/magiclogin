<?php
/**
 * Fichier gerant le login avec google
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cedric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * S'identifier via Google
 *	 https://console.developers.google.com/
 *   https://developers.google.com/identity/sign-in/web/sign-in
 *   https://github.com/google/google-api-php-client
 *
 * @param array $arg $arg[0] -> id_token $arg[1]-> redirect
 * @return null|string
 */

function action_magiclogin_with_google_dist($arg) {
	if (isset($GLOBALS['visiteur_session']['statut'])
		AND $GLOBALS['visiteur_session']['statut'])
		return;

 	$id_token = isset($arg) && isset($arg[0]) ? $arg[0] : false;
	if(!$id_token) return;

	include_spip("inc/config");
	include_spip("inc/filtres");
	include_spip("lib/google-api-php-client/autoload");

	$redirect = isset($arg) && isset($arg[1]) ? $arg[1] : (isset($_SESSION['google_redirect'])?$_SESSION['google_redirect']
	 		:(_request('redirect')?_request('redirect'):$GLOBALS["meta"]["adresse_site"]));

	// Verifier le $id_token avec la lib php google-api-php-client
	$client = new Google_Client(['client_id' =>  lire_config('magicplugin/google_client_id')]);  // Specify the CLIENT_ID of the app that accesses the backend
	$userdata = $client->verifyIdToken($id_token);

	if($userdata['email_verified']) {
			//id_token est verifie
			$auteur = magiclogin_informer_googleaccount($userdata);
			if (!isset($auteur['id_auteur'])){
				// si pas trouve, on redirige vers l'inscription en notant en session les infos collectees
				// pour le pre-remplissage
				include_spip("inc/session");
				session_set("magiclogin_pre_signup",$auteur);
				// et rediriger vers la page de signup
				$GLOBALS['redirect'] = parametre_url(generer_url_public("signup","",true),"redirect",$redirect,"&");
			}
			else {
				// loger l'auteur
				include_spip("inc/auth");
				auth_loger($auteur);
				// et voila
				$GLOBALS['redirect'] = $redirect;
			}
	} else {
		// Token n'a pas ete verifie
		return;
	}
}


/**
 * Retrouver l'auteur associe au token Google
 * et si il n'existe pas le pre-remplir a partir des infos collectees aupres de Google
 *
 * @param array $userdata
 *
 * @return array
 */
function magiclogin_informer_googleaccount($userdata){
	// chercher l'auteur avec ce user_id google
	if (!$infos = sql_fetsel("*",
		"spip_auteurs",
		"statut!=" . sql_quote('5poubelle') . " AND google_id=" . sql_quote($userdata['sub'], '', 'varchar'))
	){
		// si pas trouve, on pre - rempli avec les infos de Google
		$infos = array();
		$infos['source'] = "google";
		$infos['google_id'] = $userdata['sub'];
		$infos['nom'] = $userdata['name'];

		// email suggere pre-rempli mais modifiable car google impose possiblement son email @gmail.com qu'on ne veut pas forcement utiliser
		// (on a des alias pour un meme compte mail)
		$infos['suggested_email'] = $userdata['email'];

		// on met l'email google en login ca fera double login possible
		$infos['login'] =$userdata['email'];
		$infos['logo'] = $userdata['picture'];
	}

	return $infos;
}



function magiclogin_signup_with_google_dist($desc, $pre_signup_infos){
	$desc['google_id'] = $pre_signup_infos['google_id'];
	return $desc;
}
