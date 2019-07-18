<?php


namespace Mickeyto\SVideo\Extractor;


use Mickeyto\Curl\Request;
use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Helper\ArrayHelper;
use Mickeyto\SVideo\Helper\M3u8Helper;

class Twitter extends ExtractorAdapter
{
    public $_domain = 'Twitter';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @return string|null
     * @throws ParserException
     */
    public function getVid():?string
    {
        preg_match('/\d{10,}/', $this->requestUrl, $matches);

        if(!isset($matches[0])){
            throw new ParserException('Error：vid not number');
        }

        return $matches[0];
    }

    /**
     * @param string|null $vid
     * @return array
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVideos(string $vid=null):array
    {
        if(empty($vid)){
            $vid = $this->getVid();
        }
        $authorization = 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA';

        $api = 'https://api.twitter.com/1.1/statuses/show.json?include_profile_interstitial_type=1&include_blocking=1&include_blocked_by=1&include_followed_by=1&include_want_retweets=1&include_mute_edge=1&include_can_dm=1&skip_status=1&cards_platform=Web-12&include_cards=1&include_ext_alt_text=true&include_reply_count=1&tweet_mode=extended&trim_user=false&include_ext_media_color=true&id='. $vid .'&ext=mediaStats,highlightedLabel';

        $curlHeader = $this->httpHeader($authorization);

        $this->setHeader('headers', $curlHeader);
//        $getInfo = $this->httpRequest($api, 'get', ['Referer' => $this->requestUrl])->getContents();
        $getInfo = $this->customGet($api, $authorization);

        if(empty($getInfo)){
            throw new ParserException('Error：get videos info ');
        }

        $json = json_decode($getInfo, true);

        if(isset($json['errors'])){
            throw new ParserException('Error：' . $json[0]['message']);
        }

        $userName = str_replace([' ', '\\', '/', '\''], '', $json['user']['name']);

        $title = $userName . '-' . $vid;

        if(isset($json['quoted_status'])){
            $videoInfo = $json['quoted_status']['extended_entities']['media'][0]['video_info']['variants'];
        } else {
            $videoInfo = $json['extended_entities']['media'][0]['video_info']['variants'];
        }

        return [
            'title' => $title,
            'list' => $videoInfo,
        ];
    }

    public function httpHeader(string $authorization=''):array
    {
        $ip = $this->randIp();
        $curlHeader = [
            'CLIENT-IP' => "{$ip}",
            'Authorization' => "{$authorization}",
        ];

        return $curlHeader;
    }

    /**
     * @param array $videosInfo
     * @return array
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getM3u8(array $videosInfo):array
    {
        $curlHeader = $this->httpHeader();
        $baseHost = parse_url($videosInfo['application/x-mpegURL'][0]['url'], PHP_URL_HOST);
        $m3u8Urls = $this->httpRequest($videosInfo['application/x-mpegURL'][0]['url'], $this->requestUrl, $curlHeader);

        $result = [];
        if($m3u8Urls[0]){
            $m3u8Urls = M3u8Helper::getUrls($m3u8Urls[0]);
            $url = array_pop($m3u8Urls);
            $m3u8Url = $baseHost . trim($url);

            $m3u8Urls = $this->httpRequest($m3u8Url, $this->requestUrl, $curlHeader);
            if(empty($m3u8Urls[0])){
                throw new ParserException('Error：m3u8 empty');
            }

            $m3u8Url = M3u8Helper::getUrls($m3u8Urls[0]);
            if(!empty($m3u8Url)){
                foreach($m3u8Url as $row){
                    $result[] = 'https://' . $baseHost . trim($row);
                }
            } else {
                throw new ParserException('Error：m3u8 empty');
            }
        }

        return $result;
    }

    /**
     * @return Twitter
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch(): self
    {
        $videoInfo = $this->getVideos();
        $videosInfoGroup = ArrayHelper::group($videoInfo['list'], 'content_type');

        $this->setTitle($videoInfo['title']);
        if(isset($videosInfoGroup['video/mp4'])){
            $videoUrlInfo = ArrayHelper::multisort($videosInfoGroup['video/mp4'], 'bitrate', SORT_DESC);

            $this->setPlaylist($videoUrlInfo);
        }

        return $this;
    }

    public function customHeader(string $authorization=''):array
    {
        $ip = $this->randIp();
        $curlHeader = [
            CURLOPT_HTTPHEADER => [
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language: zh-CN,en-US;q=0.7,en;q=0.3",
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "HTTP_X_FORWARDED_FOR: {$ip}",
                "CLIENT-IP: {$ip}",
                "Authorization: {$authorization}",
            ],
            CURLOPT_PROXY => $this->http_proxy,
        ];

        return $curlHeader;
    }

    public function customGet(string $uri, string $authorization=null)
    {
        $client = new Request();
        $client->setUrl($uri);
        $client->setOptions($this->customHeader($authorization));
        $response = $client->exec()->content;

        return $response;
    }

}