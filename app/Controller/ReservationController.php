<?php

App::uses('AppController', 'Controller');
App::uses('Conversation', 'Model');


class ReservationController extends AppController {

	public function index() {
	}

	public function enqueue($id, $results) {
		$conversation = $this->Conversation->find('first', [
			'id' => $id
		]);
		$this->Conversation->save([
			'talk_type' => $type,
			'line_id' => $line_id,
			'tel' => $tel,
			'status' => 'waiting',
			'disabled' => 0
		]);
	}
}
