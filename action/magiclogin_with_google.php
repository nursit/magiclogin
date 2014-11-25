<?php
/**
 * Fichier gérant le login avec google
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;



/**
 * S'identifier via Google
 *   http://phppot.com/php/php-google-oauth-login/
 *
 *   https://console.developers.google.com/
 *   https://github.com/google/google-api-php-client
 *   https://developers.google.com/accounts/docs/OpenIDConnect
 *   https://developers.google.com/api-client-library/php/auth/web-app
 *
 * Lancer l'authorisation puis recuperer les tokens
 * @param bool $is_callback
 * @param string $redirect
 * @return null|string
 */
function action_magiclogin_with_google_dist() {
	if (isset($GLOBALS['visiteur_session']['statut'])
	  AND $GLOBALS['visiteur_session']['statut'])
		return;

	include_spip("inc/config");
	include_spip("inc/filtres");

	include_spip("lib/google-api-php-client/autoload");


	// Fill CLIENT ID, CLIENT SECRET ID, REDIRECT URI from Google Developer Console
	$client_id = lire_config('magiclogin/google_client_id');
	$client_secret = lire_config('magiclogin/google_client_secret');
	$simple_api_key = lire_config('magiclogin/google_api_key');

	/**
	 * L'URL de callback qui sera utilisée suite à la validation chez FB
	 * Elle vérifiera le retour et finira la configuration
	 */
	$oauth_callback = url_absolue('magiclogin.api/google/callback');

	$redirect = (isset($_SESSION['google_redirect'])?$_SESSION['google_redirect']
			:(_request('redirect')?_request('redirect'):$GLOBALS["meta"]["adresse_site"]));

	//Create Client Request to access Google API
	$client = new Google_Client();
	$client->setApplicationName("MagicLogin with Google");
	$client->setClientId($client_id);
	$client->setClientSecret($client_secret);
	$client->setRedirectUri($oauth_callback);
	$client->setDeveloperKey($simple_api_key);
	$client->addScope("https://www.googleapis.com/auth/userinfo.email");

	//Send Client Request
	$objOAuthService = new Google_Service_Oauth2($client);

	//Logout
	/*
	if (isset($_REQUEST['logout'])) {
	  unset($_SESSION['access_token']);
	  $client->revokeToken();
	  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL)); //redirect user back to page
	}
	*/

	//Set Access Token to make Request
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	  $client->setAccessToken($_SESSION['access_token']);
	}

	//Authenticate code from Google OAuth Flow
	//Add Access Token to Session
	if ($code = _request('code')) {
		try {
			$client->authenticate($code);
		}
		catch (Exception $e){
			$erreur = $e->getMessage();
			$GLOBALS['redirect'] = parametre_url(generer_url_public("login","",true),'var_erreur',$erreur,"&");
			return;
		}
	}

	// Check if allready loged
	// Get User Data from Google Plus
	// If New, Insert to Database
	if ($client->getAccessToken()){
		unset($_SESSION['google_redirect']);
		$_SESSION['access_token'] = $client->getAccessToken();
		$userData = $objOAuthService->userinfo->get();
		$auteur = magiclogin_informer_googleaccount($userData,$objOAuthService);
		if (!isset($auteur['id_auteur'])){
			// si pas trouvé, on redirige vers l'inscription en notant en session les infos collectees
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
	}
	else {

		// au premier appel
		// si pas deja loge, et si pas en retour de login, lancer la demande
		if (!_request('code') AND !_request('callback')){

			$loginUrl = $client->createAuthUrl();
			$GLOBALS['redirect'] = $loginUrl;

			if (_request('redirect')){
				$_SESSION['google_redirect'] = _request('redirect');
			}
		}
		else {
			// redirect par defaut
			$GLOBALS['redirect'] = $redirect;

			/* Error :
			$_GET = array
				  'action' => string 'login_with_google' (length=13)
				  'callback' => string '1' (length=1)
				  'error' => string 'access_denied' (length=13)
				  'error_code' => string '200' (length=3)
				  'error_description' => string 'Permissions error' (length=17)
				  'error_reason' => string 'user_denied' (length=11)
				  'state' => string '8e3d0d786767d320a65e7dd5687067a9' (length=32)
			 */
			if (_request("error")){
				spip_log("Google Login error : "._request("error")."|"._request("error_description")."|"._request("error_reason"),"magiclogin"._LOG_ERREUR);
			}
			/* Succes :
			$_GET = array
			  'action' => string 'login_with_google' (length=13)
			  'callback' => string '1' (length=1)
			  'code' => string 'AQBVXin7-1ySbUqdZbxGCjbqfKIFgG2dpIdm7-7-hXz78pV_jP8sN-9UU4ziLXAJx4V4HPle9ckP3UohQ7cJHD2fuCeH01lUhAd7k7_ZDx1sMwAV40e3-AV24PEaTU2LQgPbMymsr46_4qAAMLFweJKgdCP1popyfd27QJpBXzvD901X1Kp8Pl8gJpTp-vMLZUmJqEZmWm6B_iouMPNN7_E6gnOLqCNOEFS-ywj0LGB6zPggYpOompAVE_miXqPxC4fFj-RZucvVAnKkbgb14SaITL8HLrkSIxjzOUd8Hg7ah7JLC0Pc1leCcrPIzRsKbU6xeF4BJj7QgSeWc6qVYtiMG8vwd1RLbQ_uPXShCThVIA' (length=366)
			  'state' => string '8e3d0d786767d320a65e7dd5687067a9' (length=32)
			*/
			else {
				spip_log("Google Login innatendu : ".var_export($_GET,true),"magiclogin"._LOG_ERREUR);
			}
		}
	}
}


/**
 * Retrouver l'auteur associe aux tokens Twitter
 * et si il n'existe pas le pre-remplir a partir des infos collectees aupres de Twitter
 *
 * @param int $user_id
 * @param object $google
 *
 * @return array
 */
function magiclogin_informer_googleaccount($userData,&$google){
	// chercher l'auteur avec ce user_id google
	if (!$infos = sql_fetsel("*",
		"spip_auteurs",
		"statut!=" . sql_quote('5poubelle') . " AND google_id=" . sql_quote($userData->id, '', 'varchar'))
	){
		// si pas trouve, on pre - rempli avec les infos de Google
		$infos = array();
		$infos['source'] = "google";
		$infos['google_id'] = $userData->id;
		$infos['nom'] = $userData->name;

		// email suggere pre-rempli mais modifiable car google impose possiblement son email @gmail.com qu'on ne veut pas forcement utiliser
		// (on a des alias pour un meme compte mail)
		$infos['suggested_email'] = $userData->email;

		// on met l'email google en login ca fera double login possible
		$infos['login'] = $userData->email;
		$infos['logo'] = $userData->picture;
	}

	return $infos;
}



function magiclogin_signup_with_google_dist($desc, $pre_signup_infos){
	$desc['google_id'] = $pre_signup_infos['google_id'];
	return $desc;
}