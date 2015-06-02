<?php


/**
 * Class DvwCore responsible for the core functionality of the plugin like getting 
 * IP address, connecting to the API and getting the forecast.
 * @author Mohamed Atef
 */
class DvwCore {
    
    /**
     * Static option flag
     * @var boolean 
     */
    private $static;
    
    /**
     * Dynaic option flag
     * @var boolean
     */
    private $dynamic;
    
    /**
     * options of the plugin
     * @var array
     */
    private $dvw_options;
    
    /**
     * handle errors
     * @var object
     */
    private $dvw_error;
    
    /**
     *  final forecast array to show 
     * @var array
     */
    private $forecast;
    
    /**
     * flag of dynamic data
     * @var boolean
     */
    private $dynamic_fail = false;

    public function __construct( ) {
        $this->getAndCheckOptions();
        $this->checkState();
        $this->forecast = $this->getData();
    }
    
    private function getAndCheckOptions(  ) {
        $dvw_options = get_option('dvw_options');
        
//        check for missing parameters
        if(empty($dvw_options['api_key'])){
            $this->dvw_error = new WP_Error( 'dvw_error', __('API KEY does not exist', 'dvw-languages'));
            return;
        }elseif(empty($dvw_options['show_status'])){
            $this->dvw_error = new WP_Error( 'dvw_error', __('You have to select show status', 'dvw-languages'));
            return;
        }  elseif ( empty ( $dvw_options['temp_metric'])) {
            $this->dvw_error = new WP_Error('dvw_error', __('You have to select temperature metric', 'dvw-languages'));
            return;
        }elseif ( ($dvw_options['show_status'] == 'static_show' || $dvw_options['dynamic_fail'] == 'on') && (  empty ( $dvw_options['city']))) {
            $this->dvw_error = new WP_Error('dvw_error', __('You have to select city for rendering static forecast', 'dvw-languages'));
            return;
        }
        $this->dvw_options = $dvw_options;
    }
    
    private function checkState( ) {
        if ( is_wp_error( $this->dvw_error) ) {
            return;
        }
        if ( isset($this->dvw_options['show_status']) && ($this->dvw_options['show_status'] == 'static_show')) {
            $this->static = TRUE;
            $this->dynamic = FALSE;
        }else{
            $this->dynamic = TRUE;
            $this->static = FALSE;
        }
    }

    private function ipSafe() {
        $unsafe_ip = $_SERVER['REMOTE_ADDR'];
        $pattern = '/\b
        (?:(?:(?:25[0-4])|(?:2[0-4][0-9])|(?:1[0-9][0-9]))|(?:[1-9][0-9])|(?:[1-9]))\.  #match 254|249|199|90|1
        (?:(?:(?:25[0-4])|(?:2[0-4][0-9])|(?:1[0-9][0-9]))|(?:[1-9][0-9])|(?:[0-9]))\.  #match 254|249|199|90|0
        (?:(?:(?:25[0-4])|(?:2[0-4][0-9])|(?:1[0-9][0-9]))|(?:[1-9][0-9])|(?:[0-9]))\.  #match 254|249|199|90|0
        (?:(?:(?:25[0-4])|(?:2[0-4][0-9])|(?:1[0-9][0-9]))|(?:[1-9][0-9])|(?:[1-9]))  #match 254|249|199|90|1
        \b/x';

        if ( preg_match( $pattern, $unsafe_ip, $match) ) {
            return $match[0];
            
        }else{
            $this->dvw_error = new WP_Error('dvw_error', __('IP address is not valid', 'dvw-languages'));
            return false;
        }
    }
    
//    Build Url
    private function buildUrl( ) {
        $url = 'http://api.worldweatheronline.com/free/v2/weather.ashx?q=';
        
        if ( $this->static  ) {
            $url .= str_replace(' ', '%20', $this->dvw_options['city']);
        }else{
            $url .= $this->ipSafe();
        }
        
        $url .= '&format=json&num_of_days=3&show_comments=no&date=today&includelocation=yes&tp=6&key='.$this->dvw_options['api_key'];

        return $url;
    }

    //connect to API and return with associative array
    private function getResponseFromApi( ) {
        $url = $this->buildUrl();
        
        $response = wp_remote_get($url);
        
        $code = wp_remote_retrieve_response_code($response);
        
        if($code == 200){
            $decoded_json = json_decode( wp_remote_retrieve_body($response), TRUE );
            
            return $decoded_json;
        }else{
            $this->dvw_error = new WP_Error('dvw_error', __('There was a problem with the connection', 'dvw-languages'));  
            return;
        }
        
    }
    
    private function getResponse( ) {
        $result = $this->getResponseFromApi();
        $flag = ($result == false) || (!empty($result['data']['error']));
        if ( $flag ) {
            if ( !$this->dynamic_fail ) {
                return false;
            }
            $this->dvw_error = new WP_Error('dvw_error', __('Connection Error', 'dvw-languages'));
            return false;
        }
        
        $return_result = array();

//        city
        $return_result['city'] = $result['data']['nearest_area'][0]['areaName'][0]['value'];
        
//        temperature metric
        $return_result['temp_metric'] = $this->dvw_options['temp_metric'];
        

//      current condition array
        $return_result['current_condition'] = array();
        if ( $this->dvw_options['temp_metric'] == 'c') {
            $return_result['current_condition']['temp'] = $result['data']['current_condition'][0]['temp_C'];
        }else{
            $return_result['current_condition']['temp'] = $result['data']['current_condition'][0]['temp_F'];
        }
        $return_result['current_condition']['weather_desc'] = $result['data']['current_condition'][0]['weatherDesc'][0]['value'];
        $return_result['current_condition']['weather_icon_url'] = $result['data']['current_condition'][0]['weatherIconUrl'][0]['value'];
        
//        today condition array
        $return_result['today_condition'] = array();
        if ( $this->dvw_options['temp_metric'] == 'c') {
            $return_result['today_condition']['max_temp'] = $result['data']['weather'][0]['maxtempC'];
            $return_result['today_condition']['min_temp'] = $result['data']['weather'][0]['mintempC'];
        }else{
            $return_result['today_condition']['max_temp'] = $result['data']['weather'][0]['maxtempF'];
            $return_result['today_condition']['min_temp'] = $result['data']['weather'][0]['mintempF'];
        }
        $return_result['today_condition']['date'] = $result['data']['weather'][0]['date'];
        $return_result['today_condition']['weather_icon_url'] = $result['data']['weather'][0]['hourly'][0]['weatherIconUrl'][0]['value'];
        
//        todo tommorrow condition
        $return_result['tommorrow_condition'] = array();
        if ( $this->dvw_options['temp_metric'] == 'c') {
            $return_result['tommorrow_condition']['max_temp'] = $result['data']['weather'][1]['maxtempC'];
            $return_result['tommorrow_condition']['min_temp'] = $result['data']['weather'][1]['mintempC'];
        }else{
            $return_result['tommorrow_condition']['max_temp'] = $result['data']['weather'][1]['maxtempF'];
            $return_result['tommorrow_condition']['min_temp'] = $result['data']['weather'][1]['mintempF'];
        }
        $return_result['tommorrow_condition']['date'] = $result['data']['weather'][1]['date'];
        $return_result['tommorrow_condition']['weather_icon_url'] = $result['data']['weather'][1]['hourly'][0]['weatherIconUrl'][0]['value'];
        
//        todo after tommorrow condition
        $return_result['after_tommorrow_condition'] = array();
        if ( $this->dvw_options['temp_metric'] == 'c') {
            $return_result['after_tommorrow_condition']['max_temp'] = $result['data']['weather'][2]['maxtempC'];
            $return_result['after_tommorrow_condition']['min_temp'] = $result['data']['weather'][2]['mintempC'];
        }else{
            $return_result['after_tommorrow_condition']['max_temp'] = $result['data']['weather'][2]['maxtempF'];
            $return_result['after_tommorrow_condition']['min_temp'] = $result['data']['weather'][2]['mintempF'];
        }
        $return_result['after_tommorrow_condition']['date'] = $result['data']['weather'][2]['date'];
        $return_result['after_tommorrow_condition']['weather_icon_url'] = $result['data']['weather'][2]['hourly'][0]['weatherIconUrl'][0]['value'];
        
        return $return_result;
    }

    //Cache the data to save time and bandwidth
    private function cacheStaticData( ) {
        $cached_data = get_transient( 'dvw_static');
        if ($cached_data === false) {
            $data = $this->getResponse();
            if ( $data == false ) {
                $this->dvw_error = new WP_Error('dvw_error', __('Connection Error', 'dvw-languages'));
                return;
            }
            $expiration = 6 * HOUR_IN_SECONDS;

            set_transient('dvw_static', $data, $expiration);

            $cached_data = get_transient( 'dvw_static');
        }
            
        return $cached_data;
    }
    
    private function cacheDynamicData() {
        $cached_data = get_transient('dvw_dynamic');
        if ( $cached_data == false ) {
            $data = $this->getResponse();
            if ( $data == false ) {
//                check for static data instead dynamic
                $this->static = 'static_show';
                $this->dynamic_fail = TRUE;
                $static_data = $this->cacheStaticData();
                if ( $static_data == false ) {
                    $this->dvw_error = new WP_Error('dvw_error', __('Connection Error', 'dvw-languages'));
                    return FALSE;
                }
                $data = $static_data;
            }//f ( $data == false )
            
            $expiration = 6 * HOUR_IN_SECONDS;
            
//            name that will be a key in cashed data
            $city_name_for_array = strtolower($data['city']);
            
//            set cookie for city name for this visitor
            setcookie('dvw_city_name', $city_name_for_array, time()+(30*DAY_IN_SECONDS), '/', '', false, true);
            
//            ip that will be a key in cached data
            $ip_for_array = $this->ipSafe();
            
//            build dvw_dynamic cache
            $array_cached_data[$city_name_for_array] = $data;
            $array_cached_data[$ip_for_array] = $city_name_for_array;
            set_transient('dvw_dynamic', $array_cached_data, $expiration);

            $cached_data = get_transient( 'dvw_dynamic');
        }
        return $cached_data;
    }
    
    private function updateDynamicCache($safe_ip, $cached_data){
        $data = $this->getResponse();
        
        if ( $data == false ) {
//                check for static data instead dynamic
            $this->static = 'static_show';
            $this->dynamic_fail = TRUE;
            $static_data = $this->cacheStaticData();
            if ( $static_data == false ) {
                $this->dvw_error = new WP_Error('dvw_error', __('Connection Error', 'dvw-languages'));
            return FALSE;
            }
            $data = $static_data;
        }//f ( $data == false )
        
        $expiration = 6 * HOUR_IN_SECONDS;
            
//            name that will be a key in cashed data
        $city_name_for_array = strtolower($data['city']);

//            set cookie for city name for this visitor
        setcookie('dvw_city_name', $city_name_for_array, time()+(30*DAY_IN_SECONDS), '/', '', false, true);
        
//        append new records to existing cached data
        $cached_data[$city_name_for_array] = $data;
        $cached_data[$safe_ip] = $city_name_for_array;
        set_transient('dvw_dynamic', $cached_data, $expiration);
        
//        check cookie and ip again to return data
        $this->checkCookieIpCached();
    }

    private function checkCookieIpCached(  ) {
        $cached_data = $this->cacheDynamicData();
        if(empty($cached_data)){
            $this->dvw_error = new WP_Error('dvw_error', __('Critical error', 'dvw-languages'));
            return false;
        }
        //        Todo check if there is city name in cookies
        if(isset($_COOKIE['dvw_city_name']) && !empty($_COOKIE['dvw_city_name'])){
            $city_cookie = (string)$_COOKIE['dvw_city_name'];
            $city_cookie = strtolower($city_cookie);
            
//            Todo check if $city_cookie has cached data with its name
            if ( array_key_exists($city_cookie, $cached_data) && !empty($cached_data[$city_cookie]) ) {
                return $cached_data[$city_cookie];
            }
        }//if(isset($_COOKIE['dvw_city_name']) && !empty($_COOKIE['dvw_city_name']))
//         get ip and check if it's cached with a city name 
        $safe_ip = $this->ipSafe();
        if ( array_key_exists( $safe_ip, $cached_data)) {
            $key = $cached_data[$safe_ip];
            return $cached_data[$key];
        }
//        Todo get new data and assign it to appropriate variables
        $this->updateDynamicCache($safe_ip, $cached_data);
    }
    
    private function getData(  ) {
        if ( $this->static ) {
            $data = $this->cacheStaticData();
        }else{
            $data = $this->checkCookieIpCached();
        }
        return $data;
    }
    
    public function getForecast( ) {
        
        if ( is_wp_error( $this->dvw_error) ) {
            return $this->dvw_error;
        }
        return $this->forecast;
    }

}
