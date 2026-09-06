/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.allowedContent = true;
	config.extraAllowedContent = 'img[*]{*}(*);table[*]{*}(*);span[*]{*}(*)';
	config.entities = false;
	config.basicEntities = false;
	config.entities_latin = false;
	config.entities_greek = false;
	config.pasteFromWordPromptCleanup = false;
	config.pasteFromWordRemoveFontStyles = false;
	config.pasteFromWordRemoveStyles = false;
	config.forcePasteAsPlainText = true;
	config.clipboard_defaultContentType = 'text';
	config.removeDialogTabs = 'image:advanced;link:advanced';
};
