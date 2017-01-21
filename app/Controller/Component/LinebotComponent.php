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
		$this->log('LinebotComponent [type] ' . $type, 'debug');
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
				if (isset($type['adverbs']) && !empty($type['adverbs'])) {
					$replyMessage = $this->__replyMessageByAdverbs($results['target_area'], $results['key_type'], $results['genre_id'], $events, $type['adverbs']);
				} else {
					$replyMessage = $this->__carouselReplyMessage($results['target_area'], $results['key_type'], $results['genre_id'], $events);
				}
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

			case 'postback':
				$replyMessage = $this->__postbackReplyMessage($events);
				break;

			default:
				break;
		}
		return $replyMessage;
	}

	private function __textReplyMessage($type) {
		switch ($type) {
			case 'inquiry':
				$textMessageBuilder = new TextMessageBuilder("どこ?何食べたいの? ｸﾞｱ\r\n (例: 渋谷駅でイタリアン)");
				break;

			case 'genre':
				$textMessageBuilder = new TextMessageBuilder("何食べたいの? ｸﾞｱ");
				break;

			case 'address':
				$textMessageBuilder = new TextMessageBuilder("どこで食べるの? ｸﾞｱ");
				break;

		}
		return $textMessageBuilder;
	}

	private function __carouselReplyMessage($address, $type, $genreId, $events) {
		$redirectController = new RedirectController();
		$results = $this->ApiCall->getStoreInfo($address, $type, $genreId);//アドレス, ジャンルを引数に渡せばでる
		$columns = [];
		if (Hash::get($results, 'results.shop') == null) {
			$this->log('hit件数0');
			return $textMessageBuilder = new TextMessageBuilder('見つかんなかったぞ ｸﾞｧ');
		}
		foreach ($results['results']['shop'] as $result) {
			$detail = new PostbackTemplateActionBuilder('詳細', 'action=detail&name=' . $result['name'] . '&catch=' . $result['catch'] . '&aveBudget=' . $result['budget']['average'] . '&access=' . $result['access']);
			$browser = new UriTemplateActionBuilder('ブラウザで開く', $redirectController->buildRedirectUrl($result['urls']['pc'], $events));
			$maps = new PostbackTemplateActionBuilder('地図を見る', 'action=map&address=' . $result['address'] . '&lat=' . $result['lat'] . '&lng=' . $result['lng']);
			$result['name'] = mb_strimwidth($result['name'], 0, 40, '', 'UTF-8');
			$result['catch'] = mb_strimwidth($result['catch'], 0, 30, '', 'UTF-8');
			$text = mb_strimwidth($result['catch'] . "\r\n【予算】" . $result['budget']['average'] . "\r\n【アクセス】" . $result['access'], 0, 90, '...', 'UTF-8');

			$column = new CarouselColumnTemplateBuilder($result['name'], $text, $result['photo']['mobile']['l'], [$detail, $browser, $maps]);
			$columns[] = $column;
		}

		$carousel = new CarouselTemplateBuilder($columns);
		$carousel_message = new TemplateMessageBuilder("PCからの表示は対応していません", $carousel);

		return $carousel_message;
	}

	private function __replyMessageByAdverbs($address, $type, $genreId, $events, $adverbs) {
		$redirectController = new RedirectController();
		$results = $this->ApiCall->getStoreInfo($address, $type, $genreId, $adverbs);//アドレス, ジャンルを引数に渡せばでる
		$columns = [];
		if (Hash::get($results, 'results.shop') == null) {
			$this->log('hit件数0');
			return $textMessageBuilder = new TextMessageBuilder('見つかんなかったぞ ｸﾞｧ');
		}
		foreach ($results['results']['shop'] as $result) {
			$detail = new PostbackTemplateActionBuilder('詳細', 'action=detail&name=' . $result['name'] . '&catch=' . $result['catch'] . '&aveBudget=' . $result['budget']['average'] . '&access=' . $result['access']);
			$browser = new UriTemplateActionBuilder('ブラウザで開く', $redirectController->buildRedirectUrl($result['urls']['pc'], $events));
			$maps = new PostbackTemplateActionBuilder('地図を見る', 'action=map&address=' . $result['address'] . '&lat=' . $result['lat'] . '&lng=' . $result['lng']);
			$result['name'] = mb_strimwidth($result['name'], 0, 40, '', 'UTF-8');
			$result['catch'] = mb_strimwidth($result['catch'], 0, 30, '', 'UTF-8');
			$text = mb_strimwidth($result['catch'] . "\r\n【予算】" . $result['budget']['average'] . "\r\n【アクセス】" . $result['access'], 0, 90, '...', 'UTF-8');

			$column = new CarouselColumnTemplateBuilder($result['name'], $text, $result['photo']['mobile']['l'], [$detail, $browser, $maps]);
			$columns[] = $column;
		}

		$carousel = new CarouselTemplateBuilder($columns);
		$carousel_message = new TemplateMessageBuilder("PCからの表示は対応していません", $carousel);;
		$message = new MultiMessageBuilder();
		$message->add(new TextMessageBuilder("これはどうかなｸﾞ"));
		$message->add($carousel_message);
		return message;
	}

	private function __postbackReplyMessage($events) {
		$query = Hash::get($events, 'events.0.postback.data');
                parse_str($query, $data);

		switch ($data['action']) {
			case 'map':
				$postback = new LocationMessageBuilder('お店の地図を表示します', $data['address'], $data['lat'], $data['lng']);
				break;

			case 'detail':
				$postback = new TextMessageBuilder($data['name'] . "\r\n\r\n" . $data['catch'] . "\r\n\r\n【平均予算】" . $data['aveBudget'] . "\r\n【アクセス】" . $data['access']);
				break;
		}

		return $postback;
	}
}
