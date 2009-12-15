<?php
/**
*
* @package phpBB3
* @version $Id: functions_syndication.php 57 2007-07-02 15:21:20Z  $
* @copyright (c) 2007 Niklas Schmidtmer
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* check an array of forum_ids for permission
*/
function check_forum_ids(&$forum_ids)
{
	global $auth, $db, $user;

	foreach ($forum_ids as $i => $forum_id)
	{
		// if the user asked for a forum he has no access to but is not logged in, prompt him for login. He might have the permission.
		if (!$auth->acl_get('f_read', $forum_id) || !$auth->acl_get('f_syndication', $forum_id))
		{
			if ($user->data['user_id'] == ANONYMOUS)
			{
				// this function will exit if authentication fails, so no reason to break here
				http_auth();
			}
			else
			{
				unset($forum_ids[$i]);
			}
		}

		// skip link forums
		$sql = 'SELECT forum_id
			FROM ' . FORUMS_TABLE . "
			WHERE forum_id = $forum_id
				AND forum_type <> " . FORUM_LINK;
		$result = $db->sql_query($sql, 3600);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			unset($forum_ids[$i]);
		}
	}
}

/**
* generate title, description and source link of a feed
*/
function generate_feed_details($content, $global, &$feed_data)
{
	global $board_url, $config, $user, $db, $phpEx;

	if ($content == 'pm')
	{
		global $folder;

		// custom folder?
		if (is_int($folder))
		{
			global $folder_name;
		}
		else
		{
			$user->add_lang('ucp');
			$folder_name = $user->lang['PM_' . strtoupper($folder)];
		}

		$title = sprintf($user->lang['SYNDICATION_PM_TITLE'], $folder_name);
		$description = sprintf($user->lang['SYNDICATION_PM_DESCRIPTION'], $folder_name, $config['sitename']);
		$source_link = "ucp.$phpEx?i=pm&amp;folder=$folder";
	}
	else
	{
		// give the feed a name and description
		if ($global)
		{
			$title = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_GLOBAL_TITLE'], $config['sitename']);
			$description = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_GLOBAL_DESCRIPTION'], $config['sitename']);
			$source_link = "index.$phpEx";
		}
		else if ($content == 'topic_posts')
		{
			global $topic_row, $topic_id;

			$title = sprintf($user->lang['SYNDICATION_TOPIC_POSTS_TITLE'], $topic_row['topic_title']);
			$description = sprintf($user->lang['SYNDICATION_TOPIC_POSTS_DESCRIPTION'], $topic_row['topic_title'], $config['sitename']);
			$source_link = "viewtopic.$phpEx?f={$topic_row['forum_id']}&amp;t=$topic_id";
		}
		else
		{
			global $single_cat, $only_cats, $forum_ids;

			if ($single_cat)
			{
				global $forum_name;

				$title = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_CATEGORY_TITLE'], $forum_name);
				$description = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_CATEGORY_DESCRIPTION'], $forum_name, $config['sitename']);
				$source_link = "viewforum.$phpEx?f=" . get_first_key($forum_ids);
			}
			else if ($only_cats)
			{
				$title = $user->lang['SYNDICATION_' . strtoupper($content) . '_CATEGORIES_TITLE'];
				$description = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_CATEGORIES_DESCRIPTION'], $config['sitename']);
				$source_link = "index.$phpEx";
			}
			else
			{
				// if we have only a single forum, get details about it
				if (sizeof($forum_ids) == 1)
				{
					$sql = 'SELECT forum_name
						FROM ' . FORUMS_TABLE . '
						WHERE forum_id = ' . get_first_key($forum_ids);
					$result = $db->sql_query($sql, 3600);
					$forum_name = $db->sql_fetchfield('forum_name', 0, $result);
					$db->sql_freeresult($result);

					$title = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_TITLE'], $forum_name);
					$description = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_DESCRIPTION'], $forum_name, $config['sitename']);
					$source_link = "viewforum.$phpEx?f=" . get_first_key($forum_ids);
				}
				else
				{
					$title = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_VARIOUS_TITLE'], $config['sitename']);
					$description = sprintf($user->lang['SYNDICATION_' . strtoupper($content) . '_VARIOUS_DESCRIPTION'], $config['sitename']);
					$source_link = "index.$phpEx";
				}
			}
		}
	}

	$feed_data += array(
		'title'			=> $title,
		'description'	=> $description,
		'source_link'	=> "{$board_url}/$source_link",
		'lang'			=> $user->data['user_lang']
	);
}

/**
* get actual data about topics or posts
*/
function get_content_data($content, &$feed_data, $start, $end)
{
	global $global, $number_items, $board_url, $config, $phpEx, $db;

	switch ($content)
	{
		case 'posts':
		case 'topic_posts':	
			if ($content == 'posts')
			{
				global $forum_ids;
				$where_sql = $db->sql_in_set('forum_id', $forum_ids);
			}
			else
			{
				global $topic_id;
				$where_sql = 'topic_id = ' . $topic_id;
			}

			$sql = 'SELECT topic_id, forum_id, post_id, post_text, post_username, post_time, post_subject, bbcode_bitfield, bbcode_uid, enable_bbcode, enable_smilies, enable_magic_url, username
				FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . " u
				WHERE $where_sql
					AND p.poster_id = u.user_id
					AND post_approved = 1
				ORDER BY post_time DESC";
			$result = $db->sql_query_limit($sql, $end, $start, $config['syndication_ttl']);

			while ($row = $db->sql_fetchrow($result))
			{
				$link = "{$board_url}/viewtopic.$phpEx?f={$row['forum_id']}&amp;p={$row['post_id']}#p{$row['post_id']}";

				$feed_data['items'][] = array(
					'author'	=> (!empty($row['post_username'])) ? $row['post_username'] : $row['username'],
					'time'		=> $row['post_time'],
					'link'			=> $link,
					'identifier'	=> $link,
					'title'		=> $row['post_subject'],
					'text'		=> array($row['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['enable_bbcode'], $row['enable_smilies'], $row['enable_magic_url'])
				);
			}
			$db->sql_freeresult($result);
		break;

		case 'topics':
			global $forum_ids;

			$sql = 'SELECT t.topic_id, t.forum_id, topic_title, topic_first_poster_name, topic_time, post_text, bbcode_uid, bbcode_bitfield, enable_bbcode, enable_smilies, enable_magic_url
				FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . ' p
				WHERE ' . $db->sql_in_set('t.forum_id', $forum_ids) . '
					AND p.post_id = t.topic_first_post_id
					AND p.post_approved = 1
					AND t.topic_approved = 1
					AND t.topic_moved_id = 0
				ORDER BY post_time DESC';
			$result = $db->sql_query_limit($sql, $end, $start, $config['syndication_ttl']);

			while ($row = $db->sql_fetchrow($result))
			{
				$link = "{$board_url}/viewtopic.$phpEx?f={$row['forum_id']}&amp;t={$row['topic_id']}";

				$feed_data['items'][] = array(
					'author'	=> $row['topic_first_poster_name'],
					'time'		=> $row['topic_time'],
					'link'			=> $link,
					'identifier'	=> $link,
					'title'		=> $row['topic_title'],
					'text'		=> array($row['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['enable_bbcode'], $row['enable_smilies'], $row['enable_magic_url'])
				);
			}
			$db->sql_freeresult($result);
		break;

		case 'pm':
			global $user, $folder;

			// convert to corresponding integer value
			$folder = convert_pm_folder_value($folder);

			$sql = 'SELECT p.msg_id, message_text, p.author_id, message_time, message_subject, bbcode_bitfield, bbcode_uid, enable_bbcode, enable_smilies, enable_magic_url, u.username
				FROM ' . PRIVMSGS_TABLE . ' p, ' . PRIVMSGS_TO_TABLE . ' t, ' . USERS_TABLE . ' u
				WHERE p.author_id = u.user_id
					AND u.user_id = ' . (int) $user->data['user_id'] . "
					AND t.msg_id = p.msg_id
					AND t.folder_id = $folder
				ORDER BY message_time DESC";
			$result = $db->sql_query_limit($sql, $end, $start, $config['syndication_ttl']);

			while ($row = $db->sql_fetchrow($result))
			{
				$link = "{$board_url}/ucp.$phpEx?i=pm&amp;mode=view&amp;f=$folder&amp;p={$row['msg_id']}";

				$feed_data['items'][] = array(
					'author'	=> $row['username'],
					'time'		=> $row['message_time'],
					'link'			=> $link,
					'identifier'	=> $link,
					'title'		=> $row['message_subject'],
					'text'		=> array($row['message_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $row['enable_bbcode'], $row['enable_smilies'], $row['enable_magic_url'])
				);
			}
			$db->sql_freeresult($result);
		break;
	}

	// running generate_text_for_display within a database loop always crashes, so do it seperately. Although this is not the way I like it...
	for ($i = 0; $i < $size = sizeof($feed_data['items']); $i++)
	{
		$current_item = $feed_data['items'][$i]['text'];
		$feed_data['items'][$i]['text'] = parse_message($current_item[0], $current_item[1], $current_item[2], $current_item[3], $current_item[4], $current_item[5]);
	}
}

/**
* parse a message
*/
function parse_message($text, $bbcode_uid, $bbcode_bitfield, $enable_bbcode, $enable_smilies, $enable_magic_url)
{
	global $board_url;

	$flags = (($enable_bbcode) ? OPTION_FLAG_BBCODE : 0) + (($enable_smilies) ? OPTION_FLAG_SMILIES : 0) + (($enable_magic_url) ? OPTION_FLAG_LINKS : 0);
	$text = generate_text_for_display($text, $bbcode_uid, $bbcode_bitfield, $flags);
	// feed readers like to have a new line instead of just HTML code, so add it
	$text = str_replace('<br />', "<br />\n", $text);

	// smilies contain relative URL, we need it to be absolute
	return str_replace('<img src="./', '<img src="' . $board_url . '/', $text);
}

/**
* we need to deal with the message for Atom again, return as-is for RSS2
*/
function prepare_message($message, $format)
{
	if ($format == SYNDICATION_ATOM)
	{
		return atom_prepare_message($message);
	}
	return $message;
}

/**
* embed HTML entities into CDATA tag
*/
function atom_prepare_message($message)
{
	$message = str_replace('&nbsp;', ' ', $message);
	// get translation table of all HTML entities
	$translation_table = get_html_translation_table(HTML_ENTITIES);
	// unset some characters we don't want to deal with
	unset($translation_table['>'], $translation_table['<'], $translation_table['&'], $translation_table['"']);

	// we only want to have the keys in order to search for them, need to be converted to UTF-8 first
	$find = array_map('utf8_encode', array_keys($translation_table));

	for ($i = 0; $i < $size = sizeof($find); $i++)
	{
		// embed into CDATA tag
		if (strpos($message, $find[$i]) !== false)
		{
			$message = str_replace($find[$i], '<![CDATA[' . $find[$i] . ']]>', $message);
		}
	}
	return $message;
}

/**
* create a date according to RFC 3339 or 822
*/
function format_date($timestamp, $target)
{
	if ($target == SYNDICATION_ATOM)
	{
		// RFC 3339 for ATOM
		return date('Y-m-d\TH:i:s\Z', $timestamp);
	}
	else
	{
		// RFC 822 for RSS2
		return date('D, d M Y H:i:s O', $timestamp);
	}
}

/**
* parses a string to the corresponding constant
*/
function parse_format($format)
{
	if ($format == 'atom')
	{
		return SYNDICATION_ATOM;
	}

	if ($format == 'rss2')
	{
		return SYNDICATION_RSS2;
	}

	global $user;
	return $user->data['user_syndication_method'];
}

/**
* get all subforums of a specified forum
*/
function get_subforums($forum_id, &$forums)
{
	global $auth, $db;

	$sql = 'SELECT forum_id, forum_type, left_id, right_id
		FROM ' . FORUMS_TABLE . "
		WHERE parent_id = $forum_id
			AND forum_type <> " . FORUM_LINK;
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		// postable forum and sufficent permissions
		if ($row['forum_type'] == FORUM_POST && $auth->acl_get('f_read', $row['forum_id']) && $auth->acl_get('f_syndication', $row['forum_id']))
		{
			$forums[] = (int) $row['forum_id'];
		}

		if ($row['right_id'] - $row['left_id'] > 1)
		{
			get_subforums($row['forum_id'], $forums);
		}
	}
	$db->sql_freeresult($result);
}

/**
* do login for user authenticating via HTTP AUTH
*/
function phpbb_login()
{
	global $auth;

	// get username and password
	set_var($username, $_SERVER['PHP_AUTH_USER'], 'string', true);
	set_var($password, $_SERVER['PHP_AUTH_PW'], 'string', true);

	$result = $auth->login($username, $password, true, false);

	if ($result['status'] == LOGIN_SUCCESS)
	{
		// Special case... the user is effectively banned, but we allow founders to login
		if (defined('IN_CHECK_BAN') && $result['user_row']['user_type'] != USER_FOUNDER)
		{
			trigger_error('BANNED');
		}

		// user logged in successfully, redirect to the same page to make the new session become effective
		$url = build_feed_url();
		$redirect = reapply_sid($url);

		redirect($redirect);
	}
	// login failed, let's try again...
	else
	{
		http_auth();
	}
}

/**
* create a basic HTTP AUTH request
*/
function http_auth()
{
	global $user;

	header("WWW-Authenticate: Basic realm=\"{$user->lang['PRIVATE_FEED']}\"");
	header('HTTP/1.0 401 Unauthorized');
	trigger_error($user->lang['PRIVATE_FEED_CHANCEL']);
}

/**
* determine first key of an array when first key might be not be 0
*/
function get_first_key($array)
{
	if (isset($array[0]))
	{
		return $array[0];
	}

	$keys = array_keys($array);
	return $array[$keys[0]];
}

/**
* converts a string value of a PM folder to corresponding integer value if required
*/
function convert_pm_folder_value($value)
{
	if (is_string($value))
	{
		return ($value == 'inbox') ? PRIVMSGS_INBOX : (($value == 'outbox') ? PRIVMSGS_OUTBOX : PRIVMSGS_SENTBOX);
	}
	return $value;
}

/**
* build URL of current feed
*/
function build_feed_url($absolute = false, $is_amp = true)
{
	global $content, $cat, $global, $forum_ids, $topic_id, $folder, $number_items, $board_url, $config, $include_subforums, $phpEx;

	$url = "generate_feed.$phpEx?content=$content";
	($absolute) ? $url = "$board_url/$url" : '';
	$url .= ($global) ? '&amp;global=1' : '';
	$url .= ($content == 'pm') ? '&amp;folder=' . $folder : '';
	$url .= ($cat) ? '&amp;cat=1' : '';
	$url .= ($forum_ids && !$global) ? '&amp;f%5B%5D=' . implode('&amp;f%5B%5D=', $forum_ids) : '';
	$url .= ($topic_id) ? "&amp;t=$topic_id" : '';
	$url .= ($number_items < $config['syndication_items']) ? "&amp;number_items=$number_items" : '';
	$url .= ($include_subforums) ? '&amp;sub=1' : '';

	return (!$is_amp) ? str_replace('&amp;', '&', $url) : $url;
}

?>