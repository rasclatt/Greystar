<?php
namespace Greystar\Products;
/**
 *	@description	
 */
class Controller extends \Greystar\Products
{
	/**
	 *	@description	
	 */
	public	function getRetailProducts($co = 'US')
	{
		return $this->getProductsByCategory('Retail Shopping Cart', $co);
	}
	/**
	 *	@description	
	 */
	public	function getDistributorProducts($co = 'US')
	{
		return $this->getProductsByCategory('Distributor Shopping Cart', $co);
	}
	
	public	function getProductsByType($type, $co = 'US')
	{
		return (strtolower($type) == 'promoter')? $this->getDistributorProducts($co) : $this->getRetailProducts($co);
	}
}