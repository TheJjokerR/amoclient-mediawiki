<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'AmoClient' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['AmoClient'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['AmoClientAlias'] = __DIR__ . '/AmoClient.i18n.alias.php';
	$wgExtensionMessagesFiles['AmoClientMagic'] = __DIR__ . '/AmoClient.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for AmoClient extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the AmoClient extension requires MediaWiki 1.25+' );
}
