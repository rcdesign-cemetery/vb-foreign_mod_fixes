<?php

/**
 * AME - The Automatic Media Embedder 3.0.1
 * Copyright ©2009-2010 All rights reserved by sweetsquared.com
 * This code may not be used in whole or part without explicit written
 * permission from Samuel Sweet [samuel@sweetsquared.com].
 * You may not distribute this or any of the associated files in whole or significant part
 * without explicit written permission from Samuel Sweet [samuel@sweetsquared.com]
 */


/**
 * Header stuff
 */
error_reporting(E_ALL & ~E_NOTICE);
define("AME", true);
$phrasegroups = array('advertising', 'notice', 'ame_admincp');
$specialtemplates = array();
require_once('./global.php');



/**
 * Redirects to vB's rebuildvideo function
 * which resets all data back to default
 */
if ($_REQUEST['do'] == 'doresetall')
{
	print_cp_header("AME");
	print_cp_redirect('index.php?do=buildvideo');
}



/**
 * A rewritten version of half of vB's similar function. Would be far better moving forward
 * if vB would break the functionality appart as per http://www.vbulletin.com/forum/project.php?issueid=34122
 * Once that gets done Ill revert to it.
 */
if ($_REQUEST['do'] == 'rebuildvideos')
{

	$defs = array();
	
	$codes = $db->query_read_slave("SELECT provider, tagoption, embed FROM " . TABLE_PREFIX . "bbcode_video ORDER BY priority, provider");

	while ($code = $db->fetch_array($codes))
	{
		$template .= "<vb:" . ($template ? "else" : "") . "if condition=\"\$provider == '$code[tagoption]'\"". ($template ? "/" : "") . ">\r\n\t$code[embed]\r\n";
	}
	
	$template .= "</vb:if>";
	
	require_once(DIR . '/includes/adminfunctions_template.php');
	$t = compile_template($template);
	
	if ($exists = $db->query_first_slave("SELECT templateid	FROM " . TABLE_PREFIX . "template WHERE	title = 'bbcode_video' AND product IN ('', 'vbulletin')	AND	styleid = -1"))
	{
		
		$db->query_write("UPDATE " . TABLE_PREFIX . "template SET
				template = '" . $db->escape_string($t) . "',
				template_un = '" . $db->escape_string($template) . "',
				dateline = " . TIMENOW . ",
				username = '" . $db->escape_string($vbulletin->userinfo['username']) . "',
				version = '" . $vbulletin->options['templateversion'] . "'
			WHERE
				templateid = $exists[templateid]
		");
		
	
	}
	else
	{
		
		$db->query_write("REPLACE INTO " . TABLE_PREFIX . "template (template, template_un, dateline, username, templatetype, styleid, title, product, version) VALUES (
					'" . $db->escape_string($t) . "',
					'" . $db->escape_string($template) . "',
					" . TIMENOW . ",
					'" . $db->escape_string($vbulletin->userinfo['username']) . "',
					'template',
					'-1',
					'bbcode_video',
					'vbulletin',
					'" . $vbulletin->options['templateversion'] . "')
		");
		
	}	

	print_cp_header("AME");
	build_all_styles();

	define('CP_REDIRECT', 'ame.php' . ($_REQUEST['to'] ? '?do=' . $_REQUEST['to'] . ($_REQUEST['toid'] ? "&amp;providerid=" . $_REQUEST['toid'] : '') : ''));
	print_stop_message('rebuilt_video_bbcodes_successfully');	
}



/**
 * Don't print off header stuff if we are exporting
 * definitions
 */
if ($_REQUEST['do'] != "doexport")
{
print_cp_header("AME");
?>

	<script type="text/javascript">
		function grab_left(str, n)
	    {
	            if (n <= 0)
	            {
	                return "";
	            }
	            else if (n > String(str).length)
	            {
	                return str;
	            }
	            else
	            {
	                return String(str).substring(0,n);
	            }
	    }
	
	    function tick_all(formobj, type, value)
	    {
	            for (var i =0; i < formobj.elements.length; i++)
	            {
	                   var elm = formobj.elements[i];
	                   if (elm.type == "checkbox")
	                   {
	                         if (grab_left(elm.name,String(type).length) == type)
	                         {
	                             elm.checked = value;
	                         }
	                   }
	            }
	    }
	    
	    function ame_toggle_group(element_id)
	    {
	    	var obj = fetch_object('td_' + element_id);
	    	
	    	if (typeof obj != "undefined")
	    	{
	    		if (obj.style.display == "none")
	    		{
	    			obj.style.display = "block";
	    		}
	    		else
	    		{
	    			obj.style.display = "none";
	    		}
	    	}
	    	
	    	var obj = fetch_object('collapse_' + element_id);
	    	
	    	if (typeof obj != "undefined")
	    	{	
	    		if (obj.alt=="Collapse")
	    		{
	    			obj.src = "../cpstyles/<?=$vbulletin->options['cpstylefolder']?>/cp_collapse.gif";
	    			obj.alt = "Expand";
	    		}
	    		else
	    		{
	    			obj.src = "../cpstyles/<?=$vbulletin->options['cpstylefolder']?>/cp_expand.gif";		    			
	    			obj.alt = "Collapse";
	    		}
	    	}
	    }	    
	</script>
	<?php
	
}



$do = $_REQUEST['do'] ? $_REQUEST['do'] : $_POST['do'];



/**
 * Simple vB Redirection Function
 *
 * @param string $do
 * @param string $stopmessage
 * @param string $var
 */
function redirect($do, $stopmessage, $var = '')
{
	define('CP_REDIRECT', "ame.php?do=$do");
	print_stop_message($stopmessage, $var);
}



/**
 * Redirects page to the process that rebuilds the bbcode_video templates
 *
 * @param unknown_type $do
 */
function redirect_to_rebuild($do = '')
{
	redirect("rebuildvideos&to=$do", 'ame_rebuilding_bbcode_video_template');
}


/**
 * Rather lame function to ensure that vB hasn't overwritten any changed defs
 *
 * @return boolean True = all ok, false = different.
 */
function check_store_diffs()
{
	
	global $db;
	$return = true;
	
	//There must be a better way to do this?
	$results = $db->query_read_slave("Select * FROM " . TABLE_PREFIX . "bbocde_video");
	
	$info = array();
	
	while($result = $db->fetch_array($results))
	{
			
			$info["$result[tagoption]"] = serialize($result);
		
	}
	
	if (sizeof($info))
	{
		
		$results = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "ame_defstore");
		
		while($result = $db->fetch_array($results))
		{
			
			if ($info["$result[tagoption]"] != $result['data'])
			{
				
				$return = false;
				break;
								
			}
			
		}
		
	}
	
	return $return;	
	
}


/**
 * Function to push contents of defstore to bbcodE_video
 *
 */
function revert_to_defstore()
{
	
	global $db;
	$rows = "";
	
	$results = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "ame_defstore");
	
	while ($result = $db->fetch_array($results))
	{
		
		$data = unserialize($result['data']);
		
		if (sizeof($data))
		{
			
			$insert = "";
			
			foreach($data as $key => $value)
			{
				$columns["$key"] 	= $key;
				$insert 			.= ($insert ? ", " : "") . "'" . $db->escape_string($value) . "'"; 
			}
			
			if ($insert)
			{
				
				$rows .= ($rows ? ",\n\t" : "") . "($insert)";
				
			}
			
		}
				
	}
	
	if ($rows)
	{
		
		$db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "bbcode_video");		
		$sql = "INSERT INTO " . TABLE_PREFIX . "bbcode_video (" . implode(",", $columns) . ") VALUES $rows ";
		$db->query_write($sql);
		
	}
	
	
}



/**
 * Writes system to file cache and backup definitions
 *
 */
function cachedb()
{
	global $vbulletin;
	
	$results = $vbulletin->db->query_read("SELECT * FROM " . TABLE_PREFIX . "bbcode_video");
	
	while ($result = $vbulletin->db->fetch_array($results))
	{
		
		$sql .= (isset($sql) ? "," : "") . "\n\t('" . $vbulletin->db->escape_string($result['tagoption']) . "', " . TIMENOW . ", '" . $vbulletin->db->escape_string(serialize($result)) . "')";
		
	}
	
	if ($sql)
	{
		$sql = "INSERT INTO " . TABLE_PREFIX . "ame_defstore (tagoption, dateline, data) VALUES $sql";
		$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "ame_defstore");
		$vbulletin->db->query_write($sql);
	}
	
	if ($vbulletin->options['ame_file_cache'])
	{
		if (is_dir($vbulletin->options['ame_cache_path']))
		{
			//Get provider array
			require_once(DIR . "/includes/ame_prep.php");
			$tmp = new AME_message_prep($vbulletin);
			$array = $tmp->fetch_info(true, true);
			
			//write it
			require_once(DIR . "/includes/ame_cache.php");
			$cache = new AME_file_cache($vbulletin->options['ame_cache_path'], 'ame_prep_cache');
			$cache->save($array, '$info');			
		}
	}
}

/**
 * Checks existince of a stupid vbcms bug that has been around since RC2
 *
 */
function badhook_check()
{
	if (file_exists(DIR . "/includes/xml/hooks_vbcms.xml"))
	{
		$badhookfile = file_read(DIR . "/includes/xml/hooks_vbcms.xml");
		if (strpos($badhookfile, "data_preparse_bbcode_video_start"))
		{
			print_table_header("WARNING");
			print_description_row("vBulletin contains a known bug that prevents AME from working. You must follow these steps or you may as well disable AME now");
			print_description_row("1. Edit your <strong>includes/xml/hooks_vbcms.xml</strong> file");
			print_description_row("2. Delete <strong><hook>data_preparse_bbcode_video_start</hook></strong> and save the file.");
			print_description_row("3. Rebuild your hooks by browsing to your Plugin Manager, edit the <strong>AME - Auto Convert URLs</strong> hook and save (you don't need to change anything).");
			print_description_row("You can read more about this issue here: <a href='http://www.vbulletin.com/forum/project.php?issueid=33859' target='_blank'>http://www.vbulletin.com/forum/project.php?issueid=33859</a>");
			print_table_break();
		}
		
	}
	
}




/**
 * Displays list of all installed definitions
 */
if ($do == "" || $do == "display")
{
	
	print_form_header('ame', 'savedisplay');
	badhook_check();
	print_table_header("Video Definitions");	
	$results = $db->query_read_slave("SELECT providerid, provider FROM " . TABLE_PREFIX . "bbcode_video ORDER BY priority, provider");

	if ($db->num_rows($results))
	{
		while ($result = $db->fetch_array($results))
		{
			print_description_row("<a href=\"ame.php?do=edit&amp;providerid=$result[providerid]\">$result[provider]</a>");
		}
	}
	else
	{
		print_description_row("ALl your freaking video codes are gone. Whats up with that?");
	}
	
	print_table_footer(2, construct_button_code("Add New", "ame.php?do=edit") . " <input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"$vbphrase[save]\" accesskey=\"s\" />");
		
}



/**
 * Saves settings and builds cache if needed
 */
if ($do == "savesettings")
{
	
		$vbulletin->input->clean_array_gpc('p', array(
			'enable_cache'		=> TYPE_BOOL,
			'cache_path'		=> TYPE_STR,
		));	
		
		//##### Safety check for trailing slash. Man aren't we cautious! #####
		if ($vbulletin->GPC['enable_cache'])
		{
			if (strlen(trim($vbulletin->GPC['cache_path'])))
			{
		    	if ($vbulletin->GPC['cache_path'][strlen($vbulletin->GPC['cache_path'])-1] != "/")
		    	{
		    		$vbulletin->GPC['cache_path'] .= '/';
		    	}
			}
	    	
	    	if ($vbulletin->GPC['enable_cache'])
	    	{
	    		
	    		require_once(DIR . "/includes/ame_cache.php");
	    		$cache = new AME_file_cache();
	    		
	    		if (!$cache->verify_path_exists($vbulletin->GPC['cache_path']) || trim($vbulletin->GPC['cache_path']) == '')
	    		{
	    			$errors[] = construct_phrase($vbphrase['ame_cache_path_invalid'], $vbulletin->GPC['cache_path'], getcwd() . "/ame/");
	    		}
	    		
	    		if (!$cache->verify_path_writeable($vbulletin->GPC['cache_path']))
	    		{
	    			$errors[] = construct_phrase($vbphrase['ame_cache_path_unwriteable'], $vbulletin->GPC['cache_path']);
	    		}
	    		
	    		if (sizeof($errors))
	    		{
	    			$vbulletin->GPC['enable_cache'] = 0;
	    		}
	    	}	
		}
		
	    $db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value='" . $db->escape_string($vbulletin->GPC['enable_cache']) . "' WHERE varname='ame_file_cache' AND grouptitle='version'");
	    $db->query_write("UPDATE " . TABLE_PREFIX . "setting SET value='" . $db->escape_string($vbulletin->GPC['cache_path']) . "' WHERE varname='ame_cache_path' AND grouptitle='version'");		
	    
	    build_options();
	    
	    if (!sizeof($errors))
	    {
			cachedb();
			redirect('settings', 'ame_saved_settings');
	    }
	    else 
	    {
	    	$do = "settings";
		    $vbulletin->options['ame_file_cache'] = $vbulletin->GPC['enable_cache'];
		    $vbulletin->options['ame_cache_path'] = $vbulletin->GPC['cache_path'];	    	
	    }
	    
	
}



/**
 * Displays settings
 */
if ($do == "settings")
{
	
	print_form_header('ame', 'savesettings');
	badhook_check();
	if (sizeof($errors))
	{
		print_table_header($vbphrase['ame_error_warning']);
		foreach ($errors as $value)
		{
			print_description_row($value);
		}
		print_table_break();
	}
	
	//##### GLOBAL SETTINGS #####	
	print_table_header($vbphrase['ame_cache_settings']);
	print_yes_no_row($vbphrase['ame_file_cache'], 'enable_cache', $vbulletin->options['ame_file_cache']);
	print_input_row(construct_phrase($vbphrase['ame_cache_path'], getcwd() . "/ame/"), 'cache_path', $vbulletin->options['ame_cache_path']);	
	print_submit_row();
	
}



/**
 * Saves a defintion (new or existing)
 */
if ($do == "save")
{
	
	$vbulletin->input->clean_array_gpc('p', array(
			'id'			=> TYPE_UINT,
			'provider'		=> TYPE_STR,
			'tagoption'		=> TYPE_STR,
			'url'			=> TYPE_STR,
			'regex_url'		=> TYPE_STR,
			'regex_scrape'	=> TYPE_STR,
			'embed'			=> TYPE_STR,
			'original_embed'=> TYPE_STR,
			'priority'		=> TYPE_UINT,
			'return'		=> TYPE_STR,	
	));
	
	
	$id 			= $vbulletin->GPC['id'];
	$provider		= $vbulletin->GPC['provider'];
	$tagoption		= $vbulletin->GPC['tagoption'];
	$url			= $vbulletin->GPC['url'];
	$regex_url		= $vbulletin->GPC['regex_url'];
	$regex_scrape	= $vbulletin->GPC['regex_scrape'];
	$embed			= $vbulletin->GPC['embed'];
	$priority		= $vbulletin->GPC['priority'];
	$return 		= $vbulletin->GPC['return'];
	
	$ame_errors		= array();
	
	if ($id)
	{
		$sql = "UPDATE " . TABLE_PREFIX . "bbcode_video SET " .
			"url = '" . $db->escape_string($url) . "', regex_url = '" . $db->escape_string($regex_url) . "', " .
			"regex_scrape = '" . $db->escape_string($regex_scrape) . "', embed = '" . $db->escape_string($embed) . "', " . 
			"priority = " . strval($priority) . " WHERE providerid = $id ";
	}
	else
	{
		
		$check = $db->query_first_slave("SELECT providerid FROM " . TABLE_PREFIX . "bbcode_video WHERE provider = '" . $db->escape_string($provider) . "'");
		
		if ($check['providerid'])
		{
			$ame_errors[] = "You cannot use <strong>$provider</strong> as this provider is already in use.";
		}
		
		$check = $db->query_first_slave("SELECT providerid FROM " . TABLE_PREFIX . "bbcode_video WHERE tagoption = '" . $db->escape_string($tagoption) . "'");
		
		if ($check['providerid'])
		{
			$ame_errors[] = "You cannot use <strong>$tagoption</strong> as this tag is already in use.";
		}				
		
		$sql = "INSERT INTO " . TABLE_PREFIX . "bbcode_video (provider, tagoption, url, regex_url, regex_scrape, embed, priority) VALUES (" . 
			"'" . $db->escape_string($provider) . "', " .
			"'" . $db->escape_string($tagoption) . "', " .
			"'" . $db->escape_string($url) . "', " .
			"'" . $db->escape_string($regex_url) . "', " .
			"'" . $db->escape_string($regex_scrape) . "', " .
			"'" . $db->escape_string($embed) . "', " .
			"'" . strval($priority) . "') ";		
	}
	
	
	if (!sizeof($ame_errors))
	{
		$db->query_write($sql);
		
		if ($return)
		{
			$do = "edit";
		}
		
		if (!$id)
		{
			$id = $db->insert_id();
		}
		
		cachedb();
		redirect_to_rebuild(($return ? "edit&amp;toid=$id" : "display"));
		/*
		if ($vbulletin->GPC['original_embed'] != $embed)
		{
			redirect_to_rebuild(($return ? "edit&amp;toid=$id" : "display"));
		}
		else 
		{
			redirect(($return ? "edit&amp;providerid=$id" : "display"), 'ame_saved_x');
		}
		*/
		
	}
	else 
	{
		
		$do = "edit";
		$video = array(
			'id'			=> $id,
			'provider'		=> $provider,
			'tagoption'		=> $tagoption,
			'url'			=> $url,
			'tagoption'		=> $tagoption,
			'regex_url'		=> $regex_url,
			'regex_scrape'	=> $regex_scrape,
			'embed'			=> $embed,
			'original_embed'=> $vbulletin->GPC['original_embed'],
			'priority'		=> $priority,
		);
	}
}



/**
 * Edits existing definition or allows creation of a new one.
 */
if ($do == "edit" || $do == "new")
{
	
	print_form_header('ame', 'save');
	badhook_check();
	if (sizeof($ame_errors))
	{
		print_table_header("Error");
		
		foreach ($ame_errors as $value)
		{
			print_description_row("$value");
		}
		
		print_table_break();
	}
	
	if (!$id)
	{
		$id = $vbulletin->input->clean_gpc('r', 'providerid', TYPE_INT);
	}
	
	if ($id)
	{
		if (!$video)
		{
			$video = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "bbcode_video WHERE providerid=$id");
		}
		
		print_table_header("$video[provider]");
		construct_hidden_code("id", $id);
		construct_hidden_code("provider", $video['provider']);
		construct_hidden_code("tagoption", $video['tagoption']);
		print_label_row($vbphrase['ame_provider'], $video['provider']);
		print_label_row($vbphrase['ame_tagoption'], $video['tagoption']);
		
	}
	else
	{
		
		print_table_header("New Video Definition");
		print_input_row($vbphrase['ame_provider'], "provider", $video['provider']);
		print_input_row($vbphrase['ame_tagoption'], "tagoption", $video['tagoption']);		
		
	}
	

	print_input_row($vbphrase['ame_url'], "url", $video['url']);
	print_textarea_row($vbphrase['ame_regex_url'], "regex_url", $video['regex_url']);
	print_textarea_row($vbphrase['ame_regex_scrape'], "regex_scrape", $video['regex_scrape']);
	print_textarea_row($vbphrase['ame_embed'], "embed", $video['embed']);
	construct_hidden_code("original_embed", $video['embed']);
	print_input_row($vbphrase['ame_priority'], "priority", $video['priority']);
	
	print_submit_row($vbphrase['save'], '_default_', 2, '',
		"<input type=\"submit\" class=\"button\" tabindex=\"1\" name=\"return\" value=\"$vbphrase[ame_save_and_reload]\" accesskey=\"e\" />" .
		($id ? construct_button_code($vbphrase['delete'], 'ame.php?do=delete&id=' . $id) : "")
		);	
}



/**
 * Politely enquires about deletion
 */
if ($do == "delete")
{
	$id = $vbulletin->input->clean_gpc('r', 'id' , TYPE_UINT);

	if ($id)
	{
		$result = $db->query_first_slave("SELECT provider FROM " . TABLE_PREFIX . "bbcode_video  WHERE providerid=$id");

		print_form_header('ame', 'kill');
		badhook_check();
		construct_hidden_code('id', $id);
		print_table_header($vbphrase['ame_confirm_delete']);
		print_description_row(construct_phrase($vbphrase['ame_confirm_delete_question'], $result['provider']));
		print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

	}	
}



/**
 * Ruthlessly kills definition
 */
if ($do == "kill")
{
	
	$id = $vbulletin->input->clean_gpc('r', 'id', TYPE_UINT);
	
	if ($id)
	{
		$result = $db->query_first_slave("SELECT provider FROM " . TABLE_PREFIX . "bbcode_video WHERE providerid = $id");
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "bbcode_video WHERE providerid=$id");
	}
	
	cachedb();
	redirect_to_rebuild();	
}



/**
 * Display installed definitions for exporting
 */
if($do == "export")
{
	
	$results = $db->query_read_slave("SELECT providerid id, provider title, url description from " . TABLE_PREFIX . "bbcode_video ORDER BY priority, provider ASC");

	print_form_header('ame', 'doexport');
	badhook_check();
	if ($db->num_rows($results))
	{
		print_table_header($vbphrase['ame_installed_definitions'], 2);
		print_cells_row(array($vbphrase['title'], "<label for=\"export_toggle\">$vbphrase[export]</label> <input type=\"checkbox\" id=\"export_toggle\" onclick=\"tick_all(this.form, 'items', this.checked)\" checked=\"checked\" />"), true);
		
		while($result = $db->fetch_array($results))
		{
			
			print_checkbox_row("$result[title]<dfn>$result[description]</dfn>", "items[$result[id]]");
		
		}

		print_submit_row();
	}
	else
	{
		
		print_table_header($vbphrase['ame_installed_definitions'], 2);
		print_description_row($vbphrase['ame_no_definitions']);
		print_table_footer(2, construct_button_code("Add new", "ame.php?do=edit"));
		
	}
	
}



/**
 * Spits out an XML file of the exported items
 */
if($do == "doexport")
{

	$items = $vbulletin->input->clean_gpc('p', 'items', TYPE_ARRAY_UINT);

	if (sizeof($items))
	{
		
		foreach ($items as $key => $value)
		{
			
			if ($value)
			{
				
				$ids .= ($ids ? "," : "") . $key;
			
			}
			
		}
		
		$results = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "bbcode_video WHERE providerid IN ($ids)");
		
		while($result = $db->fetch_array($results))
		{
			
			$data["$result[providerid]"] = $result;
		
		}

	}
	else
	{
		
		die("No items in doexport!");
		
	}

	if (sizeof($data))
	{
		
	    require_once(DIR . '/includes/class_xml.php');
	    
		$xml = new vB_XML_Builder($vbulletin);
		$xml->add_group("AME3");

	    foreach($data as $key => $value)
	    {
	    	
			$xml->add_group("def");
			foreach($value as $columnname => $columnvalue)
			{
				
				$xml->add_tag($columnname, $columnvalue);
				
			}
			
			$xml->close_group();
	    }

	    $xml->close_group();

	    // ############## Finish up
	    $doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n" . $xml->output();
	    unset($xml);

	    require_once(DIR . '/includes/functions_file.php');
	    file_download($doc, "AME.xml", 'text/xml');
	    exit;
	    
	}
	else
	{
		
		die("Data export size is empty!");
		
	}

}



/**
 * Show options for importing
 */
if ($do == "import")
{
?>

        <script type="text/javascript">
        <!--
        function js_confirm_upload(tform, filefield)
        {
                if (filefield.value == "")
                {
                        return confirm("<?php echo construct_phrase($vbphrase['you_did_not_specify_a_file_to_upload'], '" + tform.serverfile.value + "'); ?>");
                }
                return true;
        }
        //-->
        </script>

<?php

        print_form_header('ame', 'importoptions', 1, 1, 'uploadform" onsubmit="return js_confirm_upload(this, this.uploadedfile);');
        badhook_check();
        print_table_header($vbphrase['import']);
        print_description_row($vbphrase['ame_import_desc']);
        print_upload_row($vbphrase['upload_xml_file'], 'uploadedfile', 999999999);
        print_input_row($vbphrase['import_xml_file'], 'serverfile', './includes/xml/ame.xml');
        print_submit_row($vbphrase['import'], 0);
        
}



/**
 * Display imported results
 */
if ($do == "importoptions")
{
	
	$vbulletin->input->clean_gpc('f', 'uploadedfile', TYPE_FILE);
	$vbulletin->input->clean_gpc('p', 'serverfile', TYPE_FILE);

	print_dots_start($vbphrase['ame_importing']);

	if (file_exists($vbulletin->GPC['uploadedfile']['tmp_name']))
	{
		
	        $xml = file_read($vbulletin->GPC['uploadedfile']['tmp_name']);
	        
	}
	else if (file_exists($vbulletin->GPC['serverfile']))
	{
		
	        $xml = file_read($vbulletin->GPC['serverfile']);
	        
	}
	else
	{
		
			print_dots_stop();
	        print_stop_message('no_file_uploaded_and_no_local_file_found');
	        
	}

    require_once(DIR . '/includes/class_xml.php');
	$xmlobj = new vB_XML_Parser($xml);

    if ($xmlobj->error_no == 1)
    {
    	
		print_dots_stop();
		print_stop_message('no_xml_and_no_path');
		
    }

    if(!$arr = $xmlobj->parse())
    {
    	
            print_dots_stop();
            print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
            
    }

    print_dots_stop();

    $items 	= array();
	$errors = array();

    if (is_array($arr))
    {
    	
		if (sizeof($arr['def']))
		{
			
            if (!isset($arr['def'][0]))
            {
            	
                    $arr['def'] = array($arr['def']);
                    
            }

            $results = $db->query_read_slave("SELECT providerid id, provider, tagoption, url FROM " . TABLE_PREFIX . "bbcode_video ORDER BY providerid ASC");

            while($result = $db->fetch_array($results))
            {
            	
            	$existing["$result[tagoption]"] = $result;
            	
            }

			foreach($arr['def'] as $data => $value)
			{
				
				if (!trim($value['tagoption']))
				{
					//lets try to make one eh?
					$value['tagoption'] = strtolower(str_replace(" ", "_", preg_replace("/[^a-z \d]/i", "", $value['tagoption'])));
					$arr['def']["$data"]['tagoption'] 			= $value['tagoption'];
				}
			
				
				if (!trim($value['tagoption']))
				{
					
					$errors['empty_keys'] 						= $vbphrase['ame_some_tagoptions_are_empty'];
					$arr['def']["$data"]['empty_char_key'] 	= true;
					
				}
				elseif ($existing["$value[tagoption]"])
				{
					
					$errors['duplicate_keys'] 					= $vbphrase['ame_duplicate_keys_found'];
					$arr['def']["$data"]['existing_char_key'] 	= true;
					
				}
				elseif (!preg_match('/\\A[A-Z0-9_-]+\\z/i', $value['tagoption']))
				{
					
					$errors['invalid_keys'] 					= $vbphrase['ame_some_keys_contains_invalid_characters'];
					$arr['def']["$data"]['invalid_char_key'] 	= true;
					
				}
				else
				{
					
					$existing["$value[tagoption]"] = array(
						'id'	=> 0,
						'title'	=> $value['provider'],
					);
					
				}

			}
			
			
			print_form_header('ame', 'doimport','false');
	    	print_table_header("Items");
	    	print_description_row($vbphrase['ame_import_contents']);
	    	print_table_break();

			if (sizeof($errors))
			{
				
				print_table_header("Errors with import");
				foreach($errors as $value)
				{
					
					print_description_row($value);
					
				}

				print_table_break();
				
			}

	    	$x = 0;

	    	print_description_row(
	    		"<div style=\"float: " . $vbulletin->stylevars['right']['string'] . "\"><label for=\"import_all\">Import</label><input type=\"checkbox\" id=\"import_all\" onclick=\"tick_all(this.form, 'import', this.checked)\" /></div>
	    		<strong>Definitions</strong>"
	    	,	false, 2, 'thead');
	    	
	    	function ame_cmp($a, $b)
	    	{
	    		//test
	    		$abc=1;
	    		return strcmp($a["ameid"], $b["ameid"]);
	    	}
	    	
	    	usort($arr['def'], "ame_cmp");	    	

	    	
	        foreach($arr['def'] as $data => $value)
	        {
	        	if ($value['invalid_char_key'])
	        	{
	        		
	        		$errnote = "<dfn><font color=\"red\">$vbphrase[ame_import_error_invalid_tagoption]</font></dfn>";
	        		
	        	}
	        	elseif($value['empty_char_key'])
	        	{
	        		
	        		$errnote = "<dfn><font color=\"red\">$vbphrase[ame_import_error_missing_values]</font><dfn>";
	        		
	        	}
	        	elseif($value['existing_char_key'])
	        	{
	        		
	        		$errnote = "<dfn><font color=\"red\">";
	        		
	        		if ($existing["$value[provider]"]['id'])
	        		{
	        			
	        			$errnote .= $vbphrase['ame_import_error_duplicates_in_db'];
	        			
	        		}
	        		else
	        		{
	        			
	        			$errnote .= $vbphrase['ame_import_error_duplicates_in_xml'];
	        			
	        		}
	        		
	        		$errnote = "$errnote</font></dfn>";
	        	}
	        	else
	        	{
	        		
	        		$errnote = "";
	        		
	        	}	
	        	
            	$bgcounter++;
            	print_description_row("<div ondblclick=\"ame_toggle_group('import_$x'); return false;\">
            	<div style=\"float: " . $vbulletin->stylevars['right']['string'] . "\">" . (!$errnote ? "<input type=\"checkbox\" name=\"import[$x]\" id=\"import_$x\" value=\"1\" " . ($errnote == '' ? "checked=\"checked\"" : "") . " />" : "<img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/colorpicker_close.gif\" alt=\"Cannot Import\" border=\"0\" />") . "</div>
            	<a style=\"cursor: pointer;\"  onclick=\"ame_toggle_group('import_$x'); return false;\"><img src=\"../cpstyles/" . $vbulletin->options['cpstylefolder'] . "/cp_collapse.gif\" title=\"Expand\" id=\"collapse_import_$x\" alt=\"Expand\" border=\"0\" /></a> <strong><a style=\"cursor: pointer;\" onclick=\"ame_toggle_group('import_$x'); return false;\">$value[provider]</a>$errnote</strong></div>\n\t");
				echo("<tr>\n\t<td cellspacing=\"0\" cellpadding=\"0\" colspan=\"2\" style=\"display: none\" id=\"td_import_$x\">\n\t");
            	print_table_start(false, '100%', "0", "table_import_$x");
				print_input_row($vbphrase['ame_provider'], "provider[$x]", $value['provider']);
				print_label_row($vbphrase['ame_tagoption'], $value['tagoption']);
				construct_hidden_code("tagoption[$x]", $value['tagoption']);
				print_input_row($vbphrase['ame_url'], "url[$x]", $value['url']);
				print_input_row($vbphrase['ame_priority'], "priority[$x]", $value['priority']);
				print_input_row($vbphrase['ame_regex_url'], "regex_url[$x]", $value['regex_url']);
				print_textarea_row($vbphrase['ame_regex_scrape'], "regex_scrape[$x]", $value['regex_scrape']);
				print_textarea_row($vbphrase['ame_embed'], "embed[$x]", $value['embed']);
				$x++;
				echo("</table></td></tr>");


	        }

	        print_table_break();
	        
		}
		elseif(sizeof($arr['item']))
		{
			print_stop_message('ame_import_error_old_format');
		}
		else
		{
			
			print_stop_message('ame_import_error_empty');
			
		}
		
    }
    else
    {
    	
    	print_stop_message('ame_invalid_xml');
    	
    }

    print_submit_row($vbphrase['import']);

}



/**
 * Does the actual importing
 */
if ($do == "doimport")
{

	$vbulletin->input->clean_array_gpc('p', array(
		'import'		=> TYPE_ARRAY_BOOL,
		'provider'		=> TYPE_ARRAY_STR,
		'tagoption'		=> TYPE_ARRAY_STR,
		'url'			=> TYPE_ARRAY_STR,
		'priority'		=> TYPE_ARRAY_UINT,
		'regex_url'		=> TYPE_ARRAY_STR,
		'regex_scrape'	=> TYPE_ARRAY_STR,
		'embed'			=> TYPE_ARRAY_STR,
	));

	$import			= $vbulletin->GPC['import'];
	$provider 		= $vbulletin->GPC['provider'];
	$tagoption 		= $vbulletin->GPC['tagoption'];
	$url 			= $vbulletin->GPC['url'];
	$priority 		= $vbulletin->GPC['priority'];
	$regex_url 		= $vbulletin->GPC['regex_url'];
	$regex_scrape	= $vbulletin->GPC['regex_scrape'];
	$embed			= $vbulletin->GPC['embed'];

	$errors = array();

    $results = $db->query_read_slave("SELECT providerid id, provider title, tagoption FROM " . TABLE_PREFIX . "bbcode_video ORDER BY providerid ASC");

    while($result = $db->fetch_array($results))
    {
    	
    	$existing["$result[tagoption]"] = $result;
    	
    }

	foreach($import as $key => $value)
	{
		
		if(!trim($tagoption["$key"]))
		{
			
			$errors['empty_keys'] = $vbphrase['ame_some_tagoptions_are_empty'];
			
		}
		
		if ($existing["$tagoption[$key]"])
		{
			
			$errors['duplicate_keys'] = $vbphrase['ame_duplicate_keys_found'];
			
		}
		elseif (!preg_match('/\\A[A-Z0-9_-]+\\z/i', $tagoption["$key"]))
		{
			
			$errors['invalid_keys'] = $vbphrase['ame_some_keys_contains_invalid_characters'];
			
		}
		else
		{
			
			$existing["$tagoption[$key]"] = array(
				'id'	=> 0,
				'title'	=> $provider["$key"],
			);
			
		}

	}

	if (sizeof($errors))
	{
		
		foreach($errors as $value)
		{
			
			$err_message .= "<li>$value</li>";
			
		}

		print_stop_message('ame_cant_save_errors', $vbphrase['import'], $err_message);
		
	}


	foreach ($provider as $key => $value)
	{
		
		if ($import["$key"])
		{
			
			$sql = "INSERT INTO " . TABLE_PREFIX . "bbcode_video 
						(tagoption, 
						provider, 
						url,
						regex_url,
						regex_scrape,
						embed,
						priority) VALUES (
				'" . $db->escape_string($tagoption["$key"]) . "',
				'" . $db->escape_string($provider["$key"]) . "',
				'" . $db->escape_string($url["$key"]) . "',
				'" . $db->escape_string($regex_url["$key"]) . "',
				'" . $db->escape_string($regex_scrape["$key"]) . "',
				'" . $db->escape_string($embed["$key"]) . "', 
				'" . $priority["$key"] . "')";

			$db->query_write($sql);
			
		}
		
	}
	
	cachedb();
	redirect_to_rebuild();

}



/**
 * Tools Menu
 */
if ($do == 'tools')
{
	
	print_form_header();
	badhook_check();
	print_table_header($vbphrase['ame_tools']);
	print_description_row("<a href=\"ame.php?do=rebuildcache\">$vbphrase[ame_rebuild_cache_title]</a><dfn>$vbphrase[ame_rebuild_cache_desc]</dfn>");
	print_description_row("<a href=\"ame.php?do=resetall\">$vbphrase[ame_reset_all]</a><dfn>$vbphrase[ame_reset_all_desc]</dfn>");
	print_description_row("<a href=\"ame.php?do=reverttodefstore\">$vbphrase[ame_revert_defstore]</a><dfn>$vbphrase[ame_revert_defstore_desc]</dfn>");
	print_table_header($vbphrase['ame_rebuild_and_convert']);
	print_description_row("<strong>$vbphrase[ame_rebuild_title]</strong><dfn>$vbphrase[ame_rebuild_desc]</dfn>");
	print_table_footer();

}



/**
 * Asks user if they are certain they want to reset all
 */
if ($do == 'resetall')
{

		print_form_header('ame', 'doresetall');
		print_table_header($vbphrase['ame_confirm_reset']);
		print_description_row($vbphrase['ame_confirm_reset_question']);
		print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);
	
}



/**
 * Rebuild File Cache
 */
if ($do == 'rebuildcache')
{
	
	cachedb();
	redirect("tools", "ame_saved_x");

}



/**
 * Makes sure user wants to really revert :)
 */
if ($do == 'reverttodefstore')
{

	print_form_header('ame', 'doreverttodefstore');
	print_table_header($vbphrase['ame_confirm_revertdefstore']);
	print_description_row($vbphrase['ame_confirm_revertdefstore_question']);
	print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

	
}



/**
 * does the reverting
 */
if ($do == 'doreverttodefstore')
{
	
	revert_to_defstore();
	redirect('display', 'ame_reverteddefstore');	
	
}


/**
 * REBUILD TOOL
 * Designed to rebuild posts, blogs, groups, visitor messages and signatures with VIDEO tags
 * Can also upgrade from previous AME tags
 */
if($do == 'rebuild')
{
	$zone 	= $vbulletin->input->clean_gpc('r', 'zone', TYPE_STR);
	
	//check for valid zone
	switch ($zone)
	{
		
		case 'post':
		case 'blog':
		case 'vm':
		case 'group':
		case 'sig':
			break;
		default:
			print_stop_message('ame_invalid_zone_specified');
			
	}
	
	
	if ($zone == 'blogs')
	{
		
		$db->hide_errors();
		$result = $db->query_first_slave("SELECT max(blogtextid) total FROM " . TABLE_PREFIX . "blog_text");
		$db->show_errors();

		if ($db->errno)
		{
			
			print_stop_message('ame_no_blog');
		
		}
		
	}		


	$lengths = array(

		0			=> $vbphrase['ame_length_all'],
    	604800		=> $vbphrase['ame_length_one_week'],
    	1209600		=> $vbphrase['ame_length_two_weeks'],
    	1814400		=> $vbphrase['ame_length_three_weeks'],
    	2592000		=> $vbphrase['ame_length_one_month'],
    	7776000		=> $vbphrase['ame_length_three_months'],
    	15724800	=> $vbphrase['ame_length_six_months'],
    	31449600	=> $vbphrase['ame_length_one_year'],
    
    	);

    	
    $settings = !is_array($vbulletin->AME_settings) ? unserialize($vbulletin->AME_settings) : $vbulletin->AME_settings;

    if (!$settings)
    {
    	//Defaults
    	$settings = array(
    		'length'	=> 	2592000,
    		'perpage'	=>	100,
    		'seconds'	=>	10,
    		'test'		=>	true,
    		'verbose'	=>	true
    	);
    	
    }

	print_form_header('ame', 'dorebuild', false, true, 'cpform', '90%', '', true, 'get');
	print_table_header($vbphrase['ame_convert_warning_title']);

	switch ($zone)
	{
		
		case 'blog' 	: $tablename = "blog_text"; break;
		case 'vm'		: $tablename = "visitormessage"; break;
		case 'group'	: $tablename = "groupmessage"; break;
		case 'sig'		: $tablename = "usertextfield"; break;
		default			: $tablename = "post";
	
	}

	print_description_row(construct_phrase($vbphrase["ame_convert_warning_x"], $tablename));

	print_table_break();
	print_table_header(construct_phrase($vbphrase['ame_rebuild_xs'], $vbphrase['ame_' . $zone]));
	print_yes_no_row($vbphrase['ame_test_mode'], 'test', $settings['test']);
	print_yes_no_row($vbphrase['ame_verbose_mode'], 'verbose', $settings['verbose']);
	print_yes_no_row($vbphrase['ame_upgrade_only'], 'ameup', $settings['ameup']);
	
	if ($zone != "sig")
	{
		
		print_select_row($vbphrase['ame_length'], 'length', $lengths, $settings['length']);
	
	}
	
	print_input_row($vbphrase['ame_perpage'], 'perpage', $settings['perpage']);
	print_input_row($vbphrase['ame_seconds_perpage'], 'seconds', $settings['seconds']);
	construct_hidden_code('zone', $zone);
	
	print_submit_row();

}



/**
 * DO REBUILD
 */
if ($do == 'dorebuild')
{
	
	$vbulletin->input->clean_array_gpc('r', array(
		'length'				=> TYPE_UINT,
		'perpage'				=> TYPE_UINT,
		'seconds'				=> TYPE_UINT,
		'cont'					=> TYPE_UINT,
		'test'					=> TYPE_BOOL,
		'verbose'				=> TYPE_BOOL,
		'start'					=> TYPE_UINT,
		'zone'					=> TYPE_STR,
		'ameup'					=> TYPE_BOOL,
	));

	$length 			= $vbulletin->GPC['length'];
	$start				= $vbulletin->GPC['start'];
	$perpage 			= $vbulletin->GPC['perpage'];
	$seconds 			= $vbulletin->GPC['seconds'];
	$cont				= $vbulletin->GPC['cont'];
	$test				= $vbulletin->GPC['test'];
	$verbose			= $vbulletin->GPC['verbose'];
	$zone				= $vbulletin->GPC['zone'];
	$ameup				= $vbulletin->GPC['ameup'];

	//check for valid zone
	switch ($zone)
	{
		
		case 'post':
		case 'blog':
		case 'vm':
		case 'group':
		case 'sig':
			break;
		default:
			print_stop_message('ame_invalid_zone_specified');
			
	}

	if ($start < 2 && !$cont)
	{
		
		$settings = !is_array($vbulletin->AME_settings) ? unserialize($vbulletin->AME_settings) : $vbulletin->AME_settings;
		$start 		= 0;
		$limitstart = "0";
		build_datastore('AME_settings', serialize(array('length' => $length, 'perpage' => $perpage, 'seconds' => $seconds, 'verbose' => $verbose, 'test' => $test, 'codes' => $settings['codes'], 'conversions' => $settings['conversions'])));
	
	}
	else
	{
		
		$limitstart = $start * $perpage;
	
	}

	$return 		= false;
	$x				= 0;

	require_once(DIR . "/includes/ame_prep.php");

	if ($length)
	{
		
		switch ($zone)
		{
			case 'post': 	$and .= " AND p.dateline >= " . (TIMENOW - $length);	break;
			case 'blog':
			case 'group':
			case 'vm':		$and .= " AND dateline >= " . (TIMENOW - $length);	break;
		}
		
	}

	switch ($zone)
	{
		case 'vm':

			$sql = "SELECT	count(vmid) total FROM " . TABLE_PREFIX . "visitormessage WHERE 1=1  AND (" . ($ameup ? "" : "pagetext LIKE '%[/url]%' OR ") . "pagetext LIKE '%[/ame]%' OR pagetext LIKE '%[/nomedia]%') $and ";
			break;
		
		case 'blog':
			
			$sql = "SELECT count(blogtextid) total FROM " . TABLE_PREFIX . "blog_text WHERE 1=1  AND (" . ($ameup ? "" : "pagetext LIKE '%[/url]%' OR ") . "pagetext LIKE '%[/ame]%' OR pagetext LIKE '%[/nomedia]%') $and ";
			break;
			
		case 'group':
			
			$sql = "SELECT count(gmid) total FROM " . TABLE_PREFIX . "groupmessage WHERE 1=1  AND (" . ($ameup ? "" : "pagetext LIKE '%[/url]%' OR ") . "pagetext LIKE '%[/ame]%' OR pagetext LIKE '%[/nomedia]%') $and ";
			break;
			
		case 'sig':
			
			$sql = "SELECT count(userid) total FROM " . TABLE_PREFIX . "usertextfield WHERE 1=1  AND (" . ($ameup ? "" : "signature LIKE '%[/url]%' OR ") . "signature LIKE '%[/ame]%' OR signature LIKE '%[/nomedia]%') $and ";
			break;
			
		default:
			
			$sql = "SELECT count(p.postid) total FROM " . TABLE_PREFIX . "post p WHERE 1=1  AND (" . ($ameup ? "" : "p.pagetext LIKE '%[/url]%' OR ") . "p.pagetext LIKE '%[/ame]%' OR pagetext LIKE '%[/nomedia]%') $and ";
	}

	$postcount = $db->query_first_slave($sql);

	if ($postcount['total'])
	{
		
		print_form_header('ame', 'dorebuild', false, true, 'statusform', '90%', '', true, 'get');
		print_table_header($vbphrase['ame_rebuild_status']);
		print_description_row(construct_phrase($vbphrase['ame_rebuild_status_x'], (ceil($postcount['total'] / $perpage) - ($start ? $start + 1 : 1))));
		print_table_footer(); vbflush();

		switch ($zone)
		{
			case 'vm':
				
				$sql = "SELECT 
							vmid, 
							pagetext 
						FROM " . TABLE_PREFIX . "visitormessage
						WHERE 
							1=1 AND 
							(" . ($ameup ? "" : "pagetext LIKE '%[/url]%' OR ") . "pagetext LIKE '%[/ame]%' OR pagetext LIKE '%[/nomedia]%') 
							$and 
						ORDER BY 
							dateline DESC 
						LIMIT $limitstart, $perpage";
				break;
				
			case 'blog':
				
				$sql = "SELECT
							blogtextid,
							pagetext
						FROM " . TABLE_PREFIX . "blog_text
						WHERE 
							1=1 AND 
							(" . ($ameup ? "" : "pagetext LIKE '%[/url]%' OR ") . "pagetext LIKE '%[/ame]%' OR pagetext LIKE '%[/nomedia]%') 
							$and 
						ORDER BY dateline DESC 
						LIMIT $limitstart, $perpage";
				break;
				
			case 'group':
				
				$sql = "SELECT 
							gmid, 
							pagetext
						FROM " . TABLE_PREFIX . "groupmessage
						WHERE 
							1=1 AND 
							(" . ($ameup ? "" : "pagetext LIKE '%[/url]%' OR ") . "pagetext LIKE '%[/ame]%' OR pagetext LIKE '%[/nomedia]%')
							$and 
						ORDER BY dateline DESC 
						LIMIT $limitstart, $perpage";
				break;
				
			case 'sig':
				
				$sql = "SELECT
							userid,
							signature pagetext 
						FROM " . TABLE_PREFIX . "usertextfield
						WHERE
							1=1 AND 
							(" . ($ameup ? "" : "signature LIKE '%[/url]%' OR ") . "signature LIKE '%[/ame]%' OR signature LIKE '%[/nomedia]%')
							$and 
						ORDER BY 
							userid DESC 
						LIMIT $limitstart, $perpage";
				break;
				
			default:
				
				$sql = "SELECT 
							p.postid, 
							p.pagetext,
							t.forumid 
						FROM " . TABLE_PREFIX . "post p 
						INNER JOIN " . TABLE_PREFIX . "thread t on p.threadid = t.threadid
						WHERE 
							1=1 AND 
							(" . ($ameup ? "" : "p.pagetext LIKE '%[/url]%' OR ") . "p.pagetext LIKE '%[/ame]%' OR pagetext LIKE '%[/nomedia]%')
							$and 
						ORDER BY
							p.dateline DESC 
						LIMIT $limitstart, $perpage";
		}

		$results = $db->query_read_slave($sql);
		require_once(DIR . '/includes/functions_video.php');		
		
		if ($db->num_rows($results))
		{
			
			echo("Building....<ul>");
			vbflush();

			while($result = $db->fetch_array($results))
			{
				
				$forumid = $result['forumid'];
				$x++;

				if ($x == $perpage)
				{
					
					$return = true;
					
				}

				switch ($zone)
				{
					
					case 'vm':
						echo("<li>Visitor Message $result[vmid]: ");
						break;
						
					case 'blog':
						echo("<li>Blog/Comment $result[blogtextid]: ");
						break;
						
					case 'group':
						echo("<li>Group Message $result[gmid]: ");
						break;
						
					case 'sig':
						echo("<li>Signature for userid $result[userid]: ");
						break;

					default:
						echo("<li>post $result[postid]: ");
				
				}


						
				if ($verbose)
				{
					
					$text = $result['pagetext'];
				
				}

				//change all nomedia and ame tags into URL tags
				$result['pagetext'] = preg_replace('%(\[(ame|nomedia)([^\]]*)\](.*?)\[/(\2)\])%sim', '[url\3]\4[/url]', $result['pagetext']);
				
				//parse URL tags
				$returnvalue = parse_video_bbcode($result['pagetext']);

					
				if (($returnvalue != $text) && !$test)
				{
						
					switch ($zone)
					{
						
						case 'vm':
							$sql = "UPDATE " . TABLE_PREFIX . "visitormessage SET pagetext = '" . $db->escape_string($returnvalue) . "' WHERE vmid=$result[vmid]";
							$db->query_write($sql);
							break;
							
						case 'blog':
							$sql = "UPDATE " . TABLE_PREFIX . "blog_text SET pagetext = '" . $db->escape_string($returnvalue) . "' WHERE blogtextid=$result[blogtextid]";
							$db->query_write($sql);
							$db->query_write("DELETE FROM " . TABLE_PREFIX . "blog_textparsed WHERE blogtextid=$result[blogtextid]");
							break;
							
						case 'group':
							$sql = "UPDATE " . TABLE_PREFIX . "groupmessage SET pagetext = '" . $db->escape_string($returnvalue) . "' WHERE gmid=$result[gmid]";
							$db->query_write($sql);
							break;
							
						case 'sig':
							$sql = "UPDATE " . TABLE_PREFIX . "usertextfield SET signature = '" . $db->escape_string($returnvalue) . "' WHERE userid=$result[userid]";
							$db->query_write($sql);
							$db->query_write("DELETE FROM " . TABLE_PREFIX . "sigparsed WHERE userid=$result[userid]");
							break;
							
						default:
							$sql = "UPDATE " . TABLE_PREFIX . "post SET pagetext = '" . $db->escape_string($returnvalue) . "' WHERE postid=$result[postid]";
							$db->query_write($sql);
							$db->query_write("DELETE FROM " . TABLE_PREFIX . "postparsed WHERE postid=$result[postid]");
							
					}
					
				}

				if ($verbose)
				{
					
					echo("<div style=\"border: medium;\">   was:<hr>" . htmlspecialchars_uni($text) . "<hr><br />it is now:<hr>" . htmlspecialchars_uni($returnvalue) . "<hr></div>");
				
				}
			
				echo("</li>");
				vbflush();
				unset($text, $returnvalue);

				if ($return)
				{
					
					if (ceil($postcount['total'] / $perpage) == 1)
					{
						
						$return = false;
					
					}
				
				}
			
			}
			
			echo("</ul>");

		}
		else
		{
			
			redirect('tools', 'ame_no_results');

		}
	}
	else
	{
		
		redirect('tools', 'ame_no_results');

	}

	print_form_header('ame', 'dorebuild', false, true, 'cpform', '90%', '', true, 'get');
	print_table_header($vbphrase['ame_rebuild_title']);

	if ($return)
	{
		
		print_label_row($vbphrase['ame_rebuild_seconds_till_next'], "<input type=\"text\" name=\"timer\" id=\"timer\" readonly=\"true\" value=\"$delay\" />");
		construct_hidden_code("cont", true);
		construct_hidden_code("perpage", $perpage);
		construct_hidden_code("seconds", $seconds);
		construct_hidden_code("length", $length);
		construct_hidden_code("test", $test);
		construct_hidden_code("verbose", $verbose);
		construct_hidden_code("start", $start+1);
		construct_hidden_code("zone", $zone);
		construct_hidden_code("ameup", $ameup);

		print_submit_row($vbphrase['next'], '');


		echo("<script language=\"javascript\"><!--

				var countdown = " . $seconds . ";

			  function submit_form()
			  {
			     document.cpform.submit();
			  }

			  function count_down()
			  {
			      countdown = countdown-1;
			  	  document.cpform.timer.value=countdown+ ' $vbphrase[ame_rebuild_seconds_remaining]';
			  	  if (countdown == 0)
			  	  {
			  	  	submit_form();
			  	  }
			  	  else
			  	  {
			  	  	setTimeout('count_down()',1000);
			  	  }
			  }
			  //-->
			setTimeout('count_down()',1000);
		  </script>");

	
	}
	else
	{
		
		$inp = ($in ? $in . "_" : "");
		print_description_row($vbphrase['ame_rebuild_completed']);
		print_table_footer();
		
	}


}



?>