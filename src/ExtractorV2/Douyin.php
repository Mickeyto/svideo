<?php

namespace Mickeyto\SVideo\ExtractorV2;

use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;

class Douyin extends ExtractorAdapter
{
    public $_domain = 'Douyin';
    public $_baseApi = 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * 返回重定向 url
     * @return false|mixed
     */
    public function getLocation()
    {
        $headers = get_headers($this->requestUrl, 1);
        return $headers && isset($headers['Location']) ? $headers['Location'][0] : false;
    }

    /**
     * 获取 VID
     * @return false|mixed
     */
    public function getVid()
    {
        $locationUrl = $this->getLocation();
        if(!$locationUrl){
            return false;
        }

        preg_match('/\d+/', $locationUrl, $matchers);
        return $matchers && isset($matchers[0]) ? $matchers[0] : false;
    }

    /**
     * 获取视频
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVideoItemInfo()
    {
        $apiUrl = $this->_baseApi . $this->getVid();
        $responseJson = $this->requestAsync($apiUrl, 'get')->getBody()->getContents();
        return $responseJson ? json_decode($responseJson, 1) : false;
    }

    public function fetch()
    {
        $itemInfo = $this->getVideoItemInfo();
        if(!$itemInfo){
            $_requestUrl = $this->_baseApi . $this->getVid();
            throw new ParserException('Errors：not fund playaddr》'. $_requestUrl);
        }

        $this->setTitle($itemInfo['item_list'][0]['aweme_id']);
        $this->setPlaylist($itemInfo['item_list'][0]['video']['play_addr']['url_list']);

        return $this;
    }
}