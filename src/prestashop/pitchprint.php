<?php

if (!defined('_PS_VERSION_')) exit;

    define('SERVER_URLPATH', 'https://pitchprint.net');
    define('SERVER_RSCBASE', '//s3.amazonaws.com/pitchprint.rsc/');
	define('SERVER_RSCCDN', '//dta8vnpq1ae34.cloudfront.net/');
	
    define('PP_MIN_CDN_PATH', 'https://dta8vnpq1ae34.cloudfront.net/app/js/pprint.js');
    define('PP_CLASS_CDN_PATH', 'https://dta8vnpq1ae34.cloudfront.net/app/js/pp.client.js');
    define('PPA_PS_CDN_PATH', 'https://dta8vnpq1ae34.cloudfront.net/app/js/pp.ps.a.js');
	
    define('PITCHPRINT_API_KEY', 'pitchprint_API_KEY');
    define('PITCHPRINT_SECRET_KEY', 'pitchprint_SECRET_KEY');

    define('PITCHPRINT_P_DESIGNS', 'pitchprint_p_designs');
    define('PITCHPRINT_JSCRIPT', 'pitchprint_jscript');
	define('PITCHPRINT_INLINE', 'pitchprint_inline');
	define('PITCHPRINT_AUTO_SHOW', 'pitchprint_auto_show');
	define('PITCHPRINT_SHOW_IMAGES', 'pitchprint_show_images');
    define('PITCHPRINT_CSS', 'pitchprint_css');

    define('PITCHPRINT_ID_CUSTOMIZATION_NAME', '@PP@');
  
class PitchPrint extends Module {
      
    public function __construct() {
      $this->name = 'pitchprint';
      $this->tab = 'front_office_features';
      $this->version = 8.2;
      $this->author = 'Synergic Laboratories';
      $this->need_instance = 1;
      $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
   
      parent::__construct();
   
      $this->displayName = $this->l('PitchPrint');
      $this->description = $this->l('PitchPrint Web2Print solution.');
      
      $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }
 
    public function install() {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);
            
        if (!parent::install()) 
            return false;
            
        Db::getInstance()->execute("ALTER TABLE `" . _DB_PREFIX_ . "customized_data` CHANGE `value` `value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
		
		$_pKey = Configuration::get(PITCHPRINT_API_KEY);
		$_pSec = Configuration::get(PITCHPRINT_SECRET_KEY);
		$_pDes = Configuration::get(PITCHPRINT_P_DESIGNS);
		
		if (empty($_pKey)) Configuration::updateValue(PITCHPRINT_API_KEY, '');
		if (empty($_pSec)) Configuration::updateValue(PITCHPRINT_SECRET_KEY, '');
		if (empty($_pDes)) Configuration::updateValue(PITCHPRINT_P_DESIGNS, serialize(array()));
      
        return $this->registerHook('displayProductButtons') &&
        $this->registerHook('displayHeader') &&
        $this->registerHook('displayAdminOrder') &&
        $this->registerHook('displayBackOfficeHeader') &&
        $this->registerHook('actionProductUpdate') &&
        $this->registerHook('displayAdminProductsExtra');
    }
    public function uninstall() {
        return true;
    }
    
    public function hookDisplayProductButtons($params) {
        $productId = (int)Tools::getValue('id_product');
		
        $indexval = Db::getInstance()->getValue("SELECT `id_customization_field` FROM `"._DB_PREFIX_."customization_field` WHERE `id_product` = {$productId} AND `type` = 1");
		$pp_values = (string)Tools::getValue('values');
		
        if (Tools::getValue('clear') == true) {
            $this->context->cart->deleteCustomizationToProduct($productId, (int)$indexval);
            die();
        }
		
		if (!empty($pp_values)) {
            if (!$this->context->cart->id && isset($_COOKIE[$this->context->cookie->getName()])) {
				$this->context->cart->add();
				$this->context->cookie->id_cart = (int)$this->context->cart->id;
            }
            $this->context->cart->addPictureToProduct($productId, (int)$indexval, 1, $pp_values);

			
        } else {
            $pp_values = $this->context->cart->getProductCustomization($productId, (int)$indexval, true);
			if (!empty($pp_values)) $pp_values = $pp_values[0]['value'];
        }
        
        $pp_previews = '';
        $pp_mode = 'new';
        $pp_project_id = '';
		
        $opt_ = is_string($pp_values) ? json_decode(rawurldecode($pp_values), true) : $pp_values;
        
		if (!empty($opt_)) {
			if ($opt_['type'] === 'u') {
				$pp_previews = $opt_['previews'];
				$pp_upload_ready = true;
				$pp_mode = 'upload';
			} else if ($opt_['type'] === 'p') {
				$pp_mode = 'edit';
				$pp_project_id =  $opt_['projectId'];
				$pp_previews = $opt_['numPages'];
			}
		}
        
        $pp_design_options = unserialize(Configuration::get(PITCHPRINT_P_DESIGNS));
        $ppa_productValues = isset($pp_design_options[$productId]) ? $pp_design_options[$productId] : '';
        $pp_apiKey = Configuration::get(PITCHPRINT_API_KEY);
        $pp_jscript = Configuration::get(PITCHPRINT_JSCRIPT);
        $pp_css = Configuration::get(PITCHPRINT_CSS);
		
		$pp_inline = Configuration::get(PITCHPRINT_INLINE);
		$pp_auto_show = Configuration::get(PITCHPRINT_AUTO_SHOW);
		$pp_show_images = Configuration::get(PITCHPRINT_SHOW_IMAGES);
		$ppa_designValuesArray = explode(':', $ppa_productValues);
        
        if (Tools::getValue('ajax') == true) die();
		
        if (!is_string($pp_values)) $pp_values = json_encode($pp_values, true);
		
		$userData = '';
		if ($this->context->customer->isLogged()) {
			$fname = addslashes($this->context->cookie->customer_firstname);
			$lname = addslashes($this->context->cookie->customer_lastname);
			
			$cus = new Customer((int)$this->context->cookie->id_customer);
			$cusInfo = $cus->getAddresses((int)Configuration::get('PS_LANG_DEFAULT'));
			$cusInfo = $cusInfo[0];
			$addr = "{$cusInfo['address1']}<br>";
			if (!empty($cusInfo['address2'])) $addr .= "{$cusInfo['address2']}<br>";
			$addr .= "{$cusInfo['city']} {$cusInfo['postcode']}<br>";
			if (!empty($cusInfo['state'])) $addr .= "{$cusInfo['state']}<br>";
			$addr .= "{$cusInfo['country']}";
			
			$addr = trim($addr);
			
			$userData = ",
				userData: {
					email: '{$this->context->cookie->email}',
					name: '{$fname} {$lname}',
					firstname: '{$fname}',
					lastname: '{$lname}',
					telephone: '{$cusInfo['phone']}',
					fax: '',
					address: '" . addslashes($addr) . "'.split('<br>').join('\\n')
				}";
		}
		
        return !empty($ppa_productValues) ? "
        <style>
            /*Custom CSS Style*/
            {$pp_css}
        </style>
        
        
        <script type=\"text/javascript\">
			ajaxsearch = undefined;
			
			if (typeof PPCLIENT === 'undefined') {
				var PPCLIENT = {};
			}
            
			PPCLIENT.vars = {
				client: 'ps',
				uploadUrl: '" . _PS_BASE_URL_ . __PS_BASE_URI__ . "modules/pitchprint/uploads/',
				baseUrl: '" . SERVER_URLPATH ."',
				appApiUrl: '" . SERVER_URLPATH . "/api/front/',
				rscCdn: '" . SERVER_RSCCDN . "',
				rscBase: '" . SERVER_RSCBASE . "',
				functions: { },
				
				cValues: '{$pp_values}',
				projectId: '{$pp_project_id}',
				userId: '{$this->context->cookie->id_customer}',
				previews: '{$pp_previews}',
				mode: '{$pp_mode}',
				langCode: '{$this->context->language->iso_code}',
				hideCartButton: {$ppa_designValuesArray[2]},
				enableUpload: {$ppa_designValuesArray[1]},
				designId: '{$ppa_designValuesArray[0]}',
				inline: '{$pp_inline}',
				maintainImages: {$pp_show_images},
				autoShow: {$pp_auto_show},
				apiKey: '{$pp_apiKey}',
				pageName : '',
				product: {
					id: '{$productId}',
					name: '" . addslashes($params['product']->name) . "'
				}{$userData}
			}
			
            //Custom Javascript...
            
			{$pp_jscript}
			
			window.onload = function() {
				if (typeof PPCLIENT.init === 'function') {
					PPCLIENT.start();
					PPCLIENT.init();
				}
			}
        </script>" : "";
    }
    
    public function hookDisplayHeader() {
        if ($this->context->controller->php_self === 'product') {
            $productId = (int)Tools::getValue('id_product');
            $pp_design_options = unserialize(Configuration::get(PITCHPRINT_P_DESIGNS));
            if (isset($pp_design_options[$productId])) {
                $this->context->controller->addJS(PP_MIN_CDN_PATH);
                $this->context->controller->addJS(PP_CLASS_CDN_PATH);
            }
        } else if (substr($this->context->controller->php_self, 0, 5) === 'order' || $this->context->controller->php_self === 'history') {
            $this->context->controller->addJS(PP_CLASS_CDN_PATH);
            return "
				<script>
					if (typeof PPCLIENT === 'undefined') {
						var PPCLIENT = {};
					}
					
					PPCLIENT.vars = {
						client: 'ps',
						uploadUrl: './uploads/',
						baseUrl: '" . SERVER_URLPATH ."',
						appApiUrl: '" . SERVER_URLPATH . "/api/front/',
						rscCdn: '" . SERVER_RSCCDN . "',
						rscBase: '" . SERVER_RSCBASE . "',
						pageName : '{$this->context->controller->php_self}',
						functions: { }
					}
					
					window.onload = function() {
						if (typeof PPCLIENT.start === 'function') PPCLIENT.start();
					};
				</script>
			";
        }
    }
    
    
//Admin functions =====================================================================================
    
    public function hookDisplayAdminProductsExtra($params) {
        if (Validate::isLoadedObject($product = new Product((int)Tools::getValue('id_product')))) {
            $pp_val = '';
            $id_product = (int)Tools::getValue('id_product');
            $p_designs = unserialize(Configuration::get(PITCHPRINT_P_DESIGNS));
            if (!empty($p_designs[$id_product])) $pp_val = $p_designs[$id_product];
            
            $indexval = Db::getInstance()->getValue("SELECT `id_customization_field` FROM `"._DB_PREFIX_."customization_field` WHERE `id_product` = ".(int)Tools::getValue('id_product')." AND `type` = 1");
            
            return '<div class="product-tab-content"><div class="panel product-tab"><h3>Assign PitchPrint Design</h3><div class="alert alert-info">
				  You can create your designs at <a target="_blank" href="' . SERVER_URLPATH . '/admin/designs">' . SERVER_URLPATH . '/admin/designs</a> </div><div id="w2p-div">
				  <div style="margin-bottom:10px">
				  <select id="ppa_pick" name="ppa_pick" style="width:300px;" ><option style="color:#aaa" value="0">Loading..</option></select>
				  <input type="hidden" id="ppa_values" name="ppa_values" value="' . $pp_val . '" />
				  <input type="hidden" id="pp_indexVal" name="pp_indexVal" value="' . $indexval . '" />
				  </div>
					<div class="checkbox" style="margin-bottom:10px">
					<label for="ppa_pick_upload"> <input type="checkbox" name="ppa_pick_upload" id="ppa_pick_upload" value="">Enable clients upload their files.</label>
					</div>
					<div class="checkbox">
					<label for="ppa_pick_hide_cart_btn"> <input type="checkbox" name="ppa_pick_hide_cart_btn" id="ppa_pick_hide_cart_btn" value="">Required.</label>
					</div>
				  </div><div id="pp_div_footer" class="panel-footer">
			<button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i class="process-icon-save"></i> Save</button>
			<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i class="process-icon-save"></i> Save and stay</button> </div></div></div>';
        } else {
          $this->context->controller->errors[] = Tools::displayError('You must first save the product before assigning a design!');
        }
    }
    public function hookActionProductUpdate($params) {
        $pp_pick = (string)Tools::getValue('ppa_values');
        if (!empty($pp_pick) && $pp_pick != "") {
            $id_product = (int)Tools::getValue('id_product');
            $p_designs = unserialize(Configuration::get(PITCHPRINT_P_DESIGNS));
            $p_designs[$id_product] = (string)Tools::getValue('ppa_values');
            Configuration::updateValue(PITCHPRINT_P_DESIGNS, serialize($p_designs));
            
            $custmz_field = Db::getInstance()->getValue("SELECT `id_customization_field` FROM `" . _DB_PREFIX_ . "customization_field` WHERE `id_product` = {$id_product} AND `type` = 1");
            
            if (!empty($custmz_field)) {
                $languages = Language::getLanguages(false);
                foreach ($languages as $lang) {
					Db::getInstance()->execute("INSERT INTO `" . _DB_PREFIX_ . "customization_field_lang` (`id_customization_field`, `id_lang`, `name`) VALUES ('{$custmz_field}', '{$lang['id_lang']}', '" . PITCHPRINT_ID_CUSTOMIZATION_NAME . "') ON DUPLICATE KEY UPDATE `id_lang` = '{$lang['id_lang']}', `name` = '" . PITCHPRINT_ID_CUSTOMIZATION_NAME . "'");
                }
            }
        }
        return;
    }
    
    public function hookDisplayAdminOrder($params) {
        return "
            <script type=\"text/javascript\">
			
				PPrintA = PPrintA || { version: '8.2.0' };
				PPrintA.vars = {
					rscCdn: '" . SERVER_RSCCDN . "',
					rscBase: '" . SERVER_RSCBASE . "',
					runtimePath: '" . SERVER_URLPATH . "/api/runtime/',
					adminPath: '" . SERVER_URLPATH . "/admin/',
					credentials: { timestamp: '" . $timestamp . "', apiKey: '" . PITCH_APIKEY . "', signature: '" . $signature . "'}
				}
				
				jQuery(document).ready(function () { 
					PPrintA.init(); 
				});
                
            </script>";
    }
    
    public function hookDisplayBackOfficeHeader($params) {
      $_controller = $this->context->controller;
      if (Validate::isLoadedObject($product = new Product((int)Tools::getValue('id_product')))) {
		$this->context->controller->addJS(PPA_PS_CDN_PATH);
		$pp_design_options = unserialize(Configuration::get(PITCHPRINT_P_DESIGNS));
		$pp_timestamp = time();
		$pp_apiKey = Configuration::get(PITCHPRINT_API_KEY);
		$pp_secretKey = Configuration::get(PITCHPRINT_SECRET_KEY);
		$pp_signature = (!empty($pp_secretKey) && !empty($pp_apiKey)) ? md5($pp_apiKey . $pp_secretKey . $pp_timestamp) : '';
		$pp_productid = (int)Tools::getValue('id_product');
		$pp_options = isset($pp_design_options[$pp_productid]) ? $pp_design_options[$pp_productid] : '0';
		  
        return "
            <script type=\"text/javascript\">
				PPrintA = PPrintA || { version: '8.2.0' };
				PPrintA.vars = {
					rscCdn: '" . SERVER_RSCCDN . "',
					rscBase: '" . SERVER_RSCBASE . "',
					runtimePath: '" . SERVER_URLPATH . "/api/runtime/',
					adminPath: '" . SERVER_URLPATH . "/admin/',
					productValues: \"{$pp_options}\",
					credentials: { timestamp: '" . $timestamp . "', apiKey: '" . PITCH_APIKEY . "', signature: '" . $signature . "'}
				}
				
                jQuery(document).ready(function () { PPrintA.fetchDesigns(); });
            </script>";
        }else if ($_controller->controller_name === 'AdminOrders') {
            $this->context->controller->addJS(PPA_PS_CDN_PATH);
        }
    }
    
    public function getContent() {
      $output = null;
   
      if (Tools::isSubmit('submit'.$this->name)) {
          $pitchprint_api = strval(Tools::getValue(PITCHPRINT_API_KEY));
          $pitchprint_secret = strval(Tools::getValue(PITCHPRINT_SECRET_KEY));
          if (!$pitchprint_api  || empty($pitchprint_api) || !Validate::isGenericName($pitchprint_api) || !$pitchprint_secret  || empty($pitchprint_secret) || !Validate::isGenericName($pitchprint_secret)) {
              $output .= $this->displayError( $this->l('Invalid Configuration value') );
          } else {
                $pitchprint_api = str_replace(' ', '', $pitchprint_api);
                $pitchprint_secret = str_replace(' ', '', $pitchprint_secret);
                Configuration::updateValue(PITCHPRINT_API_KEY, $pitchprint_api);
                Configuration::updateValue(PITCHPRINT_SECRET_KEY, $pitchprint_secret);
                
                $pitchprint_jscript = strval(Tools::getValue(PITCHPRINT_JSCRIPT));
                $pitchprint_css = strval(Tools::getValue(PITCHPRINT_CSS));
			  	
			  	$pitchprint_inline = strval(Tools::getValue(PITCHPRINT_INLINE));
			  	$pitchprint_auto_show = Tools::getValue(PITCHPRINT_AUTO_SHOW);
			  	$pitchprint_show_images = Tools::getValue(PITCHPRINT_SHOW_IMAGES);
			  
                Configuration::updateValue(PITCHPRINT_JSCRIPT, $pitchprint_jscript);
                Configuration::updateValue(PITCHPRINT_CSS, $pitchprint_css);
			  
			  	Configuration::updateValue(PITCHPRINT_INLINE, $pitchprint_inline);
			  	Configuration::updateValue(PITCHPRINT_AUTO_SHOW, $pitchprint_auto_show);
			  	Configuration::updateValue(PITCHPRINT_SHOW_IMAGES, $pitchprint_show_images);
                
                $output .= $this->displayConfirmation($this->l('Settings updated'));
          }
      }
      return $output.$this->renderForm();
    }
    
    public function renderForm() {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
         
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
				'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('PitchPrint API Key'),
                    'name' => PITCHPRINT_API_KEY,
                    'suffix' => '&nbsp; &nbsp; :&nbsp; <a href="https://pitchprint.net/admin/domains" target="_blank">Generate Keys here</a>, &nbsp; &nbsp; : &nbsp; &nbsp; <a target="_blank" href="http://docs.pitchprint.com">Online Documentation</a>',
                    'size' => 40,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('PitchPrint SECRET Key'),
                    'name' => PITCHPRINT_SECRET_KEY,
                    'size' => 40,
                    'required' => true
                ),
				array(
                    'type' => 'text',
                    'label' => $this->l('Inline Selector Element'),
                    'name' => PITCHPRINT_INLINE,
					'suffix' => '&nbsp; &nbsp; :&nbsp;' . $this->l('If you wish the editor to show on the page, kindly provide the selector.'),
                    'size' => 40,
                    'required' => false
                ),
				array(
					'type'  => 'radio',
					'label' => $this->l('Show editor on startup'),
					'name' => PITCHPRINT_AUTO_SHOW,
					'required' => false,
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Yes')
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('No')
						)
					)
				),
				array(
					'type'  => 'radio',
					'label' => $this->l('Maintain product images'),
					'name' => PITCHPRINT_SHOW_IMAGES,
					'required' => false,
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->l('Yes')
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->l('No')
						)
					)
				),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Custom JavaScript'),
                    'name' => PITCHPRINT_JSCRIPT,
                    'rows' => 10,
                    'cols' => 100
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Custom CSS'),
                    'name' => PITCHPRINT_CSS,
                    'rows' => 10,
                    'cols' => 100
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );
         
        $helper = new HelperForm();
         
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
         
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
         
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
         
        // Load current value
        $helper->fields_value[PITCHPRINT_API_KEY] = Configuration::get(PITCHPRINT_API_KEY);
        $helper->fields_value[PITCHPRINT_SECRET_KEY] = Configuration::get(PITCHPRINT_SECRET_KEY);
        $helper->fields_value[PITCHPRINT_JSCRIPT] = Configuration::get(PITCHPRINT_JSCRIPT);
        $helper->fields_value[PITCHPRINT_CSS] = Configuration::get(PITCHPRINT_CSS);
		
		$helper->fields_value[PITCHPRINT_INLINE] = Configuration::get(PITCHPRINT_INLINE);
		$helper->fields_value[PITCHPRINT_AUTO_SHOW] = Configuration::get(PITCHPRINT_AUTO_SHOW);
		$helper->fields_value[PITCHPRINT_SHOW_IMAGES] = Configuration::get(PITCHPRINT_SHOW_IMAGES);
         
        return $helper->generateForm($fields_form);
    }
}