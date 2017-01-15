<?php

App::uses('AppController', 'Controller');

class CallbackController extends AppController {

	public $components = ['Mecab'];

        public function index() {
                $this->autoRender = false;
                $this->response->type('json');
                $events = $this->request->input('json_decode', true);
		$result = $this->__parseEvents($events);
                $replyMessage = json_encode(
                        [
                                'replyToken' => Hash::get($events, 'events.0.replyToken'),
                                'messages' => [
                                        [
                                                'type' => 'text',
                                                'text' => $result["type"] . ':'  . $result['value']
                                        ]
                                ]
                        ]
                );
                $output = $this->__reply($replyMessage);
                $this->log($output);
                return $this->response->statusCode(200);
        }

	private function __parseEvents($events) {
		if (Hash::get($events, 'events.0.message.type') == 'location') {
			$type = 'location';
			$value = Hash::get($events, 'events.0.message.address');
		} else {
			$type = 'others';
			$value = Hash::get($events, 'events.0.message.text');
			$areas = $this->Mecab->__isContainArea($value);
		}

		return [ 'type' => $type, 'value' => $value ];
	}

	 private function __reply($replyMessage) {
                $curl = curl_init('https://api.line.me/v2/bot/message/reply');
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $this->__getHeaders());
                curl_setopt($curl, CURLOPT_POSTFIELDS, $replyMessage);
                return curl_exec($curl);
        }

	private function __getHeaders() {
                return  [
                        "Content-Type: application/json; charset=UTF-8",
                        "Authorization: Bearer <<TOKEN>>"
		];
        }
}
