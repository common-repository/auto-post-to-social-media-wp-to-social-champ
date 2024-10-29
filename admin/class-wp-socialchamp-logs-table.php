<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


class WP_SocialChamp_Logs_Table extends WP_List_Table {

	private $table = 'wpsc_logs';

	public function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => 'wpsc_log',
				'plural'   => 'wpsc_logs',
			)
		);
	}

	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	public function no_items() {

		echo esc_html__( 'No log entries found based on the given search and filter criteria.', 'wp-socialchamp' );

	}

	public function single_row( $item ) {
		echo '<tr class="log-' . esc_attr( $item['result'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}


	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}

	public function column_result( $item ) {
		return ucwords( $item['result'] );
	}

	public function column_action( $item ) {
		return ucwords( $item['action'] );
	}

	public function get_columns() {

		return array(
			'cb'             => '<input type="checkbox" class="toggle" />',
			'post_id'        => 'Post ID',
			'request_sent'   => 'Request Sent',
			'action'         => 'Action',
			'profile_name'   => 'Profile',
			'status_text'    => 'Status Text',
			'result'         => 'Result',
			'result_message' => 'Response',
		);

	}


	public function get_sortable_columns() {
		return array(
			'post_id'        => array( 'post_id', true ),
			'request_sent'   => array( 'request_sent', true ),
			'action'         => array( 'action', true ),
			'profile_name'   => array( 'profile_name', true ),
			'status_text'    => array( 'status_text', true ),
			'result'         => array( 'result', true ),
			'result_message' => array( 'result_message', true ),
		);
	}

	public function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete',
		);

		return $actions;
	}


	public function process_bulk_action() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table;

		if ( 'delete' === $this->current_action() ) {
			$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array(); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ids = array_map( 'absint', $ids );

			if ( is_array( $ids ) ) {
				$ids = implode( ',', $ids );
			}

			if ( ! empty( $ids ) ) {
				$wpdb->query( "DELETE FROM $table_name WHERE id IN($ids)" );
			}
		}
	}


	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table;

		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// here we configure table headers, defined in our methods
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// [OPTIONAL] process bulk action if any
		$this->process_bulk_action();

		// will be used in pagination settings
		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" ); // phpcs:ignore


		// prepare query params, as usual current page, order by and order direction
		$paged   = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] - 1 ) * $per_page ) : 0; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$orderby = isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ), true ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'id';

		$order   = ( isset( $_REQUEST['order'] ) && in_array( 
			$_REQUEST['order'],
			array(
				'asc',
				'desc',
			)
		) ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['order'] ) ) : 'desc';

		// [REQUIRED] define $items array
		// notice that last argument is ARRAY_A, so we will retrieve array
		$this->items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM '. $table_name .' ORDER BY %s %s LIMIT %d OFFSET %d', $orderby, $order, $per_page, $paged ), ARRAY_A ); // phpcs:ignore

		// [REQUIRED] configure pagination
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				// total items defined above
												  'per_page' => $per_page,
				// per page constant defined at top of method
												  'total_pages' => ceil( $total_items / $per_page ),
			// calculate pages count
			)
		);
	}
}
