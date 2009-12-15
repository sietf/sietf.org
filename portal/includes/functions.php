<?php
/*
*
* @package phpBB3 Portal  a.k.a canverPortal  ( www.phpbb3portal.com )
* @version $Id: functions.php,v 1.3 2007/08/19 17:51:00 angelside Exp $
* @copyright (c) Canver Software - www.canversoft.net
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

/**
*/

include($phpbb_root_path . 'includes/message_parser.'.$phpEx);

// fetch post for news & announce
function phpbb_fetch_posts($forum_sql, $number_of_posts, $text_length, $time, $type)
{
	global $db, $phpbb_root_path, $auth, $bbcode_bitfield, $user, $forum_id;
	
	$from_forum = ($forum_sql) ? 't.forum_id IN (' . $forum_sql . ') AND' : '';
	$post_time = ($time == 0) ? '' : 't.topic_time > ' . (time() - $time * 86400) . ' AND';

	if ($type == 'announcements')
	{
		// only global announcements for announcements block
		$topic_type = '( t.topic_type = ' . POST_ANNOUNCE . ') AND';
	}
	else if ($type == 'news_all')
	{
		// not show global announcements
		$topic_type = '( t.topic_type != ' . POST_ANNOUNCE . ' ) AND';
	}
	else
	{
		// only normal topic
		$topic_type = 't.topic_type = ' . POST_NORMAL . ' AND';
	}

	$sql = 'SELECT
			t.forum_id,
			t.topic_id,
			t.topic_last_post_id,
			t.topic_time,
			t.topic_title,
			t.topic_attachment,
			t.topic_views,
			t.poll_title,
			t.topic_replies,
			t.forum_id,
			t.topic_poster,
			u.username,
			u.user_id,
			u.user_type,
			u.user_colour,
			p.post_id,
			p.post_text,
			p.post_attachment,
			p.enable_smilies,
			p.enable_bbcode,
			p.enable_magic_url,
			p.bbcode_bitfield,
			p.bbcode_uid
		FROM
			' . TOPICS_TABLE . ' AS t,
			' . USERS_TABLE . ' AS u,
			' . POSTS_TABLE . ' AS p
		WHERE
			' . $topic_type . '
			' . $from_forum . '
			' . $post_time . '
			t.topic_poster = u.user_id AND
			t.topic_first_post_id = p.post_id AND
			t.topic_status <> 2 AND
			t.topic_approved = 1
		ORDER BY
			t.topic_time DESC';

	// query the database
	if(!($result = $db->sql_query_limit($sql, $number_of_posts)))
	{
		die('Could not query topic information for phpBB3 Portal news section');
	}

	//
	// fetch all postings
	//
	
	// Instantiate BBCode if need be
	if ($bbcode_bitfield !== '')
	{
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
		include_once($phpbb_root_path . 'includes/bbcode.' . $phpEx);
		$bbcode = new bbcode(base64_encode($bbcode_bitfield));
	}
	$posts = array();
	$i = 0;
	while ( ($row = $db->sql_fetchrow($result)) && ( ($i < $number_of_posts) || ($number_of_posts == '0') ) )
	{
		if ( ($auth->acl_get('f_read', $row['forum_id'])) || ($row['forum_id'] == '0') )
		{
			if ($row['user_id'] != ANONYMOUS && $row['user_colour'])
			{
				$row['username'] = '<b style="color:#' . $row['user_colour'] . '">' . $row['username'] . '</b>';
			}
		
			$posts[$i]['post_text'] = censor_text($row['post_text']);
			$posts[$i]['topic_id'] = $row['topic_id'];
			$posts[$i]['topic_last_post_id'] = $row['topic_last_post_id'];
			$posts[$i]['forum_id'] = $row['forum_id'];
			$posts[$i]['topic_replies'] = $row['topic_replies'];
			$posts[$i]['topic_time'] = $user->format_date($row['topic_time']);
			$posts[$i]['topic_title'] = $row['topic_title'];
			$posts[$i]['username'] = $row['username'];
			$posts[$i]['user_id'] = $row['user_id'];
			$posts[$i]['user_type'] = $row['user_type'];
			$posts[$i]['user_user_colour'] = $row['user_colour'];
			$posts[$i]['poll'] = ($row['poll_title']) ? true : false;
			$posts[$i]['attachment'] = ($row['topic_attachment']) ? true : false;
			$posts[$i]['topic_views'] = ($row['topic_views']);

			$message = $posts[$i]['post_text'];
			$message = smiley_text($message); // Always process smilies after parsing bbcodes
		
			if ($auth->acl_get('f_html', $forum_id)) 
			{
				$message = preg_replace('#<!\-\-(.*?)\-\->#is', '', $message); // Remove Comments from post content
			}
			
			// Parse the message and subject
			$message = censor_text($row['post_text']);
			$message = str_replace("\n", '<br />', $message);
			
			// Second parse bbcode here
			if ($row['bbcode_bitfield'])
			{
				$bbcode->bbcode_second_pass($message, $row['bbcode_uid'], $row['bbcode_bitfield']);
			}
			$posts[$i]['post_text']= $message;	

			$len_check = $posts[$i]['post_text'];

			if (($text_length != 0) && (strlen($len_check) > $text_length))
			{
				$posts[$i]['post_text'] = substr($len_check, 0, $text_length);
				$posts[$i]['post_text'] .= '...';
				$posts[$i]['striped'] = true;
			}

			$bbcode->bbcode_second_pass($posts[$i]['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield']);
			$posts[$i]['post_text'] = smiley_text($posts[$i]['post_text']);
			$i++;
		}
	}
	// return the result
	return $posts;
}

/**
* Censor title, return short title
*
* @param $title string title to censor
* @param $limit int short title character limit
*
*/
function character_limit(&$title, $limit = 0)
{
   $title = censor_text($title);
   if ($limit > 0)
   {
      return (strlen(utf8_decode($title)) > $limit + 3) ? truncate_string($title, $limit) . '...' : $title;
   }
   else
   {
      return $title;
   }
}

/**
* Get user avatar  / barroved from RC4
*
* @param string $avatar Users assigned avatar name
* @param int $avatar_type Type of avatar
* @param string $avatar_width Width of users avatar
* @param string $avatar_height Height of users avatar
* @param string $alt Optional language string for alt tag within image, can be a language key or text
*
* @return string Avatar image
*/
function get_user_avatar($avatar, $avatar_type, $avatar_width, $avatar_height, $alt = 'USER_AVATAR')
{
	global $user, $config, $phpbb_root_path, $phpEx;

	if (empty($avatar) || !$avatar_type)
	{
		return '';
	}

	$avatar_img = '';

	switch ($avatar_type)
	{
		case AVATAR_UPLOAD:
			$avatar_img = $phpbb_root_path . "download/file.$phpEx?avatar=";
		break;

		case AVATAR_GALLERY:
			$avatar_img = $phpbb_root_path . $config['avatar_gallery_path'] . '/';
		break;
	}

	$avatar_img .= $avatar;
	return '<img src="' . $avatar_img . '" width="' . $avatar_width . '" height="' . $avatar_height . '" alt="' . ((!empty($user->lang[$alt])) ? $user->lang[$alt] : $alt) . '" />';
}

/**
* Get user rank title and image  / barroved from RC4
*
* @param int $user_rank the current stored users rank id
* @param int $user_posts the users number of posts
* @param string &$rank_title the rank title will be stored here after execution
* @param string &$rank_img the rank image as full img tag is stored here after execution
* @param string &$rank_img_src the rank image source is stored here after execution
*
*/
function get_user_rank($user_rank, $user_posts, &$rank_title, &$rank_img, &$rank_img_src)
{
	global $ranks, $config;

	if (empty($ranks))
	{
		global $cache;
		$ranks = $cache->obtain_ranks();
	}

	if (!empty($user_rank))
	{
		$rank_title = (isset($ranks['special'][$user_rank]['rank_title'])) ? $ranks['special'][$user_rank]['rank_title'] : '';
		$rank_img = (!empty($ranks['special'][$user_rank]['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $ranks['special'][$user_rank]['rank_image'] . '" alt="' . $ranks['special'][$user_rank]['rank_title'] . '" title="' . $ranks['special'][$user_rank]['rank_title'] . '" />' : '';
		$rank_img_src = (!empty($ranks['special'][$user_rank]['rank_image'])) ? $config['ranks_path'] . '/' . $ranks['special'][$user_rank]['rank_image'] : '';
	}
	else
	{
		if (!empty($ranks['normal']))
		{
			foreach ($ranks['normal'] as $rank)
			{
				if ($user_posts >= $rank['rank_min'])
				{
					$rank_title = $rank['rank_title'];
					$rank_img = (!empty($rank['rank_image'])) ? '<img src="' . $config['ranks_path'] . '/' . $rank['rank_image'] . '" alt="' . $rank['rank_title'] . '" title="' . $rank['rank_title'] . '" />' : '';
					$rank_img_src = (!empty($rank['rank_image'])) ? $config['ranks_path'] . '/' . $rank['rank_image'] : '';
					break;
				}
			}
		}
	}
}

?>
