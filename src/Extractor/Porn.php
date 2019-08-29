<?php


namespace Mickeyto\SVideo\Extractor;


use Mickeyto\SVideo\Exception\ParserException;
use Mickeyto\SVideo\ExtractorAdapter;
use Mickeyto\SVideo\Helper\StringHelper;

class Porn extends ExtractorAdapter
{
    public $_domain = 'Porn';
    public $_str = '';
    public $_str2 = '';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    public function base64Decode(string $str):string
    {
        return base64_decode($str);
    }

    /**
     * 解密设置参数
     * @param string $str1
     * @param string $str2
     * @param mixed ...$arg
     * @return Porn
     */
    public function setStr(string $str1, string $str2, ...$arg):self
    {
        $this->_str = $this->base64Decode(trim($str1, '"'));
        $this->_str2 = trim($str2, '"');
        return $this;
    }

    /**
     * 解密 code
     * @return string
     */
    public function generateCode():string
    {
        $_code = '';
        $strLen = strlen($this->_str);
        $str2Len = strlen($this->_str2);

        for($i = 0; $i < $strLen; $i++){
            $k = $i % $str2Len;
            $_code .= StringHelper::fromCharCode(ord($this->_str[$i]) ^ ord($this->_str2[$k]));
        }

        return $_code;
    }

    /**
     * 解析资源
     * @param string $arg
     * @return string
     */
    public function strencode(string $arg)
    {
        $array = explode(',', $arg);
        $this->setStr($array[0], $array[1]);
        $code = $this->generateCode();

        return $code;
    }

    /**
     * @return array|null
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVideosUrl():?array
    {
        $html = $this->httpRequest($this->requestUrl,'get' , ['Referer' => $this->requestUrl])->getContents();

        if(empty($html)){
            throw new ParserException('Error：not found html');
        }

        preg_match_all('/<div\sid="viewvideo-title">\s*(.*)\s*<\/div>/', $html, $matchesTitle);
        if(!isset($matchesTitle[1][0])){
            throw new ParserException('Error：not found title');
        }

        preg_match_all('/<source\ssrc="(.*)"\stype=["|\']video\/mp4["|\']>/', $html, $matchesVideo);
        if(!isset($matchesVideo[1][0])){
            preg_match_all('/strencode\((.*)\)\);/', $html, $matchesVideo);
            if(!isset($matchesVideo[1][0])){
                throw new ParserException('Error：not found mp4 source');
            } else {
                $param = $matchesVideo[1][0];
                $code = $this->base64Decode($this->strencode($param));
                preg_match_all('/src=\'(.*)\'\stype=\'video\/mp4\'>/', $code, $matchesVideo);
            }
        }

        $title = str_replace(PHP_EOL, '', $matchesTitle[1][0]);
        $this->setTitle($title);

        $videosUrl = $matchesVideo[1][0];
        $videosInfo = [
            [
                'type' => 'mp4',
                'url' => $videosUrl,
                'title' => $this->title,
                'size' => 0,
            ]
        ];

        if(!$videosUrl){
            throw new ParserException('Error：videos url is empty');
        }

        return $videosInfo;

    }

    /**
     * @param null $argvOpt
     * @return Porn
     * @throws ParserException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch($argvOpt=null):self
    {
        $videosUrl = $this->getVideosUrl();
        $this->setPlaylist($videosUrl);

        return $this;
    }
}