<?php
/**
 * The main plugin file for WP Archive Plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       WP Archive Plugin
 * Description:       This WordPress plugin gives you an easy to use Archives page.
 * Version:           1.0.2
 * Author:            A6 Software
 * Author URI:        https://a6software.co.uk
 * Text Domain:       wp-archive-plugin
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main plugin class.

 * @package   WP Archive Plugin
 * @author    Christopher Moss <chris@a6software.co.uk>
 * @license   GPL-3.0
 * @link      https://a6software.co.uk
 * @copyright 2019 A6 Software
 */
class WP_Archive_Plugin {

	/**
	 * Basic cache buster.
	 */
	const VERSION = '20190609';

	private $shortcode = 'wp_archive_plugin';

	/**
	 * @var WP_Archive_Plugin_Data_Lookup
	 */
	private $wp_archive_data_lookup;

	/**
	 * WP_Archive_Plugin constructor.
	 *
	 * @param WP_Archive_Plugin_Data_Lookup $wp_archive_data_lookup
	 */
	public function __construct( $wp_archive_data_lookup ) {
		$this->bootstrap();

		$this->wp_archive_data_lookup = $wp_archive_data_lookup;
	}

	/**
	 * Bootstrap the plugin.
	 */
	public function bootstrap() {
		/**
		 * Ensures the archive list is always up to date.
		 */
		add_action( 'save_post', [ $this, 'flush_post_data_transient' ] );
		add_action( 'delete_post', [ $this, 'flush_post_data_transient' ] );

		add_shortcode( $this->shortcode, [ $this, 'shortcode' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_style' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dompurify' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_nested_date_sorter' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_wp_archive_display' ] );
	}

	/**
	 * Set up the shortcode.
	 *
	 * @param array  $attrs an array of shortcode attributes, not currently used.
	 * @param string $content content passed to shortcode, not currently used.
	 */
	public function shortcode( $attrs, $content = '' ) {
		$attrs = shortcode_atts(
			[],
			$attrs,
			$this->shortcode
		);

		$this->render_list_placeholder_on_dom();
	}

	/**
	 * Setup a `<ul>` element on the DOM that JS can use for rendering the archive post list.
	 */
	public function render_list_placeholder_on_dom() {
		if ( false === $this->is_archive_page() ) {
			return;
		}

		echo wp_kses_post( '<div id="wp_archive_plugin_wrapper"></div>' );
	}

	/**
	 * Make things look real pretty.
	 */
	public function enqueue_style() {
		if ( false === $this->is_archive_page() ) {
			return;
		}

		$script = 'wp-archive-plugin-style';
		wp_enqueue_style(
			$script,
			plugin_dir_url( __FILE__ ) . 'assets/css/wp-archive-plugin.css',
			[
				// Deps.
			],
			self::VERSION
		);
	}

	/**
	 * Helper to purify constructed JS strings.
	 *
	 * @see https://wpvip.com/documentation/vip-go/vip-code-review/javascript-security-best-practices/
	 */
	public function enqueue_dompurify() {
		if ( false === $this->is_archive_page() ) {
			return;
		}

		$script = 'dompurify';
		wp_register_script(
			$script,
			plugin_dir_url( __FILE__ ) . 'node_modules/dompurify/dist/purify.js',
			[
				// Deps.
			],
			self::VERSION,
			true // In footer.
		);
		wp_enqueue_script( $script );
	}

	/**
	 * Custom JS that does all the hard work of sorting the WordPress post data into a nicely formatted JS object.
	 */
	public function enqueue_nested_date_sorter() {
		if ( false === $this->is_archive_page() ) {
			return;
		}

		$script = $this->shortcode . '_nested_date_sorter';

		wp_register_script(
			$script,
			plugin_dir_url( __FILE__ ) . 'assets/js/nested-date-sorter.umd.js',
			[
				// Deps.
			],
			self::VERSION,
			true // In footer.
		);
		wp_enqueue_script( $script );
	}

	/**
	 * JS to display the WP Post data as a nicely formatted archive post list.
	 */
	public function enqueue_wp_archive_display() {
		if ( false === $this->is_archive_page() ) {
			return;
		}

		$script = $this->shortcode . '_display';

		wp_register_script(
			$script,
			plugin_dir_url( __FILE__ ) . 'assets/js/wp-archive-display.js',
			[
				// Deps.
				'jquery',
				'dompurify',
				'underscore',
				$this->shortcode . '_nested_date_sorter',
			],
			self::VERSION,
			true // In footer.
		);

		wp_localize_script(
			$script,
			$script . '_settings',
			[
				'data' => wp_json_encode( $this->wp_archive_data_lookup->get_post_data() ),
			]
		);

		wp_enqueue_script( $script );
	}

	/**
	 * To make things more efficient, the post data is only fetched from the DB when the cache is empty.
	 * The cache is cleared whenever a post is created, updated, or deleted.
	 */
	public function flush_post_data_transient() {
		$this->wp_archive_data_lookup->flush_post_data_transient();
	}

	/**
	 * Helper function to determine if this page is an archive (has the shortcode in the post content).
	 *
	 * @return mixed
	 */
	private function is_archive_page() {
		global $post;

		return has_shortcode( $post->post_content, $this->shortcode );
	}

}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-archive-plugin-data-lookup.php';

$plugin = new WP_Archive_Plugin(
	new WP_Archive_Plugin_Data_Lookup()
);
$plugin->bootstrap();
