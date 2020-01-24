<?php
namespace Greystar;
/**
 *	@description	
 */
class Ranks extends \Nubersoft\nApp
{
	private	static	$ranks	=	[];
	/**
	 *	@description	
	 */
	public	static	function addRank($name, $volume)
	{
		self::$ranks[self::filterName($name)]	=	[
			'name' => $name,
			'volume' => $volume
		];
	}
	/**
	 *	@description	
	 */
	public	static	function getRank($name = false)
	{
		if($name){
			$name	=	self::filterName($name);
			return (isset(self::$ranks[$name]))? self::$ranks[$name] : false;
		}
		
		return self::$ranks;
	}
	/**
	 *	@description	
	 */
	public	static	function getVolumeByRank($name)
	{
		$name	=	self::filterName($name);
		return (isset(self::$ranks[$name]))? self::$ranks[$name]['volume'] : false;
	}
	/**
	 *	@description	
	 */
	public	static	function getNextRank($name)
	{
		$stop	=	false;
		$name	=	self::filterName($name);
		foreach(self::$ranks as $rname => $info) {
			if($name == $rname) {
				$stop	=	true;
			}
			if($stop && ($name != $rname)) {
				return self::$ranks[$rname];
			}
		}
		
		return self::$ranks[$rname];
	}
	/**
	 *	@description	
	 */
	public	static	function filterName($name)
	{
		return strtolower(str_replace([' ','-'], '_', $name));
	}
}