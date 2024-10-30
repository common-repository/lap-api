<?php

class Elementor_Banner_Widget extends \Elementor\Widget_Base {

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
	}

	public function get_name() {
		return 'realads-native-ad';
	}

	public function get_title() {
		return __( 'Native Ad', 'lap-api' );
	}

	public function get_icon() {
		return 'eicon-skill-bar';
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
				'label' => __( 'Native-Ad classes', 'lap-api' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'container-class',
			[
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label'       => __('Container', 'lap-api'),
				'placeholder' => 'container-class',
			]
		);

		$this->add_control(
			'title-class',
			[
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label'       => __('Title', 'lap-api'),
				'placeholder' => 'title-class',
			]
		);

		$this->add_control(
			'text-container-class',
			[
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label'       => __('Text Container', 'lap-api'),
				'placeholder' => 'text-container-class',
			]
		);

		$this->add_control(
			'content-class',
			[
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label'       => __('Content', 'lap-api'),
				'placeholder' => 'content-class',
			]
		);

		$this->add_control(
			'image-class',
			[
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label'       => __('Image', 'lap-api'),
				'placeholder' => 'image-class',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings  = $this->get_settings_for_display();
		$container = $settings['container-class'];
		$textContainer = $settings['text-container-class'];
		$title     = $settings['title-class'];
		$text      = $settings['text-container-class'];
		$content   = $settings['content-class'];
		$image     = $settings['image-class'];

		$this->add_render_attribute( 'wrapper', [
			'data-lap-n' => "",
		] );

		if ( $container ) {
			$this->add_render_attribute( 'wrapper', [
				'data-lap-n-container' => $container,
			] );
		}

		if ( $title ) {
			$this->add_render_attribute( 'wrapper', [
				'data-lap-n-title' => $title,
			] );
		}

		if ( $text ) {
			$this->add_render_attribute( 'wrapper', [
				'data-lap-n-text' => $text,
			] );
		}

		if ( $content ) {
			$this->add_render_attribute( 'wrapper', [
				'data-lap-n-content' => $content,
			] );
		}

		if ( $image ) {
			$this->add_render_attribute( 'wrapper', [
				'data-lap-n-image' => $image,
			] );
		}

		if ( $textContainer ) {
			$this->add_render_attribute( 'wrapper', [
				'data-lap-n-text-container' => $textContainer,
			] );
		}

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>></div>
		<?php
	}

	protected function _content_template() {
		?>
		<div>
<!--			<span>-->
<!--				--><?php //echo __( 'Native Ad', 'lap-wordpress' ); ?>
<!--			</span>-->
		</div>
		<?php
	}
}