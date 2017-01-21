<?php

App::uses('Component', 'Controller');

class MecabComponent extends Component {

	public $components = ['ApiCall'];

	public function isContainArea($text) {
                $options = ['-d', '/usr/local/lib/mecab/dic/ipadic/'];
                $mecab = new MeCab_Tagger($options);
                $nodes = $mecab->parseToNode($text);
		$areas = [];
                foreach ($nodes as $n) {
                        if (strpos($n->getFeature(), '地域') !== false) {
				$areas[] = $n->getSurface();
                        }
                }
		$areas = $this->__checkStation($areas);
		return $areas;
        }

	private function __checkStation($areas) {
		if ($areas[1] == '駅') {
			$areas[0] = $areas[0] . $areas[1];
		}
		return $areas;
	}


	public function isContainGenreOrFood($text) {
		$options = ['-d', '/usr/local/lib/mecab/dic/ipadic/'];
                $mecab = new MeCab_Tagger($options);
                $nodes = $mecab->parseToNode($text);
		$nouns = [];
                foreach ($nodes as $n) {
                        if (strpos($n->getFeature(), '名詞') !== false) {
				$noun[] = $this->ApiCall->getGenreCode($n->getSurface());
                        }
                }
		$noun = array_filter($noun, "strlen");
		$noun = array_values($noun);
		if (empty($noun)) {
			$noun = $this->isContainFood($text);
		} else {
			$noun['type'] = 'genre';
			$noun['id'] = $noun[0];
		}
		return $noun;
	}

	public function isContainFood($text) {
		$options = ['-d', '/usr/local/lib/mecab/dic/ipadic/'];
		$mecab = new MeCab_Tagger($options);
		$nodes = $mecab->parseToNode($text);
		$foods = [];
		foreach ($nodes as $n) {
			if (strpos($n->getFeature(), '名詞') !== false) {
				$foods[] = $this->ApiCall->getFoodCode($n->getSurface());
			}
		}
		$foods = array_filter($foods, 'strlen');
		$foods = array_values($foods);
		if (empty($foods)) {
			$foods = false;
		} else {
			$foods['type'] = 'food';
			$foods['id'] = $foods[0];
		}
		return $foods;
	}
}
