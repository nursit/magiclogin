<?php
/**
 * Formulaire de configuration
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_configurer_magiclogin_verifier_dist(){

	$erreurs = array();

	// si secret vide, mais cle presente, reprendre celui de la config actuelle
	if (!trim(_request('facebook_consumer_secret')) AND _request('facebook_consumer_key')){
		include_spip("inc/config");
		set_request('facebook_consumer_secret',lire_config("magiclogin/facebook_consumer_secret"));
	}
	if (!trim(_request('google_client_secret')) AND _request('google_client_id')){
		include_spip("inc/config");
		set_request('google_client_secret',lire_config("magiclogin/google_client_secret"));
	}

	return $erreurs;
}
