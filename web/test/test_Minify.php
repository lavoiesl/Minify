<?php

require '_inc.php';
/**
 * Note: All Minify classes are E_STRICT except for Cache_Lite_File.
 */
error_reporting(E_ALL);
require 'Minify.php';
$tomorrow = time() + 86400;
$lastModified = time() - 86400;



// Test minifying JS and serving with Expires header

$expected = array(
    // Minify_Javascript always converts to \n line endings
	'content' => preg_replace('/\\r\\n?/', "\n", file_get_contents($thisDir . '/minify/minified.js'))
    ,'headers' => array (
        'Cache-Control' => 'public',
        'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $tomorrow),
        'Content-Type' => 'application/x-javascript',
    )
);
$output = Minify::serve('Files', array(
    $thisDir . '/minify/email.js'
    ,$thisDir . '/minify/QueryString.js'
), array(
    'quiet' => true
    ,'setExpires' => $tomorrow
    ,'encodeOutput' => false
));
$passed = assertTrue($expected === $output, 'Minify - JS and Expires');
echo "\nOutput: " .var_export($output, 1). "\n\n";
if (! $passed) {
    echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";    
}



// Test minifying CSS and responding with Etag/Last-Modified

// don't allow conditional headers
unset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE']);

$expected = array(
	'content' => file_get_contents($thisDir . '/minify/minified.css')
    ,'headers' => array (
        'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
        'ETag' => "\"{$lastModified}pub\"",
        'Cache-Control' => 'max-age=0, public, must-revalidate',
        'Content-Type' => 'text/css',
    ) 
);
$output = Minify::serve('Files', array(
    $thisDir . '/css/styles.css'
    ,$thisDir . '/css/subsilver.css'
), array(
    'quiet' => true
    ,'lastModifiedTime' => $lastModified
    ,'encodeOutput' => false
));
$passed = assertTrue($expected === $output, 'Minify - CSS and Etag/Last-Modified');
echo "\nOutput: " .var_export($output, 1). "\n\n";
if (! $passed) {
    echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";    
}


// Test 304 response

// simulate conditional headers
$_SERVER['HTTP_IF_NONE_MATCH'] = "\"{$lastModified}pub\"";
$_SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate('D, d M Y H:i:s \G\M\T', $lastModified);

$expected = array (
    'content' => '',
    'headers' => array (
    	'_responseCode' => 'HTTP/1.0 304 Not Modified',
    ),
);
$output = Minify::serve('Files', array(
    $thisDir . '/css/styles.css'
), array(
    'quiet' => true
    ,'lastModifiedTime' => $lastModified
    ,'encodeOutput' => false
));
$passed = assertTrue($expected === $output, 'Minify - 304 response');
echo "\nOutput: " .var_export($output, 1). "\n\n";
if (! $passed) {
    echo "\n\n\n\n---Expected: " .var_export($expected, 1). "\n\n";    
}
