<?php
/**
*
* functions_view_or_mark_unread_posts.php
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
* checks to see if the user has any unread posts in the forum
* (returns true if there are unread posts and false if there are not)
*/
function check_unread_posts()
{
	global $db, $user, $auth, $exists_unreads;

	if ($exists_unreads == 1)
	{
		// functions_display() has already been called (user is on index) and there are unreads
		return true;
	}

	if ($exists_unreads == -1)
	{
		// functions_display has already been called (user is on index) but there are no unreads
		return false;
	}

	// Note that the code below is adapted from code that appears in display_forums()
	//
	$sql = 'SELECT f.forum_id, forum_last_post_time, ft.mark_time
		FROM ' . FORUMS_TABLE . ' f
		LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . $user->data['user_id'] . ' AND ft.forum_id = f.forum_id)';

	$result = $db->sql_query($sql);

	while($row = $db->sql_fetchrow($result))
	{
		if (!$auth->acl_get('f_list', $row['forum_id']))
		{
			// if we get here, the user does not have permission to see the forum in question so we can skip it
			continue;
		}

		$forum_tracking_info = (!empty($row['mark_time'])) ? $row['mark_time'] : $user->data['user_lastmark'];

		if ($row['forum_last_post_time'] > $forum_tracking_info)
		{
			// if we get here, at least one forum has posts marked unread so set answer to true and break out
			return true;
		}
	}
	$db->sql_freeresult($result);
	// if we get here, we've been through all forums for this user and none have unread posts, so return false
	return false;
}


/**
* marks a private message unread when the user clicks the mark pm as unread link
* when viewing the private message.  Takes a single parameter, which is the msg_id of the pm
* being marked as unread
*/
function mark_unread_pm($msg_id)
{
	global $db, $user, $phpbb_root_path, $phpEx;

	// redirect the user to the index if the user is not logged in or if user is a bot
	if (($user->data['user_id'] == ANONYMOUS) || $user->data['is_bot'])
	{
		redirect(append_sid("{$phpbb_root_path}index.$phpEx"));
	}

	$user->setup('ucp');

	// find out what folder we are talking about so we can confine our actions to that folder
	$folder_id = request_var('f', PRIVMSGS_INBOX);

	$sql = 'SELECT msg_id
		FROM ' . PRIVMSGS_TO_TABLE . '
		WHERE msg_id = ' . $msg_id . '
			AND user_id = ' . $user->data['user_id'] . '
			AND pm_deleted = 0
			AND folder_id =' . $folder_id;
	$result = $db->sql_query($sql);

	if ($row = $db->sql_fetchrow($result))
	{
		// there is a pm in the relevant mailbox that matches that msg_id
		// so go ahead and mark it unread
		$sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . '
			SET pm_unread = 1
			WHERE msg_id = ' . $msg_id . '
				AND user_id = ' . $user->data['user_id'] . '
				AND pm_deleted = 0
				AND folder_id =' . $folder_id;
		$db->sql_query($sql);
		include($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
		update_pm_counts();
	}
	else
	{
		// if we get here, there is no pm in this user's inbox that matches that msg_id
		trigger_error('NO_MESSAGE');
	}
	$db->sql_freeresult($result);

	$meta_info = append_sid("{$phpbb_root_path}ucp.$phpEx", 'i=pm&amp;folder=inbox');
	meta_refresh(3, $meta_info);
	$message = $user->lang['PM_MARKED_UNREAD'] . '<br /><br />';
	$message .= '<a href="' . $meta_info . '">' . $user->lang['RETURN_INBOX'] . '</a><br /><br />';
	$message .= sprintf($user->lang['RETURN_INDEX'], '<a href="' . append_sid("{$phpbb_root_path}index.$phpEx") . '">', '</a>');
	trigger_error($message);
}


/**
* marks a post unread when the user clicks the mark post as unread link for the
* post in viewtopic.  Takes a single parameter, which is the post_id of the post
* being marked as unread
*/
function mark_unread_post($unread_post_id)
{
	global $db, $config, $user, $auth, $phpbb_root_path, $phpEx;

	// redirect the user to the index if the user is not logged in or the board is set up
	// to use cookies rather than the database to store read topic info (since this mod is
	// set up to work only with logged in users and not with cookies); also redirect
	// to index if the user is a bot
	if (($user->data['user_id'] == ANONYMOUS) || !$config['load_db_lastread'] || $user->data['is_bot'])
	{
		redirect(append_sid("{$phpbb_root_path}index.$phpEx"));
	}

	$user->setup('viewtopic');
	// fetch the post_time and topic_id of the post being marked as unread
	$sql = 'SELECT post_time, topic_id, forum_id
		FROM ' . POSTS_TABLE . '
		WHERE post_id = ' . $unread_post_id;
	$result = $db->sql_query($sql);
	if ($row = $db->sql_fetchrow($result))
	{
		$post_time = $row['post_time'];
		$mark_time = $post_time - 1;
		$topic_id = $row['topic_id'];
		$forum_id = $row['forum_id'];
	}
	else
	{
		// if we get here, post didn't exist so give an error
		trigger_error('NO_TOPIC');
	}
	$db->sql_freeresult($result);

	// Only proceed if the post, topic and forum exist and the user is allowed to read it
	if (!$topic_id || !$forum_id || !$auth->acl_get('f_read', $forum_id))
	{
		trigger_error('NO_TOPIC');
	}

	// find out if there already is an entry in the topics_track table
	// for this user_id and topic_id
	$sql = 'SELECT topic_id
		FROM ' . TOPICS_TRACK_TABLE . '
		WHERE topic_id = ' . $topic_id . '
			AND user_id = ' . $user->data['user_id'];
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (isset($row['topic_id']))
	{
		// in this case there is already an entry for this user and topic_id
		// in the topics_track table, so update the entry
		$sql = 'UPDATE ' . TOPICS_TRACK_TABLE . '
			SET mark_time = ' . $mark_time . '
			WHERE topic_id = ' . $topic_id . '
				AND user_id = ' . $user->data['user_id'];
		$db->sql_query($sql);
	}
	else
	{
		// in this case there is no entry for this user and topic_id
		// in the topics_track table, so insert one
		$sql = 'INSERT INTO ' . TOPICS_TRACK_TABLE . ' ' . $db->sql_build_array('INSERT', array(
			'user_id'	=> $user->data['user_id'],
			'topic_id'	=> $topic_id,
			'forum_id'	=> $forum_id,
			'mark_time'	=> $mark_time,
		));
		$db->sql_query($sql);
	}

	// now, tinker with the forums_track and topics_track tables in accorance with these rules:
	//
	//	-	calculate the forum_tracking_info time using the method that appears in display_forums();
	//
	//	-	if a post being marked unread has a post time less than the
	// 		forum_tracking_info, then add a new topics_track entry
	//		(with mark_time = forum_tracking_info before it gets changed)
	//		for each other topic in the forum that meets all of the following tests:
	//
	//			1. does not already have a topics_track entry for the user and
	//
	//			2. has a last post time less than or equal to the then current forum_tracking_info mark_time
	//
	//			3. has a last post time greater than the new $mark_time that will be used for the forums_track table
	//
	//	-	update or insert a forums_track mark_time to the time of the post minus 1
	//
	//	-	make sure that user's forums_track mark_time for forum 0 is the max of all
	//		mark_times for that user in the forums track table

	// first step, calculate the forum_tracking_info (most of the code is adapted from display_forums() in functions_display.php)

	$sql = 'SELECT mark_time
		FROM ' . FORUMS_TRACK_TABLE . '
		WHERE forum_id = ' . $forum_id . '
		AND user_id = ' . $user->data['user_id'];
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	$forum_tracking_info = (!empty($row['mark_time'])) ? $row['mark_time'] : $user->data['user_lastmark'];

	// next, check to see if the post being marked unread has a post_time before $forum_tracking _info
	if ($post_time < $forum_tracking_info )
	{
		// ok, post being marked unread has post time before $forum_tracking_info, so we will
		// need to create special topics_track entries for all topics that
		// meet the three tests described in the comment that appears before the $sql definition above
		// (since these are the topics that are currently considered 'read' and would otherwise
		// no longer be considered read when we change the forums_track entry to an earlier mark_time
		// later in the script)

		// so, fetch the topic_ids for the topics in this forum that meet the three tests
		$sql = 'SELECT t.topic_id, t.topic_last_post_time, tt.mark_time
			FROM ' . TOPICS_TABLE . ' t
			LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (t.topic_id = tt.topic_id AND tt.user_id = ' . $user->data['user_id'] . ')
			WHERE tt.mark_time IS NULL
			AND t.forum_id = ' . $forum_id . '
			AND t.topic_last_post_time <= ' . $forum_tracking_info . '
			AND	t.topic_last_post_time > ' . $mark_time;

		$result = $db->sql_query($sql);
		while($row = $db->sql_fetchrow($result))
		{
			// for each of the topics meeting the three tests, create a topics_track entry
			$sql = 'INSERT INTO ' . TOPICS_TRACK_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'user_id'	=> $user->data['user_id'],
				'topic_id'	=> $row['topic_id'],
				'forum_id'	=> $forum_id,
				'mark_time'	=> $forum_tracking_info,
			));
			$db->sql_query($sql);
		}
		$db->sql_freeresult($result);

		// finally, move the forums_track time back to $mark_time by inserting or updating the relevant row
		// to do that, find out if there already is an entry for this user_id and forum_id
		$sql = 'SELECT forum_id
			FROM ' . FORUMS_TRACK_TABLE . '
			WHERE forum_id = ' . $forum_id . '
				AND user_id = ' . $user->data['user_id'];
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (isset($row['forum_id']))
		{
			// in this case there is already an entry for this user and forum_id
			// in the forums_track table, so update the entry for the forum_id
			$sql = 'UPDATE ' . FORUMS_TRACK_TABLE . '
				SET mark_time = ' . $mark_time . '
				WHERE forum_id = ' . $forum_id . '
					AND user_id = ' . $user->data['user_id'];
			$db->sql_query($sql);
		}
		else
		{
			// in this case there is no entry for this user and forum_id
			// in the forums_track table, so insert one
			$sql = 'INSERT INTO ' . FORUMS_TRACK_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'user_id'	=> $user->data['user_id'],
				'forum_id'	=> $forum_id,
				'mark_time'	=> $mark_time,
			));
			$db->sql_query($sql);
		}

		// find out if there already is an entry for this user_id and forum_id of 0
		$sql = 'SELECT forum_id
			FROM ' . FORUMS_TRACK_TABLE . '
			WHERE forum_id = 0
				AND user_id = ' . $user->data['user_id'];
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (isset($row['forum_id']))
		{
			// in this case there is already an entry for this user and forum_id of 0
			// in the forums_track table, so update the entry to whatever the max mark time
			// is for the user in the forum_tracks table
			$sql = 'SELECT mark_time
				FROM ' . FORUMS_TRACK_TABLE . '
				WHERE forum_id != 0
				AND user_id = ' . $user->data['user_id'] . '
				ORDER BY mark_time DESC';
			$result = $db->sql_query_limit($sql,1);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			$sql = 'UPDATE ' . FORUMS_TRACK_TABLE . '
				SET mark_time = ' . $row['mark_time'] . '
				WHERE forum_id = 0
					AND user_id = ' . $user->data['user_id'];
			$db->sql_query($sql);
		}
		else
		{
			// in this case there is no entry for this user and forum_id of 0
			// in the forums_track table, so insert one with the new $mark_time
			$sql = 'INSERT INTO ' . FORUMS_TRACK_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'user_id'	=> $user->data['user_id'],
				'forum_id'	=> 0,
				'mark_time'	=> $mark_time,
			));
			$db->sql_query($sql);
		}
	}

	$meta_info = append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id);
	meta_refresh(3, $meta_info);
	$message = $user->lang['POST_MARKED_UNREAD'] . '<br /><br />';
	$message .= sprintf($user->lang['RETURN_FORUM'], '<a href="' . $meta_info . '">', '</a>') . '<br /><br />';
	$message .= sprintf($user->lang['RETURN_INDEX'], '<a href="' . append_sid("{$phpbb_root_path}index.$phpEx") . '">', '</a>');
	trigger_error($message);
}


/**
* create a sql that will generate a list of unreads for search.php
* This function is closely patterned after the case 'newposts' in search.php,
* modified to pick up unread posts (using the user_lastmark entry in the users table and
* the information in the forums_track and topics_track tables).  The parameters all come
* from variables that are already set by the time this function gets called in search.php.
*/
function unread_list_sql(&$l_search_title, &$show_results, &$sort_key, &$sort_dir, &$sort_by_sql, &$sql_sort, &$limit_days, &$sort_by_text, &$sort_days, &$s_limit_days, &$s_sort_key, &$s_sort_dir, &$u_sort_param, $m_approve_fid_sql, $ex_fid_ary, &$field)
{
	global $db, $phpbb_root_path, $phpEx, $template, $config, $user;

	// if user is not logged in, send him to a login screen and explain why
	if (!$user->data['is_registered'])
	{
		login_box('', $user->lang['LOGIN_EXPLAIN_VIEWUNREADS']);
	}

	// if the board is not using db to track unreads, $sql gets set to '' and no search results will be returned
	if (!$config['load_db_lastread'])
	{
		return '';
	}

	$l_search_title = $user->lang['VIEW_UNREADS'];
	$template->assign_vars(array(
		'U_MARK_FORUMS'				=> append_sid("{$phpbb_root_path}index.$phpEx", 'mark=forums'),
		'S_SHOW_MARK_FORUMS_LINK'	=> true,
	));

	// force sorting
	$show_results = (request_var('sr', 'topics') == 'posts') ? 'posts' : 'topics';
	$sort_key = 't';
	$sort_dir = 'd';
	$sort_by_sql['t'] = ($show_results == 'posts') ? 'p.post_time' : 't.topic_last_post_time';
	$sql_sort = 'ORDER BY ' . $sort_by_sql[$sort_key] . (($sort_dir == 'a') ? ' ASC' : ' DESC');

	gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);
	$s_sort_key = $s_sort_dir = $u_sort_param = $s_limit_days = '';

	//

	if ($show_results == 'posts')
	{
		$sql = 'SELECT p.post_id
			FROM ' . POSTS_TABLE . ' p
			LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (p.forum_id = ft.forum_id AND ft.user_id = ' . $user->data['user_id'] . ')
			LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (p.topic_id = tt.topic_id AND ft.user_id = ' . $user->data['user_id'] . ')
			WHERE
			(
				p.post_time > tt.mark_time
				OR (tt.mark_time IS NULL AND p.post_time > ft.mark_time)
				OR (tt.mark_time IS NULL AND ft.mark_time IS NULL AND  p.post_time > ' . $user->data['user_lastmark'] . ")
			)
			$m_approve_fid_sql
			" . ((sizeof($ex_fid_ary)) ? ' AND ' . $db->sql_in_set('p.forum_id', $ex_fid_ary, true) : '') . "
			$sql_sort";
		$field = 'post_id';
	}
	else
	{
		$sql = 'SELECT t.topic_id
			FROM ' . TOPICS_TABLE . ' t
			LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (t.forum_id = ft.forum_id AND ft.user_id = ' . $user->data['user_id'] . ')
			LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (t.topic_id = tt.topic_id AND tt.user_id = ' . $user->data['user_id'] . ')
			WHERE
			(
				t.topic_last_post_time > tt.mark_time
				OR (tt.mark_time IS NULL AND t.topic_last_post_time > ft.mark_time)
				OR (tt.mark_time IS NULL AND ft.mark_time IS NULL AND  t.topic_last_post_time > ' . $user->data['user_lastmark'] . ')
			)
			AND t.topic_moved_id = 0
			' . str_replace(array('p.', 'post_'), array('t.', 'topic_'), $m_approve_fid_sql) . '
			' . ((sizeof($ex_fid_ary)) ? 'AND ' . $db->sql_in_set('t.forum_id', $ex_fid_ary, true) : '') . "
			$sql_sort";
		$field = 'topic_id';
	}
	return $sql;
}

?>