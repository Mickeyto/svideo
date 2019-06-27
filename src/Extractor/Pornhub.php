<?php


namespace Mickeyto\SVideo\Extractor;


use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Helper\ArrayHelper;

class Pornhub extends ExtractorAdapter
{
    public $_domain = 'Pornhub';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @param array|null $curlOptions
     * @return string|null
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVideosJson(?array $curlOptions=[]):?string
    {
        $html = $this->httpRequest($this->requestUrl, 'get', ['Referer' => 'https://www.pornhub.com/'])->getContents();
        if(empty($html)){
            throw new ParserException('request pornhub error');
        }

        preg_match_all('/<div\sid="player"\sclass="original\smainPlayerDiv"\sdata-video-id="(.*)">/', $html, $matchesVid);

        if(!isset($matchesVid[1][0])){
            throw new ParserException('无法解析该视频');
        }

        $patter = "/flashvars_{$matchesVid[1][0]} = (.*?)};/is";
        preg_match_all($patter, $html, $matches);

        if(!isset($matches[1][0])){
            throw new ParserException('无法解析该视频真实地址');
        }

        return $matches[1][0] . '}';
    }

    /**
     * @param $videosJson
     * @return array
     */
    public function getVideosList($videosJson):array
    {
        $videosLists = json_decode($videosJson, true);

        $this->setTitle($videosLists['video_title']);

        $videosList = array_filter($videosLists['mediaDefinitions'], function($var){
            if(!empty($var['videoUrl'])){
                return $var;
            }
        });

        return $videosList;
    }

    /**
     * @return Pornhub
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch():self
    {
        $videosJson = $this->getVideosJson();
        $videosList = $this->getVideosList($videosJson);

        if(!$videosList){
            throw new ParserException('No video found');
        }

        $videosList = ArrayHelper::multisort($videosList, 'quality', SORT_DESC);

        $playlist = [];
        foreach($videosList as $row){
            if(is_array($row['quality'])){
                continue;
            }

            if($row['format'] == 'mp4'){
                $playlist[] = [
                    'size' => $row['quality'],
                    'format' => $row['format'],
                    'url' => $row['videoUrl'],
                ];
            }
        }

        $this->setPlaylist($playlist);
        return $this;
    }
}