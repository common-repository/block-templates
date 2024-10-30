<?php

namespace Gutenberg_Templates\Controllers;

use \Gutenberg_Templates\Controllers\Gutenberg_Templates as GBT;
use \Gutenberg_Templates\Controllers\Gutenberg_Templates_Post_Type as GBT_PostType;

/**
 * "Gutenberg Templates" plugin's frontend rendering handler class.
 *
 * @category Class
 * @package  Gutenberg_Templates
 * @author   Konstantinos Galanakis
 */
class Gutenberg_Templates_Rendering {
	/**
	 * Class initialization.
	 */
	public function initialize() {
		add_filter( 'the_content', array( $this, 'the_content_for_gutenberg_templates' ), 1, 1 );

		add_filter( 'wpv_filter_wpv_override_content_template', array( $this, 'disable_toolset_views_content_template_rendering' ), 10, 2 );
	}

	/**
	 * The callback of "the_content" filter that handles the overall rendering of a post from the aspect of a Gutenberg Tempalte.
	 *
	 * @param string $content The content.
	 *
	 * @return string
	 */
	public function the_content_for_gutenberg_templates( $content ) {
		global $post;

		if ( is_null( $post ) ) {
			return $content;
		}

		// Views currently supports Gutenberg Template only for singular frontend pages.
		if ( ! is_singular() ) {
			return $content;
		}

		if ( ! $this->maybe_allow_gutenberg_template_rendering() ) {
			// There is some debug info, which means that the Gutenberg Template rendering process should stop.
			return $content;
		}

		$template_slug = $this->maybe_get_the_selected_gutenberg_template_slug( $post );

		if ( ! $template_slug ) {
			return $content;
		}

		$content_from_gutenberg_template = $this->render_gutenberg_template( $template_slug );

		if ( null === $content_from_gutenberg_template ) {
			return $content;
		}

		return $content_from_gutenberg_template;
	}

	/**
	 * Decides whether the current "the_content" filter call qualifies for Gutenberg Template rendering.
	 *
	 * @return bool.
	 */
	public function maybe_allow_gutenberg_template_rendering() {
		// Core functions that we accept calls from.
		$the_content_core = array(
			'the_content',
		);

		$the_content_blacklist = array(
			'require',
			'require_once',
			'include',
			'include_once',
			'locate_template',
			'load_template',
			'apply_filters',
			'call_user_func_array',
			'wpcf_fields_wysiwyg_view',
		);

		if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
			// @codingStandardsIgnoreLine
			$db = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 ); // phpcs:ignore PHPCompatibility.PHP.NewFunctionParameters.debug_backtrace_limitFound
		} else {
			$db = debug_backtrace();
		}

		$function_candidate = array();

		// From php7 debug_backtrace() has changed, and the target function might be at index 2 instead of 3 as in php < 7
		// Also, from WP 4.7 the new way to manage hooks adds some intermediary items so let's cover our backs reaching to 5
		// Also, the_excerpt_for_archives is supposed to be at index 1
		// Note: we might want to add a pluk and filter here, maybe...
		if ( isset( $db[5]['function'] ) ) {
			if ( isset( $db[5]['class'] ) ) {
				$function_candidate[] = $db[5]['class'] . '::' . $db[5]['function'];
			} else {
				$function_candidate[] = $db[5]['function'];
			}
		}

		if ( isset( $db[4]['function'] ) ) {
			if ( isset( $db[4]['class'] ) ) {
				$function_candidate[] = $db[4]['class'] . '::' . $db[4]['function'];
			} else {
				$function_candidate[] = $db[4]['function'];
			}
		}

		if ( isset( $db[3]['function'] ) ) {
			if ( isset( $db[3]['class'] ) ) {
				$function_candidate[] = $db[3]['class'] . '::' . $db[3]['function'];
			} else {
				$function_candidate[] = $db[3]['function'];
			}
		}

		if ( isset( $db[2]['function'] ) ) {
			if ( isset( $db[2]['class'] ) ) {
				$function_candidate[] = $db[2]['class'] . '::' . $db[2]['function'];
			} else {
				$function_candidate[] = $db[2]['function'];
			}
		}

		if ( isset( $db[1]['function'] ) ) {
			if ( isset( $db[1]['class'] ) ) {
				$function_candidate[] = $db[1]['class'] . '::' . $db[1]['function'];
			} else {
				$function_candidate[] = $db[1]['function'];
			}
		}

		$function_candidate = array_diff( $function_candidate, $the_content_blacklist );

		if ( empty( $function_candidate ) ) {
			return false;
		}

		$function_ok = false;

		foreach ( $function_candidate as $function_candidate_for_content ) {
			if ( in_array( $function_candidate_for_content, $the_content_core, true ) ) {
				$function_ok = true;
			}
		}

		if ( ! $function_ok ) {
			return false;
		}

		return true;
	}

	/**
	 * Decides if the given $post should use a Gutenberg Template either assigned to its post type or directly assigned to
	 * the post itself.
	 *
	 * @param \WP_post $post  The WP_Post object of the current post.
	 *
	 * @return null|string
	 */
	public function maybe_get_the_selected_gutenberg_template_slug( $post ) {
		if (
			isset( $post->view_template_override ) &&
			strtolower( $post->view_template_override ) === 'none'
		) {
			return null;
		}

		$template_slug = get_post_meta( $post->ID, GBT::OPTION_POST_META_KEY, true );

		if ( ! empty( $template_slug ) ) {
			return $template_slug;
		}

		// "None" has been selected on the Gutenberg Template assignment metabox for the given post.
		if ( '0' === $template_slug ) {
			return null;
		}

		$gutenberg_templates_assignments = get_option( GBT::OPTION_POST_META_KEY ) ?: array();

		$assigned_gutenberg_template_for_post_type = isset( $gutenberg_templates_assignments[ $post->post_type ] ) ? $gutenberg_templates_assignments[ $post->post_type ] : false;

		if ( $assigned_gutenberg_template_for_post_type ) {
			return $assigned_gutenberg_template_for_post_type;
		}

		return null;
	}

	/**
	 * Handles the rendering of the Gutenberg Template by returning the actual template content.
	 *
	 * @param string $template_slug The post slug of the Gutenberg Template post.
	 *
	 * @return string
	 */
	public function render_gutenberg_template( $template_slug ) {
		$args = array(
			'name' => $template_slug,
			'post_type' => GBT_PostType::CUSTOM_POST_TYPE_SLUG,
			'post_status' => 'publish',
			'posts_per_page' => 1,
		);

		$gutenberg_templates = get_posts( $args );
		$gutenberg_template = ( $gutenberg_templates[0] ) ?: false;

		if ( ! $gutenberg_template ) {
			return null;
		}

		return $gutenberg_template->post_content;
	}

	/**
	 * Disables the rendering mechanism of the Toolset Views Content Templates when a Gutenberg Template has been assigned to the
	 * post specifically.
	 *
	 * @param string $template_selected The selected Content Template ID.
	 * @param int    $id                The current post ID.
	 *
	 * @return null|string
	 */
	public function disable_toolset_views_content_template_rendering( $template_selected, $id ) {
		$post_meta = get_post_meta( $id, GBT::OPTION_POST_META_KEY, true );

		if (
			'' !== $post_meta &&
			'0' !== $post_meta
		) {
			return null;
		}

		if ( '0' === $post_meta ) {
			return $template_selected;
		}

		$assigned_templates = get_option( GBT::OPTION_POST_META_KEY ) ?: array();

		if ( ! empty( $assigned_templates ) ) {
			$post = get_post( $id );
			if (
				$post &&
				isset( $assigned_templates[ $post->post_type ] ) &&
				'' !== $assigned_templates[ $post->post_type ]
			) {
				return null;
			}
		}

		return $template_selected;
	}
}
