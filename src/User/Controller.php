<?php
namespace Greystar\User;

use \Greystar\User\Volume;

class Controller extends \Greystar\User
{
	public function toRetail($distid)
	{
		$this->modify(['username' => $distid, 'distrib' => 'R']);
		
		return $this->isRetail($distid);
	}
	
	public function toDistributor($distid, $leg = false)
	{
		if(!is_bool($leg)) {
			$leg	=	(strtolower($leg) == 'l')? 0 : 1;
		}
		$settings	=	[
			'username' => $distid,
			'distrib' => 'D'
		];
		
		if(!empty($leg))
			$settings['leg']	=	$leg;
		
		$this->modify($settings);
		return empty($this->isRetail($distid));
	}
	
	public function isRetail($distid)
	{
		return (strtolower($this->getDistType($distid)) == 'retail');
	}
	
	public function isDistributor($distid)
	{
		return (!$this->isRetail($distid));
	}
	/**
	 *	@description	
	 */
	public function getUserSummaryBlock($distid, $enroller = false)
	{
		$Volume	=	new Volume($distid);
		if($enroller) {
			$Genealogy	=	new Genealogy();
			$upline		=	$Genealogy->getUpline($distid);
			
			if(empty($upline))
				return $Volume->setData([]);
			
			if(!in_array($enroller, $upline))
				return $Volume->setData([]);
		}
		
		$data	=	$Volume->getVolume(function($new){
			
			if(!empty($new['leg_left']) && isset($new['leg_left']['personally_sponsored_customers']))
				unset($new['leg_left']['personally_sponsored_customers']);
			
			if(!empty($new['leg_right']) && isset($new['leg_right']['personally_sponsored_customers']))
				unset($new['leg_right']['personally_sponsored_customers']);
			
			if(isset($new['not_ps_tree']))
				$new['preferred_customers']	=	$new['not_ps_tree']['customers'];
			
			//echo printpre($new);
			
			return $new;
		})->getData();
		
		if(empty($data))
			return $Volume->setData([]);
		
		$user						=	$this->getDistInfo($distid);
		
		$data['autoship_bv']		=	(!empty($user['autoship'][0]['total_bv']))? $user['autoship'][0]['total_bv'] : 0;
		$data['autoship_next']	=	(!empty($user['autoship'][0]['next_billing_date']))? $user['autoship'][0]['next_billing_date'] : false;
		$data['join_date']			=	$user['user']['join_date'];
		$data['rank']				=	$user['user']['highest_achieved_rank'];
		$data['country']			=	$user['general']['country'];
		$data['name']				=	$user['user']['full_name'];
		$data['membership']			=	[
			'user_type' => $user['user']['user_type'],
			'member_type' => $user['user']['member_type']
		];
		
		ksort($data);
		
		return $Volume->setData($data);
	}
	/**
	 *	@description	
	 */
	public function saveFlag($distid, $flagname, $value)
	{
		return $this->modify([
			'distid' => $distid,
			'setflag1' => $flagname,
			'flagval1' => $value
		]);
	}
	/**
	 *	@description	
	 */
	public function saveFlags($distid, $flags)
	{
		$args	=	[
			'distid' => $distid
		];
		
		$i	=	1;
		foreach($flags as $flag) {
			$args['setflag'.$i]	=	$flag['name'];
			$args['flagval'.$i]	=	$flag['value'];
			
			$i++;
		}
		
		return $this->modify($args);
	}
}
