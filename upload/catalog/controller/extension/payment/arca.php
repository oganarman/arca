<?php
class ControllerExtensionPaymentArca extends Controller {
	
	private static $testUrl = 'https://ipaytest.arca.am:8445/payment/rest/';
	private static $liveUrl = 'https://ipay.arca.am/payment/rest/';

	public function index() {
	    return $this->load->view('extension/payment/arca');
	}
	
	protected function getHash($order_id,$total){
	    $total = $this->normalizePrice($total);
	    return md5($order_id . $total .  $this->config->get('payment_arca_login') . $this->config->get('payment_arca_password'));
	}
	
	protected function normalizePrice($price){
	    return number_format($price,2,'.','');
	}
	
	protected function convertToAmd($price,$code){
	    return $this->currency->convert($price, $code, 'AMD'); 
	}

	public function confirm(){
	    $this->load->model('checkout/order');
	    $orderId=$this->session->data['order_id'];
	    $order_info = $this->model_checkout_order->getOrder($orderId);	
	    if(!empty($order_info)){
		$currency = $order_info['currency_code'];
		if($currency!=='')
		$amount = $order_info['total']*$order_info['currency_value'];
		if($currency!=='USD' && $currency!=='AMD' && $currency!=='EUR'){
		    $amount = $this->convertToAmd($amount, $currency);
		    $currency='AMD';
		}
		$amount=$this->normalizePrice($amount);
		$params = array(
		    'amount'=>$amount*100,
		    'orderNumber'=>$orderId,
		    'description'=>  urldecode($order_info['store_name'])  ,
		    'pageView'=>!empty($this->request->post['isMobile']) && $this->request->post['isMobile']!=='false'?'MOBILE':'DESKTOP',
		    'returnUrl'=>$this->url->link('extension/payment/arca/callback', 'hash=' . $this->getHash($orderId, $amount))
		);
		$result = $this->send('register.do', $params);
		$this->response->addHeader('Content-Type: application/json');
		$response = array();
		if(!empty($result['formUrl']) && !empty($result['orderId'])){
		    $response['redirect'] = $result['formUrl'];
		}
		else{
		    $this->load->language('extension/payment/arca');
		    $response['error'] = $this->language->get('error_payment');
		}
		$this->response->setOutput(json_encode($response));
	    }
	}
	
	public function callback(){
	    $is_404=true;
	    if(isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code']==='arca' && !empty($this->request->get['hash'])){
		$this->load->model('checkout/order');
		$orderId=(int)$this->session->data['order_id'];
	  
		if($orderId>0){
		    $order_info = $this->model_checkout_order->getOrder($orderId);
		    if(!empty($order_info) && $order_info['payment_code']===$this->session->data['payment_method']['code']){
			$currency = $order_info['currency_code'];
			$amount = $order_info['total']*$order_info['currency_value'];
			
			if($currency!=='USD' && $currency!=='AMD' && $currency!=='EUR'){
			    $amount = $this->convertToAmd($amount, $currency);
			    $currency='AMD';
			}
			$amount = $this->normalizePrice($amount);
			
			if($this->getHash($orderId, $amount)===$this->request->get['hash']){
			    $params = array(
			      'orderNumber'=>$orderId  
			    );
			    $result = $this->send('getOrderStatusExtended.do', $params);
			    if(!empty($result['actionCode']) && $result['actionCode']==0  && $result['actionCode']==2  && $result['amount']==($amount*100)){
				$is_404=false;
				$comment = "=== ARCA Transaction Details ===\r\n";
				$comment .= "Order ID: ".$orderId."\r\n";
				$comment .= "Amount: ".$this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)."\r\n";
				$comment .= "Date/Time: ".date('Y-m-d H:i',$result['date'])."\r\n";
				$comment .= "Description: ".$result['orderDescription']."\r\n";
				$this->model_checkout_order->addOrderHistory($orderId, $this->config->get('payment_arca_order_status_id'), $comment, true);
				$this->response->redirect($this->url->link('checkout/success'));
			    }
			}
		    }
		}
	    }
	    if($is_404===true){
		$this->load->language('extension/payment/arca');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('common/home'),
			'text' => $this->language->get('text_home')
		);

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('checkout/cart'),
			'text' => $this->language->get('text_cart')
		);

		$data['heading_title'] = $this->language->get('error_heading_title');

		$data['button_continue'] = $this->language->get('button_continue');

		$data['continue'] = $this->url->link('checkout/cart');

		unset($this->session->data['success']);

		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('error/not_found', $data));
	    }
	}


	protected function send($action,array $params){
	    $isTestMode = $this->config->get('payment_arca_test');
	    $reqUestUrl = $isTestMode?self::$testUrl:self::$liveUrl;
	    $reqUestUrl.=$action;
	    $lang = $this->language->get('code'); 
	    if($lang==='am'){
		$lang ='hy';
	    }
	    elseif($lang==='en-gb'){
		$lang='en';
	    }
	    $params['userName']=$this->config->get('payment_arca_login');
	    $params['password']=$this->config->get('payment_arca_password');
	    $params['language']=$lang;
	    $reqUestUrl.='?';
	    
	    foreach($params as $k=>$v){
		$reqUestUrl.=$k.'='.urldecode($v);
		if($k!=='language'){
		    $reqUestUrl.='&';
		}
	    }
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_URL, $reqUestUrl);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	    $ret = curl_exec($ch);
	    curl_close($ch);
	    if(!empty($ret)){
		$ret = json_decode($ret);
	    }
	    return !empty($ret)?get_object_vars($ret):array();
	}

}