<?php
/**
 * Debuging - Display the logfile content in an HTML table
 * 	to make easier to work with the file and quickly be able
 * 	to, for instance, display errors by filtering on the type.
 *
 * Answer to URL index.php?task=task.debug.readfile
 */
namespace MarkNotes\Plugins\Task\debug;

defined('_MARKNOTES') or die('No direct access allowed');

class Readfile extends \MarkNotes\Plugins\Task\Plugin
{
	protected static $me = __CLASS__;
	protected static $json_settings = 'plugins.task.debug.readfile';
	protected static $json_options = 'plugins.options.task.debug.readfile';

	public static function run(&$params = null) : bool
	{
		$aeSettings = \MarkNotes\Settings::getInstance();
		$aeDebug = \MarkNotes\Debug::getInstance();

		$sFileName = $aeDebug->getLogFilename();

		$handle  = fopen($sFileName,"r") or die("Error");

		$html =
			'<thead><tr>'.
				'<th>Type</th>'.
				'<th>Plugin</th>'.
				'<th>Line</th>'.
				'<th>Caller 1</th>'.
				'<th>Caller 2</th>'.
				'<th>Caller 3</th>'.
			'</tr></thead>'.
			'<tfoot><tr>'.
				'<th>Type</th>'.
				'<th>Plugin</th>'.
				'<th>Line</th>'.
				'<th>Caller 1</th>'.
				'<th>Caller 2</th>'.
				'<th>Caller 3</th>'.
			'</tr></tfoot>'.
			'<tbody>';

		// Process the logfile line by line
		while(($line = fgets($handle)) != false) {

			// Line will contains something like :
			// [DEBUG] MarkNotes\Debug::enable - *** START of marknotes *** [{"caller":"MarkNotes\\Debug::enable line 321"},{"caller":"MarkNotes\\Settings::setDebugMode line 423"},{"caller":"MarkNotes\\Settings::enableDebugMode line 90"}]
			$line = trim($line);

			// Get the type i.e. the info between brackets at the very
			// start of the string (can be [DEBUG], [INFO],
			// [ERROR], ...)
			$pattern = '/^\[(.*?)\]/m';

			if (preg_match($pattern, $line, $matches)) {
				$type = strtoupper($matches[1]);

				switch ($type) {
					case 'ALERT':
					case 'EMERGENCY':
					case 'ERROR':
					case 'CRITICAL':
						$class = 'danger';
						break;
					case 'INFO':
					case 'NOTICE':
						$class = 'info';
						break;
					case 'WARNING':
						$class = 'warning';
						break;
					default:
						// debug
						$class = '';
						break;
				}

				// Remove the type from the line since already
				// extracted
				$line = substr($line, strlen($type)+3);
			}

			// Get the plugin if the output was from a plubin
			$pattern = '/(MarkNotes\\\\Plugins[^]\\:]*)/m';
			$plugin = '';

			if (preg_match($pattern, $line, $matches)) {
				$plugin = trim($matches[1]);
			}

			// Get the callers : the log line is something like (on one line)
			// 		MarkNotes\Debug::enable - *** START of marknotes ***
			// 		[{"caller":"MarkNotes\\Debug::enable line 321"},
			// 		{"caller":"MarkNotes\\Settings::setDebugMode line 423"},
			// 		{"caller":"MarkNotes\\Settings::enableDebugMode line 90"}]
			//
			// 	Extract the three callers
			$pattern = '/\[?({("caller":"[^}]*)\}(,?){1,})\]?/m';

			$caller1 = '';
			$caller2 = '';
			$caller3 = '';

			if (preg_match_all($pattern, $line, $matches)) {

				$caller1 = trim($matches[2][0]);
				$caller2 = trim($matches[2][1]??'');
				$caller3 = trim($matches[2][2]??'');

				$caller1 = str_replace('"caller":"', '', $caller1);
				$caller2 = str_replace('"caller":"', '', $caller2);
				$caller3 = str_replace('"caller":"', '', $caller3);

				$caller1 = trim($caller1, '"');
				$caller2 = trim($caller2, '"');
				$caller3 = trim($caller3, '"');

				// Remove the caller from the debug line (since already displayed)
				$line = str_replace($matches[0][0], '', $line);
				$line = str_replace($matches[0][1], '', $line);

				if (isset($matches[0][2])) {
					$line = str_replace($matches[0][2], '', $line);
				}
			}

			$html.= '<tr class="'.$class.'">'.
				'<td>'.$type.'</td>'.
				'<td>'.$plugin.'</td>'.
				'<td>'.trim($line).'</td>'.
				'<td>'.trim($caller1).'</td>'.
				'<td>'.trim($caller2).'</td>'.
				'<td>'.trim($caller3).'</td>'.
				'</tr>';
		}

		$html .= '</tbody>';

		fclose($handle);

		$aeFunctions = \MarkNotes\Functions::getInstance();
		$sRoot = rtrim($aeFunctions->getCurrentURL(), '/').'/';

		$filename = __DIR__.DS.'assets/readfile.frm';

		$template = file_get_contents($filename);
		$html = str_replace('%CONTENT%', $html, $template);
		$html = str_replace('%FILENAME%', $sFileName, $html);
		$html = str_replace('%ROOT%', $sRoot, $html);

		echo $html;

		return true;
	}

	/**
	* Determine if this plugin is needed or not
	*/
	final protected static function canRun() : bool
	{
		$bCanRun = false;

		$aeSettings = \MarkNotes\Settings::getInstance();

		/*<!-- build:debug -->*/
		$bCanRun = parent::canRun();

		if ($bCanRun) {
			// Both Debug and DevMode should be enabled
			if ($aeSettings->getDebugMode()) {
				$aeDebug = \MarkNotes\Debug::getInstance();
				$bCanRun = $aeDebug->getDevMode();
			}
		}

		if (!$bCanRun) {
			/*<!-- endbuild -->*/
			$return = array();
			$return['status'] = 0;
			$return['message'] = $aeSettings->getText('only_dev_mode_on', '', true);

			header('Content-Type: application/json');
			echo json_encode($return, JSON_PRETTY_PRINT);
			/*<!-- build:debug -->*/
		}

		return $bCanRun;
	}
}
