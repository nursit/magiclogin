<?php
/**
 * Fichier gérant le login avec Facebook
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;



/**
 * S'identifier via Facebook
 * cf http://www.designaesthetic.com/2012/03/02/create-facebook-login-oauth-php-sdk/
 *
 *   Lancer l'authorisation puis recuperer les tokens
 * @param bool $is_callback
 * @param string $redirect
 * @return null|string
 */
function action_magiclogin_with_facebook_dist($args) {
	if (isset($GLOBALS['visiteur_session']['statut'])
	  AND $GLOBALS['visiteur_session']['statut'])
		return;

	include_spip("inc/config");
	include_spip("inc/filtres");
	include_spip("lib/facebook-php-sdk/facebook");

	$facebook = new Facebook(array(
		'appId' => lire_config('magiclogin/facebook_consumer_key'),
		'secret' => lire_config('magiclogin/facebook_consumer_secret'),
	));

	$redirect = (isset($_SESSION['facebook_redirect'])?$_SESSION['facebook_redirect']
		:(_request('redirect')?_request('redirect'):$GLOBALS["meta"]["adresse_site"]));

	// Check if allready loged
	$user_id = $facebook->getUser();
	if ($user_id) {
		unset($_SESSION['facebook_redirect']);
		$auteur = magiclogin_informer_facebookaccount($user_id,$facebook);
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
		if (!$user_id AND !_request('code') AND !in_array("callback",$args)){

			/**
			 * L'URL de callback qui sera utilisée suite à la validation chez FB
			 * Elle vérifiera le retour et finira la configuration
			 */
			$oauth_callback = url_absolue("magiclogin.api/facebook/callback");

			$loginUrl = $facebook->getLoginUrl(array('redirect_uri'=>$oauth_callback,'response_type'=>'code'/*,'scope'=>'email'*/));
			$GLOBALS['redirect'] = $loginUrl;

			if (_request('redirect')){
				$_SESSION['facebook_redirect'] = _request('redirect');
			}
		}
		else {
			// redirect par defaut
			$GLOBALS['redirect'] = $redirect;

			/* Error :
			$_GET = array
				  'action' => string 'login_with_facebook' (length=13)
				  'callback' => string '1' (length=1)
				  'error' => string 'access_denied' (length=13)
				  'error_code' => string '200' (length=3)
				  'error_description' => string 'Permissions error' (length=17)
				  'error_reason' => string 'user_denied' (length=11)
				  'state' => string '8e3d0d786767d320a65e7dd5687067a9' (length=32)
			 */
			if (_request("error")){
				spip_log("FB Login error : "._request("error")."|"._request("error_description")."|"._request("error_reason"),"magiclogin"._LOG_ERREUR);
			}
			/* Succes :
			$_GET = array
			  'action' => string 'login_with_facebook' (length=13)
			  'callback' => string '1' (length=1)
			  'code' => string 'AQBVXin7-1ySbUqdZbxGCjbqfKIFgG2dpIdm7-7-hXz78pV_jP8sN-9UU4ziLXAJx4V4HPle9ckP3UohQ7cJHD2fuCeH01lUhAd7k7_ZDx1sMwAV40e3-AV24PEaTU2LQgPbMymsr46_4qAAMLFweJKgdCP1popyfd27QJpBXzvD901X1Kp8Pl8gJpTp-vMLZUmJqEZmWm6B_iouMPNN7_E6gnOLqCNOEFS-ywj0LGB6zPggYpOompAVE_miXqPxC4fFj-RZucvVAnKkbgb14SaITL8HLrkSIxjzOUd8Hg7ah7JLC0Pc1leCcrPIzRsKbU6xeF4BJj7QgSeWc6qVYtiMG8vwd1RLbQ_uPXShCThVIA' (length=366)
			  'state' => string '8e3d0d786767d320a65e7dd5687067a9' (length=32)
			*/
			else {
				spip_log("FB Login innatendu : ".var_export($_GET,true),"magiclogin"._LOG_ERREUR);
			}
		}
	}
}


/**
 * Retrouver l'auteur associe aux tokens Twitter
 * et si il n'existe pas le pre-remplir a partir des infos collectees aupres de Twitter
 *
 * @param int $user_id
 * @param object $facebook
 *
 * @return array
 */
function magiclogin_informer_facebookaccount($user_id,&$facebook){

	// chercher l'auteur avec ce user_id facebook
	if (!$infos = sql_fetsel("*",
		"spip_auteurs",
		"statut!=" . sql_quote('5poubelle') . " AND facebook_id=" . sql_quote($user_id, '', 'varchar'))
	){

		// si pas trouve, on pre - rempli avec les infos de FB
		$infos = array();
		$infos['source'] = "facebook";
		$infos['facebook_id'] = $user_id;

		try {
			$user_profile = $facebook->api('/me');
			$infos['nom'] = $user_profile['name'];
			$infos['lang'] = reset(explode("_", $user_profile['lang']));
			# debug : a virer car donnees persos
			spip_log("user_profile:" . var_export($user_profile, true), "magiclogin");
		} catch (FacebookApiException $e) {
			spip_log("Echec /me FacebookApiException $e", "magiclogin" . _LOG_ERREUR);
		}
	}

	return $infos;
}



function magiclogin_signup_with_facebook_dist($desc, $pre_signup_infos){
	$desc['facebook_id'] = $pre_signup_infos['facebook_id'];
	return $desc;
}