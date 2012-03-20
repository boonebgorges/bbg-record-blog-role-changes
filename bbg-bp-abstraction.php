<?php
// need this to prevent errors if the code is working on a site without BuddyPress
if ( !function_exists('bp_get_current_group_id') ){
    function bp_get_current_group_id(){
        return 0;
    }
}
?>