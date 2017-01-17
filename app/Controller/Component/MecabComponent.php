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
		return $areas;
        }

	public function isContainGenre($text) {
		$options = ['-d', '/usr/local/lib/mecab/dic/ipadic/'];
                $mecab = new MeCab_Tagger($options);
                $nodes = $mecab->parseToNode($text);
		$nouns = [];
                foreach ($nodes as $n) {
                        if (strpos($n->getFeature(), '名詞') !== false) {
				$noun[] = $this->ApiCall->getGenreCode($n->getSurface());
                        }
                }
		return $noun;
	}

}
