<?php
/**
 * Main plugin class file.
 *
 * @package WordPress Plugin Template/Includes
 */

namespace OllieJones;

require_once plugin_dir_path( __FILE__ ) . 'class-super-sonic-search-query-hooks.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-super-sonic-search-admin-api.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-super-sonic-search-admin-hooks.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-ingest.php';

use Super_Sonic_Search_Admin_API;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Main plugin class.
 */
class Super_Sonic_Search {

  /**
   * The single instance of Super_Sonic_Search.
   *
   * @var     object
   * @access  private
   * @since   1.0.0
   */
  private static $_instance = null; //phpcs:ignore

  /**
   * Local instance of Super_Sonic_Search_Admin_API
   *
   * @var Super_Sonic_Search_Admin_API|null
   */
  public $admin = null;

  /**
   * Settings class object
   *
   * @var     object
   * @access  public
   * @since   1.0.0
   */
  public $settings = null;

  /**
   * The version number.
   *
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $_version; //phpcs:ignore

  /**
   * The token.
   *
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $_token; //phpcs:ignore

  /**
   * The main plugin file.
   *
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $file;

  /**
   * The main plugin directory.
   *
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $dir;

  /**
   * The plugin assets directory.
   *
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $assets_dir;

  /**
   * The plugin assets URL.
   *
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $assets_url;

  /**
   * Suffix for JavaScripts.
   *
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $script_suffix;

  public $admin_hooks;
  public $query_hooks;

  /**
   * Constructor funtion.
   *
   * @param string $file File constructor.
   * @param string $version Plugin version.
   */
  public function __construct( $file = '', $version = '1.0.0' ) {
    $this->_version = $version;
    $this->_token   = 'super_sonic_search';

    // Load plugin environment variables.
    $this->file       = $file;
    $this->dir        = dirname( $this->file );
    $this->assets_dir = trailingslashit( $this->dir ) . 'assets';
    $this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

    $this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

    register_activation_hook( $this->file, [ $this, 'activate' ] );
    register_deactivation_hook( $this->file, [ $this, 'deactivate' ] );

    // Load frontend JS & CSS.
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ], 10 );
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 10 );

    // Load admin JS & CSS.
    add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ], 10, 1 );
    add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ], 10, 1 );

    // Load API for various admin functions.
    if ( is_admin() ) {
      $this->admin       = new Super_Sonic_Search_Admin_API();
      $this->admin_hooks = new Super_Sonic_Search_Hooks();
    }
    $this->query_hooks = new Super_Sonic_Search_Query_Hooks();

    // Handle localization.
    $this->load_plugin_textdomain();
    add_action( 'init', [ $this, 'load_localization' ], 0 );
  } // End __construct ()

  /**
   * Load frontend CSS.
   *
   * @access  public
   * @return void
   * @since   1.0.0
   */
  public function enqueue_styles() {
    wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', [], $this->_version );
    wp_enqueue_style( $this->_token . '-frontend' );
  } // End enqueue_styles ()

  /**
   * Load frontend Javascript.
   *
   * @access  public
   * @return  void
   * @since   1.0.0
   */
  public function enqueue_scripts() {
    wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', [ 'jquery' ], $this->_version, true );
    wp_enqueue_script( $this->_token . '-frontend' );
  } // End enqueue_scripts ()

  /**
   * Admin enqueue style.
   *
   * @param string $hook Hook parameter.
   *
   * @return void
   */
  public function admin_enqueue_styles( $hook = '' ) {
    wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', [], $this->_version );
    wp_enqueue_style( $this->_token . '-admin' );
  } // End admin_enqueue_styles ()

  /**
   * Load admin Javascript.
   *
   * @access  public
   *
   * @param string $hook Hook parameter.
   *
   * @return  void
   * @since   1.0.0
   */
  public function admin_enqueue_scripts( $hook = '' ) {
    wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', [ 'jquery' ], $this->_version, true );
    wp_enqueue_script( $this->_token . '-admin' );
  } // End admin_enqueue_scripts ()

  /**
   * Load plugin localisation
   *
   * @access  public
   * @return  void
   * @since   1.0.0
   */
  public function load_localization() {
    load_plugin_textdomain( 'super-sonic-search', false, dirname( plugin_basename( $this->file ) ) . '/languages/' );
  } // End load_localisation ()

  /**
   * Load plugin textdomain
   *
   * @access  public
   * @return  void
   * @since   1.0.0
   */
  public function load_plugin_textdomain() {
    $domain = 'super-sonic-search';

    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
  } // End load_plugin_textdomain ()

  /**
   * Main Super_Sonic_Search Instance
   *
   * Ensures only one instance of Super_Sonic_Search is loaded or can be loaded.
   *
   * @param string $file File instance.
   * @param string $version Version parameter.
   *
   * @return Object Super_Sonic_Search instance
   * @see Super_Sonic_Search()
   * @since 1.0.0
   * @static
   */
  public static function instance( $file = '', $version = '1.0.0' ) {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self( $file, $version );
    }

    return self::$_instance;
  } // End instance ()

  /**
   * Cloning is forbidden.
   *
   * @since 1.0.0
   */
  public function __clone() {
    _doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of Super_Sonic_Search is forbidden' ) ), esc_attr( $this->_version ) );
  } // End __clone ()

  /**
   * Unserializing instances of this class is forbidden.
   *
   * @since 1.0.0
   */
  public function __wakeup() {
    _doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of Super_Sonic_Search is forbidden' ) ), esc_attr( $this->_version ) );
  } // End __wakeup ()

  /**
   * Installation. Runs on activation.
   *
   * @access  public
   * @return  void
   * @since   1.0.0
   */
  public function activate() {
    $this->_log_version_number();

    //TODO this is a terrible place for the initial indexing operation.
    require_once( 'lib/class-ingest.php' );
    $ingester = new Ingester();
    $query    = [
      'post_type'     => 'any',
      'nopaging'      => true,
      'post_status'   => 'publish',
      'orderby'       => 'ID',
      'cache_results' => false, /* don't invalidate the whole cache */
    ];
    $ingester->posts_ingest( $query );
  }

  /**
   * Deactivation
   * @access  public
   * @return  void
   * @since   1.0.0
   */
  public function deactivate() {
    require_once( 'lib/class-ingest.php' );
    $ingester = new Ingester();
    $ingester->removeAll();
  }

  /**
   * Log the plugin version number.
   *
   * @access  public
   * @return  void
   * @since   1.0.0
   */
  private function _log_version_number() { //phpcs:ignore
    update_option( $this->_token . '_version', $this->_version );
  } // End _log_version_number ()

}
