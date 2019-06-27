<?php

namespace Mickeyto\Test;

use Mickeyto\SVideo\SVideo;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @test
     * @throws \Mickeyto\SVideo\Exception\ParserException
     */
    public function weiboTest()
    {
        $svido = new SVideo();
//        $parser = $svido->parser('https://weibo.com/tv/v/HARDiD19b?fid=1034:4387859407250945');
        $parser = $svido->parser('https://weibo.com/tv/v/HARhfoVe1?fid=1034:4387845943795564');
//        $parser->setHttpProxy('http://127.0.0.1:1087');

        $parser->setHeader('Cookie', 'SUBP=0033WrSXqPxfM725Ws9jqgMF55529P9D9WW3PsCb55p3CXxHR9oDv0OW5JpX5KzhUgL.FozN1KefehzXS052dJLoIEXLxKBLB.BLBK5LxKnL1hBLBo2LxKnLBoBLB-zLxKBLB.2L1hqLxK-L1K5L1KMt;  SUB=_2A25xqLwdDeRhGeRJ4lEU8CzIzDyIHXVS36rVrDV8PUNbn9BeLWTXkW9NUklEG2ghQmTSYlBq7Ojj_RI2o5TCBO6W;');
        $parser->fetch();
        var_export($parser->playlist());

        $this->assertEmpty($parser->playlist());
    }

    /**
     * @test
     * @throws \Mickeyto\SVideo\Exception\ParserException
     */
    public function pornhubTest()
    {
        $svido = new SVideo();
        $parser = $svido->parser('https://www.pornhub.com/view_video.php?viewkey=ph5c0aa01a52aaa');
        $parser->setHttpProxy('http://127.0.0.1:1087');
        $parser->fetch();

        $this->assertEmpty($parser->playlist());
    }

    /**
     * @test
     */
    public function twitterTest()
    {
        $svido = new SVideo();
        $parser = $svido->parser('https://twitter.com/i/status/1142189380661989376');
//        $parser->setHttpProxy('http://127.0.0.1:1087');
        $parser->fetch();

        $this->assertEmpty($parser->playlist());
    }

    /**
     * @test
     * @throws \Mickeyto\SVideo\Exception\ParserException
     */
    public function pornTest()
    {
        $svido = new SVideo();
        $parser = $svido->parser('http://91porn.com/view_video.php?viewkey=edc5bf40273f98d59bb6&page=9&viewtype=basic&category=mr');
//        $parser->setHttpProxy('http://127.0.0.1:1087');
        $parser->fetch();

        $this->assertEmpty($parser->playlist());

    }

    /**
     * @test
     * @throws \Mickeyto\SVideo\Exception\ParserException
     */
    public function iqiyiTest()
    {
        $svido = new SVideo();
        $parser = $svido->parser('http://www.iqiyi.com/w_19s1z2krpp.html');
        $parser->fetch();

        $this->assertEmpty($parser->playlist());
    }

    /**
     * @test
     * @throws \Mickeyto\SVideo\Exception\ParserException
     */
    public function toutiaoimgTest()
    {
        $svido = new SVideo();
        $parser = $svido->parser('https://m.toutiaoimg.com/group/6704875330928116228/?app=news_article&timestamp=1561439252&group_id=6704875330928116228');

//        $parser->setHttpProxy('http://127.0.0.1:1087');
        $parser->fetch();

        $this->assertEmpty($parser->playlist());
    }

    /**
     * @test
     * @throws \Mickeyto\SVideo\Exception\ParserException
     */
    public function youkuTest()
    {
        $svido = new SVideo();
        $parser = $svido->parser('http://v.youku.com/v_show/id_XNDI0MDk1MjQxNg==.html?spm=a2h0z.8244218.2371631.d6373');
        $parser->fetch();

        $this->assertEmpty($parser->playlist());
    }

    /**
     * @test
     * @throws \Mickeyto\SVideo\Exception\ParserException
     */
    public function bilibiliTest()
    {
        $svido = new SVideo();
        $parser = $svido->parser('https://www.bilibili.com/video/av56862102');
        $parser->fetch();

        $this->assertEmpty($parser->playlist());
    }

}