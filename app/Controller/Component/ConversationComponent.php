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
				$areas = $this->Mecab->__isContainArea($results['message']);
				if (!empty($areas)) {
					$addressInstance = ClassRegistry::init('Address');
					$id = Hash::get($conversation, 'Conversation.id');
					$data = [
						'id' => $id,
						'status' => 'genre',
						'message' => $areas[0]
					];
					$conversationInstance->save($data);
					$addressInstance->save(['conversation_id' => $id, 'message' => $results['message']]);
					$format = 'genre';
				} else {
					$format = 'address';
				}
				break;

			case 'genre':
				$genre = $this->Mecab->__isContainGenre($results['message']);
				$genreInstance = ClassRegistry::init('Genre');
	                        $id = Hash::get($conversation, 'Conversation.id');
				//$format = 'recommend';
				$format = 'genre';
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
}
