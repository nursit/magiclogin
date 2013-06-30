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
		return array('message_erreur'=>_T('signup:erreur_signup_inscription_desactivee'),'editable'=>false);

	$valeurs = array(
		'nom' => '',
		'email' => '',
		'_logo' => '',
	);

	// si l'email fourni par le social login, on a pas le droit de le changer
	if (isset($GLOBALS['visiteur_session']['magiclogin_pre_signup']['email'])){
		$valeurs['email'] = $GLOBALS['visiteur_session']['magiclogin_pre_signup']['email'];
		$valeurs['_email_readonly'] = true;
	}

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
			if ($row = sql_fetsel("*","spip_auteurs","email=".sql_quote($email))){
				// c'est un email deja existant en base
				// on lance une inscription sur cet email

				magiclogin_signup_confirmer_email($statut,$email,$nom,$row,$GLOBALS['visiteur_session']['magiclogin_pre_signup'],$redirect);
				$res = array('message_ok' => _T('signup:info_confirmer_email_deja_utilise',array('email'=>$email,"social_source"=>ucfirst($GLOBALS['visiteur_session']['magiclogin_pre_signup']['source']))));
			}
			else {
				$source = $GLOBALS['visiteur_session']['magiclogin_pre_signup']['source'];
				$infos = array(
				  'email'=>$email,
				  'nom'=>$nom,
				  'statut'=>$statut,
			  );
				$res = magiclogin_finish_signup($source, $infos, $GLOBALS['visiteur_session']['magiclogin_pre_signup']);
				// redirect dans tous les cas pour montrer qu'on est loge
				if (!isset($res['message_erreur']))
					$res['redirect'] = ($redirect?$redirect:self());
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


function magiclogin_signup_confirmer_email($statut,$email,$nom,$desc,$pre_signup_infos,$redirect=""){
	include_spip("action/inscrire_auteur");
	// attribuer un jeton de confirmation
	$jeton = auteur_attribuer_jeton($desc['id_auteur']);

	// stocker les infos de pre_signup dans un fichier
	$file = sous_repertoire(_DIR_TMP,"magiclogin").$desc['id_auteur']."-".$jeton;
	$pre_signup_infos['email'] = $email;
	$pre_signup_infos['statut'] = $statut;
	$pre_signup_infos['nom'] = $nom;
	ecrire_fichier($file,serialize($pre_signup_infos));

	// generer un mail
	$contexte = $desc;
	$contexte['nom'] = $nom;
	$contexte['mode'] = $statut;
	$contexte['redirect'] = $redirect;
	$contexte['url_confirm'] = generer_url_action('confirm_signin','',true,true);
	$contexte['url_confirm'] = parametre_url($contexte['url_confirm'],'email',$email);
	$contexte['url_confirm'] = parametre_url($contexte['url_confirm'],'jeton',$jeton);

	$message = recuperer_fond('modeles/mail_confirmsignup',$contexte);
	include_spip("inc/notifications");
	notifications_envoyer_mails($email,$message);
}


/**
 * Finir l'inscription sociale
 * @param string $source
 *   source dont sont issues les infos et qui sert a peupler la table auteur
 * @param $infos
 *   infos dispo
 * @param $pre_signup_infos
 *   infos issues du reseau social
 * @return array
 */
function magiclogin_finish_signup($source, $infos, $pre_signup_infos){
	$desc = array(
		'nom'=>$infos['nom'],
		'email'=>$infos['email'],
		'login'=>$infos['email'],
	);

	if ($source
	  AND include_spip("action/login_with_$source")
	  AND $signup_with = charger_fonction("signup_with_$source","magiclogin",true)){
		$desc = $signup_with($desc,$pre_signup_infos);
	}
	else
		return array('message_erreur' => "Unknown signup source");

	if (isset($pre_signup_infos['bio']) AND $pre_signup_infos['bio'])
		$desc['bio'] = $pre_signup_infos['bio'];

	if (isset($infos['id_auteur'])){
		if (isset($infos['statut']))
			$desc['statut'] = $infos['statut'];

		include_spip('inc/autoriser');
		include_spip("action/editer_auteur");
		// lever l'autorisation pour pouvoir modifier le statut
		autoriser_exception('modifier','auteur',$infos['id_auteur']);
		auteur_modifier($infos['id_auteur'], $desc);
		autoriser_exception('modifier','auteur',$infos['id_auteur'],false);
	}
	else {
		include_spip("action/inscrire_auteur");
		$desc = inscription_nouveau($desc);
		if (is_array($desc)
			AND isset($infos['statut'])){
			autoriser_exception('modifier','auteur',$desc['id_auteur']);
			auteur_modifier($desc['id_auteur'], array('statut'=>$desc['statut']=$infos['statut']));
			autoriser_exception('modifier','auteur',$desc['id_auteur'],false);
		}
	}

	// l'auteur a-t-il un logo ?
	if (isset($pre_signup_infos['logo'])){
		$chercher_logo = charger_fonction("chercher_logo","inc");
		if (!$chercher_logo($desc['id_auteur'],"id_auteur")){
			include_spip("inc/distant");
			$copie = _DIR_RACINE . copie_locale($pre_signup_infos['logo'],'modif');
			$parts = pathinfo($copie);
			if (in_array($parts['extension'],array('gif','png','jpg'))){
				@rename($copie,_DIR_IMG . "auton".$desc['id_auteur'].".".$parts['extension']);
			}
		}
	}


	// erreur ?
	if (is_string($desc)){
		return array('message_erreur'=> $desc);
	}
	// OK
	else{
		include_spip("inc/auth");
		auth_loger($desc);
		// super on a reussi : on loge l'auteur !
		return array('message_ok' => _T('signup:info_signup_ok'), 'id_auteur' => $desc['id_auteur']);
	}
}