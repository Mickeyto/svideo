<?php

namespace Mickeyto\SVideo\ExtractorV2;

use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;

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
     * 暂不可使用
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function buildGraphqlQuery():string
    {
        $_requestParams = [
            'operationName' => 'visionVideoDetail',
            'query' => 'query visionVideoDetail($photoId: String, $type: String, $page: String, $webPageArea: String) {
  visionVideoDetail(photoId: $photoId, type: $type, page: $page, webPageArea: $webPageArea) {
    status
    type
    author {
      id
      name
      following
      headerUrl
      __typename
    }
    photo {
      id
      duration
      caption
      likeCount
      realLikeCount
      coverUrl
      photoUrl
      liked
      timestamp
      expTag
      llsid
      viewCount
      videoRatio
      stereoType
      croppedPhotoUrl
      manifest {
        mediaType
        businessType
        version
        adaptationSet {
          id
          duration
          representation {
            id
            defaultSelect
            backupUrl
            codecs
            url
            height
            width
            avgBitrate
            maxBitrate
            m3u8Slice
            qualityType
            qualityLabel
            frameRate
            featureP2sp
            hidden
            disableAdaptive
            __typename
          }
          __typename
        }
        __typename
      }
      __typename
    }
    tags {
      type
      name
      __typename
    }
    commentLimit {
      canAddComment
      __typename
    }
    llsid
    danmakuSwitch
    __typename
  }
}
',
            'variables' => [
                'pcursor' => 'detail',
                'photoId' => $this->getPhotoId(),
            ],
        ];


//        echo json_encode($_requestParams);die;

//        echo $this->_baseApi . PHP_EOL;die;



        $s = $this->requestAsync($this->_baseApi, 'post', [
            'form_params' => $_requestParams,
        ])->withAddedHeader('Referer', $this->requestUrl)
            ->withAddedHeader('Host', 'video.kuaishou.com')
            ->withAddedHeader('Origin', 'https://video.kuaishou.com')
        ->withAddedHeader('User-Agent', 'Paw/3.2.1 (Macintosh; OS X/12.0.1) GCDHTTPRequest')
        ->withAddedHeader('Date', 'Tue, 26 Oct 2021 16:14:46 GMT')
        ->withAddedHeader('Content-Type', 'application/json');
        var_export($s->getBody()->getContents());

        return 'test';
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
     * @return $this|void
     * @throws ParserException
     */
    public function fetch()
    {
        $itemInfo = $this->getItemInfo();
        $photoId = $this->getPhotoId();
        if(empty($itemInfo)){
            $_requestUrl = $this->_baseApi . $photoId;
            throw new ParserException('Errors：not fund playaddr》'. $_requestUrl);
        }

        $this->setTitle($photoId);
        $this->setPlaylist($itemInfo);
        return $this;
    }

}