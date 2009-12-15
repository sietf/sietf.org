<?php
/*
*
* @package phpBB3 Portal  a.k.a canverPortal  ( www.phpbb3portal.com )
* @version $Id: latest_bots.php,v 1.2 2007/08/05 09:47:37 angelside Exp $
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

// Last x visited bots
$sql = 'SELECT username, user_colour, user_lastvisit
	FROM ' . USERS_TABLE . '
	WHERE user_type = ' . USER_IGNORE . '
	ORDER BY user_lastvisit DESC';
$result = $db->sql_query_limit($sql, $config['portal_last_visited_bots_number']);

while ($row = $db->sql_fetchrow($result))
{
//	if ($row['user_lastvisit'] == 0)
	if (!$row['user_lastvisit'])
	{
		$template->assign_vars(array(
			'S_DISPLAY_LAST_BOTS'	=> false,
		));
	}
	else 
	{
		$template->assign_vars(array(
			'S_DISPLAY_LAST_BOTS'	=> true,
		));
		
		$template->assign_block_vars('last_visited_bots', array(
			'BOT_NAME'			=> get_username_string('full', '', $row['username'], $row['user_colour']),
	//		'LAST_VISIT_DATE'	=> $user->format_date($row['user_lastvisit'], 'd.m.Y, H:i'),
			'LAST_VISIT_DATE'	=> $user->format_date($row['user_lastvisit'], 'd/M/Y, H:i'),
		));
	}
}
$db->sql_freeresult($result);

// Assign specific vars
$template->assign_vars(array(
	'LAST_VISITED_BOTS'		=> sprintf($user->lang['LAST_VISITED_BOTS'], $config['portal_last_visited_bots_number']),
	'S_LAST_VISITED_BOTS'	=> ($config['portal_load_last_visited_bots']) ? true : false,
));

?>