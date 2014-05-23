<?php
/*
Plugin Name: Pods Validation
Plugin URI: https://github.com/mpsteel/pods-validation
Description: Adds front end validation on a form with Pods meta fields.
Version: 0.2
Author: Marc Steel
Author URI: http://www.movingpaperfantasy.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.htm
*/

/**
 * Copyright (c) 2014 Marc Steel (email: ms@marcsteel.us). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Pods_Validation class
 *
 * @class Pods_Validation The class that holds the entire Pods_Validation plugin
 *
 * @since 0.0.1
 */
class Pods_Validation {

	/**
	 * Constructor for the Pods_Validation class
	 *
	 * Sets up all the appropriate hooks and actions
	 * within the plugin.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		/**
		 * Plugin Setup
		 */
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Localize our plugin
		add_action( 'init', array( $this, 'localization_setup' ) );
    
    add_action( 'admin_init', array( $this, 'admin_init' ) );
    
		/**
		 * Scripts/ Styles
		 */
		// Loads frontend scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Loads admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		
	}

	/**
	 * Initializes the Pods_Validation() class
	 *
	 * Checks for an existing Pods_Validation() instance
	 * and if it doesn't find one, creates it.
	 *
	 * @since 0.0.1
	 */
	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new Pods_Validation();
		}

		return $instance;
		
	}


  function admin_init() {
    add_action( 'wp_ajax_pods_validation', array( $this, 'pods_validation_ajax' ) );
  }
  
	/**
	 * Placeholder for activation function
	 *
	 * @since 0.0.1
	 */
	public function activate() {

	}

	/**
	 * Placeholder for deactivation function
	 *
	 * @since 0.0.1
	 */
	public function deactivate() {

	}

	/**
	 * Initialize plugin for localization
	 *
	 * @since 0.0.1
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'pods-validation', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
	}

	/**
	 * Enqueue front-end scripts
	 *
	 * Allows plugin assets to be loaded.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_scripts() {

		/**
		 * All styles goes here
		 */
		wp_enqueue_style( 'pods-validation-styles', plugins_url( 'css/front-end.css', __FILE__ ) );

		/**
		 * All scripts goes here
		 */
		wp_enqueue_script( 'pods-validation-scripts', plugins_url( 'js/front-end.js', __FILE__ ), array( ), false, true );
		
	}

	/**
	 * Enqueue admin scripts
	 *
	 * Allows plugin assets to be loaded.
	 *
	 * @since 0.0.1
	 */
	public function admin_enqueue_scripts() {

		/**
		 * All admin styles goes here
		 */
		wp_enqueue_style( 'pods-validation-admin-styles', plugins_url( 'css/admin.css', __FILE__ ) );

		/**
		 * All admin scripts goes here
		 */
		wp_enqueue_script( 'pods-validation-admin-scripts', plugins_url( 'js/admin.js', __FILE__ ), array( ), false, true );
		
	}
  
  function pods_validation_ajax() {
    if ( ! defined( 'PODS_VERSION' ) ) return;

    $post_id = $_POST['post_id'];
    $post_type = $_POST['post_type'];
    $form_data = $_POST['form_data'];
    parse_str($form_data,$form_data);

    $pods_api = new PodsAPI($post_type);
    $pod = pods($post_type);
    $pod_fields = $pod->fields;
    $pod_array = $pod->api->pod_data;

    //if there is an error make sure it is returned instead of having WP die out.
    add_filter( 'pods_error_die', create_function('$die_bypass, $error', 'return $error;'), 10, 2 );

    //init the results array
    $results = array();

    //cycle through all of the pods fields instead of the form data.
    //if no pick field options were checked then the field would not be submitted
    //and the loop would not know there is even a field to validate.
    foreach( array_keys( $pod_fields ) as $pod_field_name ) {

      //get the field information
      $pod_field = $pod_fields[$pod_field_name];

      //if this is not a checkable form field, don't check it
      if ( ! $pod_field ) continue;

      //reference for the name of the pod field in the form data
      $form_data_field_name = 'pods_meta_'. $pod_field_name;

      //used for jquery selector reference
      $form_field_clean_name = str_replace('_', '-', $form_data_field_name);

      //get the value of the field from the form
      $field_value = $form_data[ $form_data_field_name ];

      //get field type
      $pod_field_type = $pod_field['type'];

      //if the field is required and blank then flag it.
      if ( $pod_field['options']['required'] && '' == $field_value ) {
        $results[ $form_field_clean_name ] = $pod_field['label'] .' is empty.';
      }
      //otherwise run the Pods validation. WARNING: limited testing
      else if ( $pod_field['options']['required'] ) {
        $result = $pods_api->handle_field_validation( $field_value, $pod_field_name, $pod_array['object_fields'], $pod_fields, $pod_array, $params);

        if ( is_array( $result ) ) $result = (string) $result[0];

        //something went wrong, add it to the final results
        if ( intval( $result ) !== 1 ) $results[ $form_field_clean_name ] = (string) $result;
      }
    }

    die( json_encode( $results ) );
  }


} // Pods_Validation

/**
 * Initialize class, if Pods is active.
 *
 * @since 0.0.1
 */
add_action( 'plugins_loaded', 'Pods_Validation_safe_activate');
function Pods_Validation_safe_activate() {
	if ( defined( 'PODS_VERSION' ) ) {
		$GLOBALS[ 'Pods_Validation' ] = Pods_Validation::init();
	}

}


/**
 * Throw admin nag if Pods isn't activated.
 *
 * Will only show on the plugins page.
 *
 * @since 0.0.1
 */
add_action( 'admin_notices', 'Pods_Validation_admin_notice_pods_not_active' );
function Pods_Validation_admin_notice_pods_not_active() {

	if ( ! defined( 'PODS_VERSION' ) ) {

		//use the global pagenow so we can tell if we are on plugins admin page
		global $pagenow;
		if ( $pagenow == 'plugins.php' ) {
			?>
			<div class="updated">
				<p><?php _e( 'You have activated Pods Extend, but not the core Pods plugin.', 'Pods_Validation' ); ?></p>
			</div>
		<?php

		} //endif on the right page
	} //endif Pods is not active

}

/**
 * Throw admin nag if Pods minimum version is not met
 *
 * Will only show on the Pods admin page
 *
 * @since 0.0.1
 */
add_action( 'admin_notices', 'Pods_Validation_admin_notice_pods_min_version_fail' );
function Pods_Validation_admin_notice_pods_min_version_fail() {

	if ( defined( 'PODS_VERSION' ) ) {

		//set minimum supported version of Pods.
		$minimum_version = '2.3.18';

		//check if Pods version is greater than or equal to minimum supported version for this plugin
		if ( version_compare(  $minimum_version, PODS_VERSION ) >= 0) {

			//create $page variable to check if we are on pods admin page
			$page = pods_v('page','get', false, true );

			//check if we are on Pods Admin page
			if ( $page === 'pods' ) {
				?>
				<div class="updated">
					<p><?php _e( 'Pods Extend, requires Pods version '.$minimum_version.' or later. Current version of Pods is '.PODS_VERSION, 'Pods_Validation' ); ?></p>
				</div>
			<?php

			} //endif on the right page
		} //endif version compare
	} //endif Pods is not active

}
