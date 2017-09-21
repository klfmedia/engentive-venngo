<?php
class ControllerApiLsorder extends Controller {

	private $curl;

	public function update() {
		$this->load->model('api/order');
		$this->load->library('curl');
		$this->curl = new Curl();
		$this->curl->sayHi();
	}

}