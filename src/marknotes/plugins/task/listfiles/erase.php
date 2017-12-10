<?php
/**
* Scan the /docs folder and retrieve any .html, .docx,
* .pdf, .odt, ... files that was generated by marknotes and
* kill them.
*
* This task will respect the ACLs plugin i.e. won't retrieve files
* if the user can't access them.
*
* Can answer to /index.php?task=task.listfiles.erase
*/
namespace MarkNotes\Plugins\Task\ListFiles;

defined('_MARKNOTES') or die('No direct access allowed');

class Erase extends \MarkNotes\Plugins\Task\Plugin
{
	protected static $me = __CLASS__;
	protected static $json_settings = 'plugins.task.listfiles';
	protected static $json_options = 'plugins.options.task.listfiles';

	private static $arrExtensions = array();

	// @TODO - List of extensions that marknotes support as
	// output file i.e. generated by a converter. This list
	// should be maintained when new converters
	// will be added to marknotes.
	private static $arrSupportedExtensions = array(
		'epub','docx','odt','pdf','remark','reveal','txt');

		/**
		* Get the list of notes, relies on the listFiles task plugin
		* for this in order to, among other things, be sure that
		* only files that the user can access are retrieved and
		* not confidential ones
		*/
		private static function getFiles() : array
		{
			$arrFiles = array();

			// Call the listfiles.get event and initialize $arrFiles
			$aeEvents = \MarkNotes\Events::getInstance();
			$args=array(&$arrFiles);
			$aeEvents->loadPlugins('task.listfiles.get');
			$aeEvents->trigger('task.listfiles.get::run', $args);

			return $args[0];
		}

		private static function EraseFiles(&$params = null) : bool
		{
			$aeDebug = \MarkNotes\Debug::getInstance();
			$aeFiles = \MarkNotes\Files::getInstance();
			$aeFunctions = \MarkNotes\Functions::getInstance();
			$aeSession = \MarkNotes\Session::getInstance();
			$aeSettings = \MarkNotes\Settings::getInstance();

			// Get the list of files
			$arrFiles = self::getFiles();

			$status = 1;

			/*<!-- build:debug -->*/
			if ($aeSettings->getDebugMode()) {
				$aeDebug = \MarkNotes\Debug::getInstance();
			}
			/*<!-- endbuild -->*/

			if (count($arrFiles)>0) {
				foreach ($arrFiles as $file) {
					$file=\realpath($file);
					$file_no_ext = $aeFiles->removeExtension($file);

					foreach (static::$arrExtensions as $ext) {
						// Just to be sure : never ever delete a .md file
						if ($ext == '.md') {
							continue;
						}

						$tmp = $file_no_ext.$ext;

						if (is_file($tmp)) {
							if (is_writable($tmp)) {
								$error = '';

								// Remove the file
								try {
									unlink($tmp);
									if (is_file($tmp)) {
										// Still there ? Not normal
										$status = 0;
									}
								} catch (Exception $e) {
									$error = $e->getMessage();
								}

								/*<!-- build:debug -->*/
								if ($aeSettings->getDebugMode()) {
									$aeDebug->log(
										"File [".$tmp."] ".
										($status==1 ? "has been removed" : "was not removed : ".
										"[".$error."]"),
										($status==1 ? "debug" : "error")
									);
								}
								/*<!-- endbuild -->*/
							}
						}
					}
				} // foreach

				$sMessage = ($status==1 ? "settings_erase_done" :  "error_delete_files");
			} else {
				// if (count($arrFiles)>0)
				$sMessage = "settings_erase_nofile";
			}

			$sMessage = $aeSettings->getText($sMessage);

			header('Content-Type: application/json; charset=utf-8');
			header('Content-Transfer-Encoding: ascii');
			die('{"status":'.$status.',"message":"'.$sMessage.'"}');

			return true;
		}

		/**
		* Remove files that were generated by the different
		* converter (.docx, .pdf, ...)
		*/
		public static function run(&$params = null) : bool
		{
			$bReturn = true;

			$aeSettings = \MarkNotes\Settings::getInstance();
			$arrSettings = $aeSettings->getPlugins('plugins.buttons');

			/**
			* Detect which conversion tool is enable by looking
			* which buttons appears in the content. These buttons
			* allow the user to make the conversion.
			*/
			static::$arrExtensions=array();

			// marknotes will generate html files even when no
			// buttons are enabled
			static::$arrExtensions[]='.html';

			foreach ($arrSettings as $plugin => $arrPlugin) {
				// $arrPlugin is an array with, f.i.
				//		enabled=>1
				// 		quickIcons=>0
				if ($arrPlugin['enabled']==1) {
					if (in_array($plugin, static::$arrSupportedExtensions)) {
						// Never for .md
						if ($plugin!=='md') {
							static::$arrExtensions[]='.'.$plugin;
						}
					}
				}
			}

			if (count(static::$arrExtensions)>0) {
				$bReturn = self::EraseFiles($params);
			} else {
				$bReturn = false;

				header('Content-Type: application/json');
				die('{"status":1,"message":"The erase task has nothing to remove since there are no enabled converters (docx, epub, html, ...)."}');
			}

			return $bReturn;
		}
	}
