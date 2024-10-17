<?php

namespace Esvlad\ZkTimeTrackingTable\Controllers;

use Illuminate\Http\Request;
use Esvlad\ZkTimeTrackingTable\View\{
	Page, View
};

class Controller{
	protected $request;
	protected $layout = 'app';
	protected $title = '';

	public function __construct(){
		$this->request = Request::capture();
	}

	protected function render($view, $data) {
		$page = new Page($this->layout, $this->title, $view, $data);
		$view = new View();

		return $view->render($page);
	}

	protected function toJson($value, $options = JSON_UNESCAPED_UNICODE){
		header('Content-Type: application/json');
		echo json_encode($value, $options);
	}
}