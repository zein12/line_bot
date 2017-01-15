<?php

App::uses('Component', 'Controller');

class LinebotComponent extends Component {
	public $components = ['Mecab'];

	public function buildReplyMessage($events) {
		switch (Hash::get($events, 'events.0.message.type')) {
			case 'location':
				$replyMessage = $this->__locationReplyMessage($events);
				break;
			case 'text':
				$replyMessage = $this->__textReplyMessage($events);
				break;
			case 'carousel':
				$replyMessage = $this->__carouselReplyMessage($events);
				break;
		}
		return $replyMessage;
	}

	private function __textReplyMessage($events) {
		return json_encode(
			[
				'replyToken' => Hash::get($events, 'events.0.replyToken'),
                                'messages' => [
					[
                                                'type' => 'text',
                                                'text' => Hash::get($events, 'events.0.message.type') . ':' . Hash::get($events, 'events.0.message.text')
                                        ]
                                ]
                        ]
		);
	}
}
