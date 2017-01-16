<?php
App::uses('Component', 'Controller');

class ConversationComponent extends Component {
	public $uses = ['Genre', 'Address', 'Conversation'];

        #メッセージを返すフォーマットを決める
        public function checkReplyFormat($events) {
		$results = $this->__parseEvents($events);
		$conversationInstance = ClassRegistry::init('Conversation');
                $conversation = $conversationInstance->find('first', [
                        'conditions'=> [ 
				'line_id' => $results['id'] 
			], 
		]);
		$this->log($conversation, 'debug');
                if (!$conversation) {
			$data =  [ 
				'status' => 'address',
				'talk_type' => $results['type'],
				'line_id' => $results['id'],
				'disabled' => 0
			];
			$conversationInstance->save($data);
			return 'address';
                } else if (Hash::get($conversation, 'Conversation.status') == 'address') {
			$addressInstance = ClassRegistry::init('Address');
		} else if (Hash::get($conversation, 'Conversation.genre') == 'genre') {
			$genreInstance = ClassRegistry::init('Genre');
		}
        }

	private function __parseEvents($events) {
		$type = Hash::get($events, 'events.0.source.type');
		$id = Hash::get($events, 'events.0.source.' . $type . 'Id' );
		return ['type' =>$type,  'id' => $id];
	}
}
