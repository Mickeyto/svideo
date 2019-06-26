<?php


namespace MickeyTo\SVideo;


interface ParserInterface
{
    public function playlist():array ;

    public function title():string ;

    public function domain():string ;

    public function fetch();
}