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
	/**
	 *	@description	
	 */
	public	function createWithAutoShip(array $array, array $products)
	{
		$settings	=	[
			'amount' => $array['amount'],
			'ccaddr' => $array['ccaddr'],
			'ccaddr2' => (!empty($array['ccaddr2']))? $array['ccaddr2'] : false,
			'cccity' => $array['cccity'],
			'cccountry' => $array['cccountry'],
			'ccexpmo' => $array['ccexpmo'],
			'ccexpyr' => $array['ccexpyr'],
			'ccexp' => $array['ccexpmo'].$array['ccexpyr'],
			'ccname' => $array['ccname'],
			'ccno' => $array['ccno'],
			'ccstate' => $array['ccstate'],
			'cczip' => $array['cczip'],
			'email' => $array['email'],
			'ccphone' => $array['ccphone'],
			'distid' => $array['distid'],
			'newuser' => 'N',
			'shipaddress1' => $array['shipaddress1'],
			'shipaddress2' => (!empty($array['shipaddress2']))? $array['shipaddress2'] : false,
			'shipcity' => $array['shipcity'],
			'shipcountry' => $array['shipcountry'],
			'shipstate' => $array['shipstate'],
			'shipzip' => $array['shipzip'],
			'inv' => $array['inv'],
			'paid' => 'N'
		];
		$product	=	array_values($product);
		foreach($products as $key => $product) {
			if($key == 0) {
				$settings['autoshipproduct'.$key]	=	$product['itemcode'];
				$settings['autoshipq'.$key]			=	(!empty($product['qty']))? $product['qty'] : $product['quantity'];
			}
			$settings['product'.$key]			=	$product['itemcode'];
			$settings['quantity'.$key]			=	(!empty($product['qty']))? $product['qty'] : $product['quantity'];
		}
		
		self::$order['raw']			=	$settings;
		self::$order['response']	=
		$charge	=	$Greystar->doService(['creditcardcharge','invoice','createautoship'], $settings);
		
		if(empty($charge['Invoice'])) {
			$this->toError(json_encode($charge));
			return false;
		}
		else {
			$this->toSuccess("Transaction Successful–Thank you for your order!");
			foreach($charge as $key => $value) {
				$success[strtolower(str_replace([' '],['_'], $key))]	=	ltrim($value, '$');
			}
			$this->modifyinvoice([
				'username' => $settings['distid'],
				'paid' => 'Y',
				'inv' => $success['invoice']
			]);
			$this->modifyinvoice([
				'username' => $settings['distid'],
				'paid' => 'Y',
				'inv' => $settings['inv']
			]);
			return $success;
		}
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