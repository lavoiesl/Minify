<?php
require_once '_inc.php';

require_once 'Minify/Javascript.php';

function test_Javascript()
{
    global $thisDir;
    
    $src = file_get_contents($thisDir . '/_test_files/js/before.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/js/before.min.js');
    $minOutput = Minify_Javascript::minify($src);
    
    $passed = assertTrue($minExpected == $minOutput, 'Minify_Javascript');
    
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .strlen($src). " bytes\n\n{$src}\n\n\n";
    }
    
    $src = file_get_contents($thisDir . '/_test_files/js/issue74.js');
    $minExpected = file_get_contents($thisDir . '/_test_files/js/issue74.min.js');
    $minOutput = Minify_Javascript::minify($src);
    
    $passed = assertTrue($minExpected == $minOutput, 'Minify_Javascript : Quotes in RegExp literals (Issue 74)');
    
        if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .strlen($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .strlen($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .strlen($src). " bytes\n\n{$src}\n\n\n";
    }
}

test_Javascript();
