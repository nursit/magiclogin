<?php
/**
 * Fonctions utiles au plugin MagicLogin
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

// pour desactiver la verification du certificat de securite dans l'auth via google
// define('_MAGICLOGIN_IGNORE_SSL_VERIFYPEER',true);

function magiclogin_ok(){
	if (magiclogin_facebook_ok() OR magiclogin_google_ok() OR magiclogin_twitter_ok() OR magiclogin_persona_ok())
		return ' ';
	return '';
}

/**
 * verifier que FB est configure
 * @return string
 */
function magiclogin_facebook_ok(){
	include_spip("inc/config");
	if (lire_config('magiclogin/activer_facebook','oui')=='oui'
		AND lire_config('magiclogin/facebook_consumer_key')
	  AND lire_config('magiclogin/facebook_consumer_secret'))
		return ' ';
	return '';
}

/**
 * verifier que Google est configure
 * @return string
 */
function magiclogin_google_ok(){
	include_spip("inc/config");
	if (lire_config('magiclogin/activer_google','oui')=='oui'
		AND lire_config('magiclogin/google_client_id')
		AND lire_config('magiclogin/google_client_secret')
		AND lire_config('magiclogin/google_api_key'))
		return ' ';
	return '';
}

/**
 * Verifier que Twitter est configure
 * @return string
 */
function magiclogin_twitter_ok(){
	if (!defined("_DIR_PLUGIN_TWITTER"))
		return '';
	include_spip("inc/config");
	if (lire_config('magiclogin/activer_twitter','oui')=='oui'
		AND lire_config('microblog/twitter_consumer_key')
	  AND lire_config('microblog/twitter_consumer_secret'))
		return ' ';
	return '';
}

function magiclogin_persona_ok(){
	include_spip("inc/config");
	if (lire_config('magiclogin/activer_persona','oui')=='oui')
		return ' ';
	return '';
}

/**
 * Inserer la css de Persona dans le head
 * @param string $flux
 * @return string
 */
function magiclogin_insert_head_css($flux){
	$flux .= '<link rel="stylesheet"  href="'.find_in_path("css/magiclogin.css").'" type="text/css">';
	return $flux;
}

/**
 * Inserer le javascript de Persona dans le head
 * @param string $flux
 * @return string
 */
function magiclogin_insert_head($flux){
	$flux .= '<script src="'.find_in_path("javascript/persona.js").'" type="text/javascript"></script>';
	return $flux;
}

/**
 * Balise #LOGIN_LINKS qui affiche les liens de connexion si on est pas deja identifie
 *
 * @param $p
 * @return mixed
 */
function balise_LOGIN_LINKS_dist($p) {
	$_target_url = interprete_argument_balise(1,$p);

	if (!$_target_url AND $_target_url!="''"){
		$_target_url = "";
	}
	else
		$_target_url = "\''.addslashes($_target_url).'\'";

	$p->code = "'<!--magiclogin--><' . '"."?php echo magiclogin_login_links($_target_url); ?'.'>'";

	$p->interdire_scripts = false;
	return $p;
}

/**
 * Fonction appelee par la balise #LOGIN_LINKS, par commodite
 * @param string $target_url
 * @return array|string
 */
function magiclogin_login_links($target_url=''){
	// if already connected : empty return
	if (isset($GLOBALS['visiteur_session']['statut'])
	  AND $GLOBALS['visiteur_session']['statut'])
		return '';

	return recuperer_fond("inclure/login_links",array('url'=>$target_url));
}


/**
 * Balise #LOGOUT_LINK qui affiche le lien de deconexion si on est identifie
 * @param $p
 * @return mixed
 */
function balise_LOGOUT_LINK_dist($p) {
	$_target_url = interprete_argument_balise(1,$p);

	if (!$_target_url AND $_target_url!="''"){
		$_target_url = "";
	}
	else
		$_target_url = "\''.addslashes($_target_url).'\'";

	$p->code = "'<!--magiclogin--><' . '"."?php echo magiclogin_logout_link($_target_url); ?'.'>'";

	$p->interdire_scripts = false;
	return $p;
}

/**
 * Fonction appelee par la balise #LOGOUT_LINK par commodite
 * @param string $target_url
 * @return array|string
 */
function magiclogin_logout_link($target_url=''){
	// if not connected : empty return
	if (!isset($GLOBALS['visiteur_session']['statut'])
	  OR !$GLOBALS['visiteur_session']['statut'])
		return '';

	return recuperer_fond("inclure/logout_link",array('url'=>$target_url));
}

/**
 * Filtre pour afficher une partie du code secret de l'app FB (plus pratique que input type="password"
 * @param string $secret
 * @return string
 */
function magiclogin_masquer_secret($secret){
	$affiche = "";
	if (strlen($secret))
		$affiche = substr($secret,0,4).str_pad("*",strlen($secret)-8,"*").substr($secret,-4);
	return $affiche;
}

/**
 * Ajouter les liens de login rapide dans le form de login
 * @param $flux
 * @return mixed
 */
function magiclogin_formulaire_fond($flux){
	// determiner le nom du formulaire
	$form = $flux['args']['form'];
	if ($form=="login") {
		if (magiclogin_ok()){
			$links = magiclogin_login_links(_request('url'));
			// trouver le dernier fieldset, puis le premier input apres : c'est le bouton
			$pf = strripos($flux['data'],"</fieldset>");
			if ($pi = stripos($flux['data'],"<p",$pf)){
				$flux['data'] = substr_replace($flux['data'],$links,$pi,0);
			}
		}
	}

	return $flux;
}
?>
