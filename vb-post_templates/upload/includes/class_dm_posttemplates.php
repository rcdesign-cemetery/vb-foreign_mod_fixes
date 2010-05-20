<?php
/****
 * vB Post Templates
 * Copyright 2010; Deceptor, DragonByte Technologies
 * All Rights Reserved
 * Code may not be copied, in whole or part without written permission
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

if (!class_exists('vB_DataManager'))
{
	exit;
}

class vB_DataManager_PostTemplates extends vB_DataManager
{
	var $validfields = array(
		'pid'			=> array(TYPE_UINT,			REQ_INCR,	VF_METHOD,	'verify_nonzero'),
		'userid'		=> array(TYPE_UINT,			REQ_YES,	VF_METHOD),
		'parentid'		=> array(TYPE_UINT, 			REQ_NO,		VF_METHOD),
		'type'			=> array(TYPE_STR,			REQ_YES,	VF_METHOD),
		'title'			=> array(TYPE_NOHTML,			REQ_YES,	VF_METHOD,	'verify_nonempty'),
		'global'		=> array(TYPE_BOOLEAN,			REQ_NO,		VF_METHOD),
		'content'		=> array(TYPE_STR,			REQ_NO),
	);

	var $condition_construct = array('pid = %1$d', 'pid');
	var $table = 'posttemplate';
	var $posttemplate = array();
	var $count = array();
	var $fetched = array();
	var $result;

	function vB_DataManager_PostTemplates(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);
	}

	function verify_type(&$type)
	{
		if (!in_array($type, array('category', 'template')))
		{
			$this->error('invalid_posttemplate_type');

			return false;
		}

		return true;
	}

	function verify_parentid(&$parentid)
	{
		if (!$category = $this->registry->db->query_first("select pid from " . TABLE_PREFIX . "{$this->table} where type='category' and userid=" . $this->fetch_field('userid') . " and pid=$parentid"))
		{
			$this->error('invalid_posttemplate_category');

			return false;
		}

		return true;
	}

	function verify_global(&$global)
	{
		if ($global && !($this->registry->userinfo['permissions']['vbpt_permissions'] & $this->registry->bf_ugp_vbpt_permissions['vbpt_create_defined']))
		{
			$this->error('cannot_create_global_posttemplate');

			return false;
		}

		if ($global && $this->fetch_field('type') == 'template')
		{
			if (!$category = $this->registry->db->query_first("select pid from " . TABLE_PREFIX . "{$this->table} where type='category' and global=1 and userid=" . $this->fetch_field('userid') . " and pid=" . $this->fetch_field('parentid')))
			{
				$this->error('cannot_create_global_posttemplate_parent');

				return false;
			}
		}

		return true;
	}

	function verify_fetched()
	{
		if ($this->result['type'] != $this->info['type'] || !$this->info['pid'])
		{
			$this->error('posttemplate_item_invalid');
		}
	}

	function delete()
	{
		if ($this->fetch_field('type') == 'category')
		{
			$this->registry->db->query("delete from " . TABLE_PREFIX . "{$this->table} where type='template' and userid=" . $this->fetch_field('userid') . " and parentid=" . $this->fetch_field('pid'));
		}

		parent::delete();
	}

	function fetched_data()
	{
		if (!isset($this->count[$this->info['type']]))
		{
			$this->count[$this->info['type']] = -1;
		}

		$this->count[$this->info['type']]++;

		if (md5(implode(',', $this->info)) != $this->fetched)
		{
			return false;
		}

		return true;
	}

	function fetch_data()
	{
		return $this->fetched_data() ? $this->result : $this->do_fetch_data();
	}

	function do_fetch_data()
	{
		$query = $this->info['single'] ? 'query_first' : 'query';
		$this->fetched	= md5(implode(',', $this->info));
		$this->result	= $this->registry->db->$query("select * from " . TABLE_PREFIX . "{$this->table} where type='{$this->info['type']}' and userid={$this->info['userid']}" . ($this->info['pid'] ? " and pid={$this->info['pid']}" : '') . " order by title;");

		if ($this->info['single'])
		{
			$this->verify_fetched();
		}

		return $this->result;
	}
}
?>