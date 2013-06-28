<?php
/**
 * Signup Form
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_signup_charger_dist($statut="6forum", $redirect=""){

	// Verifier le droit de s'inscrire avec ce statut
	// pas de formulaire si le mode est interdit
	include_spip('inc/autoriser');
	if (!autoriser('inscrireauteur', $statut))
		return false;

	$valeurs = array(
		'nom' => '',
		'email' => '',
		'_logo' => '',
	);

	if (isset($GLOBALS['visiteur_session']['magiclogin_pre_signup']['nom']))
		$valeurs['nom'] = $GLOBALS['visiteur_session']['magiclogin_pre_signup']['nom'];
	if (isset($GLOBALS['visiteur_session']['magiclogin_pre_signup']['logo']))
		$valeurs['_logo'] = $GLOBALS['visiteur_session']['magiclogin_pre_signup']['logo'];

	return $valeurs;
}

function formulaires_signup_verifier_dist($statut="6forum", $redirect=""){
	$erreurs = array();

	foreach(array('nom','email') as $obli){
		if (!_request($obli))
			$erreurs[$obli] = _T('info_obligatoire');
	}

	if (!isset($erreurs["email"])){
		include_spip("inc/filtres");
		if (!email_valide(_request('email'))){
			$erreurs['email'] = _T('info_email_invalide');
		}
	}

	return $erreurs;
}

function formulaires_signup_traiter_dist($statut="6forum", $redirect=""){
	$res = array();
	$email = _request('email');
	$nom = _request('nom');

	include_spip('inc/filtres');
	include_spip('inc/autoriser');
	if (!autoriser('inscrireauteur', $statut))
		$res['message_erreur'] = "Not allowed";
	else {
		// c'est une fin de signup par une source sociale
		if (isset($GLOBALS['visiteur_session']['magiclogin_pre_signup'])){
			if (sql_fetsel("*","spip_auteurs","email=".sql_quote($email))){
				// TODO : c'est un email deja existant en base
			}
			else {
				$source = $GLOBALS['visiteur_session']['magiclogin_pre_signup']['source'];
				if ($source
				  AND include_spip("action/login_with_$source")
				  AND $signup_with = charger_fonction("signup_with_twitter","magiclogin",true)){
					$res = $signup_with(array(
						'email'=>$email,
						'nom'=>$nom,
						'statut'=>$statut,
					));
					if (!isset($res['message_erreur']) AND $redirect)
						$res['redirect'] = $redirect;
				}
				else {
					$res['message_erreur'] = "Unknown signup source";
				}
			}
		}
		// c'est un signup direct
		else {
			$inscrire_auteur = charger_fonction("inscrire_auteur","action");
			$desc = $inscrire_auteur($statut, $email, $nom, array("login"=>$email));
			// erreur ?
			if (is_string($desc))
				$res['message_erreur']= $desc;
			// OK
			else
				$res = array('message_ok' => _T('form_forum_identifiant_mail'), 'id_auteur' => $desc['id_auteur']);
		}
	}

	return $res;
}