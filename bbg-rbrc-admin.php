<?php
// Actually - add the menu...
if(is_multisite()){
    add_action( 'network_admin_menu', 'rbrc_add_menu' );
}else{
    add_action( 'admin_menu', 'rbrc_add_menu' );
}
function rbrc_add_menu(){
    $file = 'tools.php';
    if(is_multisite()){
        $file = 'users.php';
    }
    add_submenu_page(
        $file,
        __('BBG Record Blog Roles Changes', 'rbrc'),
        __('Blog Roles Changes', 'rbrc'),
        'edit_users',
        'rbrc-admin',
        'rbrc_admin' )
    ;
}

// ... and display the content
function rbrc_admin(){
    echo '<div id="rbrc-admin" class="wrap">';
        screen_icon('tools');
    
        echo '<h2>';
            _e('Blog Roles Changes Data','rbrc');
        echo '</h2>';

        echo '<form id="brc-form" method="post">';
            //Prepare Table of elements
            $brc_list_table = new BRC_List_Table();
            $brc_list_table->prepare_items();
            $brc_list_table->display();   
        echo '</form>';
        
    echo '</div>';
}

if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BRC_List_Table extends WP_List_Table {

    public $per_page = 20;

	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	function __construct() {
		parent::__construct( array(
            'singular' => 'blog_roles_change', //Singular label
            'plural'   => 'blog_roles_changes', //plural label, also this well be one of the table css class
            'ajax'	   => false
		) );
	}
        
    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns() {
        $columns = array(
            'cb'                => '<input type="checkbox" />',
            'user_id'           => __('User Affected', 'rbrc'),
            'loggedin_user_id'  => __('User Initiator', 'rbrc'),
            'blog_id'           => __('Site', 'rbrc')
        );
        if(defined('BP_VERSION')){    
            $columns['group_id'] = __('Group Name', 'rbrc');
        }
        $columns['url']          = __('Url', 'rbrc');
        $columns['roles']        = __('Roles (from->to)', 'rbrc');
        $columns['date']         = __('Date', 'rbrc');

        return $columns;
    }
    
    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns() {
        $sortable = array(
            'user_id'           => array('user_id', false),
            'loggedin_user_id'  => array('loggedin_user_id', false),
            'group_id'          => array('group_id', false),
            'date'              => array('date', true)
        );
        if(defined('BP_VERSION')){
            $sortable['blog_id'] = array('blog_id', false);
        }
        
        return $sortable;
    }

    // Bulk options to selected items
    function get_bulk_actions() {
        $actions = array(
            'delete'    => __('Delete', 'rbrc')
        );
        return $actions;
    }
    
    function process_bulk_action() {
        // Detect when a bulk action is being triggered...
        if( 'delete' === $this->current_action() && !empty($_POST['brc_ids']) ) {
            global $wpdb;
            $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->bbg_rbrc_table . ' WHERE id IN ('.implode(',',$_POST['brc_ids']) . ')'));
        }
    }
    
    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    function prepare_items() {
        global $wpdb;
    
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Do smth with seleceted bulk rows
        $this->process_bulk_action();
        
        // Basic query to get them all
        $query = "SELECT * FROM {$wpdb->bbg_rbrc_table}";

	    // Parameters that are going to be used to order the result
	    $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'date';
	    $order   = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : 'DESC';
	    if(!empty($orderby) & !empty($order)){
            $query .= ' ORDER BY `'.$orderby.'` '.$order;
        }

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows

        //Which page is this?
        $paged = $this->get_pagenum();

        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged <= 0 ){
            $paged = 1;
        }

        //How many pages do we have in total?
        $totalpages = ceil( $totalitems / $this->per_page );
        
        //adjust the query to take pagination into account
	    if(!empty($paged) && !empty($this->per_page)){
		    $offset = ($paged - 1) * $this->per_page;
    		$query .= ' LIMIT ' . (int) $offset . ',' . (int) $this->per_page;
	    }

        // Register the pagination
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page"    => $this->per_page,
		) );

        // Fetch the items
		$this->items = $wpdb->get_results($query);
    }

    // Fallback
    function column_default($item, $column_name){
        return print_r($item,true); //Show the whole array for troubleshooting purposes
    }
    
    // Checkboxes
    function column_cb($item){
        return '<input type="checkbox" name="brc_ids[]" value="'.$item->id.'" />';
    }
    
    function column_user_id($item){
        $user = get_userdata($item->user_id);
        return $user->display_name . ' (ID:'.$item->user_id.')';
    }
    
    function column_loggedin_user_id($item){
        $loggedin = get_userdata($item->loggedin_user_id);
        return $loggedin->display_name . ' (ID:'.$item->loggedin_user_id.')';
    }
    
    function column_blog_id($item){
        $site = get_blog_details($item->blog_id);
        return $site->blogname;
    }
    
    function column_group_id($item){
        $group = new Stdclass;
        if($item->group_id > 0)
            $group = groups_get_group(array('group_id' => $item->group_id));
        else
            $group->name = '';
        return $group->name;
    }
    
    function column_url($item){
        return '<a href="'.$item->url.'" target="_blank">'.$item->url;
    }
    
    function column_roles($item){
        return $item->role_from.' &rarr; '.$item->role_to;
    }
    
    function column_date($item){
        return $item->date;
    }

}



?>