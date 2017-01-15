<?php

App::uses('Component', 'Controller');

class MecabComponent extends Component {

        public function initialize(Controller $controller) {
                $this->controller = $controller;
        }

	public function __isContainArea($text) {
                $options = ['-d', '/usr/local/lib/mecab/dic/ipadic/'];
                $mecab = new MeCab_Tagger($options);
                $nodes = $mecab->parseToNode($text);
		$areas = [];
                foreach ($nodes as $n) {
                        if (strpos($n->getFeature(), '地域') !== false) {
				$areas[] = $n->getSurface();
				$this->log($areas, 'debug');
                        }
                }
		return $areas;
        }	

}
