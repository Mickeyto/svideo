<?php

namespace Mickeyto\Test;

use Mickeyto\SVideo\SVideo;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
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