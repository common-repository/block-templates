<?php

namespace Gutenberg_Templates\Controllers;

use \Gutenberg_Templates\Controllers\Gutenberg_Templates_Post_Type as GBT_PostType;
use \Gutenberg_Templates\Controllers\Gutenberg_Templates_Meta_Boxes as GBT_MetaBoxes;
use \Gutenberg_Templates\Controllers\Gutenberg_Templates_Rendering as GBT_Rendering;
use \Gutenberg_Templates\Controllers\Gutenberg_Templates_Blocks as GBT_Blocks;

/**
 * "Gutenberg Templates" plugin's main class.
 *
 * @category Class
 * @package  Gutenberg_Templates
 * @author   Konstantinos Galanakis
 */
class Gutenberg_Templates {
	const OPTION_POST_META_KEY = 'gbt_assigned_post_types';

	/**
	 * Main initializer.
	 */
	public function initialize() {
		$this->add_hooks();
	}

	/**
	 * Add the necessary hooks for the plugin initialization.
	 */
	public function add_hooks() {
		add_action( 'plugins_loaded', array( $this, 'initialize_classes' ) );

		add_filter( 'gbt_filter_get_gutenberg_templates', array( $this, 'get_gutenberg_templates' ) );

		add_filter( 'gbt_filter_is_gutenberg_active', array( $this, 'is_gutenberg_active' ) );
	}

	/**
	 * Initializes various secondary classed.
	 */
	public function initialize_classes() {
		$gbt_cpt = new GBT_PostType();
		$gbt_cpt->initialize();

		$gbt_meta_boxes = new GBT_MetaBoxes();
		$gbt_meta_boxes->initialize();

		$gbt_rendering = new GBT_Rendering();
		$gbt_rendering->initialize();

		$gbt_blocks = new GBT_Blocks();
		$gbt_blocks->initialize();
	}

	/**
	 * Filter that returns the published Gutenberg Templates.
	 *
	 * @param null|array $gutenberg_templates The Gutenberg Templates.
	 *
	 * @return array
	 */
	public function get_gutenberg_templates( $gutenberg_templates = null ) {
		$args = array(
			'post_type'      => GBT_PostType::CUSTOM_POST_TYPE_SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		$gutenberg_templates = get_posts( $args );

		return $gutenberg_templates;
	}

	/**
	 * Filter that detects if Gutenberg is active either as a plugin or in COre.
	 *
	 * @param null|bool $active The initial value of the filter.
	 *
	 * @return bool
	 */
	public function is_gutenberg_active( $active = null ) {
		return function_exists( 'register_block_type' );
	}
}
