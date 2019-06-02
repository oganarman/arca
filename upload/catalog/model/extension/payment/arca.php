<?php
class ModelExtensionPaymentArca extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/arca');
		$zoneId = (int)$this->config->get('payment_arca_geo_zone_id');
		$status = !$zoneId;
		if($status===false){
		    $query = $this->db->query("SELECT 1 FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . $zoneId. "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0' LIMIT 1)");
		    $status = (bool)$query->num_rows;
		}
		if($status===true){
		    $totalPrice = (float)$this->config->get('payment_arca_total');
		    $status = $totalPrice>0? $totalPrice>$total:true;
		    if($status===true){
			$status = $this->config->get('payment_arca_login') && $this->config->get('payment_arca_password');
		    }
		}
		return $status===true?
		    array(
			'code'       => 'arca',
			'title'      => $this->language->get('text_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_arca_sort_order')
		    ):
		    array();
	}
}