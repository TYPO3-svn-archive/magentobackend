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

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Magento Connect' for the 'magentoconnect' extension.
 *
 * @author	 <>
 * @package	TYPO3
 * @subpackage	tx_magentoconnect
 */
class tx_magentoconnect_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_magentoconnect_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_magentoconnect_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'magentoconnect';	// The extension key.
	var $pi_checkCHash = true;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->init($conf);


		
		if($this->checkConfiguration()) {



			$this->magento->open();

			
			// load the correct mode
			switch ($this->config['mode']) {
				case 'SINGLEPRODUCT':	
					$result = $this->magento->getSingleProduct($this->config['sku'], $this->conf['productWithImages']);
    			break;
				case 'PRODUCTS':	
					$result = $this->magento->getProducts($this->config['where'], $this->config['whereField']);
    			break;
				case 'PRODUCTIMAGE':	
					$result = $this->magento->getProductImage($this->config['sku']);
    			break;
			}
			
			if (is_array($result)) {
				$content.= $this->fillTemplate($result);
			} else {
				$content.= $result;
			}
			
			$this->magento->close();		
			
		} else {
			$content .= 'No configuration!!';
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	
	function fillTemplate($row) {
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_'.$this->config['mode'].'###');		
		
		foreach ($row as $key=>$value) {
  		$markerArray['###'.strtoupper($key).'###'] = $this->cObj->stdWrap($value, $this->conf[strtolower($his->config['mode']).'.'][$key]);
  	}
  	
  	if (is_array($row['categories'])) {
			$markerArray['###CATEGORIES###'] = $this->getCategories($row['categories']);
		}
  	

	
		$content.= $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $subpartArray);
  #	$content.= t3lib_div::view_array($row);
		return $content;

	}
	
	function getCategories($cats) {
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_CATEGORIES###');
		$template['item'] = $this->cObj->getSubpart($template['total'],'###ITEM###');


		foreach ($cats as $key=>$catId) {

			$category = $this->magento->getCategory($catId);		
				  	
			foreach ($category as $key=>$value) {
	  		$markerArray['###'.strtoupper($key).'###'] = $this->cObj->stdWrap($value, $this->conf['category.'][$key]);
	  	}
			$content_item .= $this->cObj->substituteMarkerArrayCached($template['item'], $markerArray);
  	}
  	
  		// put everything into the template
		$subpartArray['###CONTENT###'] = $content_item;

		
		$content.= $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $subpartArray);
		return $content;

	}


	/**
	 * Check the login configuration
	 *
	 * @return	boolean If there is any connection available
	 */	
	function checkConfiguration() {
		$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['magentoconnect']);		
		
		if ($config['username']!='' && $config['password']!='' && $config['url']!='')	{
			$this->magento->getConnection($config['url'], $config['username'], $config['password']);	
			
			return	true;
		} else {
			return false;
		}
	}
	
	/**
	 * The whole preconfiguration: Get the flexform values
	 *
	 * @param	array		$conf: The PlugIn configuration
	 * @return	void
	 */
	function init($conf) {
		$this->conf=$conf;		
		$this->pi_loadLL(); // Loading language-labels
		$this->pi_setPiVarDefaults(); // Set default piVars from TS
		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin
		

		// add the flexform values
		$this->config['mode']		= $this->getFlexform('', 'mode', 'mode');
		$this->config['sku'] 		= $this->getFlexform('', 'sku', 'sku');
		$this->config['where'] 		= $this->getFlexform('', 'where', 'where');
		$this->config['whereField'] 		= $this->getFlexform('', 'whereField', 'whereField');		  	

			// Template
		$this->templateCode = $this->cObj->fileResource($conf['templateFile']);

			// CSS file
		$GLOBALS['TSFE']->additionalHeaderData['magentoconnect'] = (isset($this->conf['pathToCSS'])) ? '<link rel="stylesheet" href="'.$GLOBALS['TSFE']->tmpl->getFileName($this->conf['pathToCSS']).'" type="text/css" />' : '';
		
		require_once( t3lib_extMgm::extPath('magentoconnect').'/class.tx_magentoconnect_api.php');
		$this->magento = t3lib_div::makeInstance('tx_magentoconnect_api');

	}	
	
	/**
	 * Get the value out of the flexforms and if empty, take if from TS
	 *
	 * @param	string		$sheet: The sheed of the flexforms
	 * @param	string		$key: the name of the flexform field
	 * @param	string		$confOverride: The value of TS for an override
	 * @return	string	The value of the locallang.xml
	 */
	function getFlexform ($sheet, $key, $confOverride='') {
		// Default sheet is sDEF
		$sheet = ($sheet=='') ? $sheet = 'sDEF' : $sheet;
		$flexform = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $key, $sheet);
		
		// possible override through TS
		if ($confOverride=='') {
			return $flexform;
		} else {
			$value = $flexform ? $flexform : $this->conf[$confOverride];
			$value = $this->cObj->stdWrap($value,$this->conf[$confOverride.'.']);
			return $value;
		}
	}  	
	
	
	
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magentoconnect/pi1/class.tx_magentoconnect_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magentoconnect/pi1/class.tx_magentoconnect_pi1.php']);
}

?>
