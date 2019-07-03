<?php
namespace Greystar\Products;
/**
 *	@description	
 */
class Subscription extends \Greystar\Products
{
	private	$distid;
	/**
	 *	@description	
	 */
	public	function setDistId($distid)
	{
		$this->distid	=	$distid;
		
		return $this;
	}	
	public	function update($username = false, $products = false)
	{
		if(!empty($username))
			$this->distid	=	$username;
		elseif(empty($this->distid))
			return false;
		
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
		
		$array['distid']	=	$this->distid;

		return $this->doService(['autoshipproducts', 'logonly'], $array);
	}
	/**
	 *	@description	
	 */
	public	function delete($distid = false)
	{
		if(!empty($distid))
			$this->distid	=	$distid;
	
		if(empty($this->distid))
			return false;
		
		$resp	=	$this->doService('deleteautoship', ['distid' => $this->distid]);

		return (!empty($resp['result']) && in_array($resp['result'], ['Autoship Deleted','No Autoship Detected']));
	}
}