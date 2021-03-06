<?php
/**
 * Load balloon.min.css
 */
namespace MarkNotes\Plugins\Page\HTML;

defined('_MARKNOTES') or die('No direct access allowed');

class Balloon extends \MarkNotes\Plugins\Page\HTML\Plugin
{
	protected static $me = __CLASS__;
	protected static $json_settings = 'plugins.page.html.balloon';
	protected static $json_options = '';

	/**
	 * Provide additionnal stylesheets
	 */
	public static function addCSS(&$css = null) : bool
	{
		$aeSession = \MarkNotes\Session::getInstance();

		// Not sure that the HTML has been stored in the session
		$html = $aeSession->get('html', '');

		// balloon.css use a data-attribute called "data-balloon"
		// This attribute is set by /plugins/content/html/balloon
		// If the attribute is not in the HTML string, no need to
		// load the css
		if (stripos($html, 'data-balloon') !== false) {
			$aeFunctions = \MarkNotes\Functions::getInstance();

			$url = rtrim($aeFunctions->getCurrentURL(), '/').'/';
			$url .= 'marknotes/plugins/page/html/balloon/';

			$script = "<link media=\"screen\" rel=\"stylesheet\" type=\"text/css\" ". 	"href=\"".$url."libs/balloon.min.css\" />\n";

			$css .= $aeFunctions->addStyleInline($script);
		}

		return true;
	}

	/**
	 * Provide additionnal javascript
	 */
	public static function addJS(&$js = null) : bool
	{
		return true;
	}

	/**
	 * Add/modify the HTML content
	 */
	public static function doIt(&$html = null) : bool
	{
		return true;
	}
}
