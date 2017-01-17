<?php

/**
 * Created by PhpStorm.
 * User: Tatsuya Takahata
 * Date: 2017/01/14
 * Time: 17:27
 */
App::uses('Component', 'Controller');

class ApiCallComponent extends Component
{
    private $apiKey = '280093b7e1baee72';
    private $requestUrl = 'http://webservice.recruit.co.jp/hotpepper/gourmet/v1/?';

    public function getStoreInfo($address = null, $genre = null)
    {
        $url = $this->requestUrl . 'key=' . $this->apiKey . '&format=json&count=5&address='. $address .'&genre=' . $this->getGenreCode($genre);

        return $this->__sendCurl($url);
    }

    private function getGenreCode($keyword)
    {
        $url = 'http://webservice.recruit.co.jp/hotpepper/genre/v1/?key=' . $this->apiKey . '&format=json&keyword=' . urlencode($keyword);
        $results = $this->__sendCurl($url);

        foreach ($results as $result) {
            return $result['genre'][0]['code'];
        }
    }

    private function __sendCurl($url)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt_array($ch, $options);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $result;
    }
}
