<?php
/*
Plugin Name: Dynamic Visitor Weather
Plugin URI: http://mohamedatef.tk/gallery/dynamic-visitor-weather/
Description: 
Version: 1.0.0
Author: Mohamed Atef
Author URI: http://mohamedatef.tk/
License: GPLv2

Copyright 2015
Mohamed Atef
(email : en.mohamed.atef@gmail.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details. 
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

//prevent direct access to the plugin.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

//add default options through activating the plugin
register_activation_hook(__FILE__, 'dvw_register_data_activation');
function dvw_register_data_activation(  ) {
    $dvw_options = array(
        'api_key' => '',
        'show_status' => 'dynamic_show',
        'temp_metric' => 'c',
        'city' => '',
        'dynamic_fail'=>''
    );
    $exist_options = get_option('dvw_options');
    if ( !empty($exist_options) ) {
       $dvw_options = wp_parse_args($exist_options, $dvw_options);
    }
    update_option('dvw_options', $dvw_options);
}

//Require core files
require_once 'includes/OptionsPage.php';

//Require widget class
require_once 'includes/DvwWidget.php';

//deactivation actions
register_deactivation_hook(__FILE__, 'dvw_register_deactivation_actions');
function dvw_register_deactivation_actions(  ) {
//    delete cached data
    delete_transient('dvw_static');
    delete_transient('dvw_dynamic');
}