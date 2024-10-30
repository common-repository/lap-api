<?php

class Elementor_NativeAd_Widget extends \Elementor\Widget_Base {

	private $bannersSize;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->bannersSize = $this->get_banners_size_from_server();
	}

	public function get_name() {
		return 'realads-banner';
	}

	public function get_title() {
		return __( 'Banner', 'lap-api' );
	}

	public function get_icon() {
		return 'eicon-custom';
	}

	public function get_custom_help_url() {
		// TODO: Change to realads docs
		return 'https://docs.google.com/document/d/1SMyPBhtJkOIQBSu5R8UHuOtBMkaICnFKx71TgSCfa5c';
	}

	public function get_categories() {
		return [ 'realads' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Banner Settings', 'lap-api' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'bannerSize',
			[
				'type'    => \Elementor\Controls_Manager::SELECT,
				'label'   => __( 'Banner Size', 'lap-api' ),
				'options' => array_map( function ( $size ) {
					return 'ID: ' . $size['id'] . ' (' . $size['width'] . 'x' . $size['height'] . ') = ' . $size['name'];
				}, $this->bannersSize ),
			]
		);

		$this->add_control(
			'autoReload',
			[
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'label'   => __( 'Auto Reload', 'lap-api' ),
				'default' => 'yes',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$autoReload  = $settings['autoReload'] === 'yes' ? 'true' : 'false';
		$sizeIdIndex = $settings['bannerSize'];
		$sizeId      = $this->bannersSize[ $sizeIdIndex ]['id'];

		$this->add_render_attribute( 'wrapper', [
			'data-lap-b'           => $sizeId,
			'data-lap-auto-reload' => $autoReload,
		] );

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>></div>
		<?php
	}

	protected function _content_template() {
		?>
		<#
		const sizes = <?php echo json_encode( $this->bannersSize ); ?>;
		const selectedSize = sizes[settings.bannerSize];
		view.addRenderAttribute( 'example', {
		'style': 'width: ' + selectedSize?.width + 'px; height: ' + selectedSize?.height + 'px; border: 1px solid green;',
		} );
		#>
		<div>
			<span>Selected Size: {{ selectedSize?.name || "Please select size" }}</span>
			<div {{{ view.getRenderAttributeString(
			'example' ) }}}>
		</div>
		</div>
		<?php
	}

	private function get_banners_size_from_server() {
		$url      = "https://lap-api.com/b/sizes";
		$response = wp_remote_get( $url );
		$body     = wp_remote_retrieve_body( $response );

		return json_decode( $body, true );
	}
}