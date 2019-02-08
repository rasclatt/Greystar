<?php
namespace Greystar;

use \Greystar\User\Subscription;

class User extends \Greystar\Model
{
	protected	$types	=	[
		'P'=> 'Promoter',
		'C'=> 'Preferred',
		'R' => 'Retail'
	];
	
	protected	$user	=	[];
	/**
	*	@description	Check and filter username to allow only a-z0-9
	*/
	public	static	function validUsername($username)
	{
		$username	=	trim($username);
		return (preg_match('/^[a-z0-9]{3,30}$/',$username))? $username : false;
	}
	/**
	*	@description	Check if username exists
	*/
	public	function userExists($username)
	{
		$data	=	$this->goodusername(['distid'=>$username]);
		
		if(empty($data['result']))
			return false;
		
		return ($data['result'] == 'In Use');
	}
	
	public	function getDist($username, $flags=[])
	{
		$this->user	=	$this->getuserdata(array_merge([
			'distid' => $username,
			'returntype'=>'all'
		],$flags));
		
		return $this;
	}
	
	public	function getDistInfo($username, $flags = [])
	{
		$user	=	$this->getDist($username, $flags)->user;
		$user	=	(!empty($user))? array_combine(array_map(function($v){
			return rtrim($v, ':');
		}, array_keys($user)), $user) : [];
		
		if(empty($user))
			return $user;
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
			}
			else
				$new['general'][$key]	=	$value;
		}
		
		if(!empty($new['autoship'])) {
			$new['autoship']	=	(new Subscription())->get($username);
		}
		
		ksort($new);
		
		return $new;
	}
	
	public	function getAvatar($username)
	{
		$arr	=	$this->getUserValue('user',$username);
		return (!empty($arr['avatar']))? $arr['avatar'] : false;
	}
	
	public	function getDistType()
	{
		$arr	=	$this->getUserValue('user', $username);
		return (!empty($arr['member_type']))? $arr['member_type'] : false;
	}
	
	public	function getShippingInfo($username)
	{
		if(empty($this->user))
			$this->user	=	$this->getDistInfo($username);
		
		return $this->getUserValue('shipping', $username);
	}
	
	public	function getBillingInfo($username)
	{
		if(empty($this->user))
			$this->user	=	$this->getDistInfo($username);
		
		return $this->getUserValue('billing', $username);
	}
	
	public	function getUserValue($key, $username)
	{
		if(!empty($this->user[$key]))
			return $this->user[$key];
		else {
			if(!empty($username))
				$this->user	=	$this->getDistInfo($username);
			
			return (!empty($this->user[$key]))? $this->user[$key] : false;
		}
	}
	
	public	function getUserArray($pattern,$username=false)
	{
		if(empty($this->user) && !empty($username))
			$data	=	$this->getDistInfo($username);
		else
			$data	=	$this->user;
		$store	=	[];
		foreach($data as $key => $value) {
			if(preg_match($pattern,$key))
				$store[$key]	=	$value;
		}
		
		return $store;
	}
}