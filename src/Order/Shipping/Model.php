<?php
namespace Greystar\Order\Shipping;

use \Greystar\Reports;
/**
 *	@description	
 */
class Model extends \Greystar\Order\Controller
{
	/**
	 *	@description	
	 */
	public	function getAllOrdersToShip($country = false, $backtime = 'today - 4 weeks')
	{
		$Reports	=	new Reports();
		$attr	=	[
			'startdate'=>date('Y-m-d',strtotime($backtime)),
			'enddate'=>date('Y-m-d',strtotime('today'))
		];
		
		$orders	=	array_values(array_filter($Reports->getReport('notrack', $attr, false, function($rows){

			$arr	=	array_map(function($v){
				return str_getcsv($v,"\t");
			}, explode(PHP_EOL, $rows));

			if(!empty($arr[0])) {
				$keys	=	array_map(function($v){
					return str_replace([' ','#','no.'],['_','num','num'],strtolower($v));
				},$arr[0]);

				unset($arr[0]);
				$count	=	count($keys);
				$data	=	array_map(function($v) use ($keys, $count){
					$vc	=	count($keys) - count($v);
					if($count > $vc) {
						for($i = 0; $i < $vc; $i++) {
							$v[]	=	'';
						}
					}
					$v	=	array_combine($keys, $v);
					
					return $v;
					
				}, $arr);

				return $data;
			}
		})));
		
		$ids	=	[];
		
		if(empty($orders))
			return $ids;
		
		foreach($orders as $order) {
			if(!empty($country)) {
				if(in_array($order['country'], $country)) {
					$ids[]	=	$order['invoice_num'];
				}
			}
			else {
				$ids[]	=	$order['invoice_num'];
			}
		}
		
		return $ids;
	}
	/**
	 *	@description	
	 */
	public	function getOrdersToShip($country = false, $backtime = 'today - 4 weeks')
	{
		$Reports	=	new Reports();
		$attr	=	[
			'startdate'=>date('Y-m-d',strtotime($backtime)),
			'enddate'=>date('Y-m-d',strtotime('today'))
		];
		
		if(!empty($country))
			$attr['country']	=	$country;
		
		return array_values(array_filter($Reports->getReport('notrack', $attr, false, function($rows){

			$arr	=	array_map(function($v){
				return str_getcsv($v,"\t");
			}, explode(PHP_EOL, $rows));

			if(!empty($arr[0])) {
				$keys	=	array_map(function($v){
					return str_replace([' ','#','no.'],['_','num','num'],strtolower($v));
				},$arr[0]);

				unset($arr[0]);
				$count	=	count($keys);
				$data	=	array_map(function($v) use ($keys, $count){
					$vc	=	count($keys) - count($v);
					if($count > $vc) {
						for($i = 0; $i < $vc; $i++) {
							$v[]	=	'';
						}
					}
					$v	=	array_combine($keys, $v);
					/*
					$v['bill_zip']	=	ltrim($v['bill_zip'],"'");
					$v['ship_zip']	=	ltrim($v['ship_zip'],"'");
					$v['tax_id']	=	ltrim($v['tax_id'],"'");
					$v['zip']		=	ltrim($v['zip'],"'");
					*/
					
				//	if(!empty($v['date_printed'])) {
				//		if(strtotime($v['date_printed']) <  strtotime('today - 4 weeks'))
				//			return false;
				//	}
					
					return $v['invoice_num'];
					
				}, $arr);

				return $data;
			}
		})));
	}
}