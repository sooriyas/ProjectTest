<?php
/**
 * Provides public helper methods.
 *
 * @package GoFetch/Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Helper class.
 */
class GoFetch_WPJM_Helper {

	/**
	 * Retrieves a given field content type if recognized. Defaults to 'text' if unknown.
	 */
	public static function get_field_type( $field, $content_type = 'post' ) {

		$type = 'text';

		$fields = self::get_known_field_types();

		$fields = array_merge( $fields, self::get_field_types( $content_type ) );

		if ( $field && isset( $fields[ $field ] ) ) {
			$type = $field;
		}
		return $type;
	}

	/**
	 * Retrieve known field types for a know list of core fields.
	 *
	 * @uses apply_filters() Calls 'goft_wpjm_known_field_types'
	 */
	public static function get_known_field_types() {

		$fields = array(
			'post_author' => array(
				'user' => __( 'User', 'gofetch-wpjm' ),
				'text' => __( 'Text', 'gofetch-wpjm' ),
			),
			'post_status' => array(
				'post_status' => __( 'Post Status', 'gofetch-wpjm' ),
				'text'        => __( 'Text', 'gofetch-wpjm' )
			),
		);
		return apply_filters( 'goft_wpjm_known_field_types', $fields );
	}

	/**
	 * Retrieve all possible field types.
	 *
	 * @uses apply_filters() Calls 'goft_wpjm_field_types'
	 *
	 */
	public static function get_field_types( $content_type = 'post' ) {

		$types = array(
			'text' => __( 'Text', 'gofetch-wpjm' ),
			'date' => __( 'Date', 'gofetch-wpjm' ),
			'user' => __( 'User', 'gofetch-wpjm' ),
		);

		if ( 'user' != $content_type ) {
			$types['post_status'] = __( 'Post Status', 'gofetch-wpjm' );
		}

		// Get existing taxonomies.
		$taxonomies = get_object_taxonomies( $content_type, 'objects' );

		// Unset the 'post_status' taxonomy since it's empty.
		$taxonomies['post_status'] = null; unset( $taxonomies['post_status'] );

		foreach ( $taxonomies as $tax ) {
			$types[ $tax->name ] = sprintf( __( "Taxonomy :: %s", 'gofetch-wpjm' ), $tax->label );
		}

		return apply_filters( 'goft_wpjm_field_types', $types, $content_type );
	}

	/**
	 * Matches a list of keywords against a string.
	 *
	 * @since 1.2
	 */
	public static function match_keywords( $text, $keywords ) {

		$match = false;

		$text = html_entity_decode( $text );

		// Check if the text contains the positive keywords.
		foreach ( (array) $keywords as $keyword ) {
			if ( false !== stripos( $text, trim( $keyword ) ) ) {
				$match = true;
				break;
			}
		}
		return $match;
	}

	/**
	 * Removes extra slashes from a string.
	 *
	 * @since 1.2.3.
	 */
	public static function remove_slashes( $string ) {
	    $string = implode( '',explode( '\\',$string ) );
	    return stripslashes( trim( $string ) );
	}


	/**
	 * Retrieves the sanitized list of saved templates.
	 *
	 * @since 1.2.3.
	 */
	public static function get_sanitized_templates() {
		global $goft_wpjm_options;

		$templates = array();

		foreach ( $goft_wpjm_options->templates as $template => $data ) {
			$template = GoFetch_WPJM_Helper::remove_slashes( $template );
			$templates[ $template ] = $data ;
		}
		return $templates;
	}

}
