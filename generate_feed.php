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
include($phpbb_root_path . 'includes/functions_syndication.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/syndication');

if (!$config['enable_syndication'])
{
	trigger_error('SERVICE_UNAVAILABLE');
}

$content = request_var('content', '');
$forum_ids = request_var('f', array(0));
$topic_id = request_var('t', 0);
$include_subforums = request_var('sub', false);
$cat = request_var('cat', false);
$syndication_method = request_var('format', '');
$number_items = request_var('items', (int) $config['syndication_items']);
$global = ($content != 'topic_posts') ? request_var('global', false) : false;
$folder = request_var('folder', '');
$http_auth = (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) ? true : false;

// do we have a guest and HTTP AUTH present?
if (($http_auth || $content == 'pm') && $user->data['user_id'] == ANONYMOUS)
{
	if ($http_auth)
	{
		phpbb_login();
	}
	else
	{
		http_auth();
	}
}

// do not exceed the limit
($number_items > $config['syndication_items'] || $number_items < 0) ? $number_items = (int) $config['syndication_items'] : '';
$syndication_method = parse_format($syndication_method);

switch ($content)
{
	case 'pm':
		if (!$config['allow_privmsg'] || !$auth->acl_get('u_readpm'))
		{
			$user->add_lang('ucp');
			trigger_error('PM_DISABLED');
		}

		switch ($folder)
		{
			case PRIVMSGS_INBOX:
				$folder = 'inbox';
			break;

			case PRIVMSGS_OUTBOX:
				$folder = 'outbox';
			break;

			case PRIVMSGS_SENTBOX:
				$folder = 'sentbox';
			break;

			case 'inbox':
			case 'outbox':
			case 'sentbox':
				// nothing to do here, but we don't want to run into the default case
			break;

			// don't let user access PMs in those folders
			case PRIVMSGS_HOLD_BOX:
			case PRIVMSGS_NO_BOX:
				$user->add_lang('ucp');
				trigger_error('UNKNOWN_FOLDER');
			break;

			// any garbage input will lead us here and be transformed to 0 (inbox) by the typecast
			default:
				$folder = (int) $folder;

				// does it even exist? Select name for later usage within generate_feed_details
				$sql = 'SELECT folder_name
					FROM ' . PRIVMSGS_FOLDER_TABLE . "
					WHERE folder_id = $folder
						AND user_id = " . (int) $user->data['user_id'];
				$result = $db->sql_query($sql, 3600);
				$folder_name = $db->sql_fetchfield('folder_name', 0, $result);
				$db->sql_freeresult($result);

				if (!$folder_name)
				{
					$user->add_lang('ucp');
					trigger_error('UNKNOWN_FOLDER');
				}
			break;
		}

		// give each feed an unique identifier under which it will get cached
		$feed_identifier = 'pm' . $user->data['user_id'] . 'f' . $folder;
	break;

	case 'topic_posts':
		if (!$topic_id)
		{
			trigger_error('NO_TOPIC');
		}

		// obtain topic_title and forum_id for specific topic, don't trust on a forum_id passed via URL
		$sql = 'SELECT forum_id, topic_title
			FROM ' . TOPICS_TABLE . '
			WHERE topic_id = ' . $topic_id;
		$result = $db->sql_query($sql, 3600);
		$topic_row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$topic_row)
		{
			trigger_error('NO_TOPIC');
		}
		else if (!$auth->acl_get('f_read', $topic_row['forum_id']))
		{
			trigger_error('SYNDICATION_DISABLED');
		}

		$forum_ids = array((int) $topic_row['forum_id']);
		$feed_identifier = $content . 't' . $topic_id;
	break;

	case 'posts':
	case 'topics':
		// get all forums for global mode
		if ($global)
		{
			// reset forum ids, no need to perform check_forum_ids() later on
			$forum_ids = array();
			// 0 is the root of the forums tree
			get_subforums(0, $forum_ids);
		}
		else
		{
			if (!$forum_ids)
			{
				trigger_error('INVALID_INPUT');
			}

			// get subforums if requested
			if ($include_subforums)
			{
				// obtain all children of given forum
				foreach ($forum_ids as $forum_id)
				{
					get_subforums($forum_id, $forum_ids);
				}
			}

			// verify permissions, get subforums for categories
			if (!$global && $content != 'topic_posts')
			{
				// do we have a category?
				// get all forums belonging to this category
				if ($cat)
				{
					$single_cat = false;
					if (sizeof($forum_ids) == 1)
					{
						$single_cat = true;
						// forum_name selected for later use
						$sql = 'SELECT forum_name
							FROM ' . FORUMS_TABLE . '
							WHERE forum_id = ' . $forum_ids[0];
						$result = $db->sql_query($sql, 3600);
						$forum_name = $db->sql_fetchfield('forum_name', 0, $result);
						$db->sql_freeresult($result);

						get_subforums($forum_ids[0], $forum_ids);
					}
					else
					{
						$only_cats = true;
						$subforums = array();
						for ($i = 0; $i < $size = sizeof($forum_ids); $i++)
						{
							$sql = 'SELECT forum_type
								FROM ' . FORUMS_TABLE . '
								WHERE forum_id = ' . $forum_ids[$i];
							$result = $db->sql_query($sql, 3600);
							$forum_type = $db->sql_fetchfield('forum_type', 0, $result);
							$db->sql_freeresult($result);

							if ($forum_type == FORUM_CAT)
							{
								get_subforums($forum_ids[$i], $subforums);
							}
							else if ($only_cats)
							{
								$only_cats = false;
							}
						}
						$forum_ids = $subforums;
					}
				}
			}
		}

		// remove those without permissions or ask for login
		check_forum_ids($forum_ids);

		if (!sizeof($forum_ids))
		{
			trigger_error('SYNDICATION_DISABLED');
		}

		$feed_identifier = $content . 'f' . implode('', $forum_ids);
	break;

	default:
		trigger_error('INVALID_INPUT');
	break;
}

$board_url = generate_board_url();

// load cached feed data or recreate
if (!($feed_data = $cache->feed_load($feed_identifier)))
{
	$feed_data = array('items' => array());
	generate_feed_details($content, $global, $feed_data);
	get_content_data($content, $feed_data, 0, $number_items);

	$cache->feed_save($feed_data, $feed_identifier, $config['syndication_ttl']);
	$number_items_current = sizeof($feed_data['items']);
}
else
{
	$number_items_current = sizeof($feed_data['items']);

	// case: cache with x items already exists, but another user requested x + y items.
	// Get additional y items. Does not apply to PMs
	if ($number_items_current < $number_items)
	{
		// are there actually any more items?
		switch ($content)
		{
			case 'posts':
				$sql = 'SELECT COUNT(post_id) AS number_items_total
					FROM ' . POSTS_TABLE . '
					WHERE ' . $db->sql_in_set('forum_id', $forum_ids);
			break;

			case 'topic_posts':
				$sql = 'SELECT topic_replies AS number_items_total
					FROM ' . TOPICS_TABLE . '
					WHERE topic_id = ' . $topic_id;
			break;

			case 'topics':
				$sql = 'SELECT COUNT(topic_id) AS number_items_total
					FROM ' . TOPICS_TABLE . '
					WHERE ' . $db->sql_in_set('forum_id', $forum_ids);
			break;

			case 'pm':
				$folder = convert_pm_folder_value($folder);

				$sql = 'SELECT pm_count AS number_items_total
					FROM ' . PRIVMSGS_FOLDER_TABLE . "
					WHERE folder_id = $folder
						AND user_id = " . (int) $user->data['user_id'];
			break;
		}

		$result = $db->sql_query($sql, $config['syndication_ttl']);
		$number_items_total = $db->sql_fetchfield('number_items_total', 0, $result);
		$db->sql_freeresult($result);

		if ($number_items_total > $number_items_current)
		{
			get_content_data($content, $feed_data, $number_items_current, $number_items);
			$cache->feed_save($feed_data, $feed_identifier, $config['syndication_ttl']);
		}
	}
	// too many items, reduce the array
	else if ($number_items_current > $number_items)
	{
		$content_items = array();
		for ($i = 0; $i < $number_items; $i++)
		{
			$content_items[] = $feed_data['items'][$i];
		}
		$feed_data['items'] = $content_items;
		$number_items_current = $number_items;
	}
}

// user has set a different language as used in cached feed description? Regenerate.
if ($user->data['user_lang'] != $feed_data['lang'])
{
	generate_feed_details($content, $global, $feed_data);
}

foreach ($feed_data['items'] as $item)
{
	// apply session id to links if user is logged in
	if ($user->data['user_id'] != ANONYMOUS)
	{
		$item_link = append_sid($item['link']);
	}
	else
	{
		$item_link = $item['link'];
	}

	$template->assign_block_vars('item', array(
		'AUTHOR'		=> $item['author'],
		'TIME'			=> format_date($item['time'], $syndication_method),
		'LINK'				=> $item_link,
		'IDENTIFIER'	=> $item['identifier'],
		'TITLE'			=> $item['title'],
		'TEXT'			=> prepare_message($item['text'], $syndication_method))
	);
}

$template->set_filenames(array(
	'body' => 'syndication_' . (($syndication_method == SYNDICATION_ATOM) ? 'atom' : 'rss2') . '.html')
);

// get time from last item or use current time in case of an empty feed
$last_build_date = ($number_items_current) ? $feed_data['items'][$number_items_current - 1]['time'] : time();

$template->assign_vars(array(
	'HEADER'			=> '<?xml version="1.0" encoding="UTF-8"?>' . "\n", // workaround for remove_php_tags() removing this line from the template
	'TITLE'				=> $feed_data['title'],
	'DESCRIPTION'	=> $feed_data['description'],
	'LINK'					=> $feed_data['source_link'],
	'FEED_LINK'		=> build_feed_url(true),
 	'LAST_BUILD'		=> format_date($last_build_date, $syndication_method))
);

// gzip compression
if ($config['gzip_compress'])
{
	if (@extension_loaded('zlib') && !headers_sent())
	{
		ob_start('ob_gzhandler');
	}
}

// text/xml for Internet Explorer
header('Content-Type: text/xml; charset=UTF-8');
header('Last-Modified: ' . date('D, d M Y H:i:s O', $last_build_date));
$template->display('body');
garbage_collection();
exit_handler();

?>