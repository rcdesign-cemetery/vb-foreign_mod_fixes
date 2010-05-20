/****
 * vB Post Templates
 * Copyright 2010; Deceptor, DragonByte Technologies
 * All Rights Reserved
 * Code may not be copied, in whole or part without written permission
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

function initialise_posttemplates(editorid)
{
	var ptitems = YAHOO.util.Dom.get(editorid + '_popup_posttemplate').getElementsByTagName('li');

	for (var c = 0; c < ptitems.length; c++)
	{
		if (ptitems[c].className == 'fontname')
		{
			YAHOO.util.Event.on(ptitems[c], "mouseover", handle_mouseevents_posttemplate);
			YAHOO.util.Event.on(ptitems[c], "mouseout", handle_mouseevents_posttemplate);
			YAHOO.util.Event.on(ptitems[c], "mouseup", handle_mouseevents_posttemplate);
			YAHOO.util.Event.on(ptitems[c], "mousedown", handle_mouseevents_posttemplate);
			YAHOO.util.Event.on(ptitems[c], "click", insert_posttemplate);
		};
	};
};

function handle_mouseevents_posttemplate(e)
{
	e = do_an_e(e);
	vB_Editor[this.id.split('~')[2]].button_context(this, e.type, 'menu');
};

function insert_posttemplate(e)
{
	editorid = this.id.split('~')[2];
	PostTemplate = new vB_AJAX_PostTemplate(this.id.split('~')[1], vB_Editor[editorid].wysiwyg_mode, vB_Editor[editorid].parsesmilies, editorid);
	PostTemplate.fetch();
	YAHOO.vBulletin.vBPopupMenu.close_all();
};

function vB_AJAX_PostTemplate(template, wysiwyg, allowsmilie, editorid)
{
	this.template = template;
	this.wysiwyg = wysiwyg;
	this.allowsmilie = allowsmilie;
	this.editorid = editorid;

	this.fetch = function()
	{
		YAHOO.util.Connect.asyncRequest("POST", "ajax.php?do=posttemplate&template=" + this.template + "&wysiwyg=" + this.wysiwyg + "&allowsmilie=" + this.allowsmilie, {
			success: this.fetched,
			timeout: vB_Default_Timeout,
			scope: this
		}, SESSIONURL + "securitytoken=" + SECURITYTOKEN + "&do=posttemplate&template=" + this.template + "&wysiwyg=" + this.wysiwyg + "&allowsmilie=" + this.allowsmilie);
	};

	this.fetched = function(ajax)
	{
		if (ajax.responseXML)
		{
			if (ajax.responseXML.getElementsByTagName('message') && ajax.responseXML.getElementsByTagName('message')[0])
			{
				vB_Editor[this.editorid].insert_text(ajax.responseXML.getElementsByTagName('message')[0].firstChild.nodeValue);
			};
		};
	};
};