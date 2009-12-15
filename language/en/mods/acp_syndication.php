<?php
/**
*
* acp_syndication [English]
*
* @package language
* @version $Id$
* @copyright (c) 2007 Niklas Schmidtmer
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'ENABLE_SYNDICATION'					=> 'Enable syndication of topics and forums',

	'SYNDICATION_ATOM'							=> 'Atom',
	'SYNDICATION_DEFAULT'					=> 'Select the default format for syndication feeds',
	'SYNDICATION_DEFAULT_EXPLAIN'	=> 'Users can still change this setting within the UCP if they like.',
	'SYNDICATION_INSTALL'					=> 'Installing Full Syndication Suite MOD',
	'SYNDICATION_INSTALL_COMPLETE'	=> 'Database installation of this MOD is now complete.',

	'SYNDICATION_ITEMS'						=> 'Number of items displayed in a syndication feed',
	'SYNDICATION_LEGEND'					=> 'Syndication configuration',
	'SYNDICATION_RSS2'							=> 'RSS2',
	'SYNDICATION_QUERIES_FAILED'	=> 'This is not supposed to happen. Should you run into problems installing this MOD please seek help in the MOD’s release topic.',
	'SYNDICATION_TITLE'						=> 'Syndication feeds configuaration',
	'SYNDICATION_TITLE_EXPLAIN'		=> 'Here you can configure basic settings for syndication feeds.',
	'SYNDICATION_TTL'							=> 'Time for caching contents of a syndication feed',
	'SYNDICATION_TTL_EXPLAIN'			=> 'Contents of posts or topics will not be retrieved more than once until this time frame has expired.',
));

?>