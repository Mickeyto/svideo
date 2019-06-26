<?php


namespace Mickeyto\SVideo\Extractor;


use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Helper\ArrayHelper;

class Toutiaoimg extends ExtractorAdapter
{
    public $_domain = 'Toutiaoimg';
    public $referer = '';
    public $locationHref = 'https://www.365yg.com/a';
    public $vid = '';
    public $jsonp = 'axiosJsonpCallback1';


    public function __construct(string $url)
    {
        $this->requestUrl = $url;
        $this->initVid();
    }

    /**
     *
     */
    public function initVid():void
    {
        preg_match_all('/group\/(.*?)\??\//', $this->requestUrl, $matches);

        $this->vid = isset($matches[1][0]) ? $matches[1][0] : '';
    }

    /**
     * 生成随机数，要小于1
     * @return string
     */
    public function generateR():string
    {
        $avgRand = mt_rand() / mt_getrandmax();
        return ltrim($avgRand, '0.');
    }

    /**
     * 字符串循环冗余检验
     * @param string $str
     * @return int
     */
    public function generateS(string $str):int
    {
        $s = crc32($str);

        return $s;
    }

    /**
     * 时间戳
     * @return int
     */
    public function getMicrotime():int
    {
        $microTime = microtime(true);
        $seconds = round($microTime, 3);

        return $seconds;
    }

    /**
     * @return string
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function matchVideoIdAndTitle():string
    {
        if(empty($this->vid)){
            throw new ParserException('vid is empty');
        }

        $locationUrl = $this->locationHref . $this->vid;
        $response = $this->httpRequest($locationUrl, 'get', ['Referer' => $locationUrl])->getContents();

        if(empty($response)){
            throw new ParserException('location url error');
        }

        preg_match_all('/videoId:\s?\'(.*)\'/i', $response, $matches);
        preg_match('/<title>(.*?)<\/title>/i', $response, $matchesTitle);

        if(!isset($matches[1][0])){
            throw new ParserException('videoId matches error');
        }
        if(isset($matchesTitle[1])){
            $this->setTitle($matchesTitle[1]);
        } else {
            $this->setTitle('toutiao-' . $this->vid);
        }

        return $matches[1][0];
    }

    /**
     * @param string $vid
     * @return array|bool|null
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function videoDetail(string $vid)
    {
        $r = $this->generateR();
        $_s = $this->getMicrotime();

        $apiUrl = 'https://ib.365yg.com';
        $locationPathname = '/video/urls/v/1/toutiao/mp4/'. $vid .'?r=' . $r;

        $s = $this->generateS($locationPathname);
        $apiUrl .= $locationPathname .'&s='. $s .'&aid=1364&vfrom=xgplayer&callback=' . $this->jsonp .'&_=' . $_s;

        $response = $this->httpRequest($apiUrl, 'get', ['Referer' => $this->referer])->getContents();

        if(empty($response)){
            throw new ParserException('request api error');
        }

        $callback = ltrim($response, $this->jsonp . '(');
        $callback = rtrim($callback, ')');
        $json = json_decode($callback, true);

        $newArray = ArrayHelper::multisort($json['data']['video_list'], 'vwidth', SORT_DESC);

        return $newArray;
    }

    /**
     * @return Toutiaoimg
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch(): self
    {
        $videoId = $this->matchVideoIdAndTitle();
        $videoDetail = $this->videoDetail($videoId);
        $urlTotal = count($videoDetail);

        $arrayKey = 'video_'.$urlTotal;
        $videosInfo = $videoDetail[$arrayKey];
        $videoUrl = base64_decode($videosInfo['main_url']);

        $this->setPlaylist([['size' => $videosInfo['definition'], 'url' => $videoUrl]]);

        return $this;
    }
}