<?php
App::uses('AppController', 'Controller');

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class CallbackController extends AppController {

	public $components = ['Linebot', 'ApiCall'];
	private $httpClient;
	private $bot;
	private $replyToken;

        public function index() {
		$line = Configure::read('line');
		$events = $this->request->input('json_decode', true);
		$this->httpClient = new CurlHTTPClient($line['accessToken']);
		$this->bot = new LINEBot($this->httpClient, ['channelSecret' => $line['channelSecret']]);
		$this->replyToken = Hash::get($events, 'events.0.replyToken');
                $this->autoRender = false;
                $this->response->type('json');
                $events = $this->request->input('json_decode', true);
		$replyMessage = $this->Linebot->buildReplyMessage($events);
		if ($replyMessage == null || empty($replyMessage)) { return $this->response->statusCode(200); }
                $output = $this->__reply($replyMessage);
                return $this->response->statusCode(200);
        }

	private function __reply($replyMessage) {
		return $this->bot->replyMessage($this->replyToken, $replyMessage);
        }
}
