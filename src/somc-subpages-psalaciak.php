<?php
/*
Plugin Name: Somc Subpages Psalaciak
Plugin URI: https://github.com/psalaciak/somc-subpages-psalaciak
Description: Widget that lists subpages of the currently displayed page.
Version: 1.0
Author: Piotr Sałaciak
Author URI: http://www.salaciak.pl
License: none
*/

class SomcSubpagesPsalaciak extends WP_Widget {
    
    /**
     * the shortcode name and also widget base id.
     */
    const defaultShortcodeName = 'somc-subpages-psalaciak';
    
    /**
     * widget name - so You could distinguish from other somc-subpages plugins/widgets 
     */
    const widgetName = 'Somc Subpages Psalaciak';
    
    /**
     * shortened length of listed post title
     */
    const shortenPostTitleLength = 20;
    
    /**
     * thumbnail dimensions
     */
    const thumbnailWidth = 55;
    const thumbnailHeight = 35;
    
    /**
     * Sets up the widgets name
     */
    public function __construct() {
        parent::__construct(
            self::defaultShortcodeName,
            __(self::widgetName, 'text_domain'),
            array('description' => 
                __('Widget that lists subpages of a currently displayed page.', 'text_domain')
            )
        );
    }

    /**
     * Initializes the plugin. Registers shortcode and a widget
     * 
     * @return void
     */
    public static function init(){
        
        add_action('wp_enqueue_scripts', array(get_class(), 'registerScript'));
        add_shortcode(self::defaultShortcodeName, array(get_class(), 'shortcode'));
        add_action('widgets_init', array(get_class(), 'registerWidget'));
        
    }

    /**
     * Outputs the content of the widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance) {
        global $wpdb;
        
        $viewParams = array(
            'title' => $instance['title'],
            'isWidget' => true
        );
        
        echo self::generateView($viewParams);
    }

    /**
     * Ouputs the options form on admin
     *
     * @param array $instance The widget options
     */
    public function form($instance) {
        
        if (isset($instance['title'])) {
            $this->title = $instance['title'];
        } else {
            $this->title = __('Somc Subpages', 'text_domain');
        }
        
        include 'views/form.php';
    }

    /**
     * Processing widget options on save
     *
     * @param array $new_instance The new options
     * @param array $old_instance The previous options
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        // --- parameters sanitization ---
        $instance['title'] = 
                (!empty($new_instance['title'])) ? 
                strip_tags($new_instance['title']) :
                '';

        return $instance;
    }

    /**
     * Generates the view with the given parameters (actually one: title)
     * 
     * @global type $wpdb
     * @param array $view
     * @return string
     */
    private static function generateView($view){
        global $wpdb;
       
        $postId = get_the_ID();
        
        $view['items'] = self::getSubPages($postId);
        $view['post_id'] = $postId;

        // --- output beffering to echo it or return as string regarding calling context ---
        ob_start();
        
        include('views/widget.php');
        
        return ob_get_clean();
    }
    
    /**
     * Recursively fetches subpages to create a logic tree
     * 
     * @global type $wpdb
     * @param int $parentId Parent's post id
     * @return array
     */
    private static function getSubPages($parentId){
        
        global $wpdb;
        
        $items = $wpdb->get_results( $wpdb->prepare( 
            "
            SELECT 
                    p.id, 
                    p.post_date,
                    p.post_content,
                    p.post_title,
                    p.post_name,
                    p.post_modified,
                    p.post_parent,
                    p.guid,
                    t.meta_value AS thumbnail
            FROM $wpdb->posts AS p LEFT JOIN
                 $wpdb->postmeta AS m ON p.id = m.post_id AND m.meta_key = '_thumbnail_id' LEFT JOIN
                 $wpdb->postmeta AS t ON t.post_id = m.meta_value AND t.meta_key = '_wp_attached_file'
            WHERE p.post_parent = %d
                AND p.post_status = 'publish'
                AND p.post_type = 'page'
            ORDER BY p.post_title
            ", $parentId
        ));
        
        for ($i = 0; $i < count($items); $i++){
            
            $postShortTitle = strip_tags($items[$i]->post_title);
        
            if (strlen($postShortTitle) > self::shortenPostTitleLength){
                $dotPosition = strpos($postShortTitle, '.', self::shortenPostTitleLength);
                $spacePosition = strpos($postShortTitle, ' ', self::shortenPostTitleLength);

                if ($dotPosition === false && $spacePosition === false){
                    $items[$i]->post_short_title = substr($postShortTitle, 0, self::shortenPostTitleLength);
                } elseif ($dotPosition !== false && $spacePosition === false) {
                    $items[$i]->post_short_title = substr($postShortTitle, 0, $dotPosition) .'..';
                } elseif ($dotPosition === false && $spacePosition !== false) {
                    $items[$i]->post_short_title = substr($postShortTitle, 0, $spacePosition) .'...';
                } else {
                    if ($dotPosition < $spacePosition){
                        $items[$i]->post_short_title = substr($postShortTitle, 0, $dotPosition) .'..';
                    } else {
                        $items[$i]->post_short_title = substr($postShortTitle, 0, $spacePosition) .'...';
                    }
                }
            }
            
            $items[$i]->is_last = ($i == count($items)-1);
            
            // --- generate small thumbnail and write it on disk if not exists ---
            if ($items[$i]->thumbnail != ''){
                
                $upload_dir = wp_upload_dir(); 
                
                $dstImageFilename = plugin_dir_path( __FILE__ ) . 'thumbnails/'. $items[$i]->id .'.jpg';
                $srcImageFilename = $upload_dir['basedir'] .'/'. $items[$i]->thumbnail;

                // --- generate image if it's not already saved on disk
                // --- or regenerate it if the post has been modified since the image last generation
                if (file_exists($dstImageFilename) == false ||
                    new DateTime($items[$i]->post_modified) > new DateTime('@'. filemtime($dstImageFilename))){
                    
                    $image = wp_get_image_editor($srcImageFilename);

                    if (!is_wp_error($image)){
                        $image->resize(self::thumbnailWidth, self::thumbnailHeight, true);
                        $image->save($dstImageFilename);
                    }
                }
                
                $items[$i]->thumbnail_url = plugins_url('/thumbnails/'. $items[$i]->id .'.jpg', __FILE__ );
            }
            
            // --- generate permalink ---
            $items[$i]->permalink = get_permalink($items[$i]->id);
            
            // --- fetch subpages recursively ---
            $items[$i]->items = self::getSubPages($items[$i]->id);
           
            
        }
        
        return $items;
        
    }
    
    /**
     * View helper function, that includes views/subpages.php to list subpages recursively
     * @param int $postId required for sorting purposes
     * @param array $items
     */
    private static function viewListSubpages($postId, $items){
        $view = array(
            'post_id' => $postId,
            'items' => $items
        );
        
        include('views/subpages.php');
    }
    
    /**
     * Executes the shortcode.
     * 
     * @param array $atts eg. array('title' => 'Subpages')
     * @param string $content unused
     * @return string
     */
    public function shortcode($atts, $content = null ) {
        
        $viewParams = array(
            'title' => (!empty($atts['title']) ? $atts['title'] : '')
        );
        
        return self::generateView($viewParams);
    }
    
    /**
     * Registers JS scripts and CSS styles
     */
    public function registerScript(){
        wp_enqueue_script("jquery");
        
	wp_enqueue_script(
            'jquery-ui',
            plugins_url( '/jquery-ui-1.10.4.custom.min.js' , __FILE__ )
	);
        
	wp_enqueue_script(
            self::defaultShortcodeName,
            plugins_url( '/'. self::defaultShortcodeName .'.js' , __FILE__ )
	);
        
	wp_register_style( 
            self::defaultShortcodeName, 
            plugins_url( '/'. self::defaultShortcodeName .'.css' , __FILE__ ) 
	);
	
        wp_register_style( 
            self::defaultShortcodeName .'-font-awesome', 
            '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css'
	);
        
	wp_enqueue_style(
            self::defaultShortcodeName
	);
        
	wp_enqueue_style(
            self::defaultShortcodeName .'-font-awesome'
	);
    }
    
    /**
     * Registers the widget
     */
    public function registerWidget(){
        register_widget(get_class());
    }
    
}

// initalize the plugin
SomcSubpagesPsalaciak::init();