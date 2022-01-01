<?php
/**
 * Plugin Name:       Opensea Custom API
 * Plugin URI:        http://themuzammil.com/
 * Description:       Connects with the Opensea API
 * Version:           1.0.0s
 * Requires at least: 5.5
 * Requires PHP:      7.2
 * Author:            Muzammil 
 * Author URI:        http://themuzammil.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Direct access protection
 */
defined('ABSPATH') or die('This path is not accessable');

/**
 * enque client assets 
 */

function osea_include_client_assets(){

    //styles
    wp_enqueue_style('osea-styles',plugins_url('assets/css/styles.css',__FILE__));
    // splide styles
    wp_enqueue_style('splide-styles-core',plugins_url('assets/css/splide-core.min.css',__FILE__));
    wp_enqueue_style('splide-styles',plugins_url('assets/css/splide.min.css',__FILE__));
    wp_enqueue_style('splide-styles-default-theme',plugins_url('assets/css/splide-default.min.css',__FILE__));
    

    //scripts
    wp_enqueue_script('osea-script', plugins_url('assets/js/script.js', __FILE__), array('jquery'),'1.0.0',true);
    // splide script
    wp_enqueue_script('splide-script', plugins_url('assets/js/splide.min.js', __FILE__), array('jquery','osea-script'),'1.0.1',true);


}
add_action( 'wp_enqueue_scripts', 'osea_include_client_assets' );

/**
 * enque admin assets 
 */

 function osea_include_admin_assets(){
    osea_include_client_assets();
}
 add_action( 'admin_enqueue_scripts', 'osea_include_admin_assets' );

 
 
 function osea_get_assets(){

        // $api_key = "xxx-xxx-xxx";
        $curl_error = "";
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.opensea.io/api/v1/assets?order_direction=desc&offset=0&limit=20",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
          ]);

        $response = curl_exec($curl);
        if(curl_errno($curl)){
            $curl_error =  curl_error($curl);
        }
        curl_close($curl);
        $data = json_decode($response);
        
        $images = array();
        $counter = 0;
        foreach($data->assets as $key=>$asset){
            $image = $asset->image_url;
            if($image != NULL){
                $images[] = $image;
                $counter++;
            }            
            if($counter == 10){
                break;
            }
        }

        $json_data = json_encode($images);
        if(file_put_contents(plugin_dir_path(__FILE__)."/data/api-images.json",$json_data)){
            return true;
        }else{
            return false;
        }

    }//function end


function osea_slider($atts = ''){
    $file = plugin_dir_path(__FILE__)."/data/api-images.json"; 
    $data = file_get_contents($file); 
    $images = json_decode($data);     
    $template = "";
    ob_start();
    ?>
    <!-- HTML -->
    <div class="splide" id = "os-splide-slider">
        <div class="splide__track">
                <ul class="splide__list">
                    
                    <?php foreach ($images as $key => $image) { ?>
                        <li class="splide__slide">
                                <img src="<?php echo $image;?>"  class = "osea_carousel_image" alt="">
                        </li>
                    <?php } ?>
                
                </ul>
        </div>
    </div>

<?php
 $template = ob_get_clean();
 return $template;
}

add_shortcode('osea_slider','osea_slider');


/**
 * plugin options page
 */

// create custom plugin settings menu
add_action('admin_menu', 'osea_plugin_options');

function osea_plugin_options() {

	//create new top-level menu
	add_menu_page('Opensea plugin Sync', 'Opensea Sync', 'administrator', __FILE__, 'osea_plugin_template' , 'dashicons-image-rotate' );

}



function osea_plugin_template(){

    if(isset($_POST['sync'])){
        if(osea_get_assets()){
            echo "New images has been fetched";
        }else{
            echo "Something went wrong, please try again";
        }
    }

    $template = '<br>';
    $template.= do_shortcode('[osea_slider]');
    $template.= '<br>';
    $template.='<form method = "POST">
                    <input class ="button action"  type = "submit" name = "sync" value = "Sync Images"/>
                </form>';

    echo $template;
}

/**
 * daily cron
 */

//add method to register event to WordPress init 
add_action( 'init', 'register_daily_sync');
 
/**
 * this method will register the cron event
 */
function register_daily_sync() {
    // make sure this event is not scheduled
    if( !wp_next_scheduled( 'osea_daily_sync' ) ) {
        // schedule an event
        wp_schedule_event( time(), 'daily', 'osea_daily_sync' );
    }
}
 
/**
 * osea_daily_sync method will be call when the cron is executed
 */
add_action( 'osea_daily_sync', 'osea_get_assets' );
 