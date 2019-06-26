<?php


namespace Mickeyto\SVideo;


use EasySwoole\Curl\Request;
use GuzzleHttp\Client;

/**
 * Class ExtractorAdapter
 * @package Mickeyto\SVideo
 */
class ExtractorAdapter implements ParserInterface
{
    public $requestUrl = '';
    public $http_proxy = '';
    public $_header = [];
    public $_playlist = [];
    public $title = '';
    public $_domain = '';
    private $_headers = [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'zh-CN,en-US;q=0.7,en;q=0.3',
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36',
    ];

    /**
     * @param string $title
     * @param array $search
     */
    public function setTitle(string $title,array $search=[])
    {
        if(count($search) < 1){
            $search = [' ', '\\', '/', '\'', '&', ')', '(', '*'];
        }

        $this->title = str_replace($search, '',$title);
        $this->_playlist['title'] = $this->title;
    }

    public function setPlaylist(array $data)
    {
        if(count($data) > 0){
            $this->_playlist['playlist'] = $data;
        }
    }

    public function playlist(): array
    {
        // TODO: Implement playlist() method.
        return $this->_playlist;
    }

    public function title(): string
    {
        // TODO: Implement title() method.
        return $this->title;
    }

    public function domain():string
    {
        return $this->_domain;
    }

    /**
     * 执行解析
     */
    public function fetch()
    {
        // TODO: Implement fetch() method.
    }

    /**
     * @param string $uri
     * @param string $method
     * @param array/null $header
     *  [
     *     'timeout'         => 0,
 *         'allow_redirects' => false,
 *         'proxy'           => '192.168.16.1:10'
     * ]
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws
     */
    public function httpRequest(string $uri, string $method ='get' , $header=null)
    {
        $httpClient = new Client();
        $this->defaultRequestOptions();
        $optHeader = $this->_headers;

        if(!empty($this->http_proxy)){
            $optHeader['proxy'] = $this->http_proxy;
        }

        if(!empty($header)){
            $optHeader = $header + $optHeader;
        }

        $response = $httpClient->request($method, $uri, $optHeader)->getBody();

        return $response;
    }

    /**
     * http proxy
     * @param string|null $httpProxy
     * @return ExtractorAdapter
     */
    public function setHttpProxy(string $httpProxy=null):self
    {
        $this->http_proxy = $httpProxy;
        return $this;
    }

    public function setHeader(string $name, $value):self
    {
        $this->_headers[$name] = $value;
        return $this;
    }

    /**
     * @param array $header
     * @return $this
     */
    public function setHeaders(array $header):self
    {
        $this->_header = $header;
        return $this;
    }

    final public function defaultRequestOptions():void
    {
        $ip = self::randIp();
        $this->setHeader('HTTP_X_FORWARDED_FOR', $ip);

        foreach($this->_headers as $key => $row){
            $this->setHeader($key, $row);
        }
    }

    /**
     * @return string
     */
    public static function randIp():string
    {
        return rand(50,250).".".rand(50,250).".".rand(50,250).".".rand(50,250);
    }

}