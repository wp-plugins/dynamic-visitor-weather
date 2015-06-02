<?php
if ( !defined('ABSPATH') && !defined( 'WP_UNINSTALL_PLUGIN')) {
    exit();
}

//delete options array from options
delete_option('dvw_options');

//delete cached data
delete_transient('dvw_static');
delete_transient('dvw_dynamic');

