<?php
namespace Greystar\User;
/**
 *	@description	
 */
class Commission extends \Greystar\User\Controller
{
	private	$data,
			$from,
			$to;
	
	protected $distid;
	
	public	function __construct($distid)
	{
		$this->distid	=	$distid;
	}
	
	public	function setData($data)
	{
		$this->data	=	$data;
		return $this;
	}
	
	public	function setTo($to)
	{
		$this->to	=	$to;
		return $this;
	}
	
	public	function setFrom($from)
	{
		$this->from	=	$from;
		return $this;
	}
	/**
	 *	@description	
	 */
	public	function get($from = false, $to = false)
	{
		$to			=	(!empty($to))? $to : $this->to;
		$from		=	(!empty($from))? $from : $this->from;
		$summary	=	$this->doService('commissiondata', [
			'distid' => $this->distid,
			'fromdate' => $from,
			'todate' => $to
		]);
		
		$this->data	=	array_combine(array_map(function($v){ return str_replace([' '],['_'],strtolower($v));}, array_keys($summary)), array_map(function($v){ return (preg_match('/[\d\.\,]+/', $v))? str_replace(['$',','],'',$v) : $v; }, array_values($summary)));
		
		return $this;
	}
	/**
	 *	@description	
	 */
	public	function getChecks($country = false, $getAll = false, $from = false, $to = false)
	{
		$to			=	(!empty($to))? $to : $this->to;
		$from		=	(!empty($from))? $from : $this->from;
		
		$args	=	[
			'distid' => $this->distid,
			'fromdate' => $from,
			'todate' => $to
		];
		
		if(!empty($country))
			$args['country']	=	$country;
		
		if(!empty($getAll))
			$args['getall']		=	'Y';
		
		$args['format']		=	'csv';
		$args['textonly']	=	'Y';
		$distid				=	$this->distid;
		$this->data	=	$this->doService('checks', $args, function($response) use ($distid) {
			
			if(empty($response))
				return [];
			
			return array_map(function($v) use ($distid) {
				$arr	=	str_getcsv($v, "\t");
				
				if(empty($arr[0]))
					return [];
				
				$arr[0]	=	str_replace('Payment for '.$distid.' - ','',$arr[0]);
				preg_match('/[\d]{4}-[\d]{2}-[\d]{2}/', $arr[0], $match);
				
				$arr['info']	=	$arr[0];
				if(preg_match('/[\d]{4}-[\d]{2}-[\d]{2}/', $arr[1]))
					$arr['date']	=	$arr[1];
				else
					$arr['date']	=	(!empty($match[0]))? $match[0] : false;
				
				if((stripos($arr[0], 'Payment amount for') !== false) || (stripos($arr[0], 'Payment Fee') !== false))
					$arr['payment']	=	preg_replace('/[^\d\.]/', '', $arr[1]);
				
				if(stripos($arr[0], 'Check number') !== false)
					$arr['check']	=	$arr[1];
				
				unset($arr[0], $arr[1]);
				
				return $arr;
			}, explode(PHP_EOL, $response));
		});
		
		if(empty($this->data)){
			$this->data	=	[];
			return $this;
		}
		
		foreach($this->data as $key => $row) {
			if(isset($row['info'])) {
				if($row['info'] == 'Week Start' || $row['info'] == 'Week End')
					unset($this->data[$key]);
			}
			
			if(isset($row['payment'])) {
				if($row['payment'] == '0.00')
					unset($this->data[$key]);
			}
		}
		$comp		=
		$this->data	=	array_values(array_filter($this->data));
		
		foreach($comp as $key => $row) {
			if(isset($row['check'])) {
				$this->data	=	array_map(function($v) use ($row){
					if($row['date'] == $v['date'] && !isset($v['check']))
						$v['check'] = $row['check'];
					
					return $v;
				}, $this->data);
				
				unset($this->data[$key]);
			}
		}
		
		$this->data	=	array_reverse(array_values($this->data));
		
		return $this;
	}
	/**
	 *	@description	
	 */
	public	function __call($class, $args=false)
	{	
		switch($class) {
			case('getData'):
				if(!empty($args[0])) {
					if(is_callable($args[0])) {
						return $args[0]($this->data);
					}
					else
						return (isset($this->data[$args[0]]))? $this->data[$args[0]] : false;
				}
				return $this->data;
			case('toJson'):
				return json_encode($this->data);
			default:
				# Strip off the "get" from the method
				$name       =   preg_replace('/^get/','',$class);
				# Split method name by upper case
				$getMethod  =   preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY);
				# Create a variable from that split
				$getKey     =   strtolower(implode('_', $getMethod));
				# Fetch the value
				$value		=	(isset($this->data[$getKey]))? $this->data[$getKey] : false;
				# Allows for stripping out commas in numerics
				return	$value;
		}
	}
	/**
	 *	@description	
	 */
	public	function __toString()
	{
		return printpre($this->data);
	}
}