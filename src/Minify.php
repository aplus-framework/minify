<?php namespace Framework\Minify;

/**
 * Class Minify.
 *
 * @see https://github.com/terrylinooo/CodeIgniter-Minifier
 */
class Minify
{
    /**
     * Minify all contents, optionally can disable any.
     *
     * Make preference to use html() if you have not css and js code and have HTML5 tags.
     *
     * @param string $contents
     * @param bool   $enable_html
     * @param bool   $enable_js
     * @param bool   $enable_css
     *
     * @return string
     */
    public static function all(
        string $contents,
        bool $enable_html = true,
        bool $enable_js = true,
        bool $enable_css = true
    ) : string {
        $contents = \trim($contents);

        if ($contents && ! ( ! $enable_html && ! $enable_css && ! $enable_js)) {
            if ($enable_js || $enable_css) {
                // You need php-xml to support PHP DOM
                $dom = new \DOMDocument();

                // Prevent DOMDocument::loadHTML error
                $use_errors = \libxml_use_internal_errors(true);

                $dom->loadHTML($contents, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);

                if ($enable_js) {
                    // Get all script Tags and minify them
                    $scripts = $dom->getElementsByTagName('script');

                    foreach ($scripts as $script) {
                        if ( ! empty($script->nodeValue)) {
                            $script->nodeValue = static::js($script->nodeValue);
                        }
                    }
                }

                if ($enable_css) {
                    // Get all style Tags and minify them
                    $styles = $dom->getElementsByTagName('style');

                    foreach ($styles as $style) {
                        if ( ! empty($style->nodeValue)) {
                            $style->nodeValue = static::css($style->nodeValue);
                        }
                    }
                }

                $new_contents = $enable_html ? static::html($dom->saveHTML()) : $dom->saveHTML();

                \libxml_use_internal_errors($use_errors);
                unset($dom);
            } elseif ($enable_html) {
                $new_contents = static::html($contents);
            }
        }

        return \trim($new_contents ?? $contents);
    }

    /**
     * Minify HTML.
     *
     * @param string $input
     *
     * @see     https://github.com/mecha-cms/mecha-cms/blob/master/engine/kernel/converter.php
     *
     * @return string
     *
     * @author  Taufik Nurrohman
     * @license GPL version 3 License Copyright
     */
    public static function html(string $input) : string
    {
        $input = \trim($input);
        if ($input === '') {
            return $input;
        }

        // Remove extra white-space(s) between HTML attribute(s)
        $input = \preg_replace_callback(
            '#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s',
            static function ($matches) {
                return '<' . $matches[1] . \preg_replace(
                    '#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s',
                    ' $1$2',
                    $matches[2]
                ) . $matches[3] . '>';
            },
            \str_replace("\r", '', $input)
        );

        // Minify inline CSS declaration(s)
        if (\strpos($input, ' style=') !== false) {
            $input = \preg_replace_callback(
                '#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s',
                static function ($matches) {
                    return '<' . $matches[1] . ' style=' . $matches[2] . static::css($matches[3]) . $matches[2];
                },
                $input
            );
        }

        return (string) \preg_replace([
            // t = text
            // o = tag open
            // c = tag close
            // Keep important white-space(s) after self-closing HTML tag(s)
            '#<(img|input)(>| .*?>)#s',
            // Remove a line break and two or more white-space(s) between tag(s)
            '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
            '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s',
            // t+c || o+t
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s',
            // o+o || c+c
            '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s',
            // c+t || t+o || o+t -- separated by long white-space(s)
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s',
            // empty tag
            '#<(img|input)(>| .*?>)<\/\1\x1A>#s',
            // reset previous fix
            '#(&nbsp;)&nbsp;(?![<\s])#',
            // clean up ...

            // Force line-break with `&#10;` or `&#xa;`
            '#&\#(?:10|xa);#',

            // Force white-space with `&#32;` or `&#x20;`
            '#&\#(?:32|x20);#',

            // Remove HTML comment(s) except IE comment(s)
            '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s',
        ], [
            "<$1$2</$1\x1A>",
            '$1$2$3',
            '$1$2$3',
            '$1$2$3$4$5',
            '$1$2$3$4$5$6$7',
            '$1$2$3',
            '<$1$2',
            '$1 ',
            "\n",
            ' ',
            '',
        ], $input);
    }

    /**
     * Minify CSS.
     *
     * @param string $input
     *
     * @see     http://ideone.com/Q5USEF + improvement(s)
     *
     * @return string
     *
     * @author  Unknown, improved by Taufik Nurrohman
     * @license GPL version 3 License Copyright
     */
    public static function css(string $input) : string
    {
        $input = \trim($input);
        if ($input === '') {
            return $input;
        }

        // Force white-space(s) in `calc()`
        if (\strpos($input, 'calc(') !== false) {
            $input = \preg_replace_callback('#(?<=[\s:])calc\(\s*(.*?)\s*\)#', static function ($matches) {
                return 'calc(' . \preg_replace('#\s+#', "\x1A", $matches[1]) . ')';
            }, $input);
        }

        return (string) \preg_replace([
            // Remove comment(s)
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',

            // Remove unused white-space(s)
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',

            // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
            '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',

            // Replace `:0 0 0 0` with `:0`
            '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',

            // Replace `background-position:0` with `background-position:0 0`
            '#(background-position):0(?=[;\}])#si',

            // Replace `0.6` with `.6`, but only when preceded by a white-space or `=`, `:`, `,`, `(`, `-`
            '#(?<=[\s=:,\(\-]|&\#32;)0+\.(\d+)#s',

            // Minify string value
            '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][-\w]*?)\2(?=[\s\{\}\];,])#si',
            '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',

            // Minify HEX color code
            '#(?<=[\s=:,\(]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',

            // Replace `(border|outline):none` with `(border|outline):0`
            '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',

            // Remove empty selector(s)
            '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s',
            '#\x1A#',
        ], [
            '$1',
            '$1$2$3$4$5$6$7',
            '$1',
            ':0',
            '$1:0 0',
            '.$1',
            '$1$3',
            '$1$2$4$5',
            '$1$2$3',
            '$1:0',
            '$1$2',
            ' ',
        ], $input);
    }

    /**
     * Minify JavaScript.
     *
     * Be careful:
     * This method doesn't support "javascript automatic semicolon insertion", you must add
     * semicolon by yourself, otherwise your javascript code will not work and generate error
     * messages.
     *
     * @param string $input
     *
     * @see     https://github.com/mecha-cms/mecha-cms/blob/master/engine/kernel/converter.php
     *
     * @return string
     *
     * @author  Taufik Nurrohman
     * @license GPL version 3 License Copyright
     */
    public static function js(string $input) : string
    {
        $input = \trim($input);
        if ($input === '') {
            return $input;
        }

        return (string) \preg_replace([
            // Remove comment(s)
            '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',

            // Remove white-space(s) outside the string and regex
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',

            // Remove the last semicolon
            '#;+\}#',

            // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
            '#([\{,])([\'])(\d+|[a-z_]\w*)\2(?=\:)#i',

            // --ibid. From `foo['bar']` to `foo.bar`
            '#([\w\)\]])\[([\'"])([a-z_]\w*)\2\]#i',

            // Replace `true` with `!0`
            '#(?<=return |[=:,\(\[])true\b#',

            // Replace `false` with `!1`
            '#(?<=return |[=:,\(\[])false\b#',

            // Clean up ...
            '#\s*(\/\*|\*\/)\s*#',
        ], [
            '$1',
            '$1$2',
            '}',
            '$1$3',
            '$1.$3',
            '!0',
            '!1',
            '$1',
        ], $input);
    }
}
