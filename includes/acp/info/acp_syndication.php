<?php
/**
*
* @package acp
* @version $Id$
* @copyright (c) 2008 Niklas Schmidtmer
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @package module_install
*/
class acp_syndication_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_syndication',
			'title'		=> 'ACP_SYNDICATION',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'main'	=> array('title' => 'ACP_SYNDICATION_FEEDS', 'auth' => '', 'cat' => array('ACP_SYNDICATION')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>