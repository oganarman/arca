<?php
class ControllerExtensionPaymentArca extends Controller {
	public function index() {
		$this->load->language('extension/payment/arca');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		$this->load->model('localisation/geo_zone');
		$this->load->model('localisation/order_status');
		$token = $this->session->data['user_token'];
		$error = $this->request->server['REQUEST_METHOD'] === 'POST'?$this->validate():array();
		if ($this->request->server['REQUEST_METHOD'] === 'POST' && empty($error)) {
			$this->model_setting_setting->editSetting('payment_arca', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/arca', 'user_token=' . $token, true)
		);

		$data['action'] = $this->url->link('extension/payment/arca', 'user_token=' . $token, true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=payment', true);
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if(!isset($error['warning'])){
		    $data['error_warning'] = '';
		    if (isset($error['login'])) {
			$data['error_login'] = $error['login'];
			$data['payment_arca_login'] = $this->request->post['payment_arca_login'];
		    } else {
			$data['payment_arca_login'] = $this->config->get('payment_arca_login');
			$data['error_login'] ='';
		    }

		    if (isset($error['password'])) {
			$data['error_password'] = $error['password'];
			$data['payment_arca_password'] = $this->request->post['payment_arca_password'];
		    } else {
			$data['payment_arca_password'] = $this->config->get('payment_arca_password');
			$data['error_password'] ='';
		    }
		    
		    $data['payment_arca_test'] = isset($this->request->post['payment_arca_test'])?$this->request->post['payment_arca_test']:$this->config->get('payment_arca_test');
		    $data['payment_arca_status'] = isset($this->request->post['payment_arca_status'])?$this->request->post['payment_arca_status']:$this->config->get('payment_arca_status');
		    $data['payment_arca_sort_order'] = isset($this->request->post['payment_arca_sort_order'])?$this->request->post['payment_arca_sort_order']:$this->config->get('payment_arca_sort_order');
		    $data['payment_arca_sort_order'] = isset($this->request->post['payment_arca_total'])?$this->request->post['payment_arca_sort_order']:$this->config->get('payment_arca_total');
		    $data['payment_arca_geo_zone_id'] = isset($this->request->post['payment_arca_geo_zone_id'])?$this->request->post['payment_arca_geo_zone_id']:$this->config->get('payment_arca_geo_zone_id');
		    $data['payment_arca_order_status_id'] = isset($this->request->post['payment_arca_order_status_id'])?$this->request->post['payment_arca_order_status_id']:$this->config->get('payment_arca_order_status_id');
		    if(empty($data['payment_arca_order_status_id'])){
			$data['payment_arca_order_status_id'] = 1;
		    }
		}
		else{
		    $data['error_warning'] = $error['warning'];
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/arca', $data));
	}

	private function validate() {
		$error = array();
		if (!$this->user->hasPermission('modify', 'extension/payment/arca')) {
		    $error['warning'] = $this->language->get('error_permission');
		}
		else{
		    if (!$this->request->post['payment_arca_login']) {
			$error['login'] = $this->language->get('error_login');
		    }
		    if (!$this->request->post['payment_arca_password']) {
			$error['password'] = $this->language->get('error_password');
		    }
		}
		return $error;
	}
}