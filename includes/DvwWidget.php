<?php

require_once 'DvwCore.php';
/**
 * Class DvwWidget resposible for building and rendering the widget
 *
 * @author Mohamed Atef
 */
class DvwWidget extends WP_Widget{
    
    /**
     * instantiate core object
     * @var object
     */
    private $forecast;

    public function __construct( ) {
        $params = array(
            'classname' => 'dvw',
            'description' => 'Display Visitor\'s city weather dynamicly'
        );
        parent::__construct('dvw', 'Dynamic Visitor Weather', $params);
        
        if(  is_active_widget( false, false, 'dvw', true) !=false && !is_admin()){
            $this->forecast = new DvwCore();
            
//            load widget's style sheet
            wp_enqueue_style('dvw-fron-style', plugins_url('../css/dvw-front-style.css', __FILE__));
        }
    }
    
    public function form( $instance) {
        $instance = wp_parse_args((array)$instance, array('title'=>'', 'referrer'=>'no_referrer'));
        $title = $instance['title'];
        
        $referrer = $instance['referrer'];
        ?>
<div class="widefat">
    <label for="<?php echo $this->get_field_id( 'title' );?>">Title:</label>
    <input type="text" name="<?php echo $this->get_field_name( 'title');?>" class="widefat" id="<?php echo $this->get_field_id( 'title');?>" value="<?php echo esc_attr( $title);?>"/>
</div>
<hr />
<div class="widefat">
    <h4>Referrer:</h4>
    <p><a href="http://www.worldweatheronline.com/api/free-api-terms.aspx" target="_blank" title="World Weather API terms">World Weather</a> require a link back from that website and clearly mentioned link. </p>
    <p>
        <label for="dvt-text">Text link referrer: </label>
        <input type="radio" id="dvt-text" name="<?php echo $this->get_field_name( 'referrer')?>" value="text_referrer" <?php checked( $referrer, 'text_referrer', TRUE )?>/>
        <span style="font-size: 0.2em;">
            <strong>EX:</strong>
            Powered by <a href="http://www.worldweatheronline.com/" title="Free Weather API" target="_blank">World Weather Online</a>
        </span>
    </p>

    <p>
        <label for="dvl-logo">Logo link referrer: </label>
        <input type="radio" id="dvl-logo" name="<?php echo $this->get_field_name( 'referrer')?>" value="logo_referrer" <?php checked( $referrer, 'logo_referrer', TRUE )?>/>
        <span>
            <strong>EX:</strong>
            <a href="https://developer.worldweatheronline.com/" target="_blank"><img alt="Weather API" src="http://cdn.worldweatheronline.net/staticv3/images/logo_small.png" style="border-style:solid; border-width:0px; height:20px; width:62px" /></a>
        </span>
    </p>

    <p style="margin-top:25px;">
        <label for="dvn-none">Don't show referrer: </label>
        <input type="radio" id="dvn-none" name="<?php echo $this->get_field_name( 'referrer')?>" value="no_referrer" <?php checked( $referrer, 'no_referrer', TRUE )?>/>
    </p>
</div>
        <?php
    }

    public function update( $new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['referrer'] = sanitize_text_field($new_instance['referrer']);
        return $instance;
    }

    public function widget( $args, $instance) {
        extract($args);
        echo $before_widget;
        
        $title = (empty($instance['title'])) ? ' ' : apply_filters('widget_titles', $instance['title']);
        $referrer = (empty($instance['referrer'])) ? '' : $instance['referrer'];
        
        if(!empty($title)){
            echo $before_title . $title . $after_title;
        }
        
//        Require the core class to continue work
        $forecast = $this->forecast->getForecast();
        
        if ( is_wp_error( $forecast ) ) {
            if ( is_super_admin() ) {?>
<div class="dvw-error"><?php echo $forecast->get_error_message('dvw_error');?></div>
            <?php }
        }else{
            $tommorrow_date = date('l', strtotime($forecast['tommorrow_condition']['date']));
            $after_tommorrow_date = date('l', strtotime( $forecast['after_tommorrow_condition']['date'] ));
            ?>
<div id="dvw-forecast">
<div id="dvw-header">
    <h4><?php echo esc_html($forecast['city']);?></h4>
</div>
<div id="dvw-current">
    <p>
    <?php echo esc_html($forecast['current_condition']['temp']);?>
        <sup>&deg;<?php echo esc_html($forecast['temp_metric']);?></sup>
    <?php echo esc_html($forecast['current_condition']['weather_desc']);?>
    </p>
    <img src="<?php echo esc_attr($forecast['current_condition']['weather_icon_url']);?>" alt="Current weather icon" />
</div>
            <ul>
                <li>
                    <p>Today</p>
                    <img src="<?php echo esc_attr($forecast['today_condition']['weather_icon_url']);?>" />
                    <p>
                        <span>
                            <?php echo esc_html($forecast['today_condition']['max_temp']);?><sup>&deg;<?php echo esc_html($forecast['temp_metric']);?></sup>
                        </span>
                        <span>
                            <?php echo esc_html($forecast['today_condition']['min_temp']);?><sup>&deg;<?php echo esc_html($forecast['temp_metric']);?></sup>
                        </span>
                    </p>
                </li>
                <li>
                    <p><?php echo $tommorrow_date;?></p>
                    <img src="<?php echo esc_html($forecast['tommorrow_condition']['weather_icon_url']);?>" />
                    <p>
                        <span>
                            <?php echo esc_html($forecast['tommorrow_condition']['max_temp']);?><sup>&deg;<?php echo $forecast['temp_metric'];?></sup>
                        </span>
                        <span>
                            <?php echo esc_html($forecast['tommorrow_condition']['min_temp']);?><sup>&deg;<?php echo $forecast['temp_metric'];?></sup>
                        </span>
                    </p>
                </li>
                <li>
                    <p><?php echo $after_tommorrow_date;?></p>
                    <img src="<?php echo esc_html($forecast['after_tommorrow_condition']['weather_icon_url']);?>" />
                    <p class="maxmin">
                        <span>
                            <?php echo esc_html($forecast['after_tommorrow_condition']['max_temp']);?><sup>&deg;<?php echo esc_html($forecast['temp_metric']);?></sup>
                        </span>
                        <span>
                            <?php echo esc_html($forecast['after_tommorrow_condition']['min_temp']);?><sup>&deg;<?php echo esc_html($forecast['temp_metric']);?></sup>
                        </span>
                    </p>
                </li>
            </ul>
            <?php
            if ( $referrer == 'text_referrer' ) {?>
            <div id="dvw-footer">Powered by <a href="http://www.worldweatheronline.com/" title="Free Weather API" target="_blank">World Weather Online</a></div></div>
            <?php }elseif($referrer == 'logo_referrer'){?>
            <div id="dvw-footer"><a href="https://developer.worldweatheronline.com/" target="_blank"><img alt="Weather API" src="http://cdn.worldweatheronline.net/staticv3/images/logo_small.png" style="border-style:solid; border-width:0px; height:43px; width:100px" /></a></div></div>
            <?php }
        }
        
        echo $after_widget;
    }
}

function dvw_register_widget(  ) {
    register_widget('DvwWidget');   
}
add_action('widgets_init', 'dvw_register_widget');