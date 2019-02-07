<?php
namespace Greystar\Order;

class Model extends \Greystar\Model
{
	private	$distid;
	private	static	$order;
	
	public	function __construct($distid = false)
	{
		$this->distid	=	$distid;
	}
	/**
	 *	@description	
	 */
	public	function create(array $input)
	{
		
		self::$order['raw']			=	$input;
		self::$order['response']	=	$this->invoice($input);
		return $this;
	}
	
	public	function markInvoicePaid($inv, $paid = true, $type = false)
	{
		$invoice			=	$this->getInvoice($inv, $type);
		$invoice['paid']	=	$paid;
		return $this->modify($invoice);
	}
	
	public	function update(array $input)
	{
		# Sample method
		$sdata	=	$this->modifyinvoice($input);
	}
	
	public	function getInvoice(...$args)
	{
		if(!empty($args[0])) {
			if(is_array($args[0])) {
				$inv	=	false;
				$input	=	$args[0];
			}
			else {
				$inv	=	$args[0];
				$input	=	(!empty($args[1]) && is_array($args[1]))? $args[1] : false;
			}
		}
		
		if(!empty($input['from']))
			$array['fromdate']	=	$input['from'];
		if(!empty($inv))
			$array['inv']	=	$inv;
		else {
			if(!empty($input['inv']))
				$array['inv']	=	$input['inv'];
		}
		if(!empty($input['type']))
			$array['returntype']	=	$input['type'];
		if(!empty($input['to']))
			$array['todate']	=	$input['to'];
		
		if(!empty($input['username']))
			$array['username']	=	$input['username'];
		elseif(!empty($input['distid']))
			$array['username']	=	$input['distid'];
		else
			$array['username']	=	$this->distid;
		
		if(empty($array['username'])) {
			trigger_error('Username/Distributor Id is required.');
			return false;
		}
		$array['format']	=	'csv';
		
		return $this->doService('getinvoices', $array, function($results) use($inv) {
			
			if(empty($results))
				return $results;
			
			$results	=	array_map(function($v){
				$v['date']		=	date('Y-m-d', strtotime($v['date']));
				$v['products']	=	array_map(function($v){
					
					preg_match('/([\d]+) of ([\w]+)/',trim($v), $match);

					return [
						'itemcode' => $match[2],
						'quantity' => $match[1]
					];

				},explode(',', $v['products']));
				
				return $v;
			}, $results);
			
			if(!empty($inv) && !empty($results))
				return ($results[key($results)]);
			else {
				if(empty($results))
					return [];
				
				usort($results, function($a, $b){
					return (strtotime($a['date']) > strtotime($b['date']));
				});
				
				return array_values(array_reverse($results));
			}
		});
	}
	
	public	function getOrderDetails($type = 'response')
	{
		if(!empty($type))
			return (isset(self::$order[$type]))? self::$order[$type] : false;
		
		return self::$order;
	}
}