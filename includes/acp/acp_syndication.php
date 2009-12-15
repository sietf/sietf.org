<?php
/**
*
* @package acp
* @version $Id$
* @copyright (c) 2008 Niklas Schmidtmer
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* This is largely code copied from acp_board.php
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package acp
*/
class acp_syndication
{
	var $u_action;
	var $new_config = array();

	function main($id, $mode)
	{
		global $db, $user, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		$this->tpl_name = 'acp_syndication';
		$this->page_title = 'SYNDICATION_TITLE';

		$submit = (isset($_POST['submit'])) ? true : false;

		$form_key = 'acp_syndication';
		add_form_key($form_key);

		$display_vars = array(
			'title'	=> 'SYNDICATION_TITLE',
			'vars'	=> array(
				'legend'				=> 'SYNDICATION_LEGEND',
				'enable_syndication'	=> array('lang' => 'ENABLE_SYNDICATION', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => false),
				'syndication_default'	=> array('lang'	=> 'SYNDICATION_DEFAULT', 'validate' => 'int', 'type' => 'custom', 'method' => 'select_syndication_default', 'explain' => true),
				'syndication_items'	=> array('lang' => 'SYNDICATION_ITEMS',	'validate' => 'int',	'type' => 'text:2:2', 'explain' => false),
				'syndication_ttl'	=> array('lang' => 'SYNDICATION_TTL', 'validate' => 'int', 'type' => 'text:3:3', 'explain' => true, 'append' => ' ' . $user->lang['SECONDS']),
			)
		);

		$this->new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		$error = array();

		// We validate the complete config if whished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}

		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		foreach ($display_vars['vars'] as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
			{
				continue;
			}

			if ($config_name == 'auth_method')
			{
				continue;
			}

			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

			if ($submit)
			{
				set_config($config_name, $config_value);

				// we update the database structure to reflect this change for new users and the guest account. Also check for valid value
				if ($config_name == 'syndication_default' && ($config_value == SYNDICATION_ATOM || $config_value == SYNDICATION_RSS2))
				{
					include($phpbb_root_path . 'includes/db/db_tools.' . $phpEx);
					$db_tools = new phpbb_db_tools($db);
					$db_tools->perform_schema_changes(array(
						'change_columns'	=> array(
							USERS_TABLE		=> array(
								'user_syndication_method'	=> array('BOOL', $config_value)
							)
						)
					));

					$sql = 'UPDATE ' . USERS_TABLE . "
						SET user_syndication_method = $config_value
						WHERE user_id = " . ANONYMOUS;
					$db->sql_query($sql);
				}
			}
		}

		if ($submit)
		{
			add_log('admin', 'LOG_CONFIG_SYNDICATION');

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang[$display_vars['title']],
			'L_TITLE_EXPLAIN'	=> $user->lang[$display_vars['title'] . '_EXPLAIN'],

			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),

			'S_SYNDICATION_DEFAULT'	=> $config['syndication_default'],

			'U_ACTION'			=> $this->u_action)
		);

		// Output relevant page
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}

			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars),
				)
			);
		
			unset($display_vars['vars'][$config_key]);
		}
	}

	/**
	* Select default syndication method
	*/
	function select_syndication_default($value, $key = '')
	{
		global $user, $config;

		$radio_ary = array(SYNDICATION_ATOM => 'SYNDICATION_ATOM', SYNDICATION_RSS2 => 'SYNDICATION_RSS2');

		return h_radio('config[syndication_default]', $radio_ary, $value, $key);
	}
}

?>