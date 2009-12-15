<?php
/*
*
* @package phpBB3 Portal  a.k.a canverPortal  ( www.phpbb3portal.com )
* @version $Id: donate.php,v 1.2 2007/08/05 09:39:57 angelside Exp $
* @copyright (c) Canver Software - www.canversoft.net
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/
if (!defined('IN_PHPBB') or !defined('IN_PORTAL'))
{
	die('Hacking attempt');
	exit;
}

/**
*/

if ($config['portal_pay_acc'])
{
	if ($config['portal_pay_c_block'])
	{
		$template->assign_vars(array(
			'S_DISPLAY_PAY_C' => true,
		));
	}

	if ($config['portal_pay_s_block'])
	{
		$template->assign_vars(array(
			'S_DISPLAY_PAY_S' => true,
		));
	}

	// Assign specific vars
	$template->assign_vars(array(
		'PAY_ACC' => $config['portal_pay_acc'],
	));
}

?>