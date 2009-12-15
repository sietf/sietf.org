<?php
/**
*
* syndication [English]
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
	'CUSTOM_SYNDICATION_TITLE'							=> 'Create custom syndication feed',

	'INVALID_INPUT'													=> 'This script has been called incorrectly.',

	'NOTHING_SELECTED'											=> 'Please select at least one forum or check “All forums”.',
	'NUMBER_ITEMS'												=> 'Number of items displayed in the feed',

	'PRIVATE_FEED'													=> 'private feed',
	'PRIVATE_FEED_CHANCEL'									=> "Access to this feed is restricted. Please login with your regular board's username and password.",

	'SELECT_FORUMS'												=> 'Select forums',
	'SELECT_FORUMS_EXPLAIN'									=> 'Select forums you would like to include in your feed.',
	'SERVICE_UNAVAILABLE'										=> 'Sorry, but this feature has been disabled by an administrator.',
	'SYNDICATION_ADMIN_LIMIT'								=> 'Please note that the administrator has set an limit of %d items which you may not exceed.',
	'SYNDICATION_DISABLED'									=> 'Sorry, but syndication has been disabled for this forum or you do not have sufficent permissions.',
	'SYNDICATION_FORUM_TOPICS'			=> 'latest topics of this forum',
	'SYNDICATION_FORUM_POSTS'			=> 'latest posts of this forum',
	'SYNDICATION_PM_DESCRIPTION'						=> 'Latest PMs from “%1$s” folder on “%2$s”.',
	'SYNDICATION_PM_TITLE'									=> 'Latest PMs from “%1$s” folder',
	'SYNDICATION_POSTS_CATEGORIES_DESCRIPTION'		=> 'Latest posts from categories on “%s” board.',
	'SYNDICATION_POSTS_CATEGORIES_TITLE'				=> 'Latest posts from various categories',
	'SYNDICATION_POSTS_CATEGORY_DESCRIPTION'		=> 'Latest posts from category “%s” on “%s” board.',
	'SYNDICATION_POSTS_CATEGORY_TITLE'				=> 'Latest posts from “%s”',
	'SYNDICATION_POSTS_DESCRIPTION'					=> 'Latest posts from forum “%1$s” on “%2$s”.',
	'SYNDICATION_POSTS_TITLE'								=> 'Latest posts from “%s” board.',
	'SYNDICATION_POSTS_GLOBAL_DESCRIPTION'		=> 'Latest posts from “%s” board.',
	'SYNDICATION_POSTS_GLOBAL_TITLE'					=> 'Latest posts from “%s” board.',
	'SYNDICATION_POSTS_VARIOUS_DESCRIPTION'	=> 'Various latest posts from “%s” board.',
	'SYNDICATION_POSTS_VARIOUS_TITLE'				=> 'Various latest posts from “%s”',
	'SYNDICATION_TOPIC_POSTS'										=> 'latest posts of this topic',
	'SYNDICATION_TOPIC_POSTS_DESCRIPTION'			=> 'Latest posts from topic “%1$s” on “%2$s”.',
	'SYNDICATION_TOPIC_POSTS_TITLE'					=> 'Latest posts from topic “%1$s”',
	'SYNDICATION_TOPICS_CAT'									=> 'latest topics of this category',
	'SYNDICATION_POSTS_CAT'									=> 'latest posts of this category',
	'SYNDICATION_TOPICS_SUB'	=> 'latest topics of this forum (including subforums)',
	'SYNDICATION_POSTS_SUB'	=> 'latest posts of this forum (including subforums)',
	'SYNDICATION_TOPICS_CATEGORIES_DESCRIPTION'		=> 'Latest topics from various categories on “%s”.',
	'SYNDICATION_TOPICS_CATEGORIES_TITLE'				=> 'Latest topics from various categories',
	'SYNDICATION_TOPICS_CATEGORY_DESCRIPTION'		=> 'Latest topics from category “%s” on “%s” board.',
	'SYNDICATION_TOPICS_CATEGORY_TITLE'				=> 'Latest topics from “%s”',
	'SYNDICATION_TOPICS_DESCRIPTION'					=> 'Latest topics from forum “%1$s” on “%2$s”.',
	'SYNDICATION_TOPICS_TITLE'								=> 'Latest topics from “%s”',
	'SYNDICATION_TOPICS_GLOBAL_DESCRIPTION'		=> 'Latest topics from “%s” board.',
	'SYNDICATION_TOPICS_GLOBAL_TITLE'				=> 'Latest topics from “%s”',
	'SYNDICATION_TOPICS_VARIOUS_DESCRIPTION'	=> 'Various latest topics from “%s” board.',
	'SYNDICATION_TOPICS_VARIOUS_TITLE'				=> 'Various latest topics from “%s”',

	'TOPICS_OR_POSTS'											=> 'Topics or posts',
	'TOPICS_OR_POSTS_EXPLAIN'								=> 'Please select whether you would like to syndicate only topic titles including the first post or every post.',
));

?>