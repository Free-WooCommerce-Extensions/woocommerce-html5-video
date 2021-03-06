<?php
/**
 * Plugin Name: WooCommerce HTML5 Video
 * Plugin URI: http://www.webilop.com/products/wp-plugins/woocommerce-html5-video/
 * Description: Add videos to your products. This WooCommerce extension uses HTML5 to render videos. It supports embedded videos and MP4 and Ogg formats.
 * Author: Webilop
 * Author URI: http://www.webilop.com
 * Version: 1.2
 * License: GPLv2 or later
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
  exit;

//Checks if the WooCommerce plugins is installed and active.
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
  if (!class_exists('WooCommerce_HTML5_Video')) {

    class WooCommerce_HTML5_Video {

      private $codigo_video = ''; //Variable to save the video code.
      private $video_type = '';
      private $mensaje = ''; //informational message to the user when viewing the video.
      static private $width_video = '400';
      static private $height_video = '400';

      /**
       * Gets things started by adding an action to initialize this plugin once
       * WooCommerce is known to be active and initialized
       */
      public function __construct() {
        add_action('woocommerce_init', array(&$this, 'init'));
      }

      /**
       * to add the necessary actions for the plugin
       */
      public function init() {
        // backend stuff
        add_action('woocommerce_product_write_panel_tabs', array($this, 'product_write_panel_tab'));
        add_action('woocommerce_product_write_panels', array($this, 'product_write_panel'));
        add_action('woocommerce_process_product_meta', array($this, 'product_save_data'), 10, 2);
        // frontend stuff
        add_filter('woocommerce_product_tabs', array($this,'video_product_tabs'),25);
        add_action('woocommerce_product_tab_panels', array($this, 'video_product_tabs_panel'), 25);
      }
      /**
       * creates the tab if the product has an associated video.          
       */
       /** Add extra tabs to front end product page **/
        function video_product_tabs( $tabs ) {
                global $post, $product;

                $custom_tab_options = array(
                        'enabled' => get_post_meta($post->ID, 'custom_tab_enabled', true),
                        'title' => get_post_meta($post->ID, 'custom_tab_title', true),
                        'content' => get_post_meta($post->ID, 'custom_tab_content', true),
                );

                if ( $custom_tab_options['enabled'] != 'no' ){
                        $tabs['html5_video'] = array(
                           'title'    => __('Video','html5_video'),
                           'priority' => 25,
                           'callback' => 'htmlvideotabcontent',
                           'content'  => $custom_tab_options['content']
                         );
                 }

                return $tabs;
        }

      /**
       * make the div which will show the video in html5 or embedded code.
       */
      public function video_product_tabs_panel() {
        global $product;

        if ($this->product_has_video_tabs($product)) {
          echo '<h2>' . __("Video","html5_video") . '</h2>';
          echo '<p>' . $this->mensaje . '</p>';
          //aqui se podria hacer trato especial a un codigo embebido o html5
          echo $this->codigo_video;
          echo '<p style="font-size:10px;color:#999;">'.__("Video embedding powered by","html5_video").' <a target="_blank" title="Web + mobile development" href="http://www.webilop.com">Webilop</a></p>';
        }
      }

      /**
       * check if a product has an associated video. and save the video in the global variable codigo_video,
       * also saves a message for use in method video_product_tabs_panel.
       */
      private function product_has_video_tabs($product) {
        $this->video_type = get_post_meta($product->id, 'wo_di_video_type', true);
        if ($this->video_type == 'embebido') {
          for ($i=0; $i<=2; $i++){
            $this->codigo_video .= get_post_meta($product->id, 'wo_di_video_product'.$i, true);
          }
          // tab must at least have a title to exist
          return !empty($this->codigo_video);
        } else {//servidor
          //$this->mensaje='El video es html5, verifique que su navegador lo puede ejecutar. formatos posibles .......';
          $this->mensaje = $this->getMensajeVideoSupport($product->id);
          $this->codigo_video = get_post_meta($product->id, 'wo_di_video_product_html5', true);
          return !empty($this->codigo_video);
        }
      }

      /**
       * creates the tab for the administrator, where administered product videos.
       */
      public function product_write_panel_tab() {
        echo "<li><a class='html5_video' href=\"#video_tab\">" . __('Video','html5_video') . "</a></li>";
      }

      /**
       * build the panel for the administrator.
       */
      public function product_write_panel() {
        global $post;

        // Pull the video tab data out of the database
           if (empty($tab_data)) {
             $tab_data = '';
           }
           echo '<div id="video_tab" class="panel woocommerce_options_panel">';
           $this->wo_di_form_admin_video(array('id' => '_tab_video', 'label' => __('Embed Code','html5_video'), 'placeholder' => __('Place your embedded video code here.','html5_video'), 'style' => 'width:70%;height:21.5em;'));
           echo '</div>';
      }

      /*
       * build form to the administrator.
       */

      private function wo_di_form_admin_video($field) {
        global $thepostid, $post;
        //wo_di_video_product Embed Code
        //wo_di_video_product_html5 html5 code
        //wo_di_video_type if using embedded or html5 code
        if (!$thepostid)
          $thepostid = $post->ID;
        if (!isset($field['placeholder'])) {
          $field['placeholder'] = '';
        }
        if (!isset($field['class'])) {
          $field['class'] = 'html5_video';
        }
        if (!isset($field['value'])) {
          $field['value'] = get_post_meta($thepostid, 'wo_di_video_product', true);
        }
        $codigo_html = get_post_meta($thepostid, 'wo_di_video_product_html5', true);

        $type_video = get_post_meta($thepostid, 'wo_di_video_type', true);
        $radio_embebido = '';
        $radio_servidor = '';
        if ($type_video == 'embebido') {
          $radio_embebido = 'checked="checked"';
        } else {
          if ($type_video = 'servidor') {
            $radio_servidor = 'checked="checked"';
          }
        }

        //creating inputs for the videos urls
        $url_video_flv = get_post_meta($thepostid, 'wo_di_video_url_flv', true);
        $url_video_mp4 = get_post_meta($thepostid, 'wo_di_video_url_mp4', true);
        $url_video_ogg = get_post_meta($thepostid, 'wo_di_video_url_ogg', true);

        $input_video_flv = '<input type="text" name="wo_di_video_url_flv"';
        if (!empty($url_video_flv)) {
          $input_video_flv.='value="' . $url_video_flv . '"';
        }
        $input_video_flv.=">";
        $input_video_mp4 = '<input type="text" id="wo_di_video_url_mp4" name="wo_di_video_url_mp4"';
        if (!empty($url_video_mp4)) {
          $input_video_mp4.='value="' . $url_video_mp4 . '"';
        }
        $input_video_mp4.=">";
        $input_video_ogg = '<input type="text" id="wo_di_video_url_ogg" name="wo_di_video_url_ogg"';
        if (!empty($url_video_ogg)) {
          $input_video_ogg.='value="' . $url_video_ogg . '"';
        }
        $input_video_ogg.=">";

        //creating checkboxes for the videos urls
        $checked_flv = get_post_meta($thepostid, 'wo_di_video_check_flv', true);
        ;
        $checked_mp4 = get_post_meta($thepostid, 'wo_di_video_check_mp4', true);
        ;
        $checked_ogg = get_post_meta($thepostid, 'wo_di_video_check_ogg', true);
        if ($checked_flv == 't') {
          $checked_flv = 'checked="checked"';
        }
        if ($checked_mp4 == 't') {
          $checked_mp4 = 'checked="checked"';
        }
        if ($checked_ogg == 't') {
          $checked_ogg = 'checked="checked"';
        }

        //video dimensions
        $height_video = get_post_meta($thepostid, 'height_video_woocommerce', true);
        $width_video = get_post_meta($thepostid, 'width_video_woocommerce', true);
        if (empty($height_video)) {
          $height_video = self::$height_video;
        }
        if (empty($width_video)) {
          $width_video = self::$width_video;
        }
        //html code
        $print = '<legend>'.__("Select video source:","html5_video").'</legend>
                        <div class="options_group">
                            <input class="radio" id="video_embebido" type="radio"  value="embebido" name="wo_di_tipo_video" ' . $radio_embebido . '>
                            <label class="radio" for="video_embebido">'.__("Embedded code","html5_video").'</label>
                            <p><textarea class="' . $field['class'] . '" name="' . $field['id'] .'[]" id="' . $field['id'] . '0" placeholder="' . $field['placeholder'] . '" rows="2" cols="20">' . esc_textarea(get_post_meta($post->ID, 'wo_di_video_product0', true)) . '</textarea><a class="remove_video" href="#" onclick="remove_video(0);return false;" title="'.__("Clear", "html5_video").'">'.__("Delete", "html5_video").'</a></p>';
         $show_link = true;
         for ($i=1; $i<=2; $i++){
         $tab_data = get_post_meta($post->ID, 'wo_di_video_product'.$i, true);
            if( !empty($tab_data) ){
                  $print .= '<p><textarea class="' . $field['class'] . '" name="' . $field['id'] .'[]" id="' . $field['id'] . $i. '" placeholder="' . $field['placeholder'] . '" rows="2" cols="20">' . esc_textarea($tab_data) . '</textarea><a class="remove_video" href="#" onclick="remove_video('.$i.');return false;" title="'.__("Clear", "html5_video").'">'.__("Delete", "html5_video").'</a></p>';
               if($i ==2) $show_link = false;
            }
         }
         if($show_link) $print .= '<a id="clone_video" href="#" onclick="clone_embedded();return false;">'.__("Add another video", "html5_video").'</a>';
        $print .= '<p>'.__('The embedded code should be taken from a video page like Youtube', 'html5_video').'</p>
                            </div><div class="options_group">
                            <input class="radio" id="video_servidor" type="radio" value="servidor" name="wo_di_tipo_video" ' . $radio_servidor . '>
                            <label class="radio" for="video_servidor">'.__("Upload video","html5_video").'</label>
                            <legend>'.__("Supported video formats","html5_video").'</legend>
                           <dl>
                            <dt><input id="_checkbox_mp4" class="checkbox" type="checkbox" value="mp4" name="videos_soportados[]" ' . $checked_mp4 . '>
                            <label class="check" for="_checkbox_mp4"> Mp4 </label></dt>
                            <dd>' . $input_video_mp4 . '<img src="'.WP_PLUGIN_URL.'/woocommerce-html5-video/images/info.png" title="'.__("Supported by", "html5_video").' IE 9+, Chrome 6+, Safari 5+" alt="info" /></dd>
                            <dt><input id="_checkbox_OGG" class="checkbox" type="checkbox" value="ogg" name="videos_soportados[]" ' . $checked_ogg . '>
                            <label class="check" for="_checkbox_OGG"> Ogg </label></dt>
                            <dd>' . $input_video_ogg . '<img src="'.WP_PLUGIN_URL.'/woocommerce-html5-video/images/info.png" title="'.__("Supported by", "html5_video").' Chrome 6+, Firefox 3.6+, Opera 10.6+" alt="info" /></dd>
                            </dl>
                            <input id="wo_di_upload_video" type="button" value="'.__("Upload video","html5_video").'" class="button tagadd">
                            <input id="wo_di_select_video" type="button" value="'.__("Select video","html5_video").'" class="button tagadd">
                            <legend> '.__("Video dimensions","html5_video").' </legend>
                            <dl>
                            <dt><label for="width_video_woocommerce"> '.__("Width","html5_video").': </label></dt> <dd><input type="text" id="width_video_woocommerce" name="width_video_woocommerce" value="' . $width_video . '"> </dd>
                            <dt><label for="height_video_woocommerce"> '.__("Height","html5_video").': </label></dt> <dd><input type="text" id="height_video_woocommerce" name="height_video_woocommerce" value="' . $height_video . '"> </dd>
                            </dl></div>
                            <div class="options_group">
                            <label for="_tab_video_html5"> '.__("Generated code","html5_video").' </label>
                            <textarea cols="20" rows="2"
                                placeholder="Generated Html5 Code" id="_tab_video_html5" name="_tab_video_html5" class="short">' . $codigo_html . '</textarea>
                      </div>';
         echo $print;

        //Product description, this is part of the woocommerce.
        if (isset($field['description']) && $field['description']) {
          echo '<span class="description">' . $field['description'] . '</span>';
        }

        //add the script and style for thickbox, I need to upload videos with javascritp.
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_register_script('my-upload', plugins_url('button_actions.js', __FILE__), array('jquery', 'media-upload', 'thickbox'));
        wp_enqueue_script('my-upload');
        wp_enqueue_style('thickbox');
      }

      /*
       * filter where the tabs of media-uploader are loaded,I remove what do not need depending on context.
       * thanks to context, I can delete everything without worrying that called another form in wp.     
       */

      function wo_di_image_tabs($_default_tabs) {
        //print_r($_default_tabs);
        //print_r($_GET);
        if (!empty($_GET['context'])) {
          unset($_default_tabs);
          $_default_tabs = array();
          if ($_GET['context'] == 'uploadVideo') {
            $_default_tabs['type'] = 'Upload Video';
          } else if ($_GET['context'] == 'selectVideo') {
            $_default_tabs['library'] = 'select video';
          }
          //print_r($_default_tabs);
          return($_default_tabs);
        } else {
          return($_default_tabs);
        }
      }

      /*
       * filter to replace the button 'insert into post' when you select a file
       *  in the library, and add the context to not lose.
       */

      function wo_di_action_button($form_fields, $post) {

        //form_fields arreglo con todas las propiedades de la imagen o archivo.        
        $send = "<input type='submit' class='button' name='send[$post->ID]' value='" . esc_attr__('Select URL') . "' />";
        //print_r($_GET['context']);
        $form_fields['buttons'] = array('tr' => "\t\t<tr class='submit'><td></td><td class='savesend'>$send</td></tr>\n");
        $form_fields['context'] = array('input' => 'hidden', 'value' => $_GET['context']);
        return $form_fields;
      }

      /*
       * filter is executed when you click the submit button on the form from 
       * the library, default button is 'insert into post'
       */

      function wo_di_image_selected($html, $send_id, $attachment) {
        //wonder if it is a valid format.          
        $url = $attachment['url'];
        $extension = $this->getExtensionOfVideo($url);
        $validate_extension = true;
        $name_input = '';
        $name_checkbox = '';
        //print "url: $url extension: $extension";
        switch ($extension) {
          case 'mp4':
            $name_input = 'wo_di_video_url_mp4';
            $name_checkbox = '_checkbox_mp4';
            break;

          case 'ogg':
            $name_input = 'wo_di_video_url_ogg';
            $name_checkbox = '_checkbox_OGG';
            break;

          default:
            $validate_extension = false;
            break;
        }
        if ($validate_extension) {
          ?>   
          <script type="text/javascript">
            /* <![CDATA[ */
            //alert('<?php echo $name_input; ?>');
            //alert('<?php echo $url; ?>');
            var win = window.dialogArguments || opener || parent || top;
                                                              
            win.jQuery( '#<?php echo $name_input; ?>' ).val('<?php echo $url; ?>');
            win.jQuery('#<?php echo $name_checkbox; ?>').attr('checked',true);
            win.tb_remove();
            /* ]]> */
          </script>
          <?php
        } else {
          ?>   
          <script type="text/javascript">
            /* <![CDATA[ */
            alert('You have selected a non-compatible video format, the supported extensions are: mp4 and ogg.');
            var win = window.dialogArguments || opener || parent || top;
            win.tb_remove();
            /* ]]> */
          </script>
          <?php
        }
        exit;
      }

      /**
       * updating the database post.
       */
      public function product_save_data($post_id, $post) {

        $tab_video = $_POST['_tab_video'];
        $radio_video_embebido = $_POST['wo_di_tipo_video'];
        //updte the video embedded
        //if it is empty the text area, and there is something in the database, I must delete it.
        if (empty($tab_video) && get_post_meta($post_id, 'wo_di_video_product', true)) {
          delete_post_meta($post_id, 'wo_di_video_product');
        }
        $videos = $_POST['_tab_video'];
        $length = count(videos);
        foreach($videos as $key=>$video){
          if(!empty($video)) update_post_meta($post_id, 'wo_di_video_product'.$key, stripslashes($video));
          else delete_post_meta($post_id, 'wo_di_video_product'.$key);
        }

        //update the video html5
        //save dimention of video
        $height_video = $_POST['height_video_woocommerce'];
        $width_video = $_POST['width_video_woocommerce'];
        if (empty($height_video)) {
          $height_video = self::$height_video;
        }
        if (empty($width_video)) {
          $width_video = self::$width_video;
        }
        update_post_meta($post_id, 'height_video_woocommerce', $height_video);
        update_post_meta($post_id, 'width_video_woocommerce', $width_video);

        //generate HTML5 code according to the available videos.
        $check_videos = $_POST['videos_soportados']; //array of check.
        $cadena_tag_video_html5 = '<video width="' . $width_video . '" height="' . $height_video . '" controls>';
        update_post_meta($post_id, 'wo_di_video_check_mp4', 'f');
        update_post_meta($post_id, 'wo_di_video_check_ogg', 'f');
        //update_post_meta($post_id, 'wo_di_video_check_flv', 'f');
        $checkbox_selected = false;
        foreach ($check_videos as $video) {
          $cadena_tag_video_html5.=$video;
          switch ($video) {

            case "mp4":
              $url = $_POST['wo_di_video_url_mp4'];
              update_post_meta($post_id, 'wo_di_video_url_mp4', $url);
              update_post_meta($post_id, 'wo_di_video_check_mp4', 't');
              $cadena_tag_video_html5.='<source src="' . $url . '" type="video/mp4" />';
              $checkbox_selected = true;
              break;

            case "ogg":
              $url = $_POST['wo_di_video_url_ogg'];
              update_post_meta($post_id, 'wo_di_video_url_ogg', $url);
              update_post_meta($post_id, 'wo_di_video_check_ogg', 't');
              $cadena_tag_video_html5.='<source src="' . $url . '" type="video/ogg" />';
              $checkbox_selected = true;
              break;

              break;
          }
        }
        if ($checkbox_selected) {
          $cadena_tag_video_html5.='<p>'.__("Your browser does not support HTML5","html5_video").'</p></video>';
          update_post_meta($post_id, 'wo_di_video_product_html5', $cadena_tag_video_html5);
        } else {//delete meta wo_di_video_product_html5
          update_post_meta($post_id, 'wo_di_video_product_html5', "");
        }
        //update the variable that tells me the type of video that will play
        $radio_video_embebido = $_POST['wo_di_tipo_video'];
        if ($radio_video_embebido == 'embebido') {
          update_post_meta($post_id, 'wo_di_video_type', 'embebido');
        } else {
          update_post_meta($post_id, 'wo_di_video_type', 'servidor');
        }
      }

      /*
       * gets the name of the video from the url
       */

      private function getNameOfVideo($url) {
        $name = substr($url, strripos($url, '/') + 1);
        return $name;
      }

      /*
       * gets the extension of the video from the url or string
       */

      private function getExtensionOfVideo($url) {
        $extension = substr($url, strripos($url, '.') + 1);
        return $extension;
      }

      /*
       * Gets the supported video message for browsers.
       */

      private function getMensajeVideoSupport($id_product) {
        $video_flv = get_post_meta($id_product, 'wo_di_video_check_flv', true);
        $video_mp4 = get_post_meta($id_product, 'wo_di_video_check_mp4', true);
        $video_ogg = get_post_meta($id_product, 'wo_di_video_check_ogg', true);
        $formatos = '';
        $bool_chrome = false;
        $bool_firefox = false;
        $bool_explorer = false;
        $bool_opera = false;
        $bool_safari = false;
        $bool_agregar_coma = false;
        if ($video_flv == 't') {
          $formatos.='flv';
          $bool_agregar_coma = true;
        }
        if ($video_mp4 == 't') {
          if ($bool_agregar_coma) {
            $formatos.=',mp4';
          } else {
            $formatos.='mp4';
          }
          $bool_explorer = true;
          $bool_chrome = true;
          $bool_safari = true;
          $bool_agregar_coma = true;
        }
        if ($video_ogg == 't') {
          if ($bool_agregar_coma) {
            $formatos.=',ogg';
          } else {
            $formatos.='ogg';
          }
          $bool_firefox = true;
          $bool_chrome = true;
          $bool_opera = true;
        }
        $navegadores = "";
        $bool_agregar_coma = false;
        if ($bool_chrome) {
          $bool_agregar_coma = true;
          $navegadores = 'Chrome';
        }
        if ($bool_firefox) {
          if ($bool_agregar_coma) {
            $navegadores.=',Firefox';
          } else {
            $navegadores.='Firefox';
          }
          $bool_agregar_coma = true;
        }
        if ($bool_explorer) {
          if ($bool_agregar_coma) {
            $navegadores.=',Explorer';
          } else {
            $navegadores.='Explorer';
          }
          $bool_agregar_coma = true;
        }
        if ($bool_opera) {
          if ($bool_agregar_coma) {
            $navegadores.=',Opera';
          } else {
            $navegadores.='Opera';
          }
          $bool_agregar_coma = true;
        }
        if ($bool_safari) {
          if ($bool_agregar_coma) {
            $navegadores.=',Safari';
          } else {
            $navegadores.='Safari';
          }
        }
        $mensaje = __('This product contains videos in the following formats: ', 'html5_video');
        $mensaje.=$formatos;
        //$mensaje.=__(', which can be seen in: ', 'html5_video') . $navegadores;
        return $mensaje;
      }

    }

    //end of the class  
  }//end of the if, if the class exists

  /*
   * Instantiate plugin class and add it to the set of globals.
   */
  $woocommerce_video_tab = new WooCommerce_HTML5_Video();

  //agrego el contexto a la url
  function add_my_context_to_url($url, $type) {
    if (isset($_REQUEST['context'])) {
      $url = add_query_arg('context', $_REQUEST['context'], $url);
    }
    return $url;
  }

  /*
   * asks if the context is the same
   */

  function check_upload_image_context($context) {
    if (isset($_REQUEST['context']) && $_REQUEST['context'] == $context) {
      add_filter('media_upload_form_url', 'add_my_context_to_url', 10, 2);
      return TRUE;
    }
    return false;
  }

  // asked by the context
  if (check_upload_image_context('uploadVideo') || check_upload_image_context('selectVideo')) {
    add_filter('media_upload_tabs', array($woocommerce_video_tab, 'wo_di_image_tabs'), 50, 1);
    add_filter('attachment_fields_to_edit', array($woocommerce_video_tab, 'wo_di_action_button'), 20, 2);
    add_filter('media_send_to_editor', array($woocommerce_video_tab, 'wo_di_image_selected'), 10, 3);
  }
} else {//end if,if installed woocommerce
  add_action('admin_notices', 'wc_video_tab_error_notice');

  function wc_video_tab_error_notice() {
    global $current_screen;
    if ($current_screen->parent_base == 'plugins') {
      echo '<div class="error"><p>' . __('WooCommerce HTML5 Video requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="' . admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce') . '" target="_blank">WooCommerce</a> first.','html5_video') . '</p></div>';
    }
  }
}

  /**
  * Enqueue plugin style-file
  */
  function add_my_scripts() {
    // Respects SSL, Style.css is relative to the current file
    wp_register_style( 'html-style', plugins_url('css/style.css', __FILE__) );
    wp_register_script( 'js-script', plugins_url('js/js-script.js', __FILE__), array('jquery') );
    wp_enqueue_style( 'html-style' );
    wp_enqueue_script( 'js-script' );
  }
  add_action( 'admin_enqueue_scripts', 'add_my_scripts' );

  /**
  * Set up localization
  */
  function html5_textdomain() {
   load_plugin_textdomain( 'html5_video', false, dirname( plugin_basename( __FILE__ ) ) );
  }
  add_action('plugins_loaded', 'html5_textdomain');

  /**
  * Render the custom product tab panel content for the callback 'custom_product_tabs_panel_content'
  */
  function htmlvideotabcontent() {
   $functions = new WooCommerce_HTML5_Video();
   $var = $functions->video_product_tabs_panel();
  }
?>
