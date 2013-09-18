<?php
/**
 * @package SeoPilot
 * @version 1.0
 */
/*
Plugin Name: SeoPilot
Plugin URI: http://www.starla.pl/wtyczka-seopilot-dla-wordpress/
Description: Plugin do obsługi systemu SeoPilot.pl
Version: 1.0
Author: Radosław Litwin
Author URI: http://www.starla.pl/
License: GPLv2 or later
*/

/*
Copyright 2013  Radosław Litwin (radek.litwin@gmail.com)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class SeoPilot {

	static $translationNamespace = 'seopilot';

	function SeoPilot_Admin_Menu() {
		add_menu_page( 'SeoPilot', 'SeoPilot', 'manage_options', 'seopilot-admin-menu', array('SeoPilot', 'SeoPilot_Admin_Options') );
	}

	function SeoPilot_Admin_Options() {

		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$options					= array();

		$SEOPILOT_USER				= 'SEOPILOT_USER';
		$SEOPILOT_CHARSET			= 'SEOPILOT_CHARSET';
		$SEOPILOT_TEST				= 'SEOPILOT_TEST';

		$options[ $SEOPILOT_USER ]		= get_option( $SEOPILOT_USER );
		$options[ $SEOPILOT_CHARSET ]	= get_option( $SEOPILOT_CHARSET );
		$options[ $SEOPILOT_TEST ]		= get_option( $SEOPILOT_TEST );

		if ( isset( $_POST['SaveSeoPilotSettings'] ) ) {

			$options[ $SEOPILOT_USER ]		= $_POST[ $SEOPILOT_USER ];
			$options[ $SEOPILOT_CHARSET ]	= $_POST[ $SEOPILOT_CHARSET ];
			$options[ $SEOPILOT_TEST ]		= $_POST[ $SEOPILOT_TEST ];

			foreach( $options as $opt_name => $opt_val ) {
				update_option( $opt_name, $opt_val );
			}

			if( isset( $_POST['SaveSeoPilotSettings'] ) ) {
				echo '<div class="updated"><p><strong>'.__('Zmiany zostały zapisane.', SeoPilot::$translationNamespace ).'</strong></p></div>';
			}
		}

		echo '<div class="wrap">';
		echo "<h2>" . __( 'Ustawienia dla SeoPilot.pl', SeoPilot::$translationNamespace ) . "</h2>";
		echo '
			<form name="SeoPilotAdminForm" method="post" action="">
			<div class="postbox">
				<div class="inside">
					<p><strong>'.__("Twój identyfikator SeoPilot:", SeoPilot::$translationNamespace ).'</strong><br/><input type="text" name="'.$SEOPILOT_USER.'" value="'.$options[ $SEOPILOT_USER ].'" style="width:100%;"/></p>
					<p><strong>'.__("Kodowanie:", SeoPilot::$translationNamespace ).'</strong><br/><input type="text" name="'.$SEOPILOT_CHARSET.'" value="'.$options[ $SEOPILOT_CHARSET ].'" style="width:100%;"/></p>
					<p><strong>'.__("Tryb testowy:", SeoPilot::$translationNamespace ).'</strong><br/><input type="checkbox" name="'.$SEOPILOT_TEST.'" value="1" '.($options[ $SEOPILOT_TEST ]==1?'checked':'').'/></p>
					<p><input type="submit" name="SaveSeoPilotSettings" class="button-primary" value="'.esc_attr__('Save Changes').'" /></p>
				</div>
			</div>
			</form>';

		echo '</div>';
	}
}

load_plugin_textdomain( SeoPilot::$translationNamespace, false, basename( dirname( __FILE__ ) ) . '/languages' );

if (!defined('SEOPILOT_USER')) {
	define('SEOPILOT_USER', get_option('SEOPILOT_USER') );
}

require_once( plugin_dir_path( __FILE__ ) . 'inc/widgets.php');

add_action( 'widgets_init', function(){
	register_widget( 'SeoPilot_Widget' );
});

// Administracyjne tematy (panel administracyjny WP)
add_action( 'admin_menu', array( 'SeoPilot', 'SeoPilot_Admin_Menu' ) );

?>
