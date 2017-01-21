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
		$addressInstance = ClassRegistry::init('Address');
		$genreInstance = ClassRegistry::init('Genre');
		$conversationInstance = ClassRegistry::init('Conversation');
                $conversation = $conversationInstance->find('first', [
                        'conditions'=> [
				'line_id' => $results['id'],
				'Conversation.disabled' => 0
			],
		]);
		switch(Hash::get($conversation, 'Conversation.status')) {
			case 'inquiry':
				$areas = $this->Mecab->isContainArea($results['message']);
				$genre = $this->Mecab->isContainGenreOrFood($results['message']);
				$id = Hash::get($conversation, 'Conversation.id');
				if (!empty($areas) && $genre !== false) {
					$conversationInstance->save([ 'id' => $id, 'status' => 'recommend' ]);
					$addressInstance->save([
						'conversation_id' => $id,
						'target_area' => $areas[0],
						'message' => $results['message'],
						'disabled' => 0
					]);
					$genreInstance->save([
						'conversation_id' => $id,
						'key_type' => $genre['type'],
						'genre_id' => $genre['id'],
						'message' => $results['message'],
						'disabled' => 0
					]);
					$format = 'recommend';
				} else if (empty($areas) && $genre == false) {
					$format = 'inquiry';
				} else if (empty($areas)) {
					$data = [
						'id' => $id,
						'status' => 'address',
					];
					$conversationInstance->save($data);
					$genreInstance->save([
						'conversation_id' => $id,
						'key_type' => $genre['type'],
						'genre_id' => $genre['id'],
						'message' => $results['message'],
						'disabled' => 0

					]);
					$format = 'address';
				} else if ($genre == false) {
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
				}
				break;

			case 'address':
				$areas = $this->Mecab->isContainArea($results['message']);
				if (!empty($areas)) {
					$addressInstance = ClassRegistry::init('Address');
					$id = Hash::get($conversation, 'Conversation.id');
					$format = (!empty($conversation['Genre']['genre_id']))? 'recommend' : 'genre';
					$data = [
						'id' => $id,
						'status' => $format,
					];
					$conversationInstance->save($data);
					$addressInstance->save([
						'conversation_id' => $id,
						'target_area' => $areas[0],
						'message' => $results['message'],
						'disabled' => 0

					]);
				} else {
					$format = 'address';
				}
				break;

			case 'genre':
				$genre = $this->Mecab->isContainGenreOrFood($results['message']);
				if (!empty($genre) && $genre !== false) {
					$genreInstance = ClassRegistry::init('Genre');
					$id = Hash::get($conversation, 'Conversation.id');
					$format = (!empty($conversation['Address']['target_area']))? 'recommend' : 'address';
					$conversationInstance->save(['id' => $id, 'status' => $format]);
					$genreInstance->save([
						'conversation_id' => $id,
						'genre_id' => $genre['id'],
						'key_type' => $genre['type'],
						'message' => $results['message'],
						'disabled' => 0
					]);
				} else {
					$format = 'genre';
				}
				break;
			case 'postback':
				$id = Hash::get($conversation, 'Conversation.id');
				$format = 'postback';
				$conversationInstance->save(['id' => $id, 'status' => $format]);
				break;

			default:
				if (strpos($results['message'], '二徹') !== false ||strpos($results['message'], 'にてつ') !== false ) {
					$data =  [
						'status' => 'inquiry',
						'talk_type' => $results['type'],
						'line_id' => $results['id'],
						'disabled' => 0
					];
					$conversationInstance->save($data);
					$format = 'inquiry';
				} else if ($results['messageType'] === 'postback' && $results['postbackData'] !== '') {
					$format = 'postback';
				} else {
					$format = 'not start';
				}
				break;
		}
		return $format;
        }

	private function __parseEvents($events) {
		$this->log($events, 'debug');
		$type = Hash::get($events, 'events.0.source.type');
		$messageType = Hash::get($events, 'events.0.type');
		$postbackData = Hash::get($events, 'events.0.postback.data');
		$id = Hash::get($events, 'events.0.source.' . $type . 'Id' );
		$message = Hash::get($events, 'events.0.message.text');
		return ['messageType' => $messageType, 'type' =>$type, 'id' => $id, 'message' => $message, 'postbackData' => $postbackData];

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
			'key_type' => $conversation['Genre']['key_type'],
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
