<?php
namespace Greystar;

use \Greystar\User\Subscription;

class User extends \Greystar\Model
{
	private	$protected;
	
	protected	$types	=	[
		'P'=> 'Promoter',
		'C'=> 'Preferred',
		'R' => 'Retail'
	];
	
	protected	$user	=	[];
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
	
	public	function getDist($username=false, $flags=[])
	{
		$this->setDistId($username);
		
		$this->user	=	$this->getuserdata(array_merge([
			'distid' => $this->distid,
			'returntype'=>'all'
		],$flags));
		
		return $this;
	}
	
	public	function getDistInfo($username = false, $flags = [])
	{
		$this->setDistId($username);
		$user	=	$this->getDist($this->distid, $flags)->user;
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
			$new['autoship']	=	(new Subscription())->get($this->distid);
		}
		
		ksort($new);
		
		return $new;
	}
	
	public	function getAvatar($username=false)
	{
		$this->setDistId($username);
		$arr	=	$this->getUserValue('user',$this->distid);
		return (!empty($arr['avatar']))? $arr['avatar'] : false;
	}
	
	public	function getDistType($username=false)
	{
		$this->setDistId($username);
		$arr	=	$this->getUserValue('user', $this->distid);
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
}
