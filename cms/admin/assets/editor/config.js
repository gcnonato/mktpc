/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{ 
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	url = 'admin';
	if(ADMINURL) {
		url = ADMINURL;
	}
	
	config.filebrowserBrowseUrl = url + '/filemanager/';
	config.filebrowserImageBrowseUrl = url + '/filemanager/';
	config.filebrowserFlashBrowseUrl = url + '/filemanager/';
	config.filebrowserUploadUrl = url + '/filemanager/';
	config.filebrowserImageUploadUrl = url + '/filemanager/';
	config.filebrowserFlashUploadUrl = url + '/filemanager/';		
	config.filebrowserWindowWidth = '800';
	config.filebrowserWindowHeight = '500';
	
	//config.extraPlugins = 'MediaEmbed';
	
//	config.extraPlugins += (config.extraPlugins ? ',MediaEmbed' : 'MediaEmbed' );
	
	config.extraPlugins += (config.extraPlugins ? ',youtube' : 'youtube' );
	
//	console.log(CKEDITOR.plugins.basePath);
	
	config.toolbar = 'Full';
	
	config.toolbar_Custom =
		[
			['Source','-'],
			['Cut','Copy','Paste','PasteText','PasteFromWord'],
			['Undo','Redo','-','SelectAll','RemoveFormat'],
			['Image','Youtube','Table','PageBreak'],
			['Bold','Italic','Underline','Strike'],
			/*'/',*/
			['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
			['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
			['Link','Unlink','Anchor'],
			['TextColor','BGColor']
		];
	
	/*

	config.resize_enabled = false;
	
	config.toolbar = 'Custom';

	config.toolbar_Custom = [
		['Source'],
		['Maximize'],
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['NumberedList','BulletedList','-','Outdent','Indent'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['SpecialChar'],
		'/',
		['Undo','Redo'],
		['Font','FontSize'],
		['TextColor','BGColor'],
		['Link','Unlink','Anchor'],
		['Image','Table','HorizontalRule']
	];
	
	config.toolbar_Full =
	[
		['Source','-','Save','NewPage','Preview','-','Templates'],
		['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
		['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
		['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
		'/',
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Link','Unlink','Anchor'],
		['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
		'/',
		['Styles','Format','Font','FontSize'],
		['TextColor','BGColor'],
		['Maximize', 'ShowBlocks','-','About']
	];
	*/
	
};

//CKEDITOR.plugins.addExternal('MediaEmbed', CKEDITOR.plugins.basePath + 'mediaembed/');
CKEDITOR.plugins.addExternal('youtube', CKEDITOR.plugins.basePath + 'youtube/');
