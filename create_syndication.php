<?php
/**
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2007 Niklas Schmidtmer
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
include($phpbb_root_path . 'includes/functions_syndication.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/syndication');

if (!$config['enable_syndication'])
{
	trigger_error('SYNDICATION_DISABLED');
}

$submit = (isset($_POST['submit'])) ? true : false;

if ($submit)
{
	$content = request_var('content', '');
	$global = request_var('all_forums', 0);
	$number_items = request_var('number_items', (int) $config['syndication_items']);
	$forum_ids = request_var('forum_id', array(0 => 0));
	$cat = false;

	if (!$forum_ids && !$global)
	{
		trigger_error('NOTHING_SELECTED');
	}

	if ($global)
	{
		// reset forum_ids, we don't want any user input
		$forum_ids = array();
	}
	else
	{
		// is there a category which has been selected?
		foreach ($forum_ids as $forum_id)
		{
			$sql = 'SELECT forum_id
				FROM ' . FORUMS_TABLE . "
				WHERE forum_id = $forum_id
					AND forum_type = " . FORUM_CAT;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if ($row)
			{
				$cat = true;
				break;
			}
		}
	}

	// build URL only with necessary elements in correct order
	$url = build_feed_url();

	redirect(reapply_sid($url));
}

// Lets build a page ...
$template->assign_vars(array(
	'S_FORUM_OPTIONS'			=> make_forum_select(false, false, false, false, false, false, false, true),
	'S_ACTION'					=> append_sid("{$phpbb_root_path}create_syndication.$phpEx"),

	'NUMBER_ITEMS'				=> $config['syndication_items'],

	'L_SYNDICATION_ADMIN_LIMIT'	=> sprintf($user->lang['SYNDICATION_ADMIN_LIMIT'], $config['syndication_items']))
);

page_header($user->lang['CUSTOM_SYNDICATION_TITLE']);

$template->set_filenames(array(
	'body' => 'syndication_body.html')
);

page_footer();

?>