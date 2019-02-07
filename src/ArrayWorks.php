<?php
namespace Greystar;

class ArrayWorks extends \Nubersoft\ArrayWorks
{
	public	static	function combineColumnRows($array,$headkey = 'Header',$countKey = 'Row Count')
	{
		if(empty($array) || !is_array($array))
			return $array;
		
		if(isset($array[$headkey])) {
			$keys	=	$array[$headkey];
			unset($array[$headkey]);
			
			if(isset($array[$countKey]) && $array[$countKey] >= 1) {
				unset($array[$countKey]);
			}
			
			foreach($array as $key => $row) {
				$new[]	=	array_combine($keys,$row);
			}

			return (!empty($new))? $new : false;
		}
		
		return $array;
	}
	
	public	static	function recursive($array,$kfunc = false,$vfunc = false)
	{
		if(!is_array($array)) {
			return (is_callable($vfunc))? $vfunc($array,false) : $array;
		}
		
		$new	=	[];
		
		foreach($array as $key => $value) {
			if(is_callable($kfunc))
				$key	=	$kfunc($key,$value);
			
			$new[$key]	=	(is_callable($vfunc))? $vfunc($value,$key) : $value;
		}
		
		return $new;
	}
	
	public	static	function convertKeys(&$array, $func = false)
	{
		foreach($array as $key => $value) {
			if(is_array($value)) {
				self::convertKeys($value, $func);
			}
			$nkey	=	str_replace(['/','?',' '],['_or_','','_'],strtolower($key));

			if(is_callable($func))
				$nkey	=	$func($nkey);
			
			$new[$nkey]	=	$value;
		}
		
		if(!empty($new))
			$array	=	$new;
	}
	
	public	static	function convertValues(&$array)
	{
		foreach($array as $key => $value) {
			$array[$key]	=	str_replace(['/','?',' '],['_or_','','_'],strtolower($value));
		}
	}
}