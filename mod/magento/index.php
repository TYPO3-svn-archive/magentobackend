<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008  <>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$GLOBALS['LANG']->includeLLFile('EXT:magento/mod/magento/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
require_once(PATH_t3lib.'class.t3lib_arraybrowser.php');

require_once(t3lib_extMgm::extPath('magento').'api/class.tx_magento_api.php');

$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'Magento' for the 'magentoconnect' extension.
 *
 * @author	 <>
 * @package	TYPO3
 * @subpackage	tx_magentoconnect
 */
class  tx_magentoconnect extends t3lib_SCbase {
				var $pageinfo;
				
				var $conf;
				var $api;
				
				/**
				 * Initializes the Module
				 * @return	void
				 */
				function init()	{
					parent::init();
				}

				/**
				 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
				 *
				 * @return	void
				 */
				function menuConfig()	{
					$this->MOD_MENU = Array (
						'function' => Array (
							'1' => $GLOBALS['LANG']->getLL('function1'),
							'2' => $GLOBALS['LANG']->getLL('function2'),
							'3' => $GLOBALS['LANG']->getLL('function3'),
						)
					);
					parent::menuConfig();
				}

				/**
				 * Main function of the module. Write the content to $this->content
				 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
				 *
				 * @return	[type]		...
				 */
				function main()	{

					$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['magento']);
					$this->conf['url'] .= 'api/soap/?wsdl';
					
					if(!$this->conf['url'] || !$this->conf['username'] || ! $this->conf['password']) {
						die('Please configure modul in extension manager.');
					}
					
					#$this->api = new tx_magento_api($this->conf['url'], $this->conf['username'], $this->conf['password']);
					
					
					// Access check!
					// The page will show only if there is a valid page and if this page may be viewed by the user
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
					$access = is_array($this->pageinfo) ? 1 : 0;
					
					

					if ($access)	{
							// Draw the header.
						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $GLOBALS['BACK_PATH'];
						$this->doc->form='<form action="" method="POST">';

							// JavaScript
						$this->doc->JScode = '
							<script language="javascript" type="text/javascript">
								script_ended = 0;
								function jumpToUrl(URL)	{
									document.location = URL;
								}
							</script>
						';
						$this->doc->postCode='
							<script language="javascript" type="text/javascript">
								script_ended = 1;
								if (top.fsMod) top.fsMod.recentIds["web"] = 0;
							</script>
						';

						$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

						$this->content.=$this->doc->startPage($GLOBALS['LANG']->getLL('title'));
						$this->content.=$this->doc->header($GLOBALS['LANG']->getLL('title'));
						$this->content.=$this->doc->spacer(5);
						$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
						$this->content.=$this->doc->divider(5);


						// Render content:
						$this->moduleContent();


						// ShortCut
						if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
							$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
						}

						$this->content.=$this->doc->spacer(10);
					} else {
							// If no access or if ID == zero

						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $GLOBALS['BACK_PATH'];

						$this->content.=$this->doc->startPage($GLOBALS['LANG']->getLL('title'));
						$this->content.=$this->doc->header($GLOBALS['LANG']->getLL('title'));
						$this->content.=$this->doc->spacer(5);
						$this->content.=$this->doc->spacer(10);
					}
				}

				/**
				 * Prints out the module HTML
				 *
				 * @return	void
				 */
				function printContent()	{

					$this->content.=$this->doc->endPage();
					echo $this->content;
				}

				/**
				 * Generates the module content
				 *
				 * @return	void
				 */
				function moduleContent()	{
					switch((string)$this->MOD_SETTINGS['function'])	{
						case 1:
							$content= $this->getAllProducts();
							$this->content.=$this->doc->section($this->LL('function1'),$content,0,1);
						break;
						case 2:
							$content='<div align=center><strong>Menu item #2...</strong></div>';
							$this->content.=$this->doc->section('Message #2:',$content,0,1);
						break;
						case 3:
							$content='<div align=center><strong>Menu item #3...</strong></div>';
							$this->content.=$this->doc->section('Message #3:',$content,0,1);
						break;
					}
				}
				
				
				function getAllProducts() {

					
					#$tree = new t3lib_arrayBrowser;
					
					#$countries = $this->api->call('category.tree');
					#$content .= $this->doc->section('Countries:', t3lib_div::view_array($countries), 0, 2);
					#$this->api->close();
					
					return $content;
				}
				
				/**
			 * Just a shorter localization
			 *
			 * @param	string		$val: the id of the localized item
			 * @return	string		localized value
			 */
			 function LL($val) {
				return $GLOBALS['LANG']->getLL($val);
			 }

				/**
			 * Make a string bold
			 *
			 * @param	string		$val: the string
			 * @return	string		the bolded string
			 */			  
			  function strong($val) {
					return '<strong>'.$val.'</strong>';
			  }	
			  
			  
			  
			  
			  
			}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magento/mod/magento/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magento/od/magento/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_magentoconnect');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
