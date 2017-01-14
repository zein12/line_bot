<?php

App::uses('AppController', 'Controller');

class CallbackController extends AppController {

        public function index() {
                $this->autoRender = false;
                $this->response->type('json');
                $events = $this->request->input('json_decode', true);
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
                return $this->response->statusCode(200);
        }

        private function __getHeaders() {
                return  [
                        "Content-Type: application/json; charset=UTF-8",
                        "Authorization: Bearer <<AccessToken>>"
                ];
        }


	 private function __reply($replyMessage) {
                $curl = curl_init('https://api.line.me/v2/bot/message/reply');
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $this->__getHeaders());
                curl_setopt($curl, CURLOPT_POSTFIELDS, $replyMessage);
                return curl_exec($curl);
        }
}
