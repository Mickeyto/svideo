<?php
namespace Mickeyto\SVideo;

use Mickeyto\SVideo\Exception\ParserException;

class SVideo
{
    public $_object = [];
    public $_url = '';

    public function __construct(string $url = null)
    {
        if($url !== null) {
            $this->_url = $url;
        }
        $this->scanExtractorDir();
    }

    public function extractor(string $name)
    {
        return $this->_object[$name] = '\Mickeyto\SVideo\Extractor\\' . $name;
    }

    /**
     * @param string $url
     * @return ExtractorAdapter
     * @throws ParserException
     */
    public function parser(string $url=null):ExtractorAdapter
    {
        if(!empty($url)){
            $this->_url = $url;
        }
        $matches = $this->matchDomain($this->_url);

        if(empty($matches)){
            throw new ParserException('Error');
        }

        if(!isset($this->_object[$matches])){
            throw new ParserException('No Support');
        }

        $class = new $this->_object[$matches]($this->_url);
        return $class;
    }

    public function matchDomain(string $url,int $index=1):?string
    {
        $preg = '/([a-z0-9][-a-z0-9]{0,62})\.(com\.cn|com\.hk|cn|com|net|edu|gov|biz|org|info|pro|name|xxx|xyz|be|me|top|cc|tv|tt|space)/';

        preg_match_all($preg, $url, $matches);

        if(count($matches) > 1){
            if(!empty($matches[$index])){
                $domain = $matches[$index][0];
                if(in_array($domain, ['91p25', '91porn'])){
                    $domain = 'Porn';
                }
                $domain = ucwords($domain);
                return $domain;
            }
        }

        return null;
    }

    /**
     * 扫描已支持平台
     */
    final private function scanExtractorDir():void
    {
        if(count($this->_object) < 1){
            $files = scandir(__DIR__ . '/Extractor');
            foreach($files as $row){
                if('.' === $row || '..' === $row){
                    continue;
                }
                $this->extractor(substr($row,0, -4));
            }
        }

    }

}