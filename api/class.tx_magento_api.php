<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Steffen Kamper, Georg Ringer <>
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

/**
 * API to include Magento
 *
 * @author	Steffen Kamper, Georg Ringer <>
 */
class tx_magento_api {
	
	public $client = NULL;
	public $sessionId;
	
	 /**
	 * class constructor
	 * connects to magento
	 *
	 * @param	string		$url: url of magento soap
	 * @param	string		$user: username for login
	 * @param	string		$password: password for login
	 */	
	public function __construct($url, $user, $password) {
		$this->url = $url;
		
		//connect to magento
		$this->client = new SoapClient($url);
		$this->sessionId = $this->client->login($user, $password);

		if(!$this->client) {
			die('No connection possible, check your params:<br /><br />
				Url: ' . $this->connect['url'] . '<br />
				username: ' . $this->connect['username'] . '<br />
				password: ' . $this->connect['password'] . '');
		}
	}
	
	 /**
	 * makes a direct api-call
	 *
	 * @param	string		$command: api-command
	 * @param	int			$id: normally the sku
	 * @return	product
	 */	
	public function call($command, $id=false) {
		if ($id) {
			return $this->client->call($this->sessionId, $command, $id);
		} else {
			return $this->client->call($this->sessionId, $command);
		}
		
	}
	
	
	/**
	 * Get a single product by its SKU
	 *
	 * @param	string		$sku: sku of the product
	 * @param	boolean		$getImages: Load images at the same time
	 * @return	product
	 */	
	public function getSingleProduct($sku, $getImages = false) {
		$sku = trim($sku);
		
		// if a sku is available
		if ($sku!='') {
			$singleProduct = $this->call('product.info', $sku);				
			
			// load the images of an product too, to get everything in 1 array
			if (count($singleProduct) && $getImages) {				
				$singleProduct['images'] = $this->call('product_media.list', $sku);
			}
			
			return $singleProduct;
		} else {
			return 'No SKU!';
		}
	}

	/**
	 * Get a single product by its SKU
	 *
	 * @param	string		$where: Input for the where clause
	 * @param	string		$whereField: Field to compare
	 * @return	Array productlist
	 */	
	public function getProducts($where, $whereField) {
		$whereField = $whereField ? $whereField : 'sku';
		
		if ($where != '') {
			$filters = array(
				$whereField => array(
					'like'=> $where
				)
			);
			
			return $this->call('product.list', array($filters));				
		} else {
			return 'No Where clause!';
		}
	}

	/**
	 * Get a single product by its SKU
	 *
	 * @param	string		$sku: sku of the product
	 * @return	product
	 */
	public function getProductImage($sku) {
		return $this->call('product_media.list', $sku);
	}

	/**
	 * Get the link to a product
	 *
	 * @param	string		$sku: sku of the product
	 * @return	link
	 */
	public function getProductLink($sku) {
		$product = $this->getSingleProduct($sku, false);
		
		$link = $product['url_key'].'.html';
		
		return $link;
	}

	

	/**
	 * Get the product info
	 *
	 * @param	string		$id: id of the category
	 * @return	product
	 */
	public function getCategory($id) {
		return $this->call('catalog_category.info', intval($id));
	}	
		
	
	/**
	 * Close the SOAP call
	 *
	 * @return void
	 */
	public function close() {
		$this->client->endSession($session);
	}
	

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magento/api/class.tx_magento_api.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magento/api/class.tx_magento_api.php']);
}

?>