<?php
namespace Greystar\Products;
/**
 *	@description	
 */
class Subscription extends \Greystar\Products
{
	public	function update($username, $products = false)
	{
		if(empty($products)) {
			$array	=	[
			  'clearproducts' => 1
			];
		}
		else {
			$i = 1;
			foreach($products as $sku => $qty) {
				$array['product'.$i]	=	$sku;
				$array['quantity'.$i]	=	$qty;
				$i++;
			}
		}
		
		$array['distid']	=	$username;
		
		return $this->autoshipproducts($array);
	}
}