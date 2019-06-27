<?php


namespace Mickeyto\SVideo\Extractor;


use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Helper\ArrayHelper;

class Bilibili extends ExtractorAdapter
{
    public $_domain = 'Bilibili';

    public $referer = 'https://www.bilibili.com';
    public $avid='';
    public $cid = '';
    public $pages = [];

    public function __construct(string $url)
    {
        $this->requestUrl = $url . '/';
        $this->initAvid();
    }

    public function initAvid():void
    {
        preg_match_all("/video\/av(.+?)[\/ | \?]/i", $this->requestUrl, $matchAid);

        $this->avid = isset($matchAid[1]) ? $matchAid[1][0] : '';
    }

    /**
     * @note https://api.bilibili.com/x/web-interface/view?aid=44640089
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setApiCid():void
    {
        if(empty($this->avid)){
            throw new ParserException('aid is empty');
        }

        $aidApi = 'https://api.bilibili.com/x/web-interface/view?aid=' . $this->avid;
        $response = $this->httpRequest($aidApi, 'get', ['Referer' => $this->referer])->getContents();

        if(empty($response)){
            throw new ParserException('cid is empty');
        }

        $json = json_decode($response, true);

        $pages = [];
        if(count($json['data']['pages']) > 0){
            $pages = $json['data']['pages'];
        } else {
            $pages[] = ['cid' => $json['data']['cid']];
        }

        $this->pages = $pages;
        $this->setTitle($json['data']['title']);
    }

    /**
     * @note https://api.bilibili.com/x/player/playurl?avid=44640089&cid=78142881&fnver=0&fnval=16&type=&otype=json
     * @param string $cid
     * @return mixed
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPlayurl($cid='')
    {
        $playurlApi = 'https://api.bilibili.com/x/player/playurl?avid='. $this->avid .'&cid='. $cid .'&fnver=0&fnval=16&type=&otype=json';
        $response = $this->httpRequest($playurlApi, 'get', ['Referer' => $this->referer])->getContents();

        if(empty($response)){
            throw new ParserException('get playurl error');
        }

        $json = json_decode($response, true);
        $json['title'] = $this->title;

        return $json;
    }

    /**
     * @return array
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function multiPageUrl():array
    {
        //pages
        $pagesUrl = [];

        if(count($this->pages) > 0){
            foreach($this->pages as $row){
                $fileSizeArray = [];
                $downloadUrls = [];
                $durl = false;
                $urlJson = $this->getPlayurl($row['cid']);
                if(isset($urlJson['data']['durl'])){
                    $durl = true;
                    $videoQuality = 'flv';
                    $downloadUrls = [$urlJson['data']['durl'][0]['url']];
                    $fileSizeArray = [
                        'totalSize' => $urlJson['data']['durl'][0]['size'],
                        'list' => 1024,
                    ];
                } else {
                    $videoUrlList = ArrayHelper::multisort($urlJson['data']['dash']['video'], 'width', SORT_DESC);
                    $audioList = $urlJson['data']['dash']['audio'];

                    $videoQuality = $videoUrlList[0]['height'];
                    $downloadUrls = [
                        'video_audio' =>[
                            $videoUrlList[0]['baseUrl'],
                            $audioList[0]['baseUrl']
                        ]
                    ];
                }

                $videoTitle = $urlJson['title'] . str_replace([' ', '\\', '/', '\'', '&'], '', $row['part']);

                $pagesUrl[] = [
                    'title' => $videoTitle,
                    'cid' => $row['cid'],
                    'file' => $fileSizeArray,
                    'videoQuality' => $videoQuality,
                    'url' => $downloadUrls,
                    'durl' => $durl
                ];
            }
        }

        return $pagesUrl;
    }

    /**
     * @return array
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function initPlaylist():array
    {
        $this->setApiCid();
        $pagesUrl = $this->multiPageUrl();

        return $pagesUrl;
    }

    /**
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch(): self
    {
        $pagesUrl = $this->initPlaylist();

        $playlist = [];
        $pagesUrlCount = count($pagesUrl);
        if($pagesUrlCount > 0){
            foreach($pagesUrl as $row){
                $title = $row['cid'] . '-' . $row['title'];
                $this->setTitle($title);

                if(isset($row['url']['video_audio'])){
                    $playlist = $row['url']['video_audio'];
                } else {
                    $playlist = $row['url'];
                }
            }

            $this->setPlaylist($playlist);
        }

        return $this;
    }
}