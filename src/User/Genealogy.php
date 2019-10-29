<?php
namespace Greystar\User;
/**
 *	@description	
 */
class Genealogy extends \Greystar\User
{
	protected	$distid;
	
	public	function __construct($distid = false)
	{
		$this->distid	=	$distid;
	}
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
	public	function getUpline($distid = false)
	{
		if(empty($distid))
			$distid	=	$this->distid;
		
		$vals	=	array_values($this->enrupline(['username' => $distid]));
		$c		=	count($vals)-1;
		if(in_array($vals[$c], [3, 'beyond']))
			$vals	=	array_reverse($vals);
		
		return $vals;
	}
	/**
	 *	@description	
	 */
	public	function getSponsor($distid = false)
	{
		if(empty($distid))
			$distid	=	$this->distid;
		
		$upline		=	array_values($this->getUpline($distid));
		$sponsor	=	array_pop($upline);
		return $sponsor;
	}
}