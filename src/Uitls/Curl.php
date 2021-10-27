<?php

namespace Mickeyto\SVideo\Uitls;

class Curl
{
    /**
     * POST 提交 jason 数据
     * @param $url
     * @param $httpReferer
     * @param $dataStr
     * @param $options
     * @return String
     */
    public static function postJason($url, $httpReferer, $dataStr, $options = []):String
    {
        $ch = curl_init();
        $defaultOptions = self::defaultOptions($url, $httpReferer);
        if($options){
            $defaultOptions = $options + $defaultOptions;
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt_array($ch, $defaultOptions);
        $chContents = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);

        curl_close($ch);

        return $chContents;
    }

    public static function randIp()
    {
        return mt_rand(20,250).".".mt_rand(20,250).".".mt_rand(20,250).".".mt_rand(20,250);
    }

    /**
     * @param $url
     * @param $httpReferer
     * @param bool|string $ip
     * @return array
     */
    public static function defaultOptions($url, $httpReferer, $ip=false)
    {
        if(!$ip){
            $ip = self::randIp();
        }

        return [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_REFERER => $httpReferer,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
//                "Accept-Encoding:gzip, deflate, br",
                "Accept-Language:zh-CN,en-US;q=0.7,en;q=0.3",
                "HTTP_X_FORWARDED_FOR:{$ip}",
                "CLIENT-IP:{$ip}"
            ]
        ];
    }

}