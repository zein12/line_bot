<?php
App::uses('Component', 'Controller');
App::uses('Genre', 'Model');
App::uses('Address', 'Model');

class ConversationComponent extends Component {
	public $components = ['Mecab'];
	public $uses = ['Genre', 'Address', 'Conversation'];

        #メッセージを返すタイプを決める
	public function checkReplyType($events) {
		$results = $this->__parseEvents($events);
		$conversationInstance = ClassRegistry::init('Conversation');
                $conversation = $conversationInstance->find('first', [
                        'conditions'=> [
				'line_id' => $results['id'],
				'Conversation.disabled' => 0
			],
		]);
		switch(Hash::get($conversation, 'Conversation.status')) {
			case 'address':
				$areas = $this->Mecab->isContainArea($results['message']);
				if (!empty($areas)) {
					$addressInstance = ClassRegistry::init('Address');
					$id = Hash::get($conversation, 'Conversation.id');
					$data = [
						'id' => $id,
						'status' => 'genre',
					];
					$conversationInstance->save($data);
					$addressInstance->save([
						'conversation_id' => $id,
						'target_area' => $areas[0],
						'message' => $results['message'],
						'disabled' => 0

					]);
					$format = 'genre';
				} else {
					$format = 'address';
				}
				break;

			case 'genre':
				$genre = $this->Mecab->isContainGenre($results['message']);
				if (!empty($genre) && $genre !== false) {
					$genreInstance = ClassRegistry::init('Genre');
					$id = Hash::get($conversation, 'Conversation.id');
					$conversationInstance->save(['id' => $id, 'status' => 'recommend']);
					$genreInstance->save([
						'conversation_id' => $id,
						'genre_id' => $genre[0],
						'message' => $results['message'],
						'disabled' => 0
					]);
					$format = 'recommend';
				} else {
					$format = 'genre';
				}
				break;

			case 'button':
				//ボタンごとにフォーマット変える
				$id = Hash::get($conversation, 'Conversation.id');
				$query = Hash::get($events, 'events.0.postback.data');
				parse_str($query, $data);

				switch ($data['action']) {
					case 'viewFavorite':
						$conversationInstance->save(['id' => $id, 'status' => 'viewFavorite']);
						$format = 'viewFavorite';
						break;

					case 'viewShops':
						$conversationInstance->save(['id' => $id, 'status' => 'viewShops']);
						$format = 'viewShops';
						break;

					case 'cancel':
						$conversationInstance->save(['id' => $id, 'status' => 'not start']);
						$format = 'not start';
						break;

					default:
						$format = 'button';
						break;
				}
				break;

			default:
				if (strpos($results['message'], 'お腹すいた') !== false) {
					$data =  [
						'status' => 'button',
						'talk_type' => $results['type'],
						'line_id' => $results['id'],
						'disabled' => 0
					];
					$conversationInstance->save($data);
					$format = 'button';
				} else {
					$format = 'not start';
				}
				break;
		}
		return $format;
        }

	private function __parseEvents($events) {
		$type = Hash::get($events, 'events.0.source.type');
		$id = Hash::get($events, 'events.0.source.' . $type . 'Id' );
		$message = Hash::get($events, 'events.0.message.text');
		return ['type' =>$type,  'id' => $id, 'message' => $message];
	}

	public function getQuery($events) {
		$type = Hash::get($events, 'events.0.source.type');
		$id = Hash::get($events, 'events.0.source.' . $type . 'Id' );
		$conversationInstance = ClassRegistry::init('Conversation');
                $conversation = $conversationInstance->find('first', [
                        'conditions'=> [
				'Conversation.line_id' => $id,
				'Conversation.disabled' => 0,
				'Address.disabled' => 0,
				'Genre.disabled' => 0
			],
		]);
		return [
			'target_area' => $conversation['Address']['target_area'],
			'genre_id' => $conversation['Genre']['genre_id']
		];
	}

	public function disableStatus($events) {
		$type = Hash::get($events, 'events.0.source.type');
		$id = Hash::get($events, 'events.0.source.' . $type . 'Id' );
		$conversationInstance = ClassRegistry::init('Conversation');
		$conversation = $conversationInstance->find('first', [
			'conditions' => [
				'line_id' => $id,
				'Conversation.disabled' => 0
			]
		]);
		$conversationInstance->saveAssociated([
			'Conversation' => [
				'id' => $conversation['Conversation']['id'],
				'disabled' => 1
			],
			'Address' => [
				'id' => $conversation['Address']['id'],
				'disabled' => 1
			],
			'Genre' => [
				'id' => $conversation['Genre']['id'],
				'disabled' => 1
			]
		]);
	}
}
