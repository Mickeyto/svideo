<?php


namespace Mickeyto\SVideo\ExtractorV2;


use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Helper\ArrayHelper;
use Mickeyto\SVideo\Helper\M3u8Helper;

class Iqiyi extends ExtractorAdapter
{
    public $_domain = 'Iqiyi';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @param int $key
     * @return string
     */
    public function getQu(int $key):string
    {
        $vd =  [
            4 => '720P',
            96 => '极速',
            1 => '流畅',
            2 => '高清',
            5 => '1080P',
            10 => '4K',
            6 => '2K',
            3 => '超清',
            19 => '4K',
            17 => '720P',
            14 => '720P',
            21 => '504P'
        ];

        return isset($vd[$key]) ? $vd[$key] : 'Unknown';
    }

    public function getClientTs():float
    {
        $microTime = microtime(true) * 1000;
        $time = round($microTime);
        return $time;
    }

    /**
     * @param string $tvid
     * @param string $vid
     * @return array
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTmts(string $tvid, string $vid):array
    {
        $tmtsUrl = 'http://cache.m.iqiyi.com/jp/tmts/'.$tvid.'/'.$vid.'/?';
        $t = $this->getClientTs();
        $src = '76f90cbd92f94a2e925d83e8ccd22cb7';
        $key = 'd5fb4bd9d50c4be6948c97edd7254b0e';
        $sc = md5($t.$key.$vid);
        $tmtsUrl .= 't='.$t.'&sc='. $sc .'&src='.$src;

        $tmtsInfo = $this->httpRequest($tmtsUrl, 'get', ['Referer' => 'https://wwww.iqiyi.com'])->getContents();
        if(empty($tmtsInfo)){
            throw new ParserException('Errors：get tmts');
        }

        $videoInfo = ltrim($tmtsInfo, 'var tvInfoJs=');
        $tmtsCache = json_decode($videoInfo, true);

        return $tmtsCache;
    }

    /**
     * @return array
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVideosInfo():array
    {
        $html = $this->httpRequest($this->requestUrl, 'get', ['Referer' => $this->requestUrl])->getContents();

        if(!$html){
            throw new ParserException('Errors：request error');
        }

        $pageInfo = $this->matchPageInfo($html);
        if(empty($pageInfo)){
            throw new ParserException('Errors：tvid empty');
        }
        $tvid = $pageInfo['tvId'];
        $vid = $pageInfo['vid'];
        $title = $pageInfo['tvName'];

        if(empty($vid)){
            throw new ParserException('Errors：vid empty');
        }

        $videoInfo = [
            'title' => $title,
            'tvid' => $tvid,
            'vid' => $vid,
        ];

        return $videoInfo;
    }

    /**
     * @param string $str
     * @param string $pattern
     * @return array
     * @throws ParserException
     */
    public function matchPageInfo(string $str, string $pattern="/:page-info='(.+?)'/i"):array
    {
        preg_match_all($pattern, $str, $matches);

        if(!$matches){
            throw new ParserException('Error：div page info is empty');
        }
        $pageInfo = json_decode($matches[1][0], true);

        return $pageInfo;
    }

    /**
     * @param null $argvOpt
     * @return Iqiyi
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch($argvOpt=null):self
    {
        $videoInfo = $this->getVideosInfo();
        $tmtsInfo = $this->getTmts($videoInfo['tvid'], $videoInfo['vid']);
        if($tmtsInfo['code'] != 'A00000'){
            throw new ParserException('Errors：get mus error -》'.$tmtsInfo['code']);
        }

        $vidl = ArrayHelper::multisort($tmtsInfo['data']['vidl'], 'screenSize', SORT_ASC);

        $stremList = [];
        foreach($vidl as $row){
            $stremList[] = [
                'type' => 'm3u8',
                'size' => $row['screenSize'],
                'url' => $row['m3u'],
            ];
        }

        $this->setTitle($videoInfo['title']);
        $this->setPlaylist($stremList);
        return $this;
    }

}