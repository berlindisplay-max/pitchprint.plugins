<?php
        /*
        * Plugin Name: PitchPrint
        * Plugin URI: http://www.pitchprint.com
        * Description: Plugin for integrating PitchPrint design app into WooCommerce
        * Author: PitchPrint
        * Version: 8.2.1
        * Author URI: http://www.pitchprint.com
		* Requires at least: 3.8
		* Tested up to: 4.4
		* 
		* @package PitchPrint
		* @category Core
		* @author PitchPrint
        */
		
	if (!session_id()) session_start();

	load_plugin_textdomain('PitchPrint', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	
	include('system/settings.php');

    define('SERVER_URLPATH', 'https://pitchprint.net');
    define('SERVER_RSCBASE', '//s3.amazonaws.com/pitchprint.rsc/');
	define('SERVER_RSCCDN', '//dta8vnpq1ae34.cloudfront.net/');

    define('PP_MIN_CDN_PATH', 'https://dta8vnpq1ae34.cloudfront.net/app/js/pprint.js');
    define('PP_CLASS_CDN_PATH', 'https://dta8vnpq1ae34.cloudfront.net/app/js/pp.client.js');
    define('PPA_WP_CDN_PATH', 'https://dta8vnpq1ae34.cloudfront.net/app/js/pp.wp.a.js');

	add_filter('woocommerce_add_cart_item_data', 'pp_add_cart_item_data', 10, 2);
	add_filter('woocommerce_add_order_item_meta', 'pp_add_order_item_meta', 10, 2);
	add_filter('woocommerce_get_cart_item_from_session', 'pp_get_cart_item_from_session', 10, 2);
	add_filter('woocommerce_get_item_data', 'pp_get_cart_mod', 10, 2);
	add_filter('woocommerce_cart_item_thumbnail', 'pp_cart_thumbnail', 10, 2);
	add_filter('woocommerce_before_my_account', 'pp_my_recent_order');
		
	
    add_action('woocommerce_after_cart', 'pp_get_cart_action');
    add_action('admin_menu', 'ppa_actions');
    add_action('wp_head', 'pp_header_files');
	add_action('admin_head', 'ppa_header_files');
	add_action('woocommerce_product_options_pricing', 'ppa_add_tab');
	add_action('woocommerce_process_product_meta', 'ppa_write_panel_save');
	add_action('woocommerce_before_add_to_cart_button', 'add_pp_edit_button');
	add_action('woocommerce_after_shop_loop_item', 'pp_remove_add_to_cart_buttons', 1);
  
    
//Client ------------------------------------------------------------------------------------------------

    function pp_header_files() {
		global $post, $product;
		$pp_set_option = get_post_meta($post->ID, '_w2p_set_option', true);
		if (!empty($pp_set_option)) {
			wp_enqueue_script('pitchprint_editor', PP_MIN_CDN_PATH);
			wp_enqueue_script('pitchprint_class', PP_CLASS_CDN_PATH);
		}
		if (defined('PITCH_JSCRIPT')) wc_enqueue_js(stripslashes(PITCH_JSCRIPT));
	}

	function pp_add_cart_item_data($cart_item_meta, $product_id) {
		if (isset($_SESSION['pp_projects'])) {
			if (isset($_SESSION['pp_projects'][$product_id])) {
				$cart_item_meta['_w2p_set_option'] = $_SESSION['pp_projects'][$product_id];
				unset($_SESSION['pp_projects'][$product_id]);
			}
		}
		return $cart_item_meta;
	}
	function pp_add_order_item_meta($item_id, $cart_item) {
		global $woocommerce;
		if (!empty($cart_item['_w2p_set_option'])) woocommerce_add_order_item_meta($item_id, '_w2p_set_option', $cart_item['_w2p_set_option']);
	}
	function pp_get_cart_item_from_session($cart_item, $values) {
		if (!empty($values['_w2p_set_option'])) $cart_item['_w2p_set_option'] = $values['_w2p_set_option'];
		return $cart_item;
	}
    function pp_cart_thumbnail($img, $val) {
        if (!empty($val['_w2p_set_option'])) {
            $itm = $val['_w2p_set_option'];
            $itm = json_decode(rawurldecode($itm), true);
            if ($itm['type'] == 'p') {
                $img = '<img style="width:90px" src="' . SERVER_RSCBASE . 'images/previews/' . $itm['projectId'] . '_1.jpg" >';
            } else {
				$img = '<img width="90" src="' . SERVER_RSCBASE . 'images/previews/' . $itm[2] . '_1.jpg" >';
			}
        }
        return $img;
    }

	function pp_my_recent_order() {
        global $post, $woocommerce;
        wp_enqueue_script('pitchprint_class', PP_CLASS_CDN_PATH);
        wp_enqueue_script('prettyPhoto', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto.min.js', array( 'jquery' ), $woocommerce->version, true );
        wp_enqueue_script('prettyPhoto-init', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto.init.min.js', array( 'jquery' ), $woocommerce->version, true );
        wp_enqueue_style('woocommerce_prettyPhoto_css', $woocommerce->plugin_url() . '/assets/css/prettyPhoto.css' );
        wp_enqueue_style('css_reset', 'http://yui.yahooapis.com/3.18.1/build/cssreset/cssreset-min.css' );
        
        echo '<div id="pp-mydesigns-div"></div>';
        wc_enqueue_js(" 
            PPCLIENT = PPCLIENT || {};
            
            PPCLIENT.vars = {
                client: 'wp',
                appApiUrl: '" . SERVER_URLPATH . "/api/front/',
                rscCdn: '" . SERVER_RSCCDN . "',
                rscBase: '" . SERVER_RSCBASE . "',
                pluginRoot: '" . plugins_url('/', __FILE__) . "',
                
                functions: {
                    
                },
                
                inline: '" . (defined('PITCH_INLINE') ? stripslashes(PITCH_INLINE) : '') . "',
                apiKey: '" . PITCH_APIKEY . "',
                langCode: '" . substr(get_bloginfo('language'), 0, 2) . "',
                userId: '" . (get_current_user_id() === 0 ? 'guest' : get_current_user_id()) . "'
            };
            
            jQuery(document).ready(function () { PPCLIENT.validate(PPCLIENT.fetchUserProjects); })
        ");
    }
    
    function pp_get_cart_action() {
        global $post, $woocommerce;
        wp_enqueue_script('pitchprint_class', PP_CLASS_CDN_PATH);
        
        wc_enqueue_js(" 
            PPCLIENT = PPCLIENT || {};
            
            PPCLIENT.vars = {
                client: 'wp',
                appUrl: '" . SERVER_URLPATH . "/api/front/',
                rscCdn: '" . SERVER_RSCCDN . "',
                rscBase: '" . SERVER_RSCBASE . "',
                pluginRoot: '" . plugins_url('/', __FILE__) . "',
                
                functions: {
                    
                },
                
                inline: '" . (defined('PITCH_INLINE') ? PITCH_INLINE : '') . "',
                apiKey: '" . PITCH_APIKEY . "',
                langCode: '" . substr(get_bloginfo('language'), 0, 2) . "',
                userId: '" . (get_current_user_id() === 0 ? 'guest' : get_current_user_id())  . "'
            };
            
            jQuery(document).ready(function () { PPCLIENT.validate(); PPCLIENT.setBtnPref(); })
        ");
    }
    
	function pp_get_cart_mod( $item_data, $cart_item ) {
		if (!empty($cart_item['_w2p_set_option'])) {
            $val = $cart_item['_w2p_set_option'];
            $itm = json_decode(rawurldecode($val), true);
			$item_data[] = array(
				'name'    => '<span id="pp-data-'.($itm['type']=='u'?'file_upload':'design').'">' . ($itm['type'] === 'u' ? __("File Upload", "PitchPrint") : __("Custom Design", "PitchPrint")) . '</span> &nbsp;&nbsp; <img style="width:14px; height:14px" src="'.plugins_url('/', __FILE__).'images/ok.png" border="0" >',
				'display' => ($itm['type']=='u' ? '' : '<a class="button pp-duplicate-design-btn" href="'.$cart_item['_w2p_set_option'].'" >' . __("Copy Design", "PitchPrint") . '</a>')
			);
        }
		return $item_data;
	}
	function pp_remove_add_to_cart_buttons() {
		//Function to hide add to cart button on the shop page...
		global $product;
		global $woocommerce;
		$pp_set_option = get_post_meta($product->id, '_w2p_set_option', true);
		if ($pp_set_option != '') {
			$handler = apply_filters('woocommerce_add_to_cart_handler', $product->product_type, $product);
			if ($handler != 'variable' && $handler != 'grouped' && $handler != 'external') {
				if ($product->is_purchasable()) {
					//wc_enqueue_js("change_addtocart_button('{$product->id}', '{$product->get_sku()}', '" . apply_filters( 'not_purchasable_url', get_permalink($product->id) ) . "', '" . apply_filters( 'not_purchasable_text', __( 'Read More', 'woocommerce' ) ) . "');");
				}
			}
		}
	}
	function add_pp_edit_button() {
		global $post;
		global $woocommerce;
		$pp_mode = 'new';
		$pp_set_option = get_post_meta( $post->ID, '_w2p_set_option', true );
        if (strpos($pp_set_option, ':') === false) $pp_set_option = $pp_set_option . ':0';
        $pp_set_option = explode(':', $pp_set_option);
        if (count($pp_set_option) === 2) $pp_set_option[2] = 0;
		$pp_project_id = '';
		$pp_uid = get_current_user_id() === 0 ? 'guest' : get_current_user_id();
		$pp_now_value = '';
		$pp_previews = '';
        $pp_upload_ready = false;
		
		if (!isset($_SESSION)) session_start();
		if (isset($_SESSION['pp_projects'])) {
			if (isset($_SESSION['pp_projects'][$post->ID])) {
				$pp_now_value = $_SESSION['pp_projects'][$post->ID];
				$opt_ = json_decode(rawurldecode($_SESSION['pp_projects'][$post->ID]), true);
                if ($opt_['type'] === 'u') {
                    $pp_upload_ready = true;
					$pp_mode = 'upload';
                } else if ($opt_['type'] === 'p') {
                    $pp_mode = 'edit';
                    $pp_project_id = $opt_['projectId'];
                    $pp_previews = $opt_['numPages'];
                }
			}
		}
		
		$userData = '';
		
		if (is_user_logged_in()) {
			global $current_user;
			get_currentuserinfo();
			$fname = addslashes(get_user_meta($current_user->ID, 'first_name', true ));
			$lname = addslashes(get_user_meta($current_user->ID, 'last_name', true ));
			$address_1 = $woocommerce->customer->get_address();
			$address_2 = $woocommerce->customer->get_address_2();
			$city = $woocommerce->customer->get_city();
			$postcode = $woocommerce->customer->get_postcode();
			$state = $woocommerce->customer->get_state();
			$country = WC()->countries->countries[$woocommerce->customer->get_country()];
			$phone = '';
			
			$address = "{$address_1}<br>";
			if (!empty($address_2)) $address .= "{$address_2}<br>";
			$address .= "{$city} {$postcode}<br>";
			if (!empty($state)) $address .= "{$state}<br>";
			$address .= $country;
			$address = addslashes($address);
			
			$userData = ",
				userData: {
					email: '" . $current_user->user_email . "',
					name: '{$fname} {$lname}',
					firstname: '{$fname}',
					lastname: '{$lname}',
					telephone: '{$phone}',
					address: '{$address}'.split('<br>').join('\\n')
				}";
		}
			
		wc_enqueue_js( " 
            PPCLIENT = PPCLIENT || {};
            
            PPCLIENT.vars = {
                client: 'wp',
                uploadUrl: '" . plugins_url('uploader/', __FILE__) . "',
                appApiUrl: '" . SERVER_URLPATH . "/api/front/',
                rscCdn: '" . SERVER_RSCCDN . "',
                rscBase: '" . SERVER_RSCBASE . "',
                pluginRoot: '" . plugins_url('/', __FILE__) . "',
                
                functions: {
                    
                },
                
                cValues: '" . $pp_now_value . "',
                projectId: '" . $pp_project_id . "',
                userId: '" . $pp_uid . "',
                previews: '" . $pp_previews . "',
                mode: '" . $pp_mode . "',
                langCode: '" . substr(get_bloginfo('language'), 0, 2) . "',
                hideCartButton: $pp_set_option[2],
                enableUpload: $pp_set_option[1],
                designId: '" . $pp_set_option[0] . "',
                inline: '" . (defined('PITCH_INLINE') ? PITCH_INLINE : '') . "',
                maintainImages: " . (defined('PITCH_RETAIN_PRODUCT_IMAGES') ? PITCH_RETAIN_PRODUCT_IMAGES : 'true') . ",
                autoShow: " . (defined('PITCH_SHOW_EDITOR_STARTUP') ? PITCH_SHOW_EDITOR_STARTUP : 'false') . ",
                apiKey: '" . PITCH_APIKEY . "',
                product: {
                    id: '" . $post->ID . "',
                    name: '" . $post->post_name . "'
                }{$userData}
            };
        ");
				
        echo '
			<input type="hidden" id="_w2p_set_option" name="_w2p_set_option" value="' . $pp_now_value . '" />
			<div id="pp_main_btn_sec" class="ppc-main-btn-sec" > </div>';
		
		wc_enqueue_js( " if(typeof PPCLIENT.init === 'function') PPCLIENT.init(); " );
		
	}


	//Admin.. ------------------------------------------------------------------------------------------------
	function ppa_actions() {
		add_menu_page( 'PitchPrint', 'PitchPrint', 'manage_options', 'w2p', 'show_ppa_admin');
    }
	function ppa_header_files() {
		wp_enqueue_script('pitchprint_admin', PPA_WP_CDN_PATH);
		$timestamp = time();
		$signature = md5(PITCH_APIKEY . PITCH_SECRETKEY . $timestamp);
		wc_enqueue_js("
			PPrintA = PPrintA || { version: '8.2.0' };
			
			PPrintA.vars = {
				rscCdn: '" . SERVER_RSCCDN . "',
				rscBase: '" . SERVER_RSCBASE . "',
				runtimePath: '" . SERVER_URLPATH . "/api/runtime/',
				adminPath: '" . SERVER_URLPATH . "/admin/',
				credentials: { timestamp: '" . $timestamp . "', apiKey: '" . PITCH_APIKEY . "', signature: '" . $signature . "'}
			}
			PPrintA.init();
		");
	}
	function ppa_add_tab() {
		global $post, $woocommerce;
        echo '</div><div class="options_group show_if_simple show_if_variable"><input type="hidden" value="' . get_post_meta( $post->ID, '_w2p_set_option', true ) . '" id="ppa_values" name="ppa_values" >';
		
        $ppa_upload_selected_option = '';
        $ppa_hide_Cart_btn_option = '';
		$ppa_selected_option = get_post_meta( $post->ID, '_w2p_set_option', true );
        $ppa_selected_option = explode(':', $ppa_selected_option);
        if (count($ppa_selected_option) > 1) $ppa_upload_selected_option = ($ppa_selected_option[1] === '1' ? 'checked' : '');
        if (count($ppa_selected_option) > 2) $ppa_hide_Cart_btn_option = ($ppa_selected_option[2] === '1' ? 'checked' : '');
        
		woocommerce_wp_select( array(
				'id'            => 'ppa_pick',
				'value'			=>	$ppa_selected_option[0],
				'wrapper_class' => '',
				'options'       => array('' => 'None'),
				'label'         => 'PitchPrint Design',
				'desc_tip'    	=> true,
				'description' 	=> __("Visit the PitchPrint Admin Panel to create and edit designs", 'PitchPrint')
			) );
        woocommerce_wp_checkbox( array(
				'id'            => 'ppa_pick_hide_cart_btn',
				'value'		    => $ppa_hide_Cart_btn_option,
                'label'         => '',
                'cbvalue'		=> 'checked',
                'description' 	=> '&#8678; ' . __("Check this to hide \"Add to Cart\" button until customer customizes or uploads their designs.", 'PitchPrint')
			) );
        
        woocommerce_wp_checkbox( array(
				'id'            => 'ppa_pick_upload',
				'value'		    => $ppa_upload_selected_option,
                'label'         => '',
                'cbvalue'		=> 'checked',
                'description' 	=> '&#8678; ' . __("Check this to enable clients to upload their files", 'PitchPrint')
			) );
		wc_enqueue_js("PPrintA.vars.selectedOption = '" . $ppa_selected_option[0] . "'; PPrintA.fetchDesigns();");
	}
	function ppa_write_panel_save( $post_id ) {
		$ppa_set_option = $_POST['ppa_values'];
		update_post_meta( $post_id, '_w2p_set_option', $ppa_set_option );
	}
    function show_ppa_admin() {
		$issues = '';
		$PITCH_APIKEY = defined('PITCH_APIKEY') ? PITCH_APIKEY : '';
		$PITCH_SECRETKEY = defined('PITCH_SECRETKEY') ? PITCH_SECRETKEY : '';
		$PITCH_JSCRIPT = defined('PITCH_JSCRIPT') ? PITCH_JSCRIPT : '';
		$PITCH_INLINE = defined('PITCH_INLINE') ? stripslashes(PITCH_INLINE) : '';
        $PITCH_RETAIN_PRODUCT_IMAGES = defined('PITCH_RETAIN_PRODUCT_IMAGES') ? PITCH_RETAIN_PRODUCT_IMAGES : '';
        $PITCH_SHOW_EDITOR_STARTUP = defined('PITCH_SHOW_EDITOR_STARTUP') ? PITCH_SHOW_EDITOR_STARTUP : '';
		
		if (!is_writable(plugin_dir_path(__FILE__) . 'system/settings.php')) {
			$issues .= '<br/><br/>' . __("Sorry, the file", 'PitchPrint') . ' "' . plugin_dir_path(__FILE__) . 'system/settings.php" ' . __("is not writable.", 'PitchPrint');
		}
		
		if (isset($_POST['ppa_inpt_apiKey']) && isset($_POST['ppa_inpt_secretKey']) && $issues === '') {
			if (!empty($_POST['ppa_inpt_apiKey']) && !empty($_POST['ppa_inpt_secretKey'])) {
				$jscr = addslashes($_POST['ppa_inpt_jscript']);
				$jscr = str_replace('\"', '"', $jscr);
                $str = "<?php define('PITCH_SHOW_EDITOR_STARTUP', '" . (isset($_POST['ppa_inpt_editor_startup']) ? ($_POST['ppa_inpt_editor_startup'] == 'on' ? 'true' : 'false') : 'false') . "'); define('PITCH_RETAIN_PRODUCT_IMAGES', '" . (isset($_POST['ppa_inpt_retain_images']) ? ($_POST['ppa_inpt_retain_images'] == 'on' ? 'true' : 'false') : 'false') . "'); define('PITCH_INLINE', '{$_POST['ppa_inpt_inline']}');  define('PITCH_APIKEY', '{$_POST['ppa_inpt_apiKey']}');     define('PITCH_SECRETKEY', '{$_POST['ppa_inpt_secretKey']}'); define('PITCH_JSCRIPT', '" . $jscr ."'); ?>";
				$handle = fopen(plugin_dir_path(__FILE__)."system/settings.php", "wb");
				fwrite($handle, $str);
				fclose($handle);
				$PITCH_APIKEY = $_POST['ppa_inpt_apiKey'];
				$PITCH_SECRETKEY = $_POST['ppa_inpt_secretKey'];
				$PITCH_JSCRIPT = $_POST['ppa_inpt_jscript'];
				$PITCH_INLINE = $_POST['ppa_inpt_inline'];
                $$PITCH_RETAIN_PRODUCT_IMAGES = isset($_POST['ppa_inpt_retain_images']) ? ($_POST['ppa_inpt_retain_images'] == 'on' ? 'true' : 'false') : 'false';
                $PITCH_SHOW_EDITOR_STARTUP = isset($_POST['ppa_inpt_editor_startup']) ? ($_POST['ppa_inpt_editor_startup'] == 'on' ? 'true' : 'false') : 'false';
			}
		}
		if ($issues !== '') {
			echo '<div class="wrap" style="color:#F00">' . $issues . '</div>';
			exit();
		}
		
		echo '<div class="wrap">
				<div style="margin-top:20px; font-size:16px"><br/><b>PITCHPRINT ' . __("SETTINGS", "PitchPrint") . ':</b><br/></div><div style="margin:20px;">
				<form method="post" action="">
					<label style="display:inline-block; width:120px">API KEY:</label> <input style="width:280px" name="ppa_inpt_apiKey" type="text" value="' . $PITCH_APIKEY . '" /><br/>
					<label style="display:inline-block; width:120px">SECRET KEY:</label> <input style="width:280px" name="ppa_inpt_secretKey" type="text" value="' . $PITCH_SECRETKEY . '" /><br/><br/>
					<label style="display:inline-block; width:120px">Inline Selector:</label> <input name="ppa_inpt_inline" type="text" value="' . htmlentities($PITCH_INLINE) . '" /><br/><br/>
                    <label style="display:inline-block; width:120px">Retain Product Images:</label> <input name="ppa_inpt_retain_images" type="checkbox" ' . ($PITCH_RETAIN_PRODUCT_IMAGES === 'true' ? 'checked="checked"' : '') . ' /><br/><br/>
                    <label style="display:inline-block; width:120px">Show Editor on StartUp:</label> <input name="ppa_inpt_editor_startup" type="checkbox" ' . ($PITCH_SHOW_EDITOR_STARTUP === 'true' ? 'checked="checked"' : '') . ' /><br/><br/>
					<label style="display:inline-block; width:120px; vertical-align: top;">Custom Js-Script:</label> <textarea rows="20" style="width:800px; font-family: monospace;" name="ppa_inpt_jscript" >' . stripslashes($PITCH_JSCRIPT) . '</textarea><br/>
					<label style="display:inline-block; width:120px"></label> <input style="width:120px" class="button action" type="submit" value="' . __("Update", "PitchPrint") . '.." /><br/>
				</form></div></div>
				
				<div class="wrap">
					<div class="frm-section-inner-noline" style="padding-left: 140px; margin-top:40px;" >
						<span style="font-size:10px; font-style:italic">' . __("To generate keys, manage designs, pitcures, templates etc, please login to the admin panel", "PitchPrint") . '</span><br/><br/>
						<a href="' . SERVER_URLPATH . '/admin/domains" target="_blank"><input type="submit" class="button action" value="' . __("LAUNCH PITCHPRINT ADMIN PANEL", "PitchPrint") . '" /></a>
					</div>
				</div>';
	}
	
?>