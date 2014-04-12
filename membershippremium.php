<?php
/*
Plugin Name: Membership Premium Development
Version: 4.0.0
Plugin URI: http://premium.wpmudev.org/project/membership
Description: The most powerful, easy to use and flexible membership plugin for WordPress, Multisite and BuddyPress sites available. Offer downloads, posts, pages, forums and more to paid members.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
WDP ID: 140
License: GNU General Public License (Version 2 - GPLv2)
Text Domain: wpmudev_membership
 */

/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/

/**  Include WPMUDev Dashboard class */
require_once dirname( __FILE__ ) . '/extra/wpmudev-dash-notification.php';

/** Constant defineing plugin text domain. */
define('MS_TEXT_DOMAIN', 'wpmudev_membership' );

/** Constant used in wp_enqueue_style and wp_enqueue_script version. */
define('MS_VERSION_DT', '2014-04-04' );

/** Plugin name dir constant */
define( 'MS_PLUGIN_NAME', dirname( plugin_basename( __FILE__ ) ) );

/** Plugin name dir constant */
define( 'MS_PLUGIN_VERSION', '4.0.0.0' );

/** Instantiate the plugin */
MS_Plugin::instance( new MS_Plugin() );

/**
 * Hooks 'membership_class_path_overrides'. 
 *
 * Overrides plugin class paths to adhere to naming conventions
 * where object names are separated by underscores or for special cases.
 *
 * @since 4.0.0.0
 *
 * @param  array $overrides Array passed in by filter.
 * @return array(class=>path) Classes with new file paths.
 */
function membership_class_path_overrides( $overrides ) {

	$overrides['MS_Model_Custom_Post_Type'] =  "app/model/class-ms-model-custom-post-type.php";

	return $overrides;
}
add_filter( 'membership_class_path_overrides', 'membership_class_path_overrides' );


// TESTING TO SEE IF CLASSES ARE LOADED
// echo ( "<h1>" . class_exists( 'MS_Model_CustomPostType' ) . "</h1>" );
// echo ( "<h1>" . class_exists( 'MS_Model_Custom_Post_Type' ) . "</h1>" );
// $this->_model->blah = "blah";

/**
 * Sets up and loads the Membership plugin.
 *
 * Initialises the autoloader and required plugin hooks.
 * Control of plugin is passed to the MVC implementation found
 * inside the /app/ folder.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_Plugin {

	const NAME = MS_PLUGIN_NAME;
	const VERSION = MS_PLUGIN_VERSION;
	
	/**
	 * Singletone instance of the plugin.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var MS_Plugin
	 */
	private static $_instance = null;

	/**
	 * The plugin name.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var name
	 */
	private $name;
	
	
	/**
	 * The plugin version.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var version
	 */
	private $version;
	
	/**
	 * The plugin file.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var file
	 */
	private $file;	
	
	/**
	 * The plugin path.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var dir
	 */
	private $dir;	

	/**
	 * The plugin URL.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _url
	 */
	private $url;

	/**
	 * Instance of MS_Model_Plugin
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _model
	 */
	public $_model;

	/**
	 * Instance of MS_View_Plugin
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _view
	 */
	private $_view;

	/**
	 * Register hooks and loads the plugin.
	 */
	function __construct() {
		
		/** Load textdomain, localization. */
		load_plugin_textdomain( MS_TEXT_DOMAIN, false, MS_PLUGIN_NAME . '/languages/' );
		
		/** Actions to execute before construction is complete. */
		do_action( 'membership_plugin_loading' ); 
				
		/** Creates the class autoloader */
		spl_autoload_register( array( &$this, 'class_loader' ) );

		add_action( 'init', array( &$this, 'register_custom_post_type' ), 1 );
		
		/** Instantiate Plugin model */
		$this->_model = apply_filters( 'membership_plugin_model', new MS_Model_Plugin() );
		/** Instantiate Plugin view */
		$this->_view = apply_filters( 'membership_plugin_view', new MS_View_Plugin( array( 'test'=>'two' )) );		
				
// 		add_action( 'plugins_loaded', array( &$this,'plugin_localization' ) );

		$this->name = self::NAME;
		$this->version = self::VERSION;		
		$this->file = __FILE__;
		$this->dir = plugin_dir_path(__FILE__) . 'app/';
		$this->url = plugin_dir_url(__FILE__) . 'app/';
// 		add_filter( "plugin_action_links_$plugin", array( &$this,'plugin_settings_link' ) );
// 		add_filter( "network_admin_plugin_action_links_$plugin", array( &$this, 'plugin_settings_link' ) );
// 		$this->_view->render();

		/** Enque admin styles (CSS) */
		add_action( 'admin_enqueue_scripts', array( $this, 'register_plugin_admin_styles' ) );

		/** Enque admin scripts (JS) */
		add_action( 'admin_enqueue_scripts', array( $this, 'register_plugin_admin_scripts' ) );

		/** Grab instance of self. */
		self::$_instance = $this;
		
		/** Setup plugin admin UI */
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
//echo plugin_dir_url(__FILE__) . 'app/assets/css/dashboard.css';
		/** Actions to execute when plugin is loaded. */
		do_action( 'membership_plugin_loaded' ); 
		
		// Maybe creare a new hook that uses 'membership_plugin_loaded'
		add_action( 'init', array( &$this, 'tst' ));  
	}
	public function tst() {
		new MS_Controller_Membership();
	}
	/*
	 * Register membership plugin custom post types. 
	 * TODO better configure custom post type args
	*/
	public function register_custom_post_type() {
		// register the orders post type
		register_post_type( 'ms_membership',
			apply_filters( 'ms_register_post_type_ms_membership',
				array(
					'labels' => array(
						'name' => __( 'Memberships', MS_TEXT_DOMAIN ),
						'singular_name' => __( 'Membership', MS_TEXT_DOMAIN ),
						'menu_name' => __( 'Membership', MS_TEXT_DOMAIN ),
						'all_items' => __( 'All Memberships', MS_TEXT_DOMAIN ),
						'add_new' => __('New Membership', MS_TEXT_DOMAIN ),
						'add_new_item' => __('New Membership', MS_TEXT_DOMAIN ),
						'edit' => __( 'Edit', MS_TEXT_DOMAIN ),
						'view_item' => __( 'View Membership', MS_TEXT_DOMAIN ),
						'search_items' => __( 'Search Memberships', MS_TEXT_DOMAIN ),
						'not_found' => __( 'No Memberships Found', MS_TEXT_DOMAIN )
					),
					'description' => __( 'Memberships user can join to.', MS_TEXT_DOMAIN ),
					'show_ui' => true,
					'show_in_menu' => true,
					'menu_position' => 70, // below Users
					'menu_icon' => $this->url . "/assets/images/members.png",
					'public' => true,
					'has_archive' => false,
					'publicly_queryable' => false,
					'supports' => false,
					'capability_type' => apply_filters( 'mp_memberships_capability', 'page' ),
					'hierarchical' => false
					) ) );
		
		register_post_type( 'ms_transaction',
			apply_filters( 'ms_register_post_type_ms_transaction',
				array(
					'labels' => array(
						'name' => __( 'transactions', MS_TEXT_DOMAIN ),
						'singular_name' => __( 'transaction', MS_TEXT_DOMAIN ),
						'edit' => __( 'Edit', MS_TEXT_DOMAIN ),
						'view_item' => __( 'View transaction', MS_TEXT_DOMAIN ),
						'search_items' => __( 'Search transactions', MS_TEXT_DOMAIN ),
						'not_found' => __( 'No transactions Found', MS_TEXT_DOMAIN )
					),
					'description' => __( 'transactions user can join to.', MS_TEXT_DOMAIN ),					
					'public' => false,
					'has_archive' => false,
					'publicly_queryable' => false,
					'supports' => false,
					'capability_type' => apply_filters( 'mp_transactions_capability', 'page' ),
					'hierarchical' => false
					) ) );
		
		register_post_type( 'ms_communication',
			apply_filters( 'ms_register_post_type_ms_communication',
				array(
					'labels' => array(
						'name' => __( 'communications', MS_TEXT_DOMAIN ),
						'singular_name' => __( 'communication', MS_TEXT_DOMAIN ),
						'edit' => __( 'Edit', MS_TEXT_DOMAIN ),
						'view_item' => __( 'View communication', MS_TEXT_DOMAIN ),
						'search_items' => __( 'Search communications', MS_TEXT_DOMAIN ),
						'not_found' => __( 'No communications Found', MS_TEXT_DOMAIN )
					),
					'description' => __( 'communications user can join to.', MS_TEXT_DOMAIN ),
					'public' => false,
					'has_archive' => false,
					'publicly_queryable' => false,
					'supports' => false,
					'capability_type' => apply_filters( 'mp_communications_capability', 'page' ),
					'hierarchical' => false
					) ) );
	
	
	}
	
	public function add_menu_pages() {
		MS_Helper_Plugin::create_admin_pages( &$this->_view );
	}
	
	
	public function register_plugin_admin_styles() {
		
		wp_register_style( 'membership_admin_css', plugin_dir_url(__FILE__) . 'app/assets/css/settings.css' );
		wp_enqueue_style( 'membership_admin_css' );

	}

	public function register_plugin_admin_scripts() {

		// wp_register_script( 'membership_admin_js', plugin_dir_url(__FILE__) . 'app/assets/js/settings.js' );
		// wp_enqueue_script( 'membership_admin_js' );

		
	}
	
	
	/**
	 * Class autoloading callback function.
	 *
	 * Uses the **MS_** namespace to autoload classes when called. 
	 * Avoids creating include functions for each file in the MVC structure.
	 * **MS_** namespace ONLY will be based on folder structure in /app/
	 *
	 * @since 4.0.0
	 * @access private
	 *
	 * @param  string $class Uses PHP autoloader function.
	 * @return boolean
	 */
	private function class_loader( $class ) {

		$basedir = dirname( __FILE__ );
		$namespaces = array( 'MS_' );
		
		$path_overrides = apply_filters( 'membership_class_path_overrides', array() );
		
		foreach ( $namespaces as $namespace ) {
			switch ( $namespace ) {
			
				/** Use /app/ path and structure only for MS_ classes */
				case "MS_":
				
					
					if ( !array_key_exists( trim( $class ), $path_overrides ) ) {
						if ( substr( $class, 0, strlen( $namespace ) ) == $namespace ) {
							$sub_path = strtolower( str_replace( 'MS_', '', $class ) );
							$path_array = explode( '_', $sub_path );
							array_pop( $path_array );
							$sub_path = implode( '_', $path_array );
							$filename = $basedir . str_replace( '_', DIRECTORY_SEPARATOR, "_app_{$sub_path}_" ) . strtolower( str_replace( '_', 
							'-', "class-{$class}.php" ) );
							if ( is_readable( $filename ) ) {
								require $filename;
								return true;
							}
						}						
					} else {
						$filename = $basedir . '/' . $path_overrides[ $class ];
						if ( is_readable( $filename ) ) {
							require $filename;
							return true;
						}						
					}
					break; 
			}
		}

		return false;
	}
	
	/**
	 * Load plugin localization files.
	 *
	 * Files located in plugin subfolder ./languages.
	 *
	 * @since 4.0.0
	 * @access private
	 *
	 * @return void
	 */	
	private function plugin_localization() {
		load_plugin_textdomain( MS_TEXT_DOMAIN, false, MS_PLUGIN_NAME . '/languages/' );
	}


	/**
	 * Add link to settings page in plugins page.
	 *
	 * @since 4.0.0
	 * @access private
	 *
	 * @param array $links Wordpress default array of links.
	 * @return array Array of links with settings page links added.
	 */
	private function plugin_settings_link( $links ) {
		if ( is_multisite() ) {
			$settings_link = '';
		} else {
			$settings_link = '';
		}
		array_unshift( $links, $settings_link );
		return $links;
	}	
	
	/**
	 * Returns singletone instance of the plugin.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access public
	 *
	 * @param Object $instance Can use "new MS_Plugin()" to instantiate. Only once.
	 * @return MS_Plugin
	 */
	public static function instance( $instance = null ) {
		if ( !$instance || 'MS_Plugin' != get_class( $instance ) ){
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new MS_Plugin();
			}
		} else {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = $instance;
			}
		}
	
		return self::$_instance;
	}	
	
	/**
	 * Override __get() to return only properties not starting with underscore.
	 *
	 * @since 4.0.0.0
	 *
	 * @access public
	 *
	 * @param Object $property Uses PHP magic method param.
	 * @return Object
	 */	
	// public function __get( $property ) {
	// 	if ( property_exists( $this, $property ) ) {
	// 		if ( '_' != $property[0] ) {
	// 			return $this->$property;
	// 	    }
	// 	}
	// }
}