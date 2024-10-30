<?php

namespace Gutenberg_Templates\Controllers;

use \Gutenberg_Templates\Controllers\Gutenberg_Templates as GBT;
use \Gutenberg_Templates\Controllers\Gutenberg_Templates_Post_Type as GBT_PostType;

/**
 * "Gutenberg Templates" plugin's custom post type registration handler class.
 *
 * @category Class
 * @package  Gutenberg_Templates
 * @author   Konstantinos Galanakis
 */
class Gutenberg_Templates_Post_Type {
	const CUSTOM_POST_TYPE_SLUG = 'gutenberg-template';

	/**
	 * Main initializer.
	 */
	public function initialize() {
		add_filter( 'init', array( $this, 'register_post_type' ) );

		add_filter( 'manage_' . self::CUSTOM_POST_TYPE_SLUG . '_posts_columns', array( $this, 'adjust_gbt_admin_columns' ) );

		add_action( 'manage_' . self::CUSTOM_POST_TYPE_SLUG . '_posts_custom_column', array( $this, 'populate_gbt_admin_columns' ), 10, 2 );
	}

	/**
	 * Registers the Gutenberg Templates custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name' => _x( 'Gutenberg Templates', 'Post Type General Name', 'gbt' ),
			'singular_name' => _x( 'Gutenberg Template', 'Post Type Singular Name', 'gbt' ),
			'menu_name' => __( 'Gutenberg Templates', 'gbt' ),
			'name_admin_bar' => __( 'Gutenberg Template', 'gbt' ),
			'archives' => '',
			'attributes' => __( 'Item Attributes', 'gbt' ),
			'parent_item_colon' => '',
			'all_items' => __( 'All Gutenberg Templates', 'gbt' ),
			'add_new_item' => __( 'Add New Gutenberg Template', 'gbt' ),
			'add_new' => __( 'Add New', 'gbt' ),
			'new_item' => __( 'New Gutenberg Template', 'gbt' ),
			'edit_item' => __( 'Edit Gutenberg Template', 'gbt' ),
			'update_item' => __( 'Update Gutenberg Template', 'gbt' ),
			'view_item' => __( 'View Gutenberg Template', 'gbt' ),
			'view_items' => __( 'View Gutenberg Templates', 'gbt' ),
			'search_items' => __( 'Search Gutenberg Templates', 'gbt' ),
			'not_found' => __( 'No Gutenberg Templates found', 'gbt' ),
			'not_found_in_trash' => __( 'No Gutenberg Templates found in Trash', 'gbt' ),
		);

		$args = array(
			'label' => __( 'Gutenberg Template', 'gbt' ),
			'description' => __( 'Templates for post built with the new editor, Gutenberg.', 'gbt' ),
			'labels' => $labels,
			'supports' => array( 'title', 'editor', 'author' ),
			'hierarchical' => false,
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 2,
			'menu_icon' => 'dashicons-media-document',
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,
			'can_export' => false,
			'has_archive' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'capability_type' => 'post',
			'show_in_rest' => true,
		);

		register_post_type( self::CUSTOM_POST_TYPE_SLUG, $args );
	}

	/**
	 * Rearranges existing columns and adds new on the Gutenberg Templates listing page.
	 *
	 * @param array $columns The columns on the admin listing page.
	 *
	 * @return array
	 */
	public function adjust_gbt_admin_columns( $columns ) {
		$columns = array (
			'cb' => $columns['cb'],
			'title' => __( 'Title' ),
			'assigned_post_type' => __( 'Assigned post type' ),
			'author' => __( 'Author' ),
			'date' => __( 'Date' ),
		);

		return $columns;
	}

	public function populate_gbt_admin_columns( $column, $post_id ) {
		// Assigned post type column
		if ( 'assigned_post_type' === $column ) {
			$post = get_post( $post_id );

			if ( ! $post ) {
				echo '-';
				return $column;
			}

			$gbt_assignments = get_option( GBT::OPTION_POST_META_KEY ) ?: array();

			if (
				empty( $gbt_assignments ) ||
				! in_array( $post->post_name, $gbt_assignments, true )
			) {
				echo '-';
				return $column;
			}

			$assigned_post_type = array_search($post->post_name, $gbt_assignments );
			$assigned_post_type_object = get_post_type_object( $assigned_post_type );

			if ( ! $assigned_post_type_object ) {
				echo '-';
				return $column;
			}

			echo esc_html( $assigned_post_type_object->labels->name );
			return $column;
		}
		return $column;
	}
}
