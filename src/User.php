<?php
namespace Greystar;

use \Greystar\User\Subscription;

class User extends \Greystar\Model
{
	private	$protected,
			$flags;
	
	private	$ranks_list	=	[
		'1_star_vip',
		'2_star_vip',
		'3_star_vip',
		'4_star_vip',
		'influencer',
		'platinum_influencer',
		'executive_influencer',
		'global_influencer',
		'ambassador',
		'platinum_ambassador',
		'executive_ambassador',
		'presidential_ambassador',
		'crown_ambassador',
		'chairman',
		'plantinum_chairman',
		'executive_chairman',
		'presidential_chairman',
		'crown_chairman'
	];
	
	protected	$types	=	[
		'P'=> 'Promoter',
		'C'=> 'Preferred',
		'R' => 'Retail'
	];
	protected	$raw_user	=	[];
	protected	$user		=	[];
	/**
	 *	@description	
	 */
	public	function __construct($username=false)
	{
		$this->setDistId($username);
	}
	
	protected	function setDistId($username)
	{
		if(!empty($username))
			$this->distid	=	trim($username);
		
		return $this;
	}
	
	/**
	*	@description	Check and filter username to allow only a-z0-9
	*/
	public	static	function validUsername($username = false)
	{
		$this->setDistId($username);
		return (preg_match('/^[a-z0-9]{3,30}$/',$this->distid))? $this->distid : false;
	}
	/**
	*	@description	Check if username exists
	*/
	public	function userExists($username = false)
	{
		$this->setDistId($username);
		$data	=	$this->goodusername(['distid'=>$this->distid]);
		
		if(empty($data['result']))
			return false;
		
		return ($data['result'] == 'In Use');
	}
	/**
	*	@description	Check if username is suspended
	*/
	public	function isSuspended($username = false)
	{
		if(!empty($this->user['general']['status']))
			$status['status']	=	$this->user['general']['status'];
		else {
			$this->setDistId($username);
			$status	=	$this->getDist($this->distid)->getUserArray('/status/i');
		}
		return (!empty($status['status']) && stripos($status['status'], 'suspended') !== false);
	}
	/**
	 *	@description	
	 */
	public	function includeFlags($flags = false)
	{
		$this->flags	=	(!empty($flags))? $flags : ['includeflags' => 'all'];
		return $this;
	}
	
	public	function getDist($username=false, $flags=[], $qv = false)
	{
		if(empty($flags) && !empty($this->flags))
			$flags	=	$this->flags;
		
		$this->setDistId($username);
		
		if(empty($this->distid))
			return $this;
		$this->raw_user	=
		$this->user	=	$this->getuserdata(array_merge([
			'distid' => $this->distid,
			'returntype' => 'all',
			'showqv' => (!empty($qv))? $qv : date('Y-m-d'),
			'includeflags' => 'all'
		],$flags));
		
		return $this;
	}
	
	public	function getDistInfo($username = false, $flags = [], $qv = false)
	{
		$this->setDistId($username);
		$user	=	$this->getDist($this->distid, $flags)->user;
		$user	=	(!empty($user))? array_combine(array_map(function($v){
			return rtrim($v, ':');
		}, array_keys($user)), $user) : [];
		
		if(empty($user))
			return $user;
		
		$volume	=	(new \Greystar\User\Volume($username))->getVolume()->getData();
		
		$new	=	[];
		foreach($user as $key => $value) {
			if(preg_match('/^user/', $key) || in_array($key, ['internal_id','join_date','highest_achieved_rank','current_rank','avatar','first_name','last_name','full_name','member_type','email_address','night_phone','cell_phone'])) {
				if($key == 'internal_id')
					$new['user']['distid']	=	$value;
				else
					$new['user'][$key]	=	$value;
			}
			elseif(preg_match('/^billing_/', $key)) {
				$new['billing'][str_replace('billing_','',$key)]	=	$value;
			}
			elseif(preg_match('/^shipping_/', $key)) {
				$new['shipping'][str_replace('shipping_','',$key)]	=	$value;
			}
			elseif(preg_match('/^cc_/', $key) || preg_match('/^exp_/', $key) || preg_match('/_card/', $key)) {
				$new['credit_card'][str_replace(['cc_', 'billing_'],'',$key)]	=	$value;
			}
			elseif(preg_match('/^autoship_/', $key)) {
				
				$new['autoship']	=	true;
			}/*
			elseif(preg_match('/_qv/', $key)) {
				if(stripos($key, 'left') !== false)
					$new['volume']['leg_left'][$key]	=	$value;
				else
					$new['volume']['leg_right'][$key]	=	$value;
			}*/
			else
				$new['general'][$key]	=	$value;
		}
		
		if(!empty($new['autoship'])) {
			$new['autoship']	=	(new Subscription())->get($this->distid);
		}
		
		ksort($new);
		$new['volume']						=	(!empty($new['volume']))? array_merge($new['volume'], $volume) : [];
		$new['volume']['leg_left']			=	(isset($volume['leg_left']))? $volume['leg_left'] : 0;
		$new['volume']['leg_right']			=	(isset($volume['leg_right']))? $volume['leg_right'] : 0;
		$new['volume']['leg_left']['QV']	=	(isset($user['left_qv']))? $user['left_qv'] : 0;
		$new['volume']['leg_right']['QV']	=	(isset($user['right_qv']))? $user['right_qv'] : 0;
		$new['autoship']['active']			=	(!empty($volume['autoship_activated']))? $volume['autoship_activated'] : false;
		$new['general']['sponsor']			=	(!empty($volume['sponsor_id']))? $volume['sponsor_id'] : false;
		
		unset($new['volume']['active'], $new['volume']['autoship_activated'], $new['volume']['rank'], $new['volume']['sponsor_id']);
		
		return $new;
	}
	
	public	function getAvatar($username=false)
	{
		$this->setDistId($username);
		
		if(empty($this->raw_user))
			$this->getDist($username);
		
		$arr	=	$this->getRawUserArray('/avatar/');
		
		return (!empty($arr['avatar']))? $arr['avatar'] : false;
	}
	
	public	function getDistType($username=false)
	{
		$this->setDistId($username);
		
		if(empty($this->raw_user))
			$this->getDist($username);
		
		$arr	=	$this->getRawUserArray('/member_type/');
		return (!empty($arr['member_type']))? $arr['member_type'] : false;
	}
	
	public	function getShippingInfo($username=false)
	{
		$this->setDistId($username);
		
		if(empty($this->user))
			$this->user	=	$this->getDistInfo($this->distid);
		
		return $this->getUserValue('shipping', $this->distid);
	}
	
	public	function getBillingInfo($username=false)
	{
		$this->setDistId($username);
		
		if(empty($this->user))
			$this->user	=	$this->getDistInfo($this->distid);
		
		return $this->getUserValue('billing', $this->distid);
	}
	
	public	function getUserValue($key, $username=false)
	{
		$this->setDistId($username);
		
		if(!empty($this->user[$key]))
			return $this->user[$key];
		else {
			if(!empty($this->distid))
				$this->user	=	$this->getDistInfo($this->distid);
			
			return (!empty($this->user[$key]))? $this->user[$key] : false;
		}
	}
	
	public	function getRawUserArray($pattern, $username=false)
	{
		$this->setDistId($username);
		
		$data	=	(empty($this->raw_user) && !empty($this->distid))? $this->getDist($this->distid)->raw_user : $this->raw_user;
		$store	=	[];
		foreach($data as $key => $value) {
			if(preg_match($pattern, $key))
				$store[$key]	=	$value;
		}
		
		return $store;
	}
	
	public	function getUserArray($pattern,$username=false)
	{
		$this->setDistId($username);
		
		if(empty($this->user) && !empty($this->distid))
			$data	=	$this->getDistInfo($this->distid);
		else
			$data	=	$this->user;
		$store	=	[];
		foreach($data as $key => $value) {
			if(preg_match($pattern,$key))
				$store[$key]	=	$value;
		}
		
		return $store;
	}
	/**
	 *	@description	
	 */
	public	function getRankScore($username=false, $realrank = false)
	{
		$this->setDistId($username);
		
		if(empty($this->user) && !empty($this->distid))
			$this->getDist($this->distid);
		
		$key	=	($realrank)? 'current_rank' : 'highest_achieved_rank';
		$data	=	$this->getUserArray('/'.$key.'/');
		
		if(empty($data[$key]))
			return false;
		
		return $this->getRankNumberFromList($data[$key]);
	}
	/**
	 *	@description	
	 */
	public	function formatRankName($rank)
	{
		return str_replace(' ','_', strtolower($rank));
	}
	/**
	 *	@description	
	 */
	public	function setCustomRankList(array $arr)
	{
		$this->ranks_list	=	$array;
		return $this;
	}
	/**
	 *	@description	
	 */
	public	function getRankNumberFromList($rank)
	{
		return array_search($this->formatRankName($rank), $this->ranks_list)+1;
	}
	/**
	 *	@description	
	 */
	public	function getRankNameFromList($number)
	{
		return (isset($this->ranks_list[$number-1]))? ucwords(str_replace('_', ' ', $this->ranks_list[$number-1])) : false;
	}
	/**
	 *	@description	
	 */
	public	function getRankNameList($beauty = true)
	{
		return ($beauty)? array_map(function($v){
			return ucwords(str_replace('_', ' ', $v));
		}, $this->ranks_list) : $this->ranks_list;
	}
}
