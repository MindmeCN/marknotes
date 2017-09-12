<?php

/* REQUIRES PHP 7.x AT LEAST */

/**
 * Author : AVONTURE Christophe - https://www.aesecure.com
 *
 * Documentation : https://github.com/cavo789/marknotes/wiki
 * Demo : https://www.marknotes.fr
 * History : https://github.com/cavo789/marknotes/blob/master/changelog.md
 */

define('_MARKNOTES', 1);

include_once 'marknotes/includes/constants.php';

// Load third parties
include_once 'libs/autoload.php';

// Load Marknotes classes
include_once 'autoload.php';
use \MarkNotes\Autoload;

if (version_compare(phpversion(), '7.0.0', '<')) {

    $root = dirname($_SERVER['SCRIPT_NAME']);
    $content = str_replace('%ROOT%', $root, file_get_contents(__DIR__.'/error_php.html'));
    echo $content;

} else {

    \MarkNotes\Autoload::register();

    $aeFunctions = \MarkNotes\Functions::getInstance();
    // Application root folder.
    $folder = rtrim(str_replace('/', DS, dirname($_SERVER['SCRIPT_FILENAME'])), DS).DS;

    $filename = rtrim(rawurldecode($aeFunctions->getParam('file', 'string', '', false)), DS);
	$filename = str_replace('/', DS, $filename);

    $params = array('filename' => $filename);

    $aeSettings = \MarkNotes\Settings::getInstance($folder, $params);

	include_once 'marknotes/includes/debug.php';

	/*<!-- build:debug -->*/
	if ($aeSettings->getDebugMode()) {
		$aeDebug = \MarkNotes\Debug::getInstance();
		$aeDebug->log('*** START of marknotes - router.php ***','debug');
	}
	/*<!-- endbuild -->*/

    $aeSession = \MarkNotes\Session::getInstance();
	$aeSession->set('img_id',0);

    $aeFiles = \MarkNotes\Files::getInstance();
    $aeEvents = \MarkNotes\Events::getInstance();

    // Retrieve the asked extension i.e. if the user try to access the /note.html
	// or /note.pdf file, extract the extension (html or pdf)
    $format = '';
    if ($filename !== '') {
        $format = $aeFiles->getExtension($filename);
    }
    // Take a look on the format parameter, if mentionned use that format
    $format = $aeFunctions->getParam('format', 'string', $format, false, 8);

    $layout = '';
    $params = array();

    if ($filename !== '') {
        $fileMD = '';

		// The filename shouldn't mention the docs folders, just the filename
		// So, $filename should not be docs/markdown.md but only markdown.md because the
		// folder name will be added later on

		$docRoot = $aeSettings->getFolderDocs(false);

		if ($aeFunctions->startsWith($filename, $docRoot)) {
			$filename = substr($filename, strlen($docRoot));
		}

		// Very special files
		$arrSpecialFiles=array('tag.json', 'timeline.html', 'timeline.json', 'sitemap.xml');

		if (!in_array($filename, $arrSpecialFiles)) {
			$filename=$aeFiles->removeExtension($filename).'.md';
		}

		$full=$aeSettings->getFolderDocs(true).$filename;

		// Note that an "index.md" note can well be present so don't inmmediatly
		// run the "index" task when index.html is called
		if ( (!file_exists($filename)) && (basename($filename) === 'index.html')) {

            $format = 'index';
            $fileMD = $filename;

        } elseif (in_array($filename, $arrSpecialFiles)) {
            // Specific files
            $aeFiles = \MarkNotes\Files::getInstance();

            // Remember the layout (json, html, ...)
            $layout = $aeFiles->getExtension($filename);

            switch ($filename) {
                case 'tag.json':
                    $format = 'tags';
                    break;

                case 'timeline.html':
                case 'timeline.json':
                    $format = 'timeline';
                    break;

                case 'sitemap.xml':
                    $format = 'sitemap';
                    break;
            } // switch

            $fileMD = '';
        } else {

            // Get the absolute folder name where the web application resides (f.i. c:\websites\marknotes\)
            $webRoot = $aeSettings->getFolderWebRoot(true);

            // Build the full filename
            $filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);

            $fileMD = $aeFiles->removeExtension($filename).'.md';

            // Get the extension (f.i. "pdf")
            // In case of double extension (f.i. "reveal.pdf"), the first part will
            // be understand as a layout ("reveal")
            $layout = '';
            $fileExt = $aeFiles->getExtension($filename);
            if (strpos($fileExt, ".") !== false) {
                $arr = explode(".", $fileExt);
                $layout = $arr[0];
                $format = $arr[1];
            }
        } // if (in_array($filename, array('timeline.html', 'sitemap.xml')))

        if ($layout !== '') {
            $params['layout'] = $layout;
        }

        $aeMarkDown = new \MarkNotes\Markdown();

        // $fileMD filename should be relative
        $aeMarkDown->process($format, $fileMD, $params);
        unset($aeMarkDown);

		/*<!-- build:debug -->*/
		if ($aeSettings->getDebugMode()) {
			$aeDebug = \MarkNotes\Debug::getInstance();
			$aeDebug->log('*** END of marknotes - router.php ***','debug');
		}
		/*<!-- endbuild -->*/
    }
}
