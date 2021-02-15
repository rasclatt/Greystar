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
	public function get($distid = false)
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
	 *	@description	Fetches the users products on an autoship
	 */
	public function getProducts($distid)
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
	/**
	 *	@description	Checks if the user has an autoship set up
	 */
	public function hasAutoShip($distid)
	{
		$arr	=	$this->get($distid);
		return (isset($arr[0]['no_autoship']))? false : true;
	}
	/**
	 *	@description	
	 */
	public function saveCreditCard($distid, $data)
	{
		foreach($data as $key => $value) {
			$data[$key]	=	trim($value);
		}
		
		$addr1		=	(!empty($data['addr1']))? $data['address'] : false;
		$city		=	(!empty($data['city']))? $data['city'] : false;
		$country	=	(!empty($data['country']))? $data['country'] : false;
		$name		=	(!empty($data['name']))? $data['name'] : false;
		$ccnum		=	(!empty($data['number']))? $data['number'] : false;
			
		if(count(array_filter([$addr1, $city, $country, $name, $ccnum, $distid])) != 6) {
			self::$error['save_cc']	=	'Missing a required field (Address, City, Country, Name, CC Number, Customer ID).';
			return false;
		}
		
		$array	=	[
			'ccaddr' => $addr1,
			'ccaddr2' => (!empty($data['addr2']))? $data['address'] : false,
			'cccity' => $city,
			'cccountry' => $country,
			'ccemail' => (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL))? $data['email'] : false,
			'ccname' => $name,
			'ccno' => $ccnum,
			'distid' => $distid,
		];
		
		return $this->autoshipcreditcard($array);
	}
}
