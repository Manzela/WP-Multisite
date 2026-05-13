<?php

namespace EventTracker;

class FoottrackingAdmin {
    
    public function __construct() {
        add_action('network_admin_menu', [$this, 'add_tracking_network_admin_menu']);
        add_action('network_admin_init', [$this, 'include_admin_files']);
    }
    
    public function add_tracking_network_admin_menu() {
        add_menu_page(
            'Foottracking',
            'Foottracking',
            'manage_network_options',
            'foottracking',
            [$this, 'render_foottracking_table_page'],
            'dashicons-admin-site',
            6
        );
    }
    
    public function render_foottracking_table_page() {
        if (!current_user_can('manage_network_options')) {
            return;
        }

        if (!class_exists('Foottracking_Table_List')) {
            echo '<div class="notice notice-error"><p>Error: Foottracking_Table_List class not found.</p></div>';
            return;
        }

        $foottracking_table = new \Foottracking_Table_List();
        $foottracking_table->prepare_items();
        ?>
        <div class="wrap">
            <h1>Foottracking Data</h1>
            <form method="post">
                <?php
                 $foottracking_table->domain_filter();
                 $foottracking_table->search_box('Search Data', 'search_id'); 
                $foottracking_table->display();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function include_admin_files() {
        // This method is called on network_admin_init
    }
} 