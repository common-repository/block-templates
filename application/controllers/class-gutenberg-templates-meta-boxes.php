<?php
namespace Gutenberg_Templates\Controllers;

use \Gutenberg_Templates\Controllers\Gutenberg_Templates as GBT;
use \Gutenberg_Templates\Controllers\Gutenberg_Templates_Post_Type as GBT_PostType;

/**
 * "Gutenberg Templates" plugin's meta boxes handler class.
 *
 * @category Class
 * @package  Gutenberg_Templates
 * @author   Konstantinos Galanakis
 */
class Gutenberg_Templates_Meta_Boxes {
	const POST_TYPE_ASSIGNMENT_ACTION = 'gbt-post-type-assignment';

	const TEMPLATE_ASSIGNMENT_ACTION = 'gbt-template-assignment';

	/**
	 * Main initializer.
	 */
	public function initialize() {
		add_action( 'add_meta_boxes_' . GBT_PostType::CUSTOM_POST_TYPE_SLUG, array( $this, 'post_type_assignment_metabox' ) );

		add_action( 'add_meta_boxes', array( $this, 'template_assignment_metabox' ), 10, 2 );

		add_action( 'save_post_' . GBT_PostType::CUSTOM_POST_TYPE_SLUG, array( $this, 'save_post_type_assignment' ), 10, 2 );

		add_action( 'save_post', array( $this, 'save_template_assignment' ), 10, 2 );

		add_filter( 'gbt_filter_add_localization_data_for_js', array( $this, 'add_metaboxes_related_localization_data' ) );
	}

	/********************************************************
	 *   Gutenberg Template Post Type assignment metabox    *
	 ********************************************************/

	/**
	 * Adds the Gutenberg Template Post Type assignment metabox inside the Gutenberg Template post edit page.
	 */
	public function post_type_assignment_metabox() {
		add_meta_box(
			'gbt-post-type-assignment-metabox',
			__( 'Use this template for:', 'gbt' ),
			array( $this, 'post_type_assignment_metabox_callback' ),
			GBT_PostType::CUSTOM_POST_TYPE_SLUG,
			'side',
			'high'
		);
	}

	/**
	 * The rendering callback of the Post Type assignment metabox.
	 *
	 * @param \WP_Post $post The WP_Post object of the current post.
	 */
	public function post_type_assignment_metabox_callback( $post ) {
		$post_types = get_post_types(
			array(
				'public' => true,
				'show_in_rest' => true,
			),
			'objects'
		);

		$none_option = array(
			'name' => '0',
			'label' => __( 'None', 'gbt' ),
		);
		array_unshift( $post_types, (object) $none_option );

		echo '<input type="hidden" name="nonce" value="' . esc_attr( wp_create_nonce( self::POST_TYPE_ASSIGNMENT_ACTION ) ) . '" />';
		echo '<select '
					. 'id="gbt-post-type-assignment" '
					. 'name="' . esc_attr( GBT::OPTION_POST_META_KEY ) . '[]" '
					. 'class="js-gbt-post-type-assignment widefat"'
			. '>';

		$gbt_assignments = get_option( GBT::OPTION_POST_META_KEY ) ?: array();

		$assigned_post_types_for_current_gbt = array_keys( $gbt_assignments, $post->post_name, true );

		foreach ( $post_types as $post_type ) {
			$assigned_gbt_for_post_type = isset( $gbt_assignments[ $post_type->name ] ) ? $gbt_assignments[ $post_type->name ] : false;

			echo '<option '
					. 'value="' . esc_attr( $post_type->name ) . '" '
					. ( in_array( $post_type->name, $assigned_post_types_for_current_gbt, true ) ? 'selected' : '' ) . ' '
					. ( ( $assigned_gbt_for_post_type ) ? 'data-gbt-assigned-template-name="' . esc_attr( $assigned_gbt_for_post_type ) . '"' : '' ) . ' '
					. ( ( $assigned_gbt_for_post_type ) ? 'data-gbt-post-type-label="' . esc_attr( $post_type->label ) . '"' : '' ) . '>'
					. esc_html( $post_type->label )
				. '</option>';
		}

		echo '</select>';
	}

	/**
	 * Handles the saving of the Gutenberg Template Post Type assignment metabox selection.
	 *
	 * @param int      $post_id  The post ID of the current post.
	 * @param \WP_Post $post     The WP_Post object of the current post.
	 */
	public function save_post_type_assignment( $post_id, $post ) {
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::POST_TYPE_ASSIGNMENT_ACTION ) ) {
			return;
		}

		$selected_post_types = isset( $_POST[ GBT::OPTION_POST_META_KEY ] ) ?
			array_map( 'sanitize_text_field', wp_unslash( $_POST[ GBT::OPTION_POST_META_KEY ] ) ) :
			false;

		if ( ! $selected_post_types ) {
			return;
		}

		$option = get_option( GBT::OPTION_POST_META_KEY ) ?: array();

		if (
			false === $option ||
			! is_array( $option )
		) {
			$option = array();
		}

		// Delete this if we ever allow multiple post type assignment to a Gutenberg Template.
		// Here we are removing any other post type assignments for this Gutenberg Template.
		$maybe_template_has_post_type_assignment = array_search( $post->post_name, $option, true );
		if ( $maybe_template_has_post_type_assignment ) {
			unset( $option[ $maybe_template_has_post_type_assignment ] );
		}

		foreach ( $selected_post_types as $selected_post_type ) {
			if ( '0' !== $selected_post_type ) {
				$option[ $selected_post_type ] = $post->post_name;
			}
		}

		update_option( GBT::OPTION_POST_META_KEY, $option );
	}

	/**********************************************
	 *   Gutenberg Template assignment metabox    *
	 **********************************************/

	/**
	 * Adds the Gutenberg Template assignment metabox inside the post edit page.
	 *
	 * @param string   $post_type The post type of the current post.
	 * @param \WP_Post $post      The WP_Post object of the current post.
	 */
	public function template_assignment_metabox( $post_type, $post ) {
		if ( ! $post ) {
			return;
		}

		if ( $this->maybe_post_type_needs_gbt_assignment_metabox( $post ) ) {
			add_meta_box(
				'gbt-template-assignment-metabox',
				__( 'Gutenberg Template', 'gbt' ),
				array( $this, 'template_assignment_metabox_callback' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	/**
	 * The rendering callback of the Gutenberg Template assignment metabox.
	 *
	 * @param \WP_Post $post The WP_Post object of the current post.
	 */
	public function template_assignment_metabox_callback( $post ) {
		$gutenberg_templates = apply_filters( 'gbt_filter_get_gutenberg_templates', array() );

		$none_option = array(
			'post_name' => '0',
			'post_title' => __( 'None', 'gbt' ),
		);
		array_unshift( $gutenberg_templates, (object) $none_option );

		$post_assigned_gutenberg_template = get_post_meta( $post->ID, GBT::OPTION_POST_META_KEY, true );

		$post_type_assigned_gutenberg_templates = get_option( GBT::OPTION_POST_META_KEY ) ?: array();

		$current_post_type_assigned_gutenberg_template = isset( $post_type_assigned_gutenberg_templates[ $post->post_type ] ) ? $post_type_assigned_gutenberg_templates[ $post->post_type ] : false;

		$post_type_object = get_post_type_object( $post->post_type );

		if ( $current_post_type_assigned_gutenberg_template ) {
			$current_post_type_assigned_gutenberg_template_object = array_filter(
				$gutenberg_templates,
				function( $obj ) use ( $current_post_type_assigned_gutenberg_template ) {
					return $obj->post_name === $current_post_type_assigned_gutenberg_template;
				}
			);

			if ( ! empty( $current_post_type_assigned_gutenberg_template_object ) ) {
				$current_post_type_assigned_gutenberg_template_label = reset( $current_post_type_assigned_gutenberg_template_object )->post_title;

				$message_part_1 = sprintf(
					/* translators: Blah blah blah blah. */
					__( 'The current post type ("%1$s") already has a Gutenberg Template assigned ("%2$s").', 'gbt' ),
					$post_type_object->label,
					$current_post_type_assigned_gutenberg_template_label
				);

				$message_part_2 = __( 'If you want to assign another, choose from the list below.', 'gbt' );

				echo '<div class="components-notice is-info"><div class="components-notice__content">' . esc_html( $message_part_1 ) . '<br />' . esc_html( $message_part_2 ) . '</div></div>';
				echo '<br />';
			}
		}

		echo '<select ' .
			'id="gbt-post-type-assignment" ' .
			'class="js-gbt-template-assignment widefat" ' .
			'name="' . esc_attr( GBT::OPTION_POST_META_KEY ) . '[' . esc_attr( $post->ID ) . ']"' .
			'>';

		foreach ( $gutenberg_templates as $gutenberg_template ) {
			if ( '' !== $post_assigned_gutenberg_template ) {
				$selected = $gutenberg_template->post_name === $post_assigned_gutenberg_template ?
					'selected' :
					'';
			} else {
				$selected = $current_post_type_assigned_gutenberg_template === $gutenberg_template->post_name ?
					'selected' :
					'';
			}

			echo '<option ' .
						'value="' . esc_attr( $gutenberg_template->post_name ) . '" ' .
						esc_attr( $selected ) .
				'>' .
						esc_html( $gutenberg_template->post_title ) .
				'</option>';
		}

		echo '</select>';

		echo '<input type="hidden" name="nonce" value="' . esc_attr( wp_create_nonce( self::TEMPLATE_ASSIGNMENT_ACTION ) ) . '" />';
	}

	/**
	 * Handles the saving of the Gutenberg Template assignment metabox selection.
	 *
	 * @param int      $post_id  The post ID of the current post.
	 * @param \WP_Post $post     The WP_Post object of the current post.
	 */
	public function save_template_assignment( $post_id, $post ) {
		if ( ! isset( $_POST['nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::TEMPLATE_ASSIGNMENT_ACTION ) ) {
			return;
		}

		$selected_templates = isset( $_POST[ GBT::OPTION_POST_META_KEY ] ) ?
			array_map( 'sanitize_text_field', wp_unslash( $_POST[ GBT::OPTION_POST_META_KEY ] ) ) :
			false;

		if ( ! $selected_templates ) {
			return;
		}

		// "None" has been selected on the Gutenberg Template assignment metabox for the given post.
		if (
			isset( $selected_templates[0] ) &&
			'0' === $selected_templates[0]
		) {
			return;
		}

		$selected_templates = $selected_templates[ $post_id ];

		$template_assignments = get_option( GBT::OPTION_POST_META_KEY ) ?: array();

		$assigned_gutenberg_template_for_post_type = isset( $template_assignments [ $post->post_type ] ) ? $template_assignments [ $post->post_type ] : false;

		// Checking to determine if the post type of the current post already has a Gutenberg Template assigned.
		// In that case if the selected Gutenberg Template differs from the assigned to the post type Gutenberg Template,
		// we are saving it as a post meta.
		if (
			(
				$selected_templates &&
				! $assigned_gutenberg_template_for_post_type
			) ||
			(
				$assigned_gutenberg_template_for_post_type &&
				$assigned_gutenberg_template_for_post_type !== $selected_templates
			)

		) {
			update_post_meta( $post_id, GBT::OPTION_POST_META_KEY, $selected_templates );
			// todo: Fix this!!!!
//			delete_post_meta( $post_id, \WPV_Content_Template_Embedded::POST_TEMPLATE_BINDING_POSTMETA_KEY );
		} else {
			delete_post_meta( $post_id, GBT::OPTION_POST_META_KEY );
		}
	}

	/**
	 * Decides whether the displayed post type edit page needs a Gutenberg Template assignment metabox or not.
	 *
	 * @param \WP_Post $post The post object.
	 *
	 * @return bool True if a Gutenberg Template assignment metabox should be displayed, false otherwise.
	 */
	private function maybe_post_type_needs_gbt_assignment_metabox( $post ) {
		if ( GBT_PostType::CUSTOM_POST_TYPE_SLUG === $post->post_type ) {
			return false;
		}

		$is_gb_page = is_callable( 'is_gutenberg_page' ) && is_gutenberg_page(); // Determines if the current page is edited by Gutenberg for the case where Gutenberg is a plugin.
		$use_block_editor = is_callable( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ); // Determines if the current page is edited by Gutenberg for the case where Gutenberg is in Core.
		$post_type_object = get_post_type_object( $post->post_type );

		if (
			apply_filters( 'gbt_filter_is_gutenberg_active', false ) &&
			( $is_gb_page || $use_block_editor ) &&
			! apply_filters( 'gbt_filter_disable_post_gbt_metabox', false, $post )
			&& (
				$post_type_object->publicly_queryable ||
				$post_type_object->public
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Adds meta boxes related localization data for the main JS bundle script.
	 *
	 * @param array $i18n The JS localization data.
	 *
	 * @return array
	 */
	public function add_metaboxes_related_localization_data( $i18n ) {
		/* translators: Blah blah blah blah. */
		$i18n['postTypeHasAssignmentNotice'] = __( 'The selected post type ("%s") already has a Gutenberg Template assigned. By saving this post, this assignment will be substituted with a new one.', 'gbt' );

		return $i18n;
	}
}
