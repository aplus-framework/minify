<?php
/*
 * This file is part of Aplus Framework Minify Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Minify;

use Framework\Minify\Minify;
use PHPUnit\Framework\TestCase;

final class MinifyTest extends TestCase
{
    public function testCss() : void
    {
        $contents = <<<'CSS'
            body {
                color: black;
            }

            div {
                height: 200px;
                width: calc(10px + 100px);
            }
            CSS;
        $expected = 'body{color:black}div{height:200px;width:calc(10px + 100px)}';
        self::assertSame($expected, Minify::css($contents));
        self::assertSame('', Minify::css(''));
    }

    public function testHtml() : void
    {
        $contents = <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Title</title>
            </head>
            <body style="color: black">
              Hello!
            </body>
            </html>
            HTML;
        $expected = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Title</title></head><body style="color:black">Hello!</body></html>';
        self::assertSame($expected, Minify::html($contents));
        self::assertSame('', Minify::html(''));
    }

    public function testJs() : void
    {
        $contents = <<<'JS'
            var a = new Date();
            console.log( a.getDate() )
            JS;
        $expected = 'var a=new Date();console.log(a.getDate())';
        self::assertSame($expected, Minify::js($contents));
        self::assertSame('', Minify::js(''));
    }

    public function testAll() : void
    {
        $contents = <<<'ALL'
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Title</title>
                <style>
                    body {
                        color: black
                    }
                </style>
            </head>
            <body style="color: black">
                Hello!
                <script>
                    var a = new Date();
                    console.log( a.getDate() )
                </script>
            </body>
            </html>
            ALL;
        $expected = <<<'EXP'
            <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Title</title><style>body{color:black}</style></head><body style="color:black">Hello! <script>var a=new Date();console.log(a.getDate())</script></body></html>
            EXP;
        self::assertSame($expected, Minify::all($contents));
        $expected = <<<'EXP'
            <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Title</title><style>body {
                        color: black
                    }</style></head><body style="color:black">Hello! <script>var a = new Date();
                    console.log( a.getDate() )</script></body></html>
            EXP;
        self::assertSame($expected, Minify::all($contents, true, false, false));
        self::assertSame('', Minify::all(''));
    }
}
