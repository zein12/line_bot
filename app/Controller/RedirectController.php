<?php
App::uses('AppController', 'Controller');

class RedirectController extends AppController {

	public function index() {
		$id = $this->request->query['redirectId'];
		$link = $this->Redirect->find('first', [
			'conditions' => [
				'id' => $id,
				'access_flag' => 0
			]
		]);
		if (empty($link)) {
			throw new NotFoundException();
		}
		$this->Redirect->id = $link['Redirect']['id'];
		$this->Redirect->save(['access_flag' => 1]);
		$this->redirect(Hash::get($link, 'Redirect.redirect_url'));
	}

	public function buildRedirectUrl ($url, $events) {
		$type = Hash::get($events, 'events.0.source.type');
		$id = Hash::get($events, 'events.0.source.' . $type . 'Id' );
		$this->Redirect->create();
		#アクセスされたらフラグを1にする
		$this->Redirect->save([
			'talk_type' => $type,
			'line_id' => $id,
			'redirect_url' => $url,
			'access_flag' => 0
		]);
		$id = $this->Redirect->getLastInsertID();
		return "https://nxtg-t.net/~hasegawa_takuya/line_bot/redirect?redirectId=$id";
	}

}
