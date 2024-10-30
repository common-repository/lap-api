<?php
/**
 * @package RealAds
 *
 * Plugin Name: lap-api
 * Plugin URI: https://lap-api.com/admin/en/docs
 * Description: A plugin for integrating with the RealAds advertising provider.
 * Version: 1.0.9
 * Author: YeudaBy
 * Author URI: mailto:yehudab@hadran.net
 * License: GPLv2 or later
 * Text Domain: lap-api
 **/

namespace Lap;

if ( ! defined( "ABSPATH" ) ) {
	exit; // Exit if accessed directly
}


class Lap {
	public $library_version = "1.3.22";
	public $plugin_version = "1.0.7";
	private $option_name = "RealAds-settings";
	public $render = null;

	public function __construct() {
		$this->render = new RenderSettings( $this->option_name );
		$this->load_common();
	}

	public function load_common() {
		add_filter( 'block_categories_all', array( $this, 'add_block_categories' ), 1, 2 );
		add_action( 'init', array( $this, 'register_blocks' ) );
		$this->create_shortcodes();
		add_action( 'rest_api_init', array( $this, 'register_options_endpoint' ) );
		$this->register_elementor_widgets();
	}

	public function load_admin() {
		add_action( 'admin_menu', [ $this, 'add_plugin_options_page' ] );
		add_action( 'admin_init', [ $this, 'add_plugin_settings' ] );
		add_action( 'admin_init', array( $this, 'compare_versions' ) );
	}

	public function load_user() {
		add_action( 'wp_head', [ $this, 'add_lap_core_js' ] );
		add_action( 'wp_print_scripts', [ $this, 'add_js_init_block' ] );
	}

	public function add_lap_core_js() {
		$script_url = "https://cdn.jsdelivr.net/npm/lap-core-js@" . $this->library_version;
		echo '<script src="' . esc_url( $script_url ) . '"></script>';
	}

	public function add_js_init_block() {
		?>
        <script type="module">
            const app = "<?php echo( esc_html( get_option( $this->option_name )['app_name'] ) ); ?>";
            const enable = "<?php echo( esc_html( get_option( $this->option_name )['enable'] ) ); ?>";
            let interstitialInterval = "<?php echo( esc_html( get_option( $this->option_name )['interstitial_interval'] ) ); ?>";
            const currentPageInclude = "<?php echo( esc_html( $this->current_page_include() ) ) ?>";
            if (enable === "1" && currentPageInclude === "1") {
                lap.init(app, {
                    interstitialInterval: isNaN(parseInt(interstitialInterval)) ? 4 : parseInt(interstitialInterval)
                });
            } else {
                console.log(
                    "<?php echo esc_attr__( "RealAds Plugin is not enabled. Go to your dashboard and enable RealAds in the settings.", 'lap-api' ) ?>"
                )
            }
        </script>
		<?php
	}

	public function add_plugin_options_page() {
		add_options_page(
			__( 'RealAds - Settings', 'lap-api' ),
			__( 'RealAds settings', 'lap-api' ),
			'manage_options',
			'RealAds',
			[ $this, 'render_admin_page' ]
		);
	}

	public function add_plugin_settings() {

		register_setting( 'RealAds-settings', 'RealAds-settings' );

		add_settings_section(
			'RealAds-general',
			__( 'RealAds - General', 'lap-api' ),
			[ $this->render, "general_instruction" ],
			'RealAds-settings'
		);

		add_settings_field(
			'RealAds-enable',
			__( 'Enable RealAds', 'lap-api' ),
			[ $this->render, 'enable_field' ],
			'RealAds-settings',
			'RealAds-general'
		);

		add_settings_field(
			'RealAds-app',
			__( 'App Name', 'lap-api' ),
			[ $this->render, 'app_name_field' ],
			'RealAds-settings',
			'RealAds-general'
		);

		add_settings_field(
			'RealAds-pages',
			__( 'Select pages to show ads', 'lap-api' ),
			[ $this->render, 'select_pages_field' ],
			'RealAds-settings',
			'RealAds-general'
		);

		add_settings_section(
			'RealAds-interstitial',
			__( 'Interstitial Ads', 'lap-api' ),
			[ $this->render, "instructions" ],
			'RealAds-settings'
		);

		add_settings_field(
			'interstitial-interval',
			__( 'Select Interstitial Interval', 'lap-api' ),
			[ $this->render, 'interstitial_interval_field' ],
			'RealAds-settings',
			'RealAds-interstitial'
		);

		add_settings_section(
			'RealAds Native Ads',
			__( 'Native Ads', 'lap-api' ),
			[ $this->render, "instructions" ],
			'RealAds-settings'
		);

		add_settings_field(
			'native-ad-default-classes',
			__( 'Default Classes', 'lap-api' ),
			[ $this->render, 'native_ad_default_classes_field' ],
			'RealAds-settings',
			'RealAds Native Ads'
		);
	}

	private function get_banners_size_from_server() {
		$url      = "https://lap-api.com/b/sizes";
		$response = wp_remote_get( $url );
		$body     = wp_remote_retrieve_body( $response );

		return json_decode( $body, true );
	}

	public function current_page_include() {
		$current_page_id = get_the_ID();
		$options         = get_option( $this->option_name );

		return empty( $options['pages'] ) || in_array( $current_page_id, $options['pages'] );
	}

	public function create_shortcodes() {
		add_shortcode( 'realads_banner', array( $this, 'banner_shortcode' ) );
		add_shortcode( 'realads_native', array( $this, 'native_ad_shortcode' ) );
	}

	public function banner_shortcode( $atts ) {
		$id = '';
		if ( isset( $atts ) && isset( $atts['id'] ) ) {
			$id = strtoupper( $atts['id'] );
		}

		return '<div data-lap-b="' . $id . '"></div>';
	}

	public function native_ad_shortcode( $atts ) {
		$container = $text_container = $title = $content = $image = ''; // set default values
		if ( isset( $atts ) ) {
			if ( isset( $atts['container'] ) ) {
				$container = $atts['container'];
			}
			if ( isset( $atts['text_container'] ) ) {
				$text_container = $atts['text_container'];
			}
			if ( isset( $atts['title'] ) ) {
				$title = $atts['title'];
			}
			if ( isset( $atts['content'] ) ) {
				$content = $atts['content'];
			}
			if ( isset( $atts['image'] ) ) {
				$image = $atts['image'];
			}
		}

		return '<div data-lap-n data-lap-n-container="' . $container . '" data-lap-n-text="' . $text_container . '" data-lap-n-title="' . $title . '" data-lap-n-content="' . $content . '" data-lap-n-image="' . $image . '"></div>';
	}

	public function register_blocks() {
		register_block_type( __DIR__ . "/banner/build" );
		register_block_type( __DIR__ . "/native/build" );
	}

	public function add_block_categories( $categories, $post ) {
		return array_merge(
			array(
				array(
					'slug'  => 'realads',
					'title' => __( 'RealAds Blocks', 'lap-api' ),
				),
			),
			$categories
		);
	}

    // Elementor

    function add_realads_elementor_widget_categories( $elements_manager ) {
        $elements_manager->add_category(
            'realads',
            [
                'title' => __( 'RealAds', 'lap-api' ),
                'icon' => 'fa fa-plug',
            ]
        );
    }

	public function register_elementor_banner_widget( $widgets_manager ) {
		require_once( __DIR__ . '/elementor/Elementor_Banner_Widget.php' );
		$widgets_manager->register_widget_type( new \Elementor_Banner_Widget() );
	}

	public function register_elementor_native_widget( $widgets_manager ) {
		require_once( __DIR__ . '/elementor/Elementor_NativeAd_Widget.php' );
		$widgets_manager->register_widget_type( new \Elementor_NativeAd_Widget() );
	}

	public function register_elementor_widgets() {
        add_action( 'elementor/elements/categories_registered', [ $this, 'add_realads_elementor_widget_categories' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_banner_widget' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_elementor_native_widget' ] );
	}

	public function compare_versions() {
		$current_versions = $this->get_current_versions();
		$library_version  = $current_versions['library_version'];
		$plugin_version   = $current_versions['wp_plugin_version'];


		if ( ! isset( $library_version ) || ! isset( $plugin_version ) ) {
			$msg = __( 'There was an error while trying to get the latest version of the plugin.', 'lap-api' );
			add_action( 'admin_notices', function () use ( $msg ) {
				$this->show_error_message( $msg );
			} );

			return;
		}

		if ( $this->library_version != $library_version || $this->plugin_version != $plugin_version ) {
			$msg = __( 'There is a new version of RealAds plugin available, please contact us at the email address in RealAds settings page.', 'lap-api' );
			add_action( 'admin_notices', function () use ( $msg ) {
				$this->show_update_notice( $msg );
			} );
		}
	}

	public function show_error_message( $message ) {
		$class = 'notice notice-error is-dismissible';
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public function show_update_notice( $message ) {
		$class = 'notice notice-warning is-dismissible';
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );

	}

	private function get_current_versions() {
		$url      = "https://lap-api.com/settings";
		$response = wp_remote_get( $url );
		$body     = wp_remote_retrieve_body( $response );

		return json_decode( $body, true );
	}

	public function register_options_endpoint() {
		register_rest_route( 'lap-api/v1', '/options', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_options' ),
		) );
	}

	public function get_options() {
		return get_option( $this->option_name );
	}

	public function render_admin_page() {
		?>
        <div class="wrap">
            <h1 style="margin-bottom: 2rem; text-align: center; font-weight: bold">
				<?php
				_e( "RealAds Settings", "lap-api" )
				?>
            </h1>
            <form method="post" action="options.php">
				<?php
				settings_fields( 'RealAds-settings' );
				do_settings_sections( 'RealAds-settings' );
				submit_button();
				?>
            </form>

            <div style="background: rgba(255,255,255,0.61);
                        padding: .5rem 1.5rem;
                        border-radius: 1.25rem;
                        box-shadow: 0 0 1rem 0 rgba(0,0,0,0.2);">
                <h3>RealAds</h3>
                <b>
					<?php _e( "Powered by " ) ?>
                    <span><a href="https://hadran.net">Lomdaat</a></span>
                </b>
                <p>
					<?php _e( "Developed by " ) ?>
                    <span><a href="mailto:yehudab@hadran.net">YeudaBy</a> from Hadran team.</span>
                </p>
                <p>
					<?php _e( "For full documentation, please visit: " ) ?>
                    <span><a href="https://lap-api.com/admin/en/docs/wordpress">https://lap-api.com/admin/en/docs/wordpress</a></span>
                </p>
                <p>
					<?php _e( "For support, information and collaborations, please contact: " ) ?>
                    <span><a href="mailto:ads@hadran.net">ads@hadran.net</a></span>
                </p>

                <hr/>

                <p>
					<?php _e( "Current Core-library version: ", 'lap-api' ) ?>
                    <b><?php echo $this->library_version ?></b>
                </p>
                <p>
					<?php _e( "Current Plugin version: ", 'lap-api' ) ?>
                    <b><?php echo $this->plugin_version ?></b>
                </p>
                <p>
					<?php _e( "When contacting the developer for troubleshooting, please specify this versions.", 'lap' ) ?>
                </p>
            </div>


			<?php
			$this->render_banner_sizes_table();
			?>
        </div>
		<?php
	}

	private function render_banner_sizes_table() {
		$banners = $this->get_banners_size_from_server();

		if ( ! isset( $banners ) ) {
			$msg = __( "Error: Can't get banner sizes from server.", 'lap-api' );
			$this->show_error_message( $msg );

			return;
		}

		?>
        <div id="banner-sizes" style="
            background-color: rgba(0,0,0,0.08);
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 1.25rem;
            box-shadow: 0 0 1rem 0 rgba(0,0,0,0.2);">
            <details>
                <summary style="user-select: none; font-size: 1.1rem; font-weight: lighter">
					<?php _e( "Banner sizes", 'lap' ) ?>
                </summary>
                <table class="form-table">
                    <tr style="border-bottom: rgba(0,0,0,0.38) 1px solid">
                        <th>
							<?php _e( "ID", 'lap' ) ?>
                        </th>
                        <th>
							<?php _e( "Name", 'lap' ) ?>
                        </th>
                        <th>
							<?php _e( "Width", 'lap' ) ?>
                        </th>
                        <th>
							<?php _e( "Height", 'lap' ) ?>
                        </th>
                    </tr>
					<?php foreach ( $banners as $banner ) { ?>
                        <tr>
                            <td><?php echo esc_html( $banner['id'] ) ?></td>
                            <td><?php echo esc_html( $banner['name'] ) ?></td>
                            <td><?php echo esc_html( $banner['width'] ) ?>px</td>
                            <td><?php echo esc_html( $banner['height'] ) ?>px</td>
                        </tr>
					<?php } ?>
                </table>
            </details>
        </div>
		<?php
	}
}


function main() {
	$plugin = new Lap();

	if ( is_admin() ) {
		$plugin->load_admin();
	} else {
		$plugin->load_user();
	}

    $plugin->register_elementor_widgets();
}

main();


class RenderSettings {
	private $settings_name;

	public function __construct( $settings_name ) {
		$this->settings_name = $settings_name;
	}


	// instructions
	public function instructions() {
	}

	public function general_instruction() {
	}

	// fields

	public function enable_field() {
		$option = get_option( $this->settings_name );
		$value  = isset( $option['enable'] ) ? $option['enable'] : '';
		echo '<label for="enable">';
		echo '<input type="checkbox" id="enable" name="' . $this->settings_name . '[enable]" value="1"' . checked( 1, $value, false ) . '/>';
		echo '<span class="description">' . __( 'Enable the plugin.', 'lap-api' ) . '</span>';
		echo '</label>';
	}

	public function app_name_field() {
		$option = get_option( $this->settings_name );
		$value  = isset( $option['app_name'] ) ? $option['app_name'] : '';
		echo '<input type="text" id="app_name" name="' . $this->settings_name . '[app_name]" value="' . $value . '">';
		echo '<p class="description">';
		_e( "The name of your app. This is used to identify your app in the dashboard.", 'lap-api' );
		echo '</p><p class="description">';
		_e( "Note: if you are not specifying a name, we can not be able to identify your app, and you will not get paid for your traffic.", 'lap-api' );
		echo '</p>';
	}

	public function interstitial_interval_field() {
		$option = get_option( $this->settings_name );
		$value  = isset( $option['interstitial_interval'] ) ? $option['interstitial_interval'] : '4';
		echo '<input type="number" id="interstitial_interval" name="' . $this->settings_name . '[interstitial_interval]" value="' . $value . '">';
		echo '<p class="description">' . __( 'The number of pages between each interstitial ad.', 'lap-api' ) . '</p>';
		echo '<p class="description">' . __( 'Note: the default value is', 'lap-api' ) . ' <b>4</b> ' . __( 'Set to', 'lap-api' ) . ' <b>0</b> ' . __( 'to disable interstitial ads.', 'lap-api' ) . '</p>';
	}

	public function native_ad_default_classes_field() {
		$option  = get_option( $this->settings_name );
		$value   = isset( $option['native_ad_default_classes'] ) ? $option['native_ad_default_classes'] : array();
		$targets = array(
			'container',
			'text-container',
			'title',
			'content',
			'image',
		);

		foreach ( $targets as $target ) {
			echo '<input type="text" name="' . $this->settings_name . '[native_ad_default_classes][' .
			     $target . ']" value="' . ( isset( $value[ $target ] ) ? $value[ $target ] : '' ) . '" placeholder=".' . $target . '"/>';
			echo '<p class="description">' . __( "The default class for the", "lap-api" ) . '<b>' . $target . '</b> ' . __( "element.", "lap-api" ) . '</p>';
			echo '<br>';
		}

		echo '<p class="description">' . __( "Note: we will add these classes to the native ad elements, in addition to the classes we insert.", "lap-api" ) . '</p>';
		echo '<p class="description">' . __( "You can always override these classes by specifying them in the block.", "lap-api" ) . '</p>';
	}

	public function select_pages_field() {
		$options = get_option( $this->settings_name );
		$pages   = get_pages();

		$output = '<div>';

		for ( $i = 0; $i < count( $pages ); $i ++ ) {
			$page    = $pages[ $i ];
			$checked = isset( $options["pages"] ) && in_array( $page->ID, $options['pages'] ) ? 'checked' : '';
			$output  .= '<label>';
			$output  .= '<input type="checkbox" name="' . $this->settings_name . '[pages][]" value="' . $page->ID . '" ' . $checked . '>';
			$output  .= '<a href="' . get_page_link( $page->ID ) . '" target="_blank">' . $page->post_title . '</a>';
			$output  .= '</label></br>';

			if ( $i === 4 && count( $pages ) > 5 ) {
				$output .= '<details><summary id="your-plugin-show-more" style="user-select: none; cursor: pointer; transition: all 0.2s ease-in-out;">' . __( 'Show more', 'lap-api' );
				$output .= '</summary></details><div id="your-plugin-hidden-pages" style="opacity: 0;height: 0;overflow: hidden;transition: opacity 0.5s ease-in-out;">';
			}
		}

		if ( count( $pages ) > 5 ) {
			$output .= '</div>';
		}

		$output .= '</div>';

		echo $output;
		echo '<p class="description">' . __( "Select the pages where you want to show ads.", "lap-api" ) . '</p>';
		echo '<p class="description">' . __( "Note: if you do not select any page, ads will be shown on all pages.", "lap-api" ) . '</p>';

		echo '<script>
                   document.getElementById("your-plugin-show-more").addEventListener("click", function(e) {
                        e.preventDefault();
                        const hiddenPages = document.getElementById("your-plugin-hidden-pages");
                        hiddenPages.style.opacity = 1;
                        hiddenPages.style.height = "auto";
                        hiddenPages.style.overflow = "visible";
                        this.style.opacity = 0;
                        this.style.height = 0;
                        this.style.overflow = "hidden";
                   });
              </script>';
	}
}