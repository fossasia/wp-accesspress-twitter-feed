<?php

defined('ABSPATH') or die('No script kiddies please!');
/**
 * Plugin Name: AccessPress Twitter Feed
 * Plugin URI: https://accesspressthemes.com/wordpress-plugins/accesspress-twitter-feed/
 * Description: A plugin to show your twitter feed in your site with various configurable settings
 * Version: 1.0.0
 * Author: AccessPress Themes
 * Author URI: http://accesspressthemes.com
 * Text Domain: ap-twitter-feed
 * Domain Path: /languages/
 * License: GPL
 */
/**
 * Declartion of necessary constants for plugin
 * */
if (!defined('APTF_IMAGE_DIR')) {
    define('APTF_IMAGE_DIR', plugin_dir_url(__FILE__) . 'images');
}
if (!defined('APTF_JS_DIR')) {
    define('APTF_JS_DIR', plugin_dir_url(__FILE__) . 'js');
}
if (!defined('APTF_CSS_DIR')) {
    define('APTF_CSS_DIR', plugin_dir_url(__FILE__) . 'css');
}
if (!defined('APTF_VERSION')) {
    define('APTF_VERSION', '1.0.0');
}

if (!defined('APTF_TD')) {
    define('APTF_TD', 'ap-twitter-feed');
}
include_once('inc/backend/widget.php');
include_once('inc/backend/slider-widget.php');
if (!class_exists('APTF_Class')) {

    class APTF_Class {

        var $aptf_settings;

        /**
         * Initialization of plugin from constructor
         */
        function __construct() {
            $this->aptf_settings = get_option('aptf_settings');
            add_action('init', array($this, 'load_text_domain')); //loads plugin text domain for internationalization
            add_action('admin_init', array($this, 'session_init')); //starts session in admin section
            add_action('admin_menu', array($this, 'add_plugin_admin_menu')); //adds the menu in admin section
            add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts')); //registers scripts and css for admin section
            register_activation_hook(__FILE__, array($this, 'load_default_settings')); //loads default settings for the plugin while activating the plugin
            add_action('admin_post_aptf_form_action', array($this, 'aptf_form_action')); //action to save settings
            add_action('admin_post_aptf_restore_settings', array($this, 'aptf_restore_settings')); //action to restore default settings
            add_action('admin_post_aptf_delete_cache', array($this, 'aptf_delete_cache')); //action to delete cache
            add_shortcode('ap-twitter-feed', array($this, 'feed_shortcode')); //registers shortcode to display the feeds
            add_shortcode('ap-twitter-feed-slider', array($this, 'feed_slider_shortcode')); //registers shortcode to display the feeds as slider
            add_action('widgets_init', array($this, 'register_widget')); //registers the widget
            add_action('wp_enqueue_scripts',array($this,'register_front_assests'));//registers assets for the frontend
        }

        /**
         * Loads Plugin Text Domain
         * 
         */
        function load_text_domain() {
            load_plugin_textdomain(APTF_TD, false, basename(dirname(__FILE__)) . '/languages');
        }

        /**
         * Starts Session
         */
        function session_init() {
            if (!session_id()) {
                session_start();
            }
        }

        /**
         * Loads Default Settings
         */
        function load_default_settings() {
            $default_settings = $this->get_default_settings();
            if (!get_option('aptf_settings')) {
                update_option('aptf_settings', $default_settings);
            }
        }

        /**
         * Adds plugin's menu in the admin section
         */
        function add_plugin_admin_menu() {
            add_menu_page(__('AccessPress Twitter Feed', APTF_TD), __('AccessPress Twitter Feed', APTF_TD), 'manage_options', 'ap-twitter-feed', array($this, 'main_setting_page'), APTF_IMAGE_DIR . '/icon.png');
        }

        /**
         * Plugin's main setting page
         */
        function main_setting_page() {
            include('inc/backend/settings.php');
        }

        /**
         * Register all the scripts in admin section
         */
        function register_admin_scripts() {
            if (isset($_GET['page']) && $_GET['page'] == 'ap-twitter-feed') {
                wp_enqueue_script('aptf-admin-script', APTF_JS_DIR . '/backend.js', array('jquery'), APTF_VERSION);
                wp_enqueue_style('aptf-backend-css', APTF_CSS_DIR . '/backend.css', array(), APTF_VERSION);
            }
        }
        
        /**
         * Return default settings array
         * @return array
         */
        function get_default_settings() {
            $default_settings = array('consumer_key' => '',
                'consumer_secret' => '',
                'access_token' => '',
                'access_token_secret' => '',
                'twitter_username' => '',
                'twitter_account_name',
                'cache_period' => '',
                'total_feed' => '5',
                'feed_template' => 'template-1',
                'time_format' => 'elapsed_time',
                'display_username' => 1,
                'display_twitter_actions'=>1,
                'fallback_message'=>'',
                'display_follow_button'=>0
            );
            return $default_settings;
        }
        
        /**
         * 
         * @param string $username
         * @param int $count
         * @param array $options
         * @return array
         */
        function get_tweets($username = false, $count = 20, $options = false) {
            include_once('APTF.class.php');
            $aptf_settings = $this->aptf_settings;
            $config['key'] = $aptf_settings['consumer_key'];
            $config['secret'] = $aptf_settings['consumer_secret'];
            $config['token'] = $aptf_settings['access_token'];
            $config['token_secret'] = $aptf_settings['access_token_secret'];
            $config['screenname'] = $aptf_settings['twitter_username'];
            $config['directory'] = plugin_dir_path(__FILE__);
            $tweets = get_transient('aptf_tweets');
            if (false === $tweets) {
                $cache_period = intval($aptf_settings['cache_period']) * 60;
                $cache_period = ($cache_period < 1) ? 3600 : $cache_period;
                $obj = new APTF_Twitter_Class($config);
                $res = $obj->getTweets($username, $count, $options);
                if(!isset($res['error'])){
                    set_transient('aptf_tweets', $res, $cache_period);
                }
                
            } else {
                $res = $tweets;
            }


            return $res;
        }

        /**
         * Prints array in pre format
         */
        function print_array($array) {
            echo "<pre>";
            print_r($array);
            echo "</pre>";
        }

        /**
         * Saves settings in option table
         */
        function aptf_form_action() {
            if (!empty($_POST) && wp_verify_nonce($_POST['aptf_nonce_field'], 'aptf_action_nonce')) {
                include('inc/backend/save-settings.php');
            } else {
                die('No script kiddies please!');
            }
        }

        /**
         * Restores Default Settings
         */
        function aptf_restore_settings() {
            if (!empty($_GET) && wp_verify_nonce($_GET['_wpnonce'], 'aptf-restore-nonce')) {
                $aptf_settings = $this->get_default_settings();
                update_option('aptf_settings', $aptf_settings);
                $_SESSION['aptf_msg'] = __('Restored Default Settings Successfully.', APTF_TD);
                wp_redirect(admin_url() . 'admin.php?page=ap-twitter-feed');
            } else {
                die('No script kiddies please!');
            }
        }

        /**
         * Registers shortcode to display feed
         */
        function feed_shortcode($atts) {
            ob_start();
            include('inc/frontend/shortcode.php');
            $html = ob_get_contents();
            ob_get_clean();
            return $html;
        }
        
        /**
         * Register shortcode for feeds slider
         */
        function feed_slider_shortcode($atts){
            ob_start();
            include('inc/frontend/slider-shortcode.php');
            $html = ob_get_contents();
            ob_get_clean();
            return $html;
        }

        /**
         * Deletes Feeds from cache
         */
        function aptf_delete_cache() {
            delete_transient('aptf_tweets');
            $_SESSION['aptf_msg'] = __('Cache Deleted Successfully.', APTF_TD);
            wp_redirect(admin_url() . 'admin.php?page=ap-twitter-feed');
        }
        
        /**
         * 
         * @param varchar $date
         * @param string $format
         * @return type
         */
        function get_date_format($date, $format) {
            switch($format){
            case 'full_date':
                $date = strtotime($date);
                $date = date('F j, Y, g:i a',$date);
            break;
            case 'date_only':
                $date = strtotime($date);
                $date = date('F j, Y',$date);
            break;
            case 'elapsed_time':
            $current_date = strtotime(date('h:i A M d Y'));
            $tweet_date = strtotime($date);
            $total_seconds = $current_date - $tweet_date;
            $seconds = $total_seconds % 60;
            $total_minutes = $total_seconds / 60;
            ;
            $minutes = $total_minutes % 60;
            $total_hours = $total_minutes / 60;
            $hours = $total_hours % 24;
            $total_days = $total_hours / 24;
            $days = $total_days % 365;
            $years = $total_days / 365;

            if ($years > 1) {
                $date = $years . __(' year ago', APTF_TD);
            } elseif ($days > 1) {
                $date = $days . __(' days ago', APTF_TD);
            } elseif ($hours > 1) {
                $date = $hours . __(' hours ago', APTF_TD);
            } elseif ($minutes > 1) {
                $date = $minutes . __(' minutes ago', APTF_TD);
            } else {
                $date = __("1 minute ago", APTF_TD);
                }
                break;
                default:
                break;
            }
           return $date;
        }
        
        /**
         * Registers Widget
         */
        function register_widget() {
            register_widget('APTF_Widget');
            register_widget('APTF_Slider_Widget');
        }
        
        /**
         * Registers Assets for frontend
         */
        function register_front_assests(){
            wp_enqueue_script('aptf-bxslider',APTF_JS_DIR.'/jquery.bxslider.min.js',array('jquery'),APTF_VERSION);
            wp_enqueue_style('aptf-bxslider',APTF_CSS_DIR.'/jquery.bxslider.css',array(),APTF_VERSION);
            wp_enqueue_script('aptf-front-js',APTF_JS_DIR.'/frontend.js',array('jquery','aptf-bxslider'),APTF_VERSION);
            wp_enqueue_style('aptf-front-css',APTF_CSS_DIR.'/frontend.css',array(),APTF_VERSION);
            wp_enqueue_style('aptf-font-css',APTF_CSS_DIR.'/fonts.css',array(),APTF_VERSION);
        }
        
        
        

    }

    /**
     * Plugin Initialization
     */
    $aptf_obj = new APTF_Class();
}

