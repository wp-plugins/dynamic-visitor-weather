<?php

/**
 * Add options page for the plugin
 *
 * @author Mohamed Atef
 */
class OptionsPage {
    public function __construct( ) {
        add_action('admin_menu', array($this, 'dvwAddOptionsPage'));
        
        add_action('admin_init', array($this, 'dvwRegisterOptions'));
    }
    
    /**
     * Add options page under settings menu 
     */
    public function dvwAddOptionsPage( ) {

        add_menu_page(__('Dynamic Visitor Weather','dvw-languages'), __('Dynamic Visitor Weather', 'dvw-languages'), 'manage_options', 'dvw_options_page', array($this, 'dvwConstructOptionsPage'), plugins_url('../images/weather.gif', __FILE__));
    }
    
    /**
     * Constructing options page
     */
    public function dvwConstructOptionsPage( ) {
        wp_enqueue_style('dvw-admin-style', plugins_url( '../css/dvw-admin-style.css', __FILE__));
        $dvw_options = get_option( 'dvw_options');
        if ( empty($dvw_options) ) {
            $dvw_options['api_key']='';
            $dvw_options['show_status']='';
            $dvw_options['city']='';
            $dvw_options['temp_metric']='';
            $dvw_options['dynamic_fail']='';
        }
        ?>
        <div class="wrap">
            <h2>Dynamic Visitor Weather Options</h2>
            <form action="options.php" method="post">
                <?php settings_fields( 'dvw-options-group' )?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="api"><?php _e('API KEY:', 'dvw-languages')?></label></th>
                        <td>
                            <input type="text" name="dvw_options[api_key]" id="api" placeholder="Enter API KEY from worldweatheronline.com" value="<?php echo esc_attr( $dvw_options['api_key'] ); ?>" required="required" autofocus="autofocus"/>
                            <span id="caution">If you don't have a Key go and get one from <a href="http://www.worldweatheronline.com/api/" target="_blank">Here</a></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Show weather status:', 'dvw-languages')?></th>
                        <td>
                            <label for="static">Static Show</label><input type="radio" id="static" name="dvw_options[show_status]" value="static_show" <?php checked( $dvw_options['show_status'], 'static_show', TRUE );?>/>

                            <label for="dynamic">Dynamic Show</label><input type="radio" id="dynamic" name="dvw_options[show_status]" value="dynamic_show" <?php checked( $dvw_options['show_status'], 'dynamic_show', TRUE );?>/>
                        </td>
                    </tr>
                    <tr valign="top" id="dynamic-fail">
                        <th scope="row"><label for="dynamic-fail-check"><?php _e('Show static data if dynamic fail', 'dvw-languages');?>:</label></th>
                        <td>
                            <input type="checkbox" id="dynamic-fail-check" name="dvw_options[dynamic_fail]" <?php checked( $dvw_options['dynamic_fail'], 'on' );?>>
                        </td>
                    </tr>
                    <tr valign="top" id="city">
                        <th scope="row"><label for="city-text"><?php _e('Write city name:', 'dvw-languages')?></label></th>
                        <td>
                            <input type="text" id="city-text" name="dvw_options[city]" placeholder="Enter City Name" value="<?php echo esc_attr( $dvw_options['city']);?>"/>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="temp"><?php _e('Choose temperature metric:', 'dvw-languages')?></label></th>
                        <td>
                            <select name="dvw_options[temp_metric]" id="temp">
                                <option id="celsius" value="c" <?php selected( $dvw_options['temp_metric'], 'c' )?>>Celsius</option>
                                <option id="fahrenheit" value="f" <?php selected( $dvw_options['temp_metric'], 'f' )?>>Fahrenheit</option>
                            </select>
                            <span id="c">&deg;C</span>
                            <span id="f">&deg;F</span>
                        </td>
                    </tr>
                    <tr>
                        <td><?php submit_button();?></td>
                    </tr>
                </table>
            </form>
        </div>
        <footer>
            <p>Plugin Created By <a href="http://mohamedatef.tk" title="Mohamed Atef Software Engineer" target="_blank">Mohamed Atef</a></p>
            <p>Support E-Mail: en.mohamed.atef@gmail.com</p>
        </footer>
        <?php
        wp_enqueue_script('dvw_admin_script', plugins_url('dynamic-visitor-weather/js/dvw_admin.js'), array('jquery'), 0.1, TRUE);
    }
    
    /**
     * Register Options
     */
    public function dvwRegisterOptions( ) {
        register_setting('dvw-options-group', 'dvw_options', array($this, 'dvwSanitizeOptions'));
    }
    
    /**
     * Sanitize Input fields before saving in database
     * @param array $inputs
     */
    public function dvwSanitizeOptions( $inputs ) {
        $inputs['api_key'] = (!empty($inputs['api_key'])) ? sanitize_text_field($inputs['api_key']) : '';
        
        $inputs['dynamic_fail'] = (!empty($inputs['dynamic_fail'])) ? sanitize_text_field($inputs['dynamic_fail']) : '';
        
//        sanitize show_status
        if ( !empty($inputs['show_status']) ) {
            $inputs['show_status'] = strtolower($inputs['show_status']);
            if ( ($inputs['show_status'] == 'static_show') || ($inputs['show_status'] == 'dynamic_show') ) {
                $inputs['show_status'] = sanitize_text_field($inputs['show_status']);
            }else{
                $inputs['show_status'] ='dynamic_show';
            }
        }else{
            $inputs['show_status'] ='dynamic_show';
        }
        
//        sanitize city
        if ( $inputs['show_status'] == 'static_show' || $inputs['dynamic_fail'] == 'on') {
            if ( empty($inputs['city']) ) {
                wp_die( 'You have to choose a city for static show forecast' );
            }
            $inputs['city'] = sanitize_text_field(strtolower($inputs['city']));
        }else{
            $inputs['city'] = (!empty($inputs['city'])) ? sanitize_text_field(strtolower($inputs['city'])) : '';
        }
        
//        sanitize temp_metric
        if ( !empty($inputs['temp_metric']) ) {
            $inputs['temp_metric'] = strtolower($inputs['temp_metric']);
            if ( ($inputs['temp_metric'] == 'c') || $inputs['temp_metric'] == 'f'){
                $inputs['temp_metric'] = sanitize_text_field($inputs['temp_metric']);
            }else{
                $inputs['temp_metric'] = 'c';
            }
        }else{
            $inputs['temp_metric'] = 'c';
        }
        
//        reset cached data if changed
        $this->resetCachIfChanged($inputs);
        return $inputs;
    }

    private function resetCachIfChanged( $inputs ) {
        $dvw_options = get_option('dvw_options');
        
        $change = (($dvw_options['api_key'] != $inputs['api_key']) || ($dvw_options['show_status'] != $inputs['show_status']) || ($dvw_options['city'] != $inputs['city']) || ($dvw_options['temp_metric'] != $inputs['temp_metric']));
        
        if ( $change ) {
            delete_transient('dvw_static');
            delete_transient('dvw_dynamic');
        }
    }

}

add_action('init', 'start_options_page');
function start_options_page(  ) {
    $options_page = new OptionsPage();
}
