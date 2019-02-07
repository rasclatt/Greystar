<?php
namespace Greystar\User;

use \Greystar\ArrayWorks;
use \Greystar\Model;
/**
 *	@description	
 */
class Subscription extends \Greystar\User
{
	/**
	 *	@description	
	 */
	public	function get($distid)
	{
		$array	=	[
		  'distid' => $distid,
		];
		
		$autoship	=	Model::getautoship($array);
		
		return (!empty($autoship['autoships']))? array_map(function($v) {
			ArrayWorks::convertKeys($v, function($v){
				return str_replace(['product_','cc_billing_'], ['','cc_'], $v);
			});
			ksort($v);
			return $v;
		}, $autoship['autoships']) : [];
	}
	/**
	 *	@description	
	 */
	public	function getProducts($distid)
	{
		$array	=	[
		  'distid' => $distid,
		];
		
		$autoship	=	Model::getautoship($array);
		
		$autoship	=	(!empty($autoship['autoships']))? array_map(function($v) {
			ArrayWorks::convertKeys($v, function($v){
				return str_replace(['product_','cc_billing_'], ['','cc_'], $v);
			});
			ksort($v);
			return $v;
		}, $autoship['autoships']) : [];
		
		foreach($autoship as $key => $as) {
			$autoship[$key]	=	$as['products'];
		}
		
		return	$autoship;
	}
}