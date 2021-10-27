<?php

namespace Mickeyto\SVideo\ExtractorV2;

use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Uitls\Curl;

class Kuaishou extends ExtractorAdapter
{
    public $_domain = 'Kuaishou';
    public $_baseApi = 'https://www.kuaishou.com/graphql';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    public function getPhotoId()
    {
        $_urlLen = strlen($this->requestUrl);
        if($_urlLen > 36){
            $_location = $this->requestUrl;
        } else {
            $headers = get_headers($this->requestUrl, 1);
            $_location = $headers && isset($headers['Location']) ? $headers['Location'][1] : '';
        }

        preg_match('/video\/(.*)\?/is', $_location, $matches);
        return $matches && isset($matches[1]) ? $matches[1] : null;
    }

    /**
     * 使用 Graphql 接口
     * @return string
     */
    public function buildGraphqlQuery():String
    {
        return '{"operationName":"visionVideoDetail","variables":{"photoId":"'. $this->getPhotoId() .'","page":"detail"},"query":"query visionVideoDetail($photoId: String, $type: String, $page: String, $webPageArea: String) {\n  visionVideoDetail(photoId: $photoId, type: $type, page: $page, webPageArea: $webPageArea) {\n    status\n    type\n    author {\n      id\n      name\n      following\n      headerUrl\n      __typename\n    }\n    photo {\n      id\n      duration\n      caption\n      likeCount\n      realLikeCount\n      coverUrl\n      photoUrl\n      liked\n      timestamp\n      expTag\n      llsid\n      viewCount\n      videoRatio\n      stereoType\n      croppedPhotoUrl\n      manifest {\n        mediaType\n        businessType\n        version\n        adaptationSet {\n          id\n          duration\n          representation {\n            id\n            defaultSelect\n            backupUrl\n            codecs\n            url\n            height\n            width\n            avgBitrate\n            maxBitrate\n            m3u8Slice\n            qualityType\n            qualityLabel\n            frameRate\n            featureP2sp\n            hidden\n            disableAdaptive\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    tags {\n      type\n      name\n      __typename\n    }\n    commentLimit {\n      canAddComment\n      __typename\n    }\n    llsid\n    danmakuSwitch\n    __typename\n  }\n}\n"}';
    }

    public function getVideosInfo()
    {

    }

    /**
     * 获取视频解析地址
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getItemInfo(): ?array
    {
        $headers = [
            'Connection' => 'keep-alive',
            'User-Agent'=>'Mozilla/5.0 (iPhone; CPU iPhone OS 12_1_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/16D57 Version/12.0 Safari/604.1'
        ];
        $client = new \GuzzleHttp\Client(['timeout' => 10, 'headers' => $headers, 'http_errors' => false,]);
        $data['headers'] = $headers;
        $jar = new \GuzzleHttp\Cookie\CookieJar;
        $data['cookies'] = $jar;
        $response = $client->request('GET', $this->requestUrl, $data);
        $body = $response->getBody();
        if ($body instanceof \GuzzleHttp\Psr7\Stream) {
            $body = $body->getContents();
        }
        $result = htmlspecialchars_decode($body);
        $pattern = '#"srcNoMark":"(.*?)"#';
        preg_match($pattern, $result, $matchVideos);
//        $pattern = '#"poster":"(.*?)"#';
//        preg_match($pattern, $result, $match);
        if (empty($matchVideos[1])) {
            return null;
        }

        return [$matchVideos[1]];
    }

    /**
     * Graphql 获取视频接口
     * @return Array|null
     */
    public function getGraphqlQuery():?Array
    {
        $_headers = [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($this->buildGraphqlQuery())
            ],
        ];
        $response = Curl::postJason($this->_baseApi, $this->requestUrl, $this->buildGraphqlQuery(), $_headers);

        return json_decode($response, 1);
    }

    /**
     * @return $this|void
     * @throws ParserException
     */
    public function fetch()
    {
        $itemInfo = $this->getGraphqlQuery();
        $photoId = $this->getPhotoId();
        if(empty($itemInfo)){
            $_requestUrl = $this->_baseApi . $photoId;
            throw new ParserException('Errors：not fund playaddr》'. $_requestUrl);
        }

        $videosUrl = $itemInfo['data']['visionVideoDetail']['photo']['photoUrl'];
        $this->setTitle($photoId);
        $this->setPlaylist([$videosUrl]);
        return $this;
    }

}