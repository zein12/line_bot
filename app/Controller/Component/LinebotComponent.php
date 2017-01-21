<?php

App::uses('Component', 'Controller');
App::uses('RedirectController', 'Controller');

use LINE\LINEBot;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;

class LinebotComponent extends Component {
	public $components = ['Mecab', 'ApiCall', 'Conversation'];

	public function buildReplyMessage($events) {
		$type = $this->Conversation->checkReplyType($events);
		switch ($type) {
			case 'inquiry':
				$replyMessage = $this->__textReplyMessage($type);
				break;

			case 'address':
				$replyMessage = $this->__textReplyMessage($type);
				break;

			case 'genre':
				$replyMessage = $this->__textReplyMessage($type);
				break;

			case 'recommend':
				$results = $this->Conversation->getQuery($events);
				$replyMessage = $this->__carouselReplyMessage($results['target_area'], $results['genre_id'], $events);
				$this->Conversation->disableStatus($events);
				break;

			case 'location':
				$replyMessage = $this->__locationReplyMessage($events);
				break;

			case 'text':
				if (Hash::get($events, 'events.0.message.text') === 'カルーセル') {
					$replyMessage = $this->__carouselReplyMessage($events);
				}
				break;

			case 'carousel':
				$replyMessage = $this->__carouselReplyMessage($events);
				break;

			case 'not start':
				$replyMessage = false;
				break;

			default:
				if (Hash::get($events, 'events.0.type') === 'postback') {
					$replyMessage = $this->__postbackReplyMessage($events);
				}
				break;
		}
		return $replyMessage;
	}

	private function __textReplyMessage($type) {
		switch ($type) {
			case 'inquiry':

				$textMessageBuilder = new TextMessageBuilder("どんなお店を探していますか? \r\n (例: 渋谷駅でイタリアン)");
				break;

			case 'genre':
				$textMessageBuilder = new TextMessageBuilder("ジャンルを教えてください:) ");
				break;

			case 'address':
				$textMessageBuilder = new TextMessageBuilder("場所を教えてください:) ");
				break;

		}
		return $textMessageBuilder;
	}

	private function __carouselReplyMessage($address, $genreId, $events) {
		$redirectController = new RedirectController();
		$results = $this->ApiCall->getStoreInfo($address, $genreId);	//アドレス, ジャンルを引数に渡せばでる
		$columns = [];
		if (Hash::get($results, 'results.shop') == null) {
			$this->log('hit件数0');
			$this->Conversation->disableStatus($events);
			return $textMessageBuilder = new TextMessageBuilder('その地域ではhitしませんでした');
		}
		foreach ($results['results']['shop'] as $result) {
			$detail = new PostbackTemplateActionBuilder('詳細', 'action=detail');
			$browser = new UriTemplateActionBuilder('Open in Browser', $redirectController->buildRedirectUrl($result['urls']['pc'], $events));
			$maps = new PostbackTemplateActionBuilder('地図を見る', 'action=map&address=' . $result['address'] . '&lat=' . $result['lat'] . '&lng=' . $result['lng']);
			$result['name'] = mb_strimwidth($result['name'], 0, 40, "...", "UTF-8");
			$result['catch'] = mb_strimwidth($result['catch'], 0, 40, "...", "UTF-8");

			$column = new CarouselColumnTemplateBuilder($result['name'], $result['catch'], $result['photo']['mobile']['l'], [$detail, $browser, $maps]);
			$columns[] = $column;
		}

		$carousel = new CarouselTemplateBuilder($columns);
		$carousel_message = new TemplateMessageBuilder("PCからの表示は対応していません", $carousel);

		return $carousel_message;
	}

	private function __postbackReplyMessage($events) {
		$query = Hash::get($events, 'events.0.postback.data');
                parse_str($query, $data);

		switch ($data['action']) {
			case 'map':
				$postback = new LocationMessageBuilder('お店の地図を表示します', $data['address'], $data['lat'], $data['lng']);
				break;

			case 'detail':
				$postback = new TextMessageBuilder('予算とか細かいの載せる');
				break;
		}

		return $postback;
	}
}
