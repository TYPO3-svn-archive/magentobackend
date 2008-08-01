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
require_once(t3lib_extMgm::extPath('magento').'api/class.tx_magento_api.php');

/**
 * Plugin 'Magento Connect' for the 'magento' extension.
 *
 * @author	 <>
 * @package	TYPO3
 * @subpackage	tx_magento
 */
class tx_magentoproducts extends tslib_pibase {
	var $prefixId      = 'tx_magento_products';		// Same as class name
	var $scriptRelPath = 'feplugins/magentoproducts/class.tx_magentoproducts.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'magento';	// The extension key.
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




			
			// load the correct mode
			switch ($this->config['mode']) {
				case 'SINGLEPRODUCT':	
					$result = $this->api->getSingleProduct($this->config['sku'], $this->conf['productWithImages']);
					$result = $this->fillTemplateSingleProduct($result);
				break;
				case 'PRODUCTS':	
					$result = $this->api->getProducts($this->config['where'], $this->config['whereField']);
					$result = $this->fillTemplateProducts($result);
				break;
				case 'PRODUCTSEARCH':	
					$result = $this->getProductSearch();
				break;

				case 'PRODUCTIMAGE':	
					#$result = $this->api->getProductImage($this->config['sku']);
				break;
			}
			
			if (is_array($result)) {
				$content.= $this->fillTemplate($result);
			} else {
				$content.= $result;
			}
			

			
		} else {
			$content .= 'No configuration!!';
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function getProductSearch() {
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_'.$this->config['mode'].'###');

		$markerArray['###ACTIONURL###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);
		$markerArray['###SWORD###'] = $this->vars['sword'];
		$markerArray['###RESULT###'] = '';
		$markerArray['###ERROR###'] = '';

		// allowed fields to search in
		$allowedSearchFields = t3lib_div::trimExplode(',', $this->conf['productsearch.']['allowedfields']);	
		
		// selected fields
		foreach ($allowedSearchFields as $key=>$value) {
			$selected = ($this->vars['searchfield']==$value) ? ' selected="selected" ' : '';
  		$markerArray['###SEARCHFIELDS###'] .= '<option value="'.$value.'"'.$selected.'>'.$value.'</option>';
  	}
		
		if ($this->vars['sword']!='') {
			// check for minimal chars
			if ($this->conf['productsearch.']['minimalChars'] > 0 && strlen($this->vars['sword']) < $this->conf['productsearch.']['minimalChars']) {
				$markerArray['###ERROR###'] = 'Minimal chars for search: '.$this->conf['productsearch.']['minimalChars'];
			}	else {
				// todo
				$sword = '%'.$this->vars['sword'].'%';
		
				if ($this->vars['searchfield']!='' && in_array($this->vars['searchfield'], $allowedSearchFields) ) {
					$sfield = $this->vars['searchfield'];
				} else {
					$sfield = $this->conf['productsearch.']['defaultSearchField'];
				}
	
				$result = $this->api->getProducts($sword, $sfield); 		
				$markerArray['###RESULT###'] = $this->fillTemplateProducts($result, true);	

			}
			
		} 


		$content.= $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $subpartArray);
		return $content;
	
	}
	
	function fillTemplateProducts($productList, $search=false) {
		$templatePrefix = ($search) ? '_RESULT' : '';

		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_'.$this->config['mode'].$templatePrefix.'###');		
		$template['item'] = $this->cObj->getSubpart($template['total'],'###ITEM###');

		foreach ($productList as $key=>$singleProduct) {
			$generalMarkers = $this->getGeneralMarkers($singleProduct);
			$markerArray = $generalMarkers['markerArray'];
			$wrappedSubpartArray = $generalMarkers['wrappedSubpartArray'];

			
			$content_item .= $this->cObj->substituteMarkerArrayCached($template['item'], $markerArray, $subpartArray, $wrappedSubpartArray);
		}
	
		// put everything into the template
		$subpartArray['###CONTENT###'] = $content_item;
		
		$content.= $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $subpartArray);
		return $content;
	}

	
	function fillTemplateSingleProduct($row) {
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_'.$this->config['mode'].'###');		
		$template['imagelist'] = $this->cObj->getSubpart($template['total'],'###ITEM###');
		
		$generalMarkers = $this->getGeneralMarkers($row);
		$markerArray = $generalMarkers['markerArray'];
		$wrappedSubpartArray = $generalMarkers['wrappedSubpartArray'];
		
		// render the images
		$imageCount = 0;
		if (count($row['images'])> 0) {
			$imageList = 	$row['images'];
			
			foreach ($row['images'] as $key=>$singleImage) {
				if($singleImage['exclude']==0 && $singleImage['types'][0]=='image') {
				 	$markerArray['###IMAGE###'] = $singleImage['url'];
				 	$markerArray['###LABEL###'] = $singleImage['label'];
				 	$imageCount++;
				 	
				 	$content_item .= $this->cObj->substituteMarkerArrayCached($template['imagelist'], $markerArray, $subpartArray, $wrappedSubpartArray);
				}
				
			}
			
			if ($imageCount>0) {
				$subpartArray['###IMAGELIST###'] = '<div class="imagelist">'.$content_item.'</div>';
			} else {
				$subpartArray['###IMAGELIST###'] = ' ';
			}
	

			

			
	#		$subpartArray['###IMAGELIST###'] .= t3lib_div::view_array($row);
			
		}
		
		// render the categories
		if (is_array($row['categories'])) {
				$markerArray['###CATEGORIES###'] = $this->getCategories($row['categories']);
		}
		
		// backlink
		if ($this->conf['listView'] && $this->vars['sku']) {
			$markerArray['###BACK###'] = $this->cObj->typolink('Back', array('parameter' => $this->conf['listView']));
		} else {
			$markerArray['###BACK###'] = '';
		}
		
	
		$content.= $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $subpartArray, $wrappedSubpartArray);
		return $content;
	}
	
	function getGeneralMarkers($row) {
		foreach ($row as $key=>$value) {
			$markerArray['###'.strtoupper($key).'###'] = $this->cObj->stdWrap($value, $this->conf[strtolower($this->config['mode']).'.'][$key.'.']);
		}

		
		if (isset($row['sku'])) {
			$link = $this->config['baseUrl'].$this->api->getProductLink($row['sku']);
			$wrappedSubpartArray['###PRODUCT_LINK###'][0] = '<a href="'.$link.'">';
			$wrappedSubpartArray['###PRODUCT_LINK###'][1] = '</a>';			

			$detailLink = array();
			$detailLink['parameter'] = $this->conf['singleView'];
			$detailLink['useCacheHash'] = 1;
			$detailLink['additionalParams'] = '&tx_magento[sku]='.$row['sku'];
			$detailLink['returnLast'] = 'url';
			$wrappedSubpartArray['###PRODUCT_DETAILS###'][0] = '<a href="'.$this->cObj->typolink('', $detailLink).'">';
			$wrappedSubpartArray['###PRODUCT_DETAILS###'][1] = '</a>';			

		}
		
		$all['markerArray'] = $markerArray;
		$all['wrappedSubpartArray'] = $wrappedSubpartArray;
		
		return $all;
	}
	
	function getCategories($cats) {
		$template['total'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_CATEGORIES###');
		$template['item'] = $this->cObj->getSubpart($template['total'],'###ITEM###');


		foreach ($cats as $key=>$catId) {

			$category = $this->api->getCategory($catId);		
					
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
		$config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['magento']);		
		$this->config['baseUrl'] = $config['url'];
		if ($config['username']!='' && $config['password']!='' && $config['url']!='')	{
			$config['url'] .= 'api/soap/?wsdl';
			$this->api = new tx_magento_api($config['url'], $config['username'], $config['password']);	
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
		$this->vars = t3lib_div::_GP('tx_magento');
		

		// add the flexform values
		$this->config['mode']		= $this->getFlexform('', 'mode', 'mode');
		$this->config['sku'] 		= $this->getFlexform('', 'sku', 'sku');
		$this->config['sku'] = $this->vars['sku'] ? $this->vars['sku'] : $this->config['sku'];
		$this->config['where'] 		= $this->getFlexform('', 'where', 'where');
		$this->config['whereField'] 		= $this->getFlexform('', 'whereField', 'whereField');		  	

			// Template
		$this->templateCode = $this->cObj->fileResource($conf['templateFile']);

			// CSS file
		$GLOBALS['TSFE']->additionalHeaderData['magento'] = (isset($this->conf['pathToCSS'])) ? '<link rel="stylesheet" href="'.$GLOBALS['TSFE']->tmpl->getFileName($this->conf['pathToCSS']).'" type="text/css" />' : '';
		


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



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magento/feplugins/magentoproducts/class.tx_magentoproducts.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magento/feplugins/magentoproducts/class.tx_magentoproducts.php']);
}

?>
