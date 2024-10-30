<?php

namespace Gutenberg_Templates\Controllers;

use \Gutenberg_Templates\Controllers\Gutenberg_Templates_Post_Type as GBT_PostType;

/**
 * "Gutenberg Templates" plugin's block handler class.
 *
 * @category Class
 * @package  Gutenberg_Templates
 * @author   Konstantinos Galanakis
 */
class Gutenberg_Templates_Blocks {
	const GBT_BLOCKS_CATEGORY_SLUG = 'gbt';

	/**
	 * Main initializer.
	 */
	public function initialize() {
		// Hook: Category registration.
		add_filter( 'block_categories', array( $this, 'register_gbt_block_category' ) );

		// Hook: Frontend assets.
		add_action( 'enqueue_block_assets', array( $this, 'gutenberg_templates_cgb_block_assets' ) );

		// Hook: Editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'gutenberg_templates_cgb_editor_assets' ) );

		// Shortcode for the output of the post content block.
		add_shortcode( 'gbt-post-content', array( $this, 'gbt_post_content' ) );
	}

	/**
	 * Enqueue Gutenberg block assets for both frontend + backend.
	 *
	 * `wp-blocks`: includes block type registration and related functions.
	 *
	 * @since 1.0.0
	 */
	public function gutenberg_templates_cgb_block_assets() {
		wp_enqueue_style(
			'gbt-post-content-block-style-css',
			GBT_URL . 'dist/blocks.style.build.css',
			array( 'wp-blocks' ),
		    filemtime( GBT_PATH . '/dist/blocks.style.build.css' )
		);
	}

	/**
	 * Enqueue Gutenberg block assets for backend editor.
	 *
	 * `wp-blocks`: includes block type registration and related functions.
	 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
	 * `wp-i18n`: To internationalize the block's text.
	 * `wp-components`: includes a library of generic WordPress components to be used for creating common UI elements.
	 *
	 * @since 1.0.0
	 */
	public function gutenberg_templates_cgb_editor_assets() {
		wp_register_script(
			'gbt-post-content-block-js',
			GBT_URL . '/dist/blocks.build.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components' ),
			filemtime( GBT_PATH . '/dist/blocks.build.js' ),
			true
		);

		$i18n = array(
			'category' => self::GBT_BLOCKS_CATEGORY_SLUG,
			'isGBT' => false,
		);

		$i18n = apply_filters( 'gbt_filter_add_localization_data_for_js', $i18n );

		global $post;
		if ( $post ) {
			$i18n['isGBT'] = GBT_PostType::CUSTOM_POST_TYPE_SLUG === $post->post_type ? 'true' : 'false';
		}

		wp_localize_script(
			'gbt-post-content-block-js',
			'gbt_post_content_block_js_i18n',
			$i18n
		);

		wp_enqueue_script( 'gbt-post-content-block-js' );

		wp_enqueue_style(
			'gbt-post-content-block-js-editor-css',
			GBT_URL . 'dist/blocks.editor.build.css',
			array( 'wp-edit-blocks' ),
			filemtime( GBT_PATH . '/dist/blocks.editor.build.css' )
		);
	}

	/**
	 * Registers the Gutenberg Templates blocks category.
	 *
	 * @param array $categories The array with the categories of the Gutenberg widgets.
	 *
	 * @return array
	 */
	public function register_gbt_block_category( $categories ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => self::GBT_BLOCKS_CATEGORY_SLUG,
					'title' => __( 'Gutenberg Templates', 'gbt' ),
				),
			)
		);
	}

	/**
	 * Rendering callback of the shortcode returned by the Post Content block.
	 *
	 * @return string
	 */
	public function gbt_post_content() {
		global $post;

		if (
			! $post ||
			GBT_PostType::CUSTOM_POST_TYPE_SLUG === $post->post_type
		) {
			return '';
		}

		return apply_filters( 'the_content', $post->post_content );
	}
}
