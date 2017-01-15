<?php
App::uses('AppController', 'Controller');


class CallbackController extends AppController {

	public $components = ['Mecab', 'ApiCall'];

	public function carousel() {
		try {
			$i = 0;
			$this->autoRender = false;
			$this->response->type('json');

			$events = $this->request->input('json_decode', true);

			if (Hash::get($events, 'events.0.type') === 'postback') {
				$query = Hash::get($events, 'events.0.postback.data');
				parse_str($query, $data);

				if (isset($data['action'], $data['lat'], $data['lng']) && $data['action'] === 'map') {
					$replyMessage = json_encode([
							'replyToken' => Hash::get($events, 'events.0.replyToken'),
							'messages' => [
							[
							'type' => 'location',
							'title' => 'お店の地図を表示します',
							'address' => $data['address'],
							'latitude' => $data['lat'],
							'longitude' => $data['lng']
							]
							]
					], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

					$output = $this->__reply($replyMessage);
					$this->log($output, "debug");
					$this->log($replyMessage, 'debug');
				}
			}

			if (Hash::get($events, 'events.0.message.text') === 'カルーセル') {
				$results = $this->ApiCall->getStoreInfo(null, null);
				$this->log($results, 'debug');

				foreach ($results['results']['shop'] as $result) {
					//if ($i === 5) break;

					$val[] = [
						'thumbnailImageUrl' => $result['photo']['mobile']['l'],
						'title' => $result['name'],
						'text' => $result['catch'],
						'actions' => [
							[
								'type' => 'message',
						'label' => 'もっと詳しく',
						'text' => "$result[address]\r\n平均予算:$result[budget][average]"
							],
							[
								'type' => 'uri',
							'label' => 'ブラウザで確認',
							'uri' => $result['urls']['pc']
							],
							[
								'type' => 'postback',
							'label' => '地図を表示',
							'data' => 'action=map&address=' . $result['address'] . '&lat=' . $result['lat'] . '&lng=' . $result['lng']
							]
						]
						];
							//$i++;
				}

				$replyMessage = json_encode([
						'replyToken' => Hash::get($events, 'events.0.replyToken'),
						'messages' => [
						[
						'type' => 'template',
						'altText' => 'store information',
						'template' => [
						'type' => 'carousel',
						'columns' => $val
						]
						]
						]
				], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

				$output = $this->__reply($replyMessage);
				$this->log($output, "debug");
				$this->log($replyMessage, 'debug');
			} else {
				$replyMessage = json_encode(
						[
						'replyToken' => Hash::get($events, 'events.0.replyToken'),
						'messages' => [
						[
						'type' => Hash::get($events, 'events.0.message.type'),
						'text' => Hash::get($events, 'events.0.message.text')
						]
						]
						]
						);
				$output = $this->__reply($replyMessage);
				$this->log($output, 'debug');
			}
			return $this->response->statusCode(200);
		} catch (Exception $e) {
		}
	}

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
