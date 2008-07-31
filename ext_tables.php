<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// shortcuts
$prod = 'products';


if (TYPO3_MODE == 'BE')	{
	// add be-modules
	t3lib_extMgm::addModule('user','txmagentobackendM1','',t3lib_extMgm::extPath($_EXTKEY).'mod/magentobackend/');
	t3lib_extMgm::addModule('web','txmagentoconnectM1','',t3lib_extMgm::extPath($_EXTKEY).'mod/magentosoap/'); 
	
	// add new content wizard
	$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_magentoproducts_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'feplugins/magentoproducts/class.tx_magentoproducts_wizicon.php';
}

// add fe-plugins
t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . $prod]='layout,select_key, pages';


t3lib_extMgm::addPlugin(array('LLL:EXT:magento/lang/backend.xml:tt_content.list_type_pi1', $_EXTKEY . $prod),'list_type');

// typoscript templates
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/magento_products/', 'Magento Products');

// Flexforms
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . $prod] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY . $prod, 'FILE:EXT:' . $_EXTKEY.'/flexform/flexform_products.xml');

?>