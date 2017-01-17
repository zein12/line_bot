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
				'line_id' => $results['id']
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
						'message' => $results['message']
					]);
					$format = 'genre';
				} else {
					$format = 'address';
				}
				break;

			case 'genre':
				$genre = $this->Mecab->isContainGenre($results['message']);
				if (!empty($genre)) {
					$genreInstance = ClassRegistry::init('Genre');
					$id = Hash::get($conversation, 'Conversation.id');
					$conversationInstance->save(['id' => $id, 'status' => 'recommend']);
					$genreInstance->save([
						'conversation_id' => $id,
						'genre_id' => $genre[0],
						'message' => $results['message']
					]);
					$format = 'recommend';
				} else {
					$format = 'genre';
				}
				break;

			default:
				if (strpos($results['message'], 'お腹すいた') !== false) {
					$data =  [
						'status' => 'address',
						'talk_type' => $results['type'],
						'line_id' => $results['id'],
						'disabled' => 0
					];
					$conversationInstance->save($data);
					$format = 'address';
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
				'line_id' => $id,
				'disabled' => 0
			],
		]);
		return [
			'target_area' => $conversation['Address']['target_area'],
			'genre_id' => $conversation['Genre']['genre_id']
		];
	}
}
