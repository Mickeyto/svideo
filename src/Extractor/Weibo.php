<?php


namespace Mickeyto\SVideo\Extractor;


use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Helper\ArrayHelper;

class Weibo extends ExtractorAdapter
{
    public $_domain = 'Weibo';
    private const WEIBO_VIDEO_OBJECT_API = 'https://m.weibo.cn/s/video/object?';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @return string
     */
    public function getVid():string
    {
        $vid = '';
        $url = parse_url($this->requestUrl, PHP_URL_PATH);

        $urls = explode('v/', $url);
        if(isset($urls[2])){
            $vid = $urls[2];
        }

        if(empty($vid)){
            $urls = explode('/', $url);
            if(isset($urls[2])){
                $vid = $urls[2];
            }
        }

        return $vid;
    }

    /**
     * 需用户登录 cookie，匹配 html video
     * @param string $html
     * @param string $pattern
     * @return array
     * @throws ParserException
     */
    public function matchHtmlVideo(string $html, $pattern='/video-sources="fluency=(.*?)"/'):array
    {
        preg_match_all($pattern, $html, $matches);
        if(!isset($matches[1][0])){
            throw new ParserException('无法获取视频，请更新配置文件 weiboCookie 值');
        }
        $urlInfo = urldecode($matches[1][0]);

        preg_match_all('/&480=(.*?)video&/i', $urlInfo, $video_480);
        preg_match_all('/&720=(.*?)video&/i', $urlInfo, $video_720);
        preg_match_all('/<div\sclass="info_txt\sW_f14">(.*)<\/div>/', $html, $matchTitle);

        $videoTitle = isset($matchTitle[1][0]) ? $matchTitle[1][0] : md5($urlInfo);

        $videoInfo = [
            'type' => 'login',
            'title' => $videoTitle
        ];
        if(isset($video_480[1][0])){
            $videoInfo['videoInfo'][480] = [
                'qType' => 480,
                'url' => $video_480[1][0] . 'video&'
            ];
        }

        if(isset($video_720[1][0])){
            $videoInfo['videoInfo'][720] = [
                'qType' => 720,
                'url' => $video_720[1][0] . 'video&'
            ];
        }

        return $videoInfo;
    }

    /**
     * url：https://m.weibo.cn/statuses/show?id=Gte2peqo6
     * @param string $vid
     * @return array|null
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVideosInfo(string $vid):?array
    {
        $getJsonUrl = 'https://m.weibo.cn/statuses/show?id=' . $vid;

        $getInfo = $this->httpRequest($getJsonUrl, 'get', ['Referer' => $this->requestUrl])->getContents();
        $videosInfo = [];

        if(!empty($getInfo)){
            $json = json_decode($getInfo, true);
            if(1 != $json['ok']){

                $html = $this->httpRequest($this->requestUrl, 'get', ['Referer' => $this->requestUrl])->getContents();

                $urlInfo = $this->matchHtmlVideo($html);
                return $urlInfo;
            }

            if(empty($json['data']['page_info']['media_info'])){
                $videosInfo = $this->getVideoObject();
                if($videosInfo){
                    return $videosInfo;
                }

                throw new ParserException('Error：media_info is empty');
            }

            $mediaInfo = array_values($json['data']['page_info']['media_info']);
            $mediaInfo = array_slice($mediaInfo, 0, count($mediaInfo)-1);
            $streamInfo = array_keys($json['data']['page_info']['media_info']);
            $streamInfo = array_slice($streamInfo, 0, count($streamInfo)-1);

            $videoTitle = md5($this->requestUrl);
            if(!empty($json['data']['page_info']['title'])){
                $videoTitle = $json['data']['page_info']['title'];
            }

            $videosInfo = [
                'type' => 'api',
                'title' => $videoTitle,
                'url' => $mediaInfo,
                'size' => $json['data']['page_info']['video_details']['size'],
                'stream' => $streamInfo,
            ];
        }

        return $videosInfo;
    }

    /**
     * @return array
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVideoObject():array
    {
        $parseurlInfo = parse_url($this->requestUrl, PHP_URL_QUERY);
        parse_str($parseurlInfo, $params);
        if(!isset($params['object_id'])){
            throw new ParserException('not object_id');
        }

        if(!isset($params['blog_mid'])){
            throw new ParserException('not blog_mid');
        }

        $apiUrl = self::WEIBO_VIDEO_OBJECT_API;
        $apiUrl .= 'object_id=' . $params['object_id']  . '&mid=' . $params['blog_mid'];
        $videoObject = $this->httpRequest($apiUrl, 'get', ['Referer' => $this->requestUrl])->getContents();

        if(empty($videoObject)){
            throw new ParserException('Error: get video object');
        }

        $json = json_decode($videoObject, true);
        $videoData = $json['data'];
        if(!is_array($videoData['object']['author'])){
            throw new ParserException('not found author');
        }

        $title = $videoData['object']['author']['screen_name'] . '-' . $params['object_id'];
        $mediaInfo = [];
        if(isset($videoData['object']['stream']['hd_url'])){
            array_push($mediaInfo, $videoData['object']['stream']['hd_url']);
        } else {
            array_push($mediaInfo, $videoData['object']['stream']['url']);
        }

        $videosInfo = [
            'type' => 'api',
            'title' => $title,
            'url' => $mediaInfo,
            'size' => 1024,
            'stream' => [$videoData['object']['stream']['width']],
        ];

        return $videosInfo;
    }

    /**
     * @return Weibo
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch(): self
    {
        $vid = $this->getVid();

        if(empty($vid)){
            throw new ParserException('Error：vid is empty');
        }

        $videosInfo = $this->getVideosInfo($vid);

        if(empty($videosInfo)){
            throw new ParserException('Error：VideosInfo is empty');
        }

        $playlist = [];
        if($videosInfo['type'] == 'api'){
            $streamSize = count($videosInfo['stream']);
            for($i=$streamSize-1; $i >= 0; --$i){
                $playlist[] = [
                    'size' => $videosInfo['stream'][$i],
                    'url' => $videosInfo['url'][$i],
                ];
            }
        } else {
            $videoList = ArrayHelper::multisort($videosInfo['videoInfo'], 'qType', SORT_DESC);
            foreach($videoList as $row){
                $playlist[] = [
                    'size' => $row['qType'],
                    'url' => $row['url']
                ];
            }
        }

        $this->setTitle($videosInfo['title']);
        $this->setPlaylist($playlist);

        return $this;
    }
}