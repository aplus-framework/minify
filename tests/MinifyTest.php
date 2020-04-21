<?php namespace Tests\Minify;

use Framework\Minify\Minify;
use PHPUnit\Framework\TestCase;

class MinifyTest extends TestCase
{
	public function testCSS()
	{
		$contents = <<<CSS
body {
	color: black
}
CSS;
		$expected = 'body{color:black}';
		$this->assertEquals($expected, Minify::css($contents));
		$this->assertEquals('', Minify::css(''));
	}

	public function testHTML()
	{
		$contents = <<<HTML
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
		$this->assertEquals($expected, Minify::html($contents));
		$this->assertEquals('', Minify::html(''));
	}

	public function testJS()
	{
		$contents = <<<JS
var a = new Date();
console.log( a.getDate() )
JS;
		$expected = 'var a=new Date();console.log(a.getDate())';
		$this->assertEquals($expected, Minify::js($contents));
		$this->assertEquals('', Minify::js(''));
	}

	public function testAll()
	{
		$contents = <<<ALL
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
		$expected = <<<EXP
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Title</title><style>body{color:black}</style></head><body style="color:black">Hello!	<script>var a=new Date();console.log(a.getDate())</script></body></html>
EXP;
		$this->assertEquals($expected, Minify::all($contents));
		$this->assertEquals('', Minify::all(''));
	}
}
