<?php

App::uses('Component', 'Controller');

class MecabComponent extends Component {

	public $components = ['ApiCall'];

	public function __isContainArea($text) {
                $options = ['-d', '/usr/local/lib/mecab/dic/ipadic/'];
                $mecab = new MeCab_Tagger($options);
                $nodes = $mecab->parseToNode($text);
		$areas = [];
                foreach ($nodes as $n) {
                        if (strpos($n->getFeature(), '地域') !== false) {
				$areas[] = $n->getSurface();
                        }
                }
		return $areas;
        }

	public function __isContainGenre($text) {
		$options = ['-d', '/usr/local/lib/mecab/dic/ipadic/'];
                $mecab = new MeCab_Tagger($options);
                $nodes = $mecab->parseToNode($text);
                foreach ($nodes as $n) {
                        if (strpos($n->getFeature(), '名詞') !== false) {
				$res = $this->ApiCall->getGenreCode($n->getSurface());
				$this->log($res, 'debug');
                        }
                }
		return $areas;
	}

}
