<?php

App::uses('Genre', 'Model');
App::uses('Address', 'Model');

class ConversationController extends AppController {
	public $uses = ['Genre', 'Address'];
}


