<?php
/**
 * Class Minify_CSS_Linearizer  
 * @package Minify
 */

/**
 * Linearize a CSS file
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_CSS_Linearizer {
    
    public static $filesIncluded = array();
    
    public static function linearize($file)
    {
        self::$filesIncluded = array();
        $obj = new Minify_CSS_Linearizer(dirname($file));
        return $obj->_getStyles($file);
    }
    
    private $_currentDir = null;
    
    private function __construct($currentDir)
    {
        $this->_currentDir = $currentDir;
    }
    
    private function _getStyles($file)
    {
        if (false === ($css = @file_get_contents($file))) {
            return '';
        }
        self::$filesIncluded[] = $file;
        $this->_currentDir = dirname($file);
        
        // TODO: rewrite relative URIs (non-imports)
        $css = preg_replace_callback(
            '/
				(?<!@import\\s)                
            	url\\(\\s*([^\\)\\s]+)\\s*\\)
            /x'
            ,array($this, '_urlCB')
            ,$css
        );
        
        // replace @imports with contents of files
        $css = preg_replace_callback(
            '/
                @import\\s+
                (?:url\\(\\s*)?[\'"]?
                (.*?)                      # 1 = URI
                [\'"]?(?:\\s*\\))?
                ([a-zA-Z,\\s]*)?           # 2 = media list
                ([;\\{])                   # 3 = put this back in
            /x'
            ,array($this, '_importCB')
            ,$css
        );
        
        return $css;
    }
    
    private function _importCB($m)
    {
        $url = $m[1];
        $mediaList = preg_replace('/\\s+/', '', $m[2]);
        $endToken = $m[3] === '{' 
            ? ':' 
            : '';
        
        if (strpos($url, '://') > 0) {
            // protocol, leave import in place
            return $m[0];
        }
        if ('/' === $url[0]) {
            // protocol-relative or root path
            $url = ltrim($url, '/');
            $file = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR
                . strtr($url, '/', DIRECTORY_SEPARATOR);
        } else {
            // relative to current path
            $file = $this->_currentDir . DIRECTORY_SEPARATOR 
                . strtr($url, '/', DIRECTORY_SEPARATOR);
        }
        $obj = new Minify_CSS_Linearizer(dirname($file));
        $css = $obj->_getStyles($file);
        if ('' === $css) {
            return "/* Minify_CSS_Linearizer : could not open '{$file}' */";
        }
        return preg_match('@(?:^$|\\ball\\b)@', $mediaList)
            ? "{$css}\n{$endToken}"
            : "@media {$mediaList} {\n{$css}\n}\n{$endToken}";
    }
    
    private function _urlCB($m)
    {
        // $m[1] is either quoted or not
        $quote = ($m[1][0] === "'" || $m[1][0] === '"')
            ? $m[1][0]
            : '';
        $url = ($quote === '')
            ? $m[1]
            : substr($m[1], 1, strlen($m[1]) - 2);
        if ('/' !== $url[0]) {
            if (strpos($url, '//') > 0) {
                // probably starts with protocol, do not alter
            } else {
                // prepend path with current dir separator (OS-independent)
                $path = $this->_currentDir 
                    . DIRECTORY_SEPARATOR . strtr($url, '/', DIRECTORY_SEPARATOR);
                // strip doc root
                $path = substr($path, strlen($_SERVER['DOCUMENT_ROOT']));
                // fix to absolute URL
                $url = strtr($path, DIRECTORY_SEPARATOR, '/');
                $url = str_replace('/./', '/', $url);
            }
        }
        return "url({$quote}{$url}{$quote})";
    }
}
