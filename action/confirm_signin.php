<?php
/**
 * Fichier gérant le routage du login vers le bon service
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Cette action permet de confirmer une connexion
 * @return void
 */
function action_confirm_signin_dist() {
	$jeton = _request('jeton');
	$email = _request('email');

	include_spip('action/inscrire_auteur');
	if ($auteur = auteur_verifier_jeton($jeton)
	  AND $auteur['email']==$email){

		// OK c'est email licite :

		// il-y-a-t-il des infos de pre-inscriptions dispos ?
		$file = sous_repertoire(_DIR_TMP,"magiclogin").$auteur['id_auteur']."-".$jeton;
		$pre_signup_infos = "";
		lire_fichier($file,$pre_signup_infos);
		if ($pre_signup_infos
		  AND $pre_signup_infos = unserialize($pre_signup_infos)){

			// ne pas recuperer la bio si deja dispo
			if ($auteur['bio'])
				unset($pre_signup_infos['bio']);

			$source = $pre_signup_infos['source'];
			if ($source
			  AND include_spip("action/login_with_$source")
			  AND $signup_with = charger_fonction("signup_with_twitter","magiclogin",true)){
				$infos = array(
					'id_auteur'=>$auteur['id_auteur'],
				  'email'=>$auteur['email'],
				  'nom'=>$auteur['nom'],
			  );
				if (isset($pre_signup_infos['statut'])
				  AND intval($pre_signup_infos['statut'])>intval($auteur['statut']))
					$infos['statut'] = $pre_signup_infos['statut'];
				$res = $signup_with($infos,$pre_signup_infos);
			}
		}

		// on le loge => ca va confirmer son statut (si besoin) et c'est plus sympa
		include_spip('inc/auth');
		auth_loger($auteur);

		// et on efface son jeton
		auteur_effacer_jeton($auteur['id_auteur']);

		// si pas de redirection demandee, rediriger vers public ou prive selon le statut de l'auteur
		// TODO: ne semble pas marcher si inscrit non visiteur, a debug
		if (!_request('redirect')){
			// on passe id_auteur explicite pour forcer une lecture en base de toutes les infos
			if (autoriser('ecrire','','',$auteur['id_auteur'])){
				// poser un cookie admin aussi
				$cookie = charger_fonction('cookie','action');
				$cookie("@".$GLOBALS['visiteur_session']['login']);
				$GLOBALS['redirect'] = _DIR_RESTREINT_ABS;
			}
			else
				$GLOBALS['redirect'] = $GLOBALS['meta']['adresse_site'];
		}
	}
	else {
		// lien perime :
		if ($GLOBALS['visiteur_session']['id_auteur']){
			// on passe id_auteur explicite pour forcer une lecture en base de toutes les infos
			if (autoriser('ecrire','','',$GLOBALS['visiteur_session']['id_auteur']))
				$GLOBALS['redirect'] = _DIR_RESTREINT_ABS;
			else
				$GLOBALS['redirect'] = $GLOBALS['meta']['adresse_site'];
		}
		else
			// rediriger vers la page de login si pas encore loge
			$GLOBALS['redirect'] = parametre_url(generer_url_public('login','',false),'url',_request('redirect'));
	}

}

?>
