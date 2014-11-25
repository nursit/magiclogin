<?php
/**
 * Fichier gérant le login avec Twitter
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * S'identifier via Twitter
 *   Lancer l'authorisation puis recuperer les tokens
 * @param bool $is_callback
 * @param string $redirect
 * @return null|string
 */
function action_magiclogin_with_twitter_dist($is_callback = false, $redirect = null) {
	if (isset($GLOBALS['visiteur_session']['statut'])
	  AND $GLOBALS['visiteur_session']['statut'])
		return;

	if (!$is_callback){
		// au premier appel
		if (!isset($GLOBALS['visiteur_session']['statut'])
		  OR !$GLOBALS['visiteur_session']['statut']){

			// lancer la demande d'autorisation en indiquant le nom de l'action qui sera rappelee au retour
			include_spip("action/twitter_oauth_authorize");
			twitter_oauth_authorize("magiclogin_with_twitter",_request('redirect'));
		}
	}
	else {
		// appel au retour de l'authorize
		// recuperer le screenname
		$tokens = array(
			'twitter_token' => $GLOBALS['visiteur_session']['access_token']['oauth_token'],
			'twitter_token_secret' => $GLOBALS['visiteur_session']['access_token']['oauth_token_secret'],
		);

		// retrouver l'auteur
		$auteur = magiclogin_informer_twitteraccount($tokens);
		if (!isset($auteur['id_auteur'])){
			// si pas trouvé, on redirige vers l'inscription en notant en session les infos collectees
			// pour le pre-remplissage
			include_spip("inc/session");
			session_set("magiclogin_pre_signup",$auteur);
			// et rediriger vers la page de signup
			return parametre_url(generer_url_public("signup","",true),"redirect",$redirect,"&");
		}
		else {
			// loger l'auteur
			include_spip("inc/auth");
			auth_loger($auteur);
			// et voila
		}
	}
}


/**
 * Retrouver l'auteur associe aux tokens Twitter
 * et si il n'existe pas le pre-remplir a partir des infos collectees aupres de Twitter
 *
 * @param array $tokens
 *   twitter_token : token du compte a utiliser
 *   twitter_token_secret : token secret du compte a utiliser
 * @return array
 */
function magiclogin_informer_twitteraccount($tokens){

	// chercher l'auteur avec ces tokens
	if (!$infos = sql_fetsel("*",
		  "spip_auteurs",
		  "statut!=".sql_quote('5poubelle')."AND twitter_token=".sql_quote($tokens['twitter_token'])." AND twitter_token_secret=".sql_quote($tokens['twitter_token_secret']))){

		include_spip("inc/twitter");
		$infos = array();
		$infos['source'] = "twitter";
		$infos['twitter_token'] = $tokens['twitter_token'];
		$infos['twitter_token_secret'] = $tokens['twitter_token_secret'];

		$options = $tokens;
		$options['force'] = true;
		if ($res = twitter_api_call("account/verify_credentials","get",array(),$options)){
			$infos['login'] = $res['screen_name'];
			$infos['nom'] = $res['name'];
			$infos['bio'] = $res['description'];
			$infos['lang'] = $res['lang'];
			$infos['logo'] = $res['profile_image_url'];
		}
		else {
			spip_log("Echec account/verify_credentials lors de l'inscription avec twitter'","magiclogin"._LOG_ERREUR);
		}
	}

	return $infos;
}


function magiclogin_signup_with_twitter_dist($desc, $pre_signup_infos){
	$desc['twitter_token'] = $pre_signup_infos['twitter_token'];
	$desc['twitter_token_secret'] = $pre_signup_infos['twitter_token_secret'];
	return $desc;
}
?>