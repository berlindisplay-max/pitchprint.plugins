<?php
class ControllerModulePitchprint extends Controller {
	private $error = array(); 

	public function index() {   
		$this->language->load('module/pitchprint');
		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting('pitchprint');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$settings['apikey'] = $this->request->post['api_value'];
			$settings['secretkey'] = $this->request->post['secret_value'];
			
			$settings['pitchprint_inline_value'] = $this->request->post['pitchprint_inline_value'];
			$settings['pitchprint_maintain_images_value'] = $this->request->post['pitchprint_maintain_images_value'];
			$settings['pitchprint_showonstartup_value'] = $this->request->post['pitchprint_showonstartup_value'];
			$settings['pitchprint_custom_js_value'] = $this->request->post['pitchprint_custom_js_value'];
			
			$this->model_setting_setting->editSetting('pitchprint', $settings);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}
		
		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/pitchprint', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['action'] = $this->url->link('module/pitchprint', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['api_label'] = $this->language->get('api_label');
		$this->data['secret_label'] = $this->language-> get('secret_label');
		
		$this->data['inline_label'] = $this->language-> get('inline_label');
		$this->data['maintain_images_label'] = $this->language-> get('maintain_images_label');
		$this->data['show_onstartup_label'] = $this->language-> get('show_onstartup_label');
		$this->data['custom_js_label'] = $this->language-> get('custom_js_label');
		$this->data['enabled_label'] = $this->language-> get('enabled_label');
		$this->data['disabled_label'] = $this->language-> get('disabled_label');
						
		$this->data['button_save'] = $this->language-> get('button_save');
		
		$this->data['api_key'] = $settings['apikey'];
		$this->data['secret_key'] = $settings['secretkey'];
		
		$this->data['pitchprint_inline_value'] = isset($settings['pitchprint_inline_value']) ? $settings['pitchprint_inline_value'] : '';
		$this->data['pitchprint_maintain_images_value'] = isset($settings['pitchprint_maintain_images_value']) ? $settings['pitchprint_maintain_images_value'] : '';
		$this->data['pitchprint_showonstartup_value'] = isset($settings['pitchprint_showonstartup_value']) ? $settings['pitchprint_showonstartup_value'] : '';
		$this->data['pitchprint_custom_js_value'] = isset($settings['pitchprint_custom_js_value']) ? $settings['pitchprint_custom_js_value'] : '';
				
		$this->data['heading_title'] = $this->language->get('heading_title');
		$this->data['button_cancel'] = $this->language->get('button_cancel');
		$this->data['text_installed'] = $this->language->get('text_installed');

		$this->template = 'module/pitchprint.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/pitchprint')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
	
	
	public function install() {
		$this->load->model('setting/setting');
		$this->load->model('pitchprint/process');
		$this->model_pitchprint_process->install();

		$settings = $this->model_setting_setting->getSetting('pitchprint');
		$settings['installed'] = 1;
		$settings['apikey'] = "";
		$settings['secretkey'] = "";
		
		$settings['pitchprint_inline_value'] = "";
		$settings['pitchprint_maintain_images_value'] = true;
		$settings['pitchprint_showonstartup_value'] = false;
		$settings['pitchprint_custom_js_value'] = "";
		
		$this->model_setting_setting->editSetting('pitchprint', $settings);
	}

	public function uninstall() {
		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting('pitchprint');
		$settings['installed'] = 0;
		$this->model_setting_setting->editSetting('pitchprint', $settings);
	}
}
?>