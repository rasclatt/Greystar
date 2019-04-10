<?php
namespace Greystar\User;

class Controller extends \Greystar\User
{
	public	function toRetail($distid)
	{
		$this->modify(['username' => $distid, 'distrib' => 'R']);
		
		return $this->isRetail($distid);
	}
	
	public	function toDistributor($distid, $leg = false)
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
	
	public	function isRetail($distid)
	{
		return (strtolower($this->getDistType($distid)) == 'retail');
	}
	
	public	function isDistributor($distid)
	{
		return (!$this->isRetail($distid));
	}
}
