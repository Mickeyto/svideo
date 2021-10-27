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

    public function kuaishouTest()
    {
        $sVideo = new SVideo(null, 'V2');
        $sVideo->_version = 'V2';
        $parser = $sVideo->parser('https://v.kuaishou.com/hK7vbx');
        $parser->fetch();
        var_dump($parser->playlist());
    }

}