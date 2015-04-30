<?php
/*
Plugin Name: Daily Cleaner & Optimizer Lite
Plugin URI: http://additifstabac.free.fr/index.php/daily-cleaner-optimizer-lite-un-plugin-tres-leger-pour-nettoyer-et-optimiser-wordpress-automatiquement/
Description: Nettoyage et optimisation automatique et/ou manuelle de la base de données.
Text Domain: daily-clean-opt-lite
Version: 1.2.1
Author: luciole135
Author URI: http://additifstabac.free.fr
*/

$today = gmdate('Ymd', current_time('timestamp'));
$auto=($today <> get_option('clean_opt_option'));

// nettoyage et optimisation pour un changement de date
if ($auto) {
	daily_cleaner_optimizer_lite();
	update_option('clean_opt_option', $today);
}

// si connexion à la page d'administration, chragement du widget
if (is_admin()) {	
	function register_clean_opt () {
		wp_add_dashboard_widget("Clean_Opt","Daily Cleaner Optimizer Lite","clean_opt_widget");
	}
	function clean_opt_textdomain() {
		load_plugin_textdomain('daily-clean-opt-lite', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	function clean_opt_widget () {
		global $wpdb;
		add_action('plugins_loaded', 'clean_opt_textdomain');
	
		$clean=(isset($_POST["clean_opt"]) ? 1 :0); //l'admin demande-t-il le nettoyage et l'optimisation ?
		
		// initialisation variables
		$data=0;
		$tableau='';
	
		// nettoyage et optimisation sur demande de l'admin
		if ($clean) daily_cleaner_optimizer_lite();	
		
		// listes des tables ayant des pertes
		$tables = $wpdb->get_results("SHOW TABLE STATUS");
		foreach ($tables as $table_name) { 
			if ($table_name->Data_free <> 0) {
				$tableau.= "<tr><td>".$table_name->Name."</td> <td align='right'>".number_format_i18n($table_name->Data_free)."</td></tr>";
				$data +=$table_name->Data_free;
				}
			}
		
		// affichage du widget dans le tableau de bord
		echo "<table class='widefat'><tr>";
		if ($data == 0) 
			echo "<td rowspan='2' style='text-align:center' ><b>".__('Toutes les tables de données sont déjà optimisées','daily-clean-opt-lite')."</b></td></tr></table>";
		else {	
			echo "<p>".__('Le nettoyage des tables <b>wp_post</b> et <b>wp_postmeta</b> ainsi que l&quot;optimisation de <b>toutes</b> les tables de données sont réalisés automatiquement chaque jour après minuit.','daily-clean-opt-lite')."</p>";
			echo "<th><b>".__('Tables non optimisées','daily-clean-opt-lite')."</b></th><th style='text-align:right'><b>".__('Pertes (en octets)','daily-clean-opt-lite')."</b></th></tr>".$tableau."</table>";
			}
		
		$jumlah = $wpdb->get_var("select count(*) from $wpdb->posts where post_type='revision' OR post_status = 'auto-draft'");
		$jumlah += $wpdb->get_var("select count(*) from $wpdb->postmeta where meta_key ='_edit_last' or meta_key = '_edit_lock' ");
	
		echo "<h2 style='text-align:center'><font color='#003399'></font>";
		
		if ($jumlah == 0) 
			echo __('0 fichier','daily-clean-opt-lite')."</h2><p>";
		else 
			echo number_format_i18n($jumlah).__(' fichiers','daily-clean-opt-lite')."</h2><p>".__('Ces fichiers sont des sauvegardes automatiques créées par WordPress lors de l&quot;écriture ou modification d&quot;articles. Leur suppression accélère la génération des pages par WordPress. L&quot;optimisation des tables est une défragmentation qui optimise leur fonctionnement.','daily-clean-opt-lite')."</p>";
		
		echo "<div style='text-align:center'><form method='post'><input type='submit' name='clean_opt' value='".__('Nettoyer & optimiser immédiatement','daily-clean-opt-lite')."' class='button-primary' /></form></div>";
	}

add_action('wp_dashboard_setup','register_clean_opt');
}

function daily_cleaner_optimizer_lite()	{	
	// nettoyage et optimisation des tables de données
	global $wpdb;
	$wpdb->query("delete from $wpdb->posts where post_type='revision' OR post_status = 'auto-draft'");
	$wpdb->query("delete from $wpdb->postmeta where meta_key ='_edit_last' or meta_key = '_edit_lock'");
	$tables = $wpdb->get_results("SHOW TABLE STATUS");
	foreach($tables as $table_name) 
		{ if ($table_name->Data_free <> 0) $wpdb->query('OPTIMIZE TABLE '.$table_name->Name);
		}
}
?>