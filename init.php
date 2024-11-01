<?php
/**
 * Plugin Name: SW Plus
 * Plugin URI: https://www.shalomworld.org
 * description: This plugin provides Media Galleries from the Shalom World TV
 * Version: 2.1
 * Author: Shalomworld
 * Author URI: https://www.shalomworld.org
 * License: GPL2
 */

/**
 * Created by Vinoth (Webandcrafts).
 * Date: 10/17/2018
 * Time: 3:03 PM
 */
add_action('plugins_loaded', ['SWTV_Mediagallery', 'init']);

class SWTV_Mediagallery {

    protected $pluginPath;
    protected $pluginUrl;
    /**
     * Holds the values to be used in the fields callbacks
     */
    public $APPID;
    public static function init() {
        $class = __CLASS__;
        new $class;
    }

    public function __construct() {

        load_plugin_textdomain( 'mediagallery', false, dirname( plugin_basename( __FILE__ )) . '/lang');
        
        $this->APPID = get_option('sw_mg_app_id');
        /// Hook into the admin menu
        add_action( 'admin_menu', [$this, 'manage_admin_menus'] );

        // Hook for registering the Script to the footer
        add_action( 'wp_footer', [$this, 'custom_footer_script'], 100);
    }

    /**
     * Add SW Plus Menu to the Admin Dashboard
     */
    public function manage_admin_menus() {
        add_menu_page('SW Plus', 'SW Plus','manage_options', 'sw_plus', [$this , 'media_gallery_render'], 'dashicons-megaphone', 60);
    }

    /**
     * Rendering the Form for setting the Appi Id for the SW Plus
     */
    public function media_gallery_render() { ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="POST">
                <input type="hidden" name="updated" value="true" />
                <?php wp_nonce_field( 'awesome_update', 'awesome_form' ); ?>
                <input type="hidden" name="action" value="add_mediagallery_key">
                <div id="universal-message-container">
                    <h2>App ID</h2>
                    <div class="options">
                        <p>
                            <label>Enter your App Id here</label>
                            <br />
                            <?php
                                $appid = get_option('sw_mg_app_id');
                                if( isset($_POST['appid']) )
                                    $appid = sanitize_text_field( $_POST['appid'] );
                            ?>
                            <input type="text" name="appid" value="<?php echo $appid; ?>" />
                        </p>
                        <?php
                        if( $_POST['updated'] === 'true' ){
                            $this->handle_form();
                        }
                        else{
                            if( !empty(get_option('sw_mg_app_id')) )
                                $this->check_media_gallery_form( get_option('sw_mg_app_id') );
                        }
                        ?>
                    </div>
                    <?php
                    wp_nonce_field( 'mediagallery', 'security' );
                    submit_button();
                    ?>
            </form>
            <style>
                .media-gallery-instruction ul{
                    padding-left: 15px;
                }
                .media-gallery-instruction li{
                    list-style: disc;
                }
            </style>
            <div class="media-gallery-instruction">
                <h2>Instruction:</h2>
                <p><strong>Where to find APP ID? </strong></p>
                <ul>
                    <li>Go to <a href="https://shalomworld.org/swplus/dashboard">https://www.shalomworld.org/swplus/dashboard</a></li>
                    <li>Sign In to your account</li>
                    <li>Your App ID will be displayed right below the title as in the following screenshot.</li>
                    <p><a href="<?php echo plugins_url( 'images/instruction1.png', __FILE__ ); ?>" target="_blank"><?php echo '<img src="' . plugins_url( 'images/instruction1.png', __FILE__ ) . '" style="max-width: 400px " > '; ?></a></p>
                </ul>
            </div>
        </div>
         <?php
    }

    /**
     * Handling the Form submission
     */
    public function handle_form() {
        if( ! isset( $_POST['awesome_form'] ) || ! wp_verify_nonce( $_POST['awesome_form'], 'awesome_update' ) )
        {
            ?>
            <div class="error">
                <p>Sorry, your nonce was not correct. Please try again.</p>
            </div>
            <?php
            exit;
        } else {
            // Handle our form data
            $app_id = sanitize_text_field( $_POST['appid'] );

            $url = 'https://www.shalomworld.org/wp-json/v4/auth?appid='.$app_id.'&domain_name='.$_SERVER['HTTP_HOST'];
            $response = wp_remote_get($url);
            $rescode = wp_remote_retrieve_response_code( $response );

            $error = 0;
            $error_msg = "";
            if($rescode==200)  //catch if curl error exists and show it
            {
                $resbody = wp_remote_retrieve_body( $response );
                $response = json_decode(json_decode($resbody, true), true);

                if( $response['status'] != "success" )
                {
                    $error = 1;
                    $error_msg = $response['error'];
                }
            }
            else
            {
                $error = 1;
            }

            update_option( 'sw_mg_app_id', $app_id );

            if( $error == 0 ):
                echo '<div style="color: orange;"><p>Your SW Plus ID has been saved successfully! </p></div>';
            else:
                echo '<div style="color: red;"><p>'.$error_msg.' </p></div>';
            endif;

        }
    }

    /**
     * Handling the Form submission
     */
    public function check_media_gallery_form($app_id) {
        $url = 'https://www.shalomworld.org/wp-json/v4/auth?appid='.$app_id.'&domain_name='.$_SERVER['HTTP_HOST'];
        $response = wp_remote_get($url);
        $rescode = wp_remote_retrieve_response_code( $response );

        $error = 0;
        $error_msg = "";
        if($rescode==200)  //catch if curl error exists and show it
        {
            $resbody = wp_remote_retrieve_body( $response );
            $response = json_decode(json_decode($resbody, true), true);

            if( $response['status'] != "success" )
            {
                $error = 1;
                $error_msg = $response['error'];
            }
        }
        else
        {
            $error = 1;
        }

        update_option( 'sw_mg_app_id', $app_id );

        if( $error == 0 ):
            echo '<div style="color: orange;"><p>Your SW Plus ID is Valid! </p></div>';
        else:
            echo '<div style="color: red;"><p>'.$error_msg.' </p></div>';
        endif;

    }

    /**
     * Registering the script to the footer after the app id is given
     */
    function custom_footer_script(){
        if( trim( $this->APPID ) !=  "" ) {
            ?>
            <script>
                (function (d, s, id) {
                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id)) return;
                    js = d.createElement(s);
                    js.id = id;
                    js.src = "//www.shalomworld.org/media-gallery/parish-mission.min.js#appID=<?php echo $this->APPID; ?>&elementID=pm_mg-trigger";
                    fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'pmission-media_gallery'));
            </script>
            <?php
        }
    }

}
