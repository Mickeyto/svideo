<?php


namespace Mickeyto\SVideo\Extractor;


use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Helper\ArrayHelper;

class Youku extends ExtractorAdapter
{
    public $_domain = 'Youku';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    public function getClientTs():float
    {
        $microTime = microtime(true);
        $time = round($microTime, 3);
        return $time;
    }

    /**
     * @return string
     * @throws ParserException
     */
    public function getUtid():string
    {
        $logEgJs = file_get_contents('https://log.mmstat.com/eg.js');

        //goldlog.Etag="字符"
        $rule = '/Etag="(.+?)"/';
        preg_match_all($rule, $logEgJs, $matches);
        if(!$matches){
            throw new ParserException('must be utid');
        }

        $utid = urlencode($matches[1][0]);

        return $utid;
    }

    public function getCKey():string
    {
        $ckey = 'DIl58SLFxFNndSV1GFNnMQVYkx1PP5tKe1siZu/86PR1u/Wh1Ptd+WOZsHHWxysSfAOhNJpdVWsdVJNsfJ8Sxd8WKVvNfAS8aS8fAOzYARzPyPc3JvtnPHjTdKfESTdnuTW6ZPvk2pNDh4uFzotgdMEFkzQ5wZVXl2Pf1/Y6hLK0OnCNxBj3+nb0v72gZ6b0td+WOZsHHWxysSo/0y9D2K42SaB8Y/+aD2K42SaB8Y/+ahU+WOZsHcrxysooUeND';

        return urlencode($ckey);
    }

    public function getVid():?string
    {
        if(empty($this->requestUrl)){
            return '';
        }
        $rule  = '/id_(.+?).html/';
        preg_match_all($rule, $this->requestUrl, $matches);
        if($matches){
            return $matches[1][0];
        }

        return '';
    }

    /**
     * @return Youku
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch():self
    {
        $vid = $this->getVid();
        $ckey = $this->getCKey();
        $clientTs = $this->getClientTs();
        $utid = $this->getUtid();
        $httpReferer = 'https://tv.youku.com';
        $ccode = '0521';

        $youkuVideoUrl = 'https://ups.youku.com/ups/get.json?vid='. $vid .'&ccode=' . $ccode . '&client_ip=192.168.1.1&client_ts=' . $clientTs .'&utid=' . $utid .'&ckey=' . $ckey;

        $json = $this->httpRequest($youkuVideoUrl, 'get', ['Referer' => $httpReferer])->getContents();
        $result = json_decode($json, true);

        if(!isset($result['data']['stream'])){
            throw new ParserException("Parser Error");
        }

        $videosTitle = $result['data']['video']['title'];

        $this->setTitle($videosTitle);
        $videosInfo = ArrayHelper::multisort($result['data']['stream'], 'width', SORT_DESC);

        $streamList = [];
        foreach($videosInfo as $row){
            if(is_array($row['segs'])){
                foreach($row['segs'] as $val){
                    $streamList[] = [
                        'size' => $val['size'],
                        'url' => $val['cdn_url'],
                    ];
                }
            }
        }

        $this->setPlaylist($streamList);
        return $this;

    }

    public function playlist(): array
    {
        return $this->_playlist;
    }

    public function title(): string
    {
        return $this->title;
    }
}