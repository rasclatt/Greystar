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
	public	function get()
	{
		$arrs	=	$this->getHelperClass('Greystar\Model')->fulldata(['distid' => $this->distid]);
		
		if(empty($arrs))
			return $this;
		
		foreach($arrs as $keys => $values) {
			if($values == 'Yes' || $values =='No')
				$values	=	($values == 'Yes');
			
			if(stripos($keys, '-') !== false)
				$keys	=	str_replace('-','', $keys);
			
			$new[trim($keys, ':')]	=	preg_replace('/[^\d\.\-]/','', $values);
		}
		
		$arrs	=	array_merge($this->getPersonalVolume()->getData(), $new);
		$new	=	[];
		foreach($arrs as $keys => $values) {
			if(preg_match('/commission/', $keys)) {
				$new['commission'][str_replace('commissions_of_type_', '', $keys)]	=	$values;
			}
			elseif(preg_match('/^left_/', $keys)) {
				$new['left'][str_replace('left_', '', $keys)]	=	$values;
			}
			elseif(preg_match('/^right_/', $keys)) {
				$new['right'][str_replace('right_', '', $keys)]	=	$values;
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
}