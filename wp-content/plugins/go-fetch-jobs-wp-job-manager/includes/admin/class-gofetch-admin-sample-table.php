<?php
/**
 * Extends the HTML table class to output an HTML table with sample content.
 *
 * @package GoFetchJobs/Admin/Sample Table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sample HTML table class.
 */
class GoFetch_WPJM_Sample_Table extends GoFetch_WPJM_HTML_Table {

	/**
	 * Constructor.
	 */
	public function __construct( $fields, $content_type ) {
		$this->fields = $this->make_fields_struct( $fields );
		$this->content_type = $content_type;
	}

	/**
	 * Prepares and displays the final table.
	 */
	public static function display( $query_args = array(), $fields = array() ) {

		$defaults = array(
			'content_type' => GoFetch_WPJM()->parent_post_type,
		);
		$query_args = wp_parse_args( $query_args, $defaults );

		// output the table
		$table = new GoFetch_WPJM_Sample_Table( $fields, $query_args['content_type'] );

		ob_start();

		$table->show();

		return ob_get_clean();
	}

	/**
	 * Outputs the final table.
	 */
	public function show() {
		echo $this->table( $this->fields, array( 'class' => 'goft_wpjm_table widefat fields' ) );
	}

	/**
	 * Retrieves the table header.
	 */
	public function header( $data ) {

		$cols = 2;

		for( $i = 0; $i <= $cols; $i++ ) {
			$atts[ $i ] = array(
				'class' => 'index',
				'width' => '80%',
			);

			if ( $i === 0) {
				$atts[ $i ]['width'] = '20%';
			}

		}

		$cells = $this->cells( array(
			__( 'Fields', 'gofetch-wpjm' ),
			__( 'Sample Content', 'gofetch-wpjm' ),
		), $atts, 'td' );

		return html( 'tr', array(), $cells );
	}

	/**
	 * Retrieves the table footer.
	 */
	public function footer( $data ) {
		return $this->header( $data );
	}

	/**
	 * Retrieves the table row.
	 */
	protected function row( $item = array(), $atts = array() ) {

		if ( empty( $item['data'] ) ) {
			$item['data'] = '-';
		} elseif ( is_serialized( $item['data'] ) ) {
			$item['data'] = __( '[serialized data]', 'gofetch-wpjm' );
		}

		$field_atts = array(
			'type'    => 'checkbox',
			'name'    => 'field[]',
			'class'   => 'field',
			'value'   => $item['name'],
			'checked' => $item['checked']
		);

		if ( 'user_pass' == $item['name'] ) {
			$field_atts['disabled'] = 'disabled';
		}

		$cells = $this->cells( array(
			html( 'span', array( 'class' => 'field' ), ucfirst( $item['name'] ) ) .
			html( 'input', array( 'type' => 'hidden', 'name' => 'type['.$item['name'].']', 'value' => $item['type'] ) ),
			html( 'span', array( 'class' => 'sample_content' ), $item['data'] ),
		) );

		if ( '_' == $item['name'][0] ) {
			$atts['class'] .= ' hidden_field';
		}
		$atts['class'] .= ' ' . $item['type'];

		return html( 'tr', $atts, $cells );
	}

	/**
	 * Given a list of fields retrieves them as a normalized associative array.
	 */
	private function make_fields_struct( $fields, $type = 'custom', $content_type = 'post' ) {

		$fields_struct = array();

		foreach( $fields as $field => $data ) {

			if ( ! is_array( $data ) && $data ) {
				$data = array( 'data' => $data );
			}

			$default = array(
				'name'              => $field,
				'type'              => $type,
				'content_data_type' => GoFetch_WPJM_Helper::get_field_type( $field, $content_type ),
				'data'              => '-',
				'label'             => '',
				'checked'           => false,
			);

			$fields_struct[ $field ] = wp_parse_args( $data, $default );

		}
		return $fields_struct;
	}

}
