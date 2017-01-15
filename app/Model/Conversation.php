<?php

class Conversation extends AppModel {

	public $useTable = 'conversation';

	public $hasOne = [
                'Address' => [
			'className' => 'Address',
                ],
                'Genre' => [
                        'className' => 'Genre',
                ],
        ];
}
