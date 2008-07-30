<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Georg Ringer <http://www.ringer.it/>
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
 * @author	Georg Ringer <http://www.ringer.it/>
 */
class tx_magentoconnect_api {

	/**
	 * Get a single product by its SKU
	 *
	 * @param	string		$sku: sku of the product
	 * @param	boolean		$getImages: Load images at the same time
	 * @return	product
	 */	
	function getSingleProduct($sku, $getImages=0) {
		$sku = trim($sku);
		
		// if a sku is available
		if ($sku!='') {
			$singleProduct = $this->client->call($this->sessionId, 'product.info', $sku);				
			
			// load the images of an product too, to get everything in 1 array
			if (count($singleProduct) > 0 && $getImages==1) {				
				$singleProduct['images'] = $this->client->call($this->sessionId, 'product_media.list', $sku);
			}
			
			return $singleProduct;
		} else {
			$content = 'No SKU!';
		}

		return $content;
	}

	/**
	 * Get a single product by its SKU
	 *
	 * @param	string		$where: Input for the where clause
	 * @param	string		$whereField: Field to compare
	 * @return	Array productlist
	 */	
	function getProducts($where, $whereField) {
		$whereField = $whereField ? $whereField : 'sku';
		
		if ($where!='') {
			$filters = array(
				$whereField => array('like'=> $where)
			);
			
			$products = $this->client->call($this->sessionId, 'product.list', array($filters));				
			
			return $products;
			
		} else {
			$content = 'No Where clause!';
		}
		
		return $content;
	}

	/**
	 * Get a single product by its SKU
	 *
	 * @param	string		$sku: sku of the product
	 * @return	product
	 */
	function getProductImage($sku) {
		$productImage = $this->client->call($this->sessionId, 'product_media.list', $sku);
		
		return $productImage;
	}

	/**
	 * Get the product info
	 *
	 * @param	string		$id: id of the category
	 * @return	product
	 */
	function getCategory($id) {
	
		$content = $this->client->call($this->sessionId, 'catalog_category.info', intval($id));
		
		return $content;
	}	
		
	/**
	 * Open the SOAP call
	 *
	 * @return void
	 */	
	function open() {
		$this->client = new SoapClient($this->connect['url']);
		$this->sessionId = $this->client->login($this->connect['username'], $this->connect['password']);
	}

	/**
	 * Close the SOAP call
	 *
	 * @return void
	 */
	function close() {
			$this->client->endSession($session);
	}
	
	/**
	 * Get the connection
	 *
	 * @return void
	 */
	function getConnection($url, $username, $password) {
		$this->connect['username'] = $username;
		$this->connect['url'] = $url;
		$this->connect['password'] = $password;
	}



}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magentoconnect/class.tx_magentoconnect_api.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/magentoconnect/class.tx_magentoconnect_api.php']);
}

?>
