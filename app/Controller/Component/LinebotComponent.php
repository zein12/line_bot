<?php

App::uses('Component', 'Controller');

use LINE\LINEBot;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\TemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

class LinebotComponent extends Component {
	public $components = ['Mecab', 'ApiCall', 'Conversation'];

	public function buildReplyMessage($events) {
		$type = $this->Conversation->checkReplyType($events);
		switch ($type) {
			case 'address':
				$replyMessage = $this->__textReplyMessage($type);
				break;

			case 'genre':
				$replyMessage = $this->__textReplyMessage($type);
				break;

			case 'button':
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

			case 'viewFavorite':
				$replyMessage = new TextMessageBuilder('お気に入り');
				$this->Conversation->disableStatus($events);
				break;

			case 'viewShops':
				$replyMessage = new TextMessageBuilder('お店を探す');
				$this->Conversation->disableStatus($events);
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
			case 'address':
				$message = new TextMessageBuilder('お店をどこ周辺でお探しですか?');
				break;

			case 'genre':
				$message = new TextMessageBuilder('お店のジャンルを教えてください');
				break;

			case 'button':
				$viewFavorite = new PostbackTemplateActionBuilder('お気に入りを見る', 'action=viewFavorite');
				$viewShops = new PostbackTemplateActionBuilder('お店を探す', 'action=viewShops');
				$cancel = new PostbackTemplateActionBuilder('キャンセル', 'action=cancel');
				$buttonTemplateBuilder = new ButtonTemplateBuilder(null, 'お手伝いしましょうか？', null, [$viewFavorite, $viewShops, $cancel]);
				$message = new TemplateMessageBuilder('PCからの表示は対応していません', $buttonTemplateBuilder);
				break;
		}
		return $message;
	}

	private function __carouselReplyMessage($address, $genreId, $events) {
		$results = $this->ApiCall->getStoreInfo($address, $genreId);	//アドレス, ジャンルを引数に渡せばでる
		$columns = [];
		if (Hash::get($results, 'results.shop') == null) {
			$this->log('hit件数0');
			$this->Conversation->disableStatus($events);
			return $textMessageBuilder = new TextMessageBuilder('ヒットしませんでした');
		}
		foreach ($results['results']['shop'] as $result) {
			$detail = new PostbackTemplateActionBuilder('詳細', 'action=detail');
			$browser = new UriTemplateActionBuilder('Open in Browser', $result['urls']['pc']);
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
