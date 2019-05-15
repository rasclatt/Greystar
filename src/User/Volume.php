<?php
namespace Greystar\User;
/**
 *	@description	
 */
class Volume  extends \Greystar\User\Commission
{
	/**
	 *	@description	
	 */
	public	function getVolume()
	{
		$args	=	func_get_args();
		$func	=	(!empty($args[0]) && is_callable($args[0]))? $args[0] : false;
		$arrs	=	$this->getHelperClass('Greystar\Model')->fulldata(['distid' => $this->distid]);

		if(empty($arrs))
			return $this;
		
		$arrs['active']	=	($arrs['rank'] != 'Inactive');
		
		foreach($arrs as $keys => $values) {
			if($values == 'Yes' || $values =='No')
				$values	=	($values == 'Yes');
			
			if(stripos($keys, '-') !== false)
				$keys	=	str_replace('-','', $keys);
			
			$new[trim($keys, ':')]	=	preg_replace('/[^\d\.\-]/','', $values);
		}
		$arrs	=	array_merge($this->getBanked()->getData(), $new, $this->getCV()->getData());
		$new	=	[];
		foreach($arrs as $keys => $values) {
			if(preg_match('/commission/', $keys)) {
				$new['commission'][str_replace('commissions_of_type_', '', $keys)]	=	$values;
			}
			elseif(preg_match('/^left_/', $keys)) {
				$new['leg_left'][str_replace('left_', '', $keys)]	=	str_replace(',', '', $values);
			}
			elseif(preg_match('/^right_/', $keys)) {
				$new['leg_right'][str_replace('right_', '', $keys)]	=	str_replace(',', '', $values);
			}
			elseif(preg_match('/^total_/', $keys)) {
				$new['totals'][str_replace('total_', '', $keys)]	=	$values;
			}
			elseif(preg_match('/^not_in_tree_personally_sponsored_/', $keys)) {
				$new['not_ps_tree'][str_replace('not_in_tree_personally_sponsored_', '', $keys)]	=	$values;
			}
			elseif(preg_match('/date/', $keys)) {
				$new['period'][str_replace(['_of_data','_of_date'], '', $keys)]	=	$values;
			}
			else
				$new[$keys]	=	$values;
		}
		
		$new['sponsor_id']	=	$this->getHelperClass('Greystar\User\Genealogy')->getSponsor($this->distid);
		
		ksort($new);
		
		//echo printpre($new);
		
		if(is_callable($func)) {
			$new	=	$func($new);
		}
		
		$this->setData($new);
		
		return $this;
	}
	/**
	 *	@description	
	 */
	public	function getPersonalVolume()
	{
		$args		=	$this->getHelperClass('Greystar\Model')->binarypoints(['distid' => $this->distid]);
		$this->setData([
			'left_personal_volume' => (!empty($args['left_volume:']))? $args['left_volume:'] : 0,
			'right_personal_volume' => (!empty($args['left_volume:']))? $args['right_volume:'] : 0
		]);
		
		return $this;
	}
	
	public	function getBanked($from = false, $to = false)
	{
		$attr	=	[
			'username' => $this->distid,
			'startdate' => (!empty($from))? $from : date("Y-m-d", strtotime("today"))
		];
		
		if(!empty($to))
			$attr['enddate']	=	$to;
		
		$report	=	$this->getHelperClass('Greystar\Reports')->getReport('holding', $attr);
		
		$this->setData([
			'left_holding' => (!empty($report[0]['holding_left']))? $report[0]['holding_left'] : 0,
			'right_holding' => (!empty($report[0]['holding_right']))? $report[0]['holding_right'] : 0
		]);
		
		return $this;
	}
	/**
	 *	@description	
	 */
	public	function getCV($back = 4)
	{
		$report	=	$this->getHelperClass('Greystar\Reports')->getReport('totalbv', [
			'username' => $this->distid,
			'startdate' => date('Y-m-d', strtotime('today - '.$back.' weeks')),
			'enddate' => date('Y-m-d', strtotime('today'))
		]);
		
		$this->setData([
			'total_cv' => (!empty($report[0]['total_bv']))? $report[0]['total_bv'] : 0
		]);
		
		return $this;
	}
}