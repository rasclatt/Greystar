<?php
namespace Greystar\User;
/**
 *	@description	
 */
class Genealogy extends \Greystar\User
{
	/**
	 *	@description	
	 */
	public	function inDownline($parent_id, $child_id)
	{
		if($parent_id == $child_id)
			return false;
		//$binary		=	$this->getbinaryplacement(['distid' => $child_id]);
		$upline		=	$this->placeupline(['username' => $child_id]);
		
		if(!is_array($upline))
			return false;
		
		return in_array($parent_id, $upline);
	}
	/**
	 *	@description	
	 */
	public	function getUpline($distid)
	{
		return $this->enrupline(['username' => $distid]);
	}
	/**
	 *	@description	
	 */
	public	function getSponsor($distid)
	{
		$upline	=	$this->getUpline($distid);
		return (!empty($upline[1]))? $upline[1] : false;
	}
}