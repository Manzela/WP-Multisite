<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Foottracking_Table_List extends WP_List_Table {

    public function __construct() {

        parent::__construct( [
            'singular' => __( 'Foottracking Data', 'visitor-tracking-plugin' ),
            'plural'   => __( 'Foottracking Data', 'visitor-tracking-plugin' ),
            'ajax'     => false,
        ] );
    }

    function prepare_items() {
        global $wpdb;

        $table_name = $wpdb->base_prefix . 'foottracking';
        $search_term = isset($_REQUEST['s']) ? trim($_REQUEST['s']) : '';
        $domain_filter = isset($_REQUEST['domain_filter']) ? trim($_REQUEST['domain_filter']) : '';
        $orderby = !empty($_REQUEST['orderby']) ? esc_sql($_REQUEST['orderby']) : 'id'; 
        $order = !empty($_REQUEST['order']) ? esc_sql($_REQUEST['order']) : 'asc';
        
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $query = "SELECT * FROM $table_name WHERE 1=1";
        if (!empty($search_term)) {
            $query .= $wpdb->prepare(" AND (site_id LIKE %s OR store_name LIKE %s OR email LIKE %s)", '%'.$search_term.'%', '%'.$search_term.'%', '%'.$search_term.'%');
        }

        if (!empty($domain_filter)) {
            $query .= $wpdb->prepare(" AND domain = %s", $domain_filter);
        }

        $query .= " ORDER BY $orderby $order";

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM ($query) as total");
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);

        $data = $wpdb->get_results($query, ARRAY_A);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $this->items = $data;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
    }

    function get_columns() {
        $columns = array(
            'id'         => __( 'ID', 'visitor-tracking-plugin' ),
            'site_id'    => __( 'Site ID', 'visitor-tracking-plugin' ),
            'domain'     => __( 'Domain', 'visitor-tracking-plugin' ),
            'store_name' => __( 'Store Name', 'visitor-tracking-plugin' ),
            'visitor_id' => __( 'Visitor ID', 'visitor-tracking-plugin' ),
            'email'      => __( 'Email', 'visitor-tracking-plugin' ),
            'pagestamp'  => __( 'Page', 'visitor-tracking-plugin' ),
            'location'   => __( 'Location', 'visitor-tracking-plugin' ),
            'dateTime'   => __( 'Date/Time', 'visitor-tracking-plugin' ),
            'rawdata'    => __( 'Raw Data', 'visitor-tracking-plugin' ),
        );
        return $columns;
    }

    function get_sortable_columns() {
        return array(
            'id'         => array('id', true),
            'site_id'    => array('site_id', false),
            'domain'     => array('domain', false),
            'store_name' => array('store_name', false),
            'visitor_id' => array('visitor_id', false),
            'location' => array('location', false),
            'pagestamp' => array('pagestamp', false),
            'rawdata' => array('rawdata', false),
            'email'      => array('email', false),
            'dateTime'   => array('dateTime', false),
        );
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'site_id':
            case 'domain':
            case 'store_name':
            case 'visitor_id':
            case 'email':
            case 'pagestamp':
            case 'location':
            case 'dateTime':
            case 'rawdata':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    public function no_items() {
        _e( 'No visitor data found.', 'visitor-tracking-plugin' );
    }

    public function domain_filter() {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'foottracking';

        $domains = $wpdb->get_col("SELECT DISTINCT domain FROM $table_name");
        $selected_domain = isset($_REQUEST['domain_filter']) ? $_REQUEST['domain_filter'] : '';

        echo '<select name="domain_filter" id="domain_filter">';
        echo '<option value="">' . __('All Domains', 'visitor-tracking-plugin') . '</option>';
        foreach ($domains as $domain) {
            echo '<option value="' . esc_attr($domain) . '" ' . selected($selected_domain, $domain, false) . '>' . esc_html($domain) . '</option>';
        }
        echo '</select>';

        submit_button(__('Filter'), '', 'filter_action', false);
    }

    public function search_box($text, $input_id) {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php echo isset($_REQUEST['s']) ? esc_attr($_REQUEST['s']) : ''; ?>" />
            <?php submit_button(__('Search', 'visitor-tracking-plugin'), 'button', false, false, ['aria-label' => __('Search', 'visitor-tracking-plugin')]); ?>
        </p>
        <?php
    }
} 