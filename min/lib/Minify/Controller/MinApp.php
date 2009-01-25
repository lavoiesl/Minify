<?php
/**
 * Class Minify_Controller_MinApp  
 * @package Minify
 */

require_once 'Minify/Controller/Base.php';

/**
 * Controller class for requests to /min/index.php
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_Controller_MinApp extends Minify_Controller_Base {
    
    /**
     * Set up groups of files as sources
     * 
     * @param array $options controller and Minify options
     * @return array Minify options
     * 
     */
    public function setupSources($options) {
        // filter controller options
        $cOptions = array_merge(
            array(
                'allowDirs' => '//'
                ,'groupsOnly' => false
                ,'groups' => array()
                ,'maxFiles' => 10                
            )
            ,(isset($options['minApp']) ? $options['minApp'] : array())
        );
        unset($options['minApp']);
        $sources = array();
        if (isset($_GET['g'])) {
            // try groups
            if (! isset($cOptions['groups'][$_GET['g']])) {
                Minify::logError("A group configuration for \"{$_GET['g']}\" was not set");
                return $options;
            }
            foreach ((array)$cOptions['groups'][$_GET['g']] as $file) {
                if ($file instanceof Minify_Source) {
                    $sources[] = $file;
                    continue;
                }
                if (0 === strpos($file, '//')) {
                    $file = $_SERVER['DOCUMENT_ROOT'] . substr($file, 1);
                }
                $file = realpath($file);
                if (is_file($file)) {
                    $sources[] = new Minify_Source(array(
                        'filepath' => $file
                    ));    
                } else {
                    Minify::logError("The path \"{$file}\" could not be found (or was not a file)");
                    return $options;
                }
            }
        } elseif (! $cOptions['groupsOnly'] && isset($_GET['f'])) {
            // try user files
            // The following restrictions are to limit the URLs that minify will
            // respond to. Ideally there should be only one way to reference a file.
            if (// verify at least one file, files are single comma separated, 
                // and are all same extension
                ! preg_match('/^[^,]+\\.(css|js)(?:,[^,]+\\.\\1)*$/', $_GET['f'])
                // no "//"
                || strpos($_GET['f'], '//') !== false
                // no "\"
                || strpos($_GET['f'], '\\') !== false
                // no "./"
                || preg_match('/(?:^|[^\\.])\\.\\//', $_GET['f'])
            ) {
                Minify::logError("GET param 'f' invalid (see MinApp.php line 63)");
                return $options;
            }
            $files = explode(',', $_GET['f']);
            if (count($files) > $cOptions['maxFiles'] || $files != array_unique($files)) {
                Minify::logError("Too many or duplicate files specified");
                return $options;
            }
            if (isset($_GET['b'])) {
                // check for validity
                if (preg_match('@^[^/]+(?:/[^/]+)*$@', $_GET['b'])
                    && false === strpos($_GET['b'], '..')
                    && $_GET['b'] !== '.') {
                    // valid base
                    $base = "/{$_GET['b']}/";       
                } else {
                    Minify::logError("GET param 'b' invalid (see MinApp.php line 84)");
                    return $options;
                }
            } else {
                $base = '/';
            }
            $allowDirs = array();
            foreach ((array)$cOptions['allowDirs'] as $allowDir) {
                $allowDirs[] = realpath(str_replace('//', $_SERVER['DOCUMENT_ROOT'] . '/', $allowDir));
            }
            foreach ($files as $file) {
                $path = $_SERVER['DOCUMENT_ROOT'] . $base . $file;
                $file = realpath($path);
                if (false === $file) {
                    Minify::logError("Path \"{$path}\" failed realpath()");
                    return $options;
                } elseif (! parent::_fileIsSafe($file, $allowDirs)) {
                    Minify::logError("Path \"{$path}\" failed Minify_Controller_Base::_fileIsSafe()");
                    return $options;
                } else {
                    $sources[] = new Minify_Source(array(
                        'filepath' => $file
                    ));
                }
            }
        }
        if ($sources) {
            $this->sources = $sources;
        } else {
            Minify::logError("No sources to serve");
        }
        return $options;
    }
}
