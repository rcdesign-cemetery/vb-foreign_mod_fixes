<?php

/**
 * AME - The Automatic Media Embedder 3.0.1
 * Copyright �2009-2010 All rights reserved by sweetsquared.com
 * This code may not be used in whole or part without explicit written
 * permission from Samuel Sweet [samuel@sweetsquared.com].
 * You may not distribute this or any of the associated files in whole or significant part
 * without explicit written permission from Samuel Sweet [samuel@sweetsquared.com]
 */


/**
 * Wrapper to handle flag saving
 *
 * @param datamanager	$dm
 */
function ame_save_flag(&$dm)
{
	
	if (!is_object($dm->registry->AME))
	{
	
		require_once(DIR . '/includes/ame_prep.php');		
		$dm->registry->AME	= new AME_message_prep($dm->registry);
	
	}
	
	if ($dm->registry->AME->verify_db())
	{
		
		$dm->registry->AME->save_disabled_flag();
			
	}
	
}

/**
 * Wrapper for the preparse. Returns $provider array for ease
 *
 * @param 	message 	$pagetext
 * @return 	$provider
 */
function ame_data_preparse(&$pagetext)
{
	
	if (class_exists("vB"))
	{
		
	    $reg = &vB::$vbulletin;
	    
	}
	else
	{
		
	    global $vbulletin;
	    $reg = &$vbulletin;
	    
	}
	
	if (!is_object($reg->AME))
	{
		
	    $reg->AME    = new AME_message_prep($reg);
	
	}
	
	if ($reg->AME->verify_db())
	{
		
		$reg->AME->preparse($pagetext);
		$providers = $reg->AME->info['providers'];
		return $providers;
		
	}
	
}


define("AME", true);

/**
 * Base class that contains shared stuff between others
 *
 */
class AME_prep_base
{
	
	/**
	 * Reference to $vbulletin object
	 *
	 * @var		object	$vbulletin
	 */
	protected	$_registry;	
	
	/**
	 * local variable for THIS_SCRIPT. 
	 *
	 * @var		string	THIS_SCRIPT
	 */
	protected 	$_scriptname;	
	
	/**
	 * Constant reflecting the area they are in after calling fetch_zone_id()
	 * -1 = unset, 1 = post, 2 = blog, 3 = group, 4 = CMS
	 *
	 * @var 	integer
	 */
	protected 	$_zoneid = -1;
		
	/**
	 * If zone has a matching id (i.e. postid, blogid, etc...)
	 * $_messageid should hold that number after calling fetch_message_id()
	 *
	 * @var 	integer
	 */
	protected 	$_messageid = -1;
		
	/**
	 * Was AME disabled by user for this message?
	 *
	 * @var 	boolean
	 */
	protected	$_flag_disabled 	= false;
		
	/**
	 * Was AME originally disabled by user for this message (i.e. this is an edit)
	 *
	 * @var unknown_type
	 */
	protected	$_flag_wasdisabled 	= false;
		
	/**
	 * User previewing?
	 *
	 * @var 	boolean
	 */
	protected	$_preview_mode		= false;	
	
	/**
	 * Ajax post?
	 *
	 * @var 	boolean
	 */
	protected	$_ajax				= false;	
		
	/**
	 * Constructor
	 *
	 * @param 	object	$registry
	 * @return AME_prep_base
	 */
	function AME_prep_base(&$registry)
	{
		
		$this->_registry = $registry;
		
		/**
		 * Calling code SHOULD set this, but why bother. Purists turn away now!
		 */
		$this->_scriptname 			= THIS_SCRIPT;
		$this->_flag_disabled		= isset($_POST['parseame_check']) ? empty($_POST['parseame']) : false;
		$this->_flag_wasdisabled 	= isset($_POST['parseame_wasdisabled']);
		$this->_preview_mode		= isset($_POST['preview']);
		$this->_ajax				= isset($_POST['ajax']) || isset($_POST['advanced']);
		
	}
	
	/**
	 * Runs a check to make sure we are registered and ready to go
	 *
	 * @return boolean
	 */
	public function verify_db()
	{
		return is_object($this->_registry->db);
	}
		
	/**
	 * Fetches and sets the $_zoneid to a constant to identify the section of the system
	 * -1 = unset, 1 = post, 2 = blog, 3 = group, 4 = CMS
	 * 
	 * @return 	integer
	 */
	public function fetch_zone_id()
	{

		if ($this->_zoneid == -1)
		{
			
			$zone = 0;
			
			switch ($this->_scriptname)
			{
				
				case 'newpost':
				case 'editpost':
				case 'newthread':
		
					$this->_zoneid = 1;
					break;
					
				case 'blog_post':
					
					$this->_zoneid = 2;
					break;
					
				case 'group':
					
					$this->_zoneid = 3;
					break;
					
				case 'vbcms':
					
					$this->_zoneid = 4;
					break;
					
			}
			
			
		}
		
		return $this->_zoneid;

	}
		
	/**
	 * sets and returns the current Messageid by clever guessing
	 * i.e. the blogid, postid, whatever. Used to help 'persist' the
	 * users option to disable auto AMEing the message
	 *
	 * @return 	integer
	 */
	public function fetch_message_id()
	{
		
		if ($this->_messageid == -1)
		{
		
			switch ($this->fetch_zone_id())
			{
				
				case 1:
					
					$this->_messageid 	= $GLOBALS['postid'];
					break;
					
				case 2:
					
					$this->_messageid 	= $GLOBALS['blogid'];
					break;
					
				case 3:
					
					$this->_messageid 	= $GLOBALS['messageinfo']['gmid'] ? $GLOBALS['messageinfo']['gmid'] : $GLOBALS['dataman']->groupmessage['gmid'];
					break;
					
				case 4:
					
					$this->_messageid = 0;
					break;
					
				default:
					
					$this->_messageid = 0;
					
			}
			
		}
		
		return $this->_messageid;
		
	}
		
	/**
	 * Peeks into DB to see if user had previously disabled auto embedding
	 * Data is stored as zoneid, messageid. If there is a match in the table,
	 * AME thinks user previously disabled AME. Otherwise AME asserts they didn't
	 *
	 * @return 	boolean
	 */
	public function fetch_disabled_flag()
	{
		
		$return 	= false;
		$zone		= $this->fetch_zone_id();
		$id			= $this->fetch_message_id();
		
		if ($this->_preview_mode)
		{
			
			$return = $this->_flag_disabled;
			
		}
		else if ($id && $zone)
		{
			
			$result = $this->_registry->db->query_first_slave("SELECT id FROM " . TABLE_PREFIX . "ame_disabled_posts WHERE id=$id and typeid=$zone");
			
			if (isset($result['id']))
			{
				
				$return = true;
				
			}
			
		}
		
		return $return;
		
	}		
		
}

/**
 * Extends base class to include functionality only needed when the 
 * editor is being loaded up. Primarily to set the option to disable
 * AME should the user want
 *
 */
class AME_editor_prep extends  AME_prep_base
{
		
	/**
	 * Array of template changes to make
	 *
	 * @var array of key value pairs:
	 * 				name 	=> 'name of template',
	 * 				type	=> 'only supports cache now(i.e. vB's template cache)',
	 * 				search 	=> 'the "hook"',
	 * 				replace => 'the injection'
	 * 						
	 */
	protected 	$_template_injections	= array();
		
	/**
	 * Constructor. Just pases on the registry object to parent class
	 *
	 * @param 	object $registry
	 * @return AME_editor_prep
	 */
	function AME_editor_prep(&$registry)
	{
		
		parent::AME_prep_base($registry);
		
	}
		
	/**
	 * Adds an entry into the $_template_injections array
	 *
	 * @param 	array $var=
	 * 				name 	=> 'name of template',
	 * 				type	=> 'only supports cache now(i.e. vB's template cache)',
	 * 				search 	=> 'the "hook"',
	 * 				replace => 'the injection' 
	 */
	public function add_template_injection($var = array())
	{
		
		$this->_template_injections[] = $var;
		
	}
		
	/**
	 * Loops through local $_template_injections and applies them
	 *
	 */
	public function do_template_injections()
	{
		
		if (sizeof($this->_template_injections))
		{
			
			$checked = "checked=\"checked\"";
			$marked = 0;
			
			if ($this->fetch_disabled_flag())
			{
				
				$checked 	= "";
				$marked		= 1;
			
			}
			
			foreach ($this->_template_injections as $value)
			{
				
				if ($value['type'] == 'cache' || !$value['type'])
				{
					
					$this->_registry->templatecache["$value[name]"] = $result = str_replace($value['search'], sprintf($value['replace'], $marked, $checked), $this->_registry->templatecache["$value[name]"]);
				
				}
			
			}
		
		}
	
	}	
	
}

/**
 * Class that performs the transforming of urls into video tags
 *
 */
class AME_message_prep extends AME_prep_base
{

	/**
	 * local array of regex's
	 *
	 * @var	array
	 */
	var 		$info = -1;
		
	/**
	 * local array of placeholders for substituted tags
	 *
	 * @var unknown_type
	 */
	protected 	$_subbed = array();
		
	/**
	 * local iterator for $_subbed
	 *
	 * @var unknown_type
	 */
	protected 	$_subbedinc = 0;
		
	/**
	 * Constructor
	 *
	 * @param 	object	$registry
	 * @return AME_message_prep
	 */
	function AME_message_prep($registry)
	{
		
		parent::AME_prep_base($registry);		
		
	}		
	
	/**
	 * Enter description here...
	 *
	 * @param	boolean 	$refresh forces a rebuild of the $info array if it is cached
	 * @return	array		$info
	 */
	public function &fetch_info($refresh = false, $forcedb = false)
	{
		
		if ($this->info == -1)
		{
			
			$this->load_info($refresh, $forcedb);
			
		}
		
		return $this->info;
		
	}
		
	/**
	 * Check if AME should auto convert or not
	 * 
	 * @return 	boolean
	 */
	protected function convert()
	{
		
		$return = !$this->_flag_disabled;

		if ($this->_ajax)
		{
			
			$return = !$this->fetch_disabled_flag();
			
		}
		($hook = vBulletinHook::fetch_hook('ame_convert_check')) ? eval($hook) : false;		
		return $return;
		
	}	
	
	/**
	 * If user doesn't want auto embedding, we will save their choice in a seperate table.
	 * This prevents us altering vB's default scheme for something that is seldom used
	 */
	public function save_disabled_flag()
	{
		
		$id 	= $this->fetch_message_id();
		$zone	= $this->fetch_zone_id();
		
		if ($id && $zone)
		{
			
			$wasdisabled 	= $this->_flag_wasdisabled;
			$isdisabled		= !$this->convert();
			
			if ($wasdisabled && !$isdisabled)
			{
				
				$sql = "DELETE FROM " . TABLE_PREFIX . "ame_disabled_posts WHERE typeid=$zone and id=$id";
				
			}
			else
			{
				
				$sql = "REPLACE INTO " . TABLE_PREFIX . "ame_disabled_posts (typeid, id) VALUES ($zone, $id)";
				
			}
			
			if (isset($sql))
			{
				
				$this->_registry->db->query_write($sql);
				
			}
				
		}
				
	}
	
	/**
	 * Automatically finds and converts definitions within URL tags
	 *
	 * @param 	string 	$text - the message to parse
	 * @return	string	The altered text (if any)
	 */
	function preparse(&$text)
	{
		
		($hook = vBulletinHook::fetch_hook('ame_prep_start')) ? eval($hook) : false;
		
		if (stripos($text, "[url") !== false)
		{
			
			if (!$this->convert())
			{
				
				return $text;
								
			}
			else
			{
				
				$info 			= $this->fetch_info();
				$this->_subbed 	= array();
				
				if (sizeof($info))
				{
					
					$text = preg_replace('%(\[(quote|php|html|code|nomedia)([^\]]*)\](.*?)\[/(\2)\])%sime', '$this->substitute(\'\1\', $this->_subbed)', $text);
//					$text = preg_replace($info['find'], $info['replace'], $text);

                    $text = preg_replace($info['find_opt'], $info['replace'], $text);
                    $text = preg_replace($info['find_simple'], $info['replace'], $text);

				}
		
				if (sizeof($this->_subbed))
				{
					
					$text = preg_replace('/<<<@!([0-9]+)!@>>>/sme', '$this->unsubstitute(\'\\1\')', $text);
				
				}
		
			}
			
		}
		
		($hook = vBulletinHook::fetch_hook('ame_prep_end')) ? eval($hook) : false;	
		return $text;
		
	}	
	
	/**
	 * Grabs definitions.
	 *
	 * @return	array
	 */
	protected function load_info($refresh = false, $forcedb = false)
	{
		
		if ($refresh)
		{		
			$this->info = -1;
		}
		
		if (!is_array($this->info))
		{
			
			if ($this->_registry->options['ame_file_cache'] && !$forcedb)
			{
				
				include($this->_registry->options['ame_cache_path'] . "ame_prep_cache.php");
				
			}
			
			$url_tail = '[\w:\/?\[\]@!$&\'()*+.,;="%\-]*';

			if (!is_array($info)) 
			{
			
				$results = $this->_registry->db->query_read_slave("SELECT
							provider, url, regex_url, regex_scrape, tagoption
							FROM " . TABLE_PREFIX . "bbcode_video
							ORDER BY priority");
				
				while($result = $this->_registry->db->fetch_array($results))
				{
					
					$info['providers']["$result[tagoption]"] 	= $result;			
//					$info['find'][] 							= "#\[url(?:\]|=\"?)($result[regex_url][&\w;=\+_\-\%\.\,\#]*)(?:\"?\](?:.*?))?\[/url\]#im";
					$info['replace'][] 							= "[video]\\1[/video]";

                    $info['find_opt'][] 						= '#\[url=\"?(' . $result['regex_url'] . $url_tail . ')(?:\"?\])+(?:.*?)\[\/url\]#im';
                    $info['find_simple'][] 						= '#\[url\](' . $result['regex_url'] . $url_tail . ')\[\/url\]#im';
				}
				
			}
		}
		
		$this->info = $info;
		
	}	
	
	/**
	 * Function that swaps out certain tags with tokens to prevent
	 * urls from getting parsed in quotes, etc...
	 *
	 * @param 	string	$sub - regex result
	 * @return 	string
	 */
	function substitute($sub)
	{
		
		$this->_subbedinc++;
		$this->_subbed[$this->_subbedinc] = $sub;
		return "<<<@!" .$this->_subbedinc . "!@>>>";
	
	}	
	
	/**
	 * Reverses the substituted items
	 *
	 * @param	string 	$item - token
	 * @return 	string	original text
	 */
	function unsubstitute($item)
	{
		
		return str_replace('\"', '"', $this->_subbed[$item]);
	
	}	
	
}

?>