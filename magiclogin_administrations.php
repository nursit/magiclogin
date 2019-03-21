<?php
/**
 * Fichier gérant l'installation et désinstallation du plugin MagicLogin
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;



/**
 * Table principale
 * champs token pour twitter sur les auteurs
 *
 * @param array $tables
 * @return array
 */
function magiclogin_declarer_tables_objets_sql($tables) {
	$tables['spip_auteurs']['field']['twitter_token'] = "VARCHAR(255) DEFAULT '' NOT NULL";
	$tables['spip_auteurs']['field']['twitter_token_secret'] = "VARCHAR(255) DEFAULT '' NOT NULL";
	$tables['spip_auteurs']['field']['facebook_id'] = "VARCHAR(255) DEFAULT '' NOT NULL";
	$tables['spip_auteurs']['field']['google_id'] = "VARCHAR(255) DEFAULT '' NOT NULL";

	return $tables;
}


/**
 * Fonction d'installation et de mise à jour du plugin MagicLogin.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
**/
function magiclogin_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	$maj['create'] = array(
		array('sql_alter',"TABLE spip_auteurs ADD twitter_token VARCHAR(255) DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_auteurs ADD twitter_token_secret VARCHAR(255) DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_auteurs ADD facebook_id VARCHAR(255) DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_auteurs ADD google_id VARCHAR(255) DEFAULT '' NOT NULL"),
	);

	$maj['0.2.0'] = array(
		array('sql_alter',"TABLE spip_auteurs ADD twitter_token VARCHAR(255) DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_auteurs ADD twitter_token_secret VARCHAR(255) DEFAULT '' NOT NULL"),
		array('sql_alter',"TABLE spip_auteurs ADD facebook_id VARCHAR(255) DEFAULT '' NOT NULL"),
	);
	$maj['0.2.1'] = array(
		array('sql_alter',"TABLE spip_auteurs ADD google_id VARCHAR(255) DEFAULT '' NOT NULL"),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Fonction de désinstallation du plugin MagicLogin.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
**/
function magiclogin_vider_tables($nom_meta_base_version) {
	sql_alter("TABLE spip_auteurs DROP twitter_token");
	sql_alter("TABLE spip_auteurs DROP twitter_token_secret");
	sql_alter("TABLE spip_auteurs DROP facebook_id");
	sql_alter("TABLE spip_auteurs DROP google_id");

	effacer_meta($nom_meta_base_version);
}

?>
