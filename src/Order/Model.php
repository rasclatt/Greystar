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
	public	function createWithAutoShip(array $array, array $products, $discount = 0, $shipping = 0)
	{
		$settings	=	[
			'amount' => $array['amount'],
			'billaddress1' => $array['ccaddr'],
			'billaddress2' => (!empty($array['ccaddr2']))? $array['ccaddr2'] : false,
			'billcity' => $array['cccity'],
			'billcountry' => $array['cccountry'],
			'billstate' => $array['ccstate'],
			'billzip' => $array['cczip'],
			'billphone' => $array['ccphone'],
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
			'username' => $array['distid'],
			'newuser' => 'N',
			'shipaddress1' => $array['shipaddress1'],
			'shipaddress2' => (!empty($array['shipaddress2']))? $array['shipaddress2'] : false,
			'shipcity' => $array['shipcity'],
			'shipcountry' => $array['shipcountry'],
			'shipstate' => $array['shipstate'],
			'shipzip' => $array['shipzip'],
			'paid' => 'N'
		];
		
		$products	=	array_values($products);
		$a	=	1;
		foreach($products as $key => $product) {
			if($a == 1) {
				$settings['autoshipproduct'.$a]	=	$product['itemcode'];
				$settings['autoshipq'.$a]		=	(!empty($product['qty']))? $product['qty'] : $product['quantity'];
			}
			
			$settings['product'.$a]	=	$product['itemcode'];
			$settings['qty'.$a]		=
			/*$settings['quantity'.$a]	=	*/(!empty($product['qty']))? $product['qty'] : $product['quantity'];
			
			$a++;
		}
		
		self::$order['raw']			=	$settings;
		
		# Create Autoship
		$ascreate			=	$this->doService(['creditcardstore','createautoship'], $settings);
		$settings['inv']	=	$array['inv'];
		# Create the invoice order
		self::$order['response']	=	
		$charge	=	$this->doService(['creditcardcharge','invoice'], $settings);
		# Stop if fail
		if(!empty($charge['error']) || (!empty($charge['result']) && stripos($charge['result'], 'fail') !== false)) {
			$this->doService('deleteautoship', ['distid' => $this->userGet('distid')]);
			$this->toError((!empty($charge['error']))? $charge['error'] : $charge['result']);
			return false;
		}
		else {
			# Set the next run date for the autoship
			$this->autoshipdatechange([
				'nextcharge'=> date('Y-m-d', strtotime("next Friday + 4 weeks")),
				'distid' => $settings['distid']
			]);
			# Mark as paid and and add the discount
			$this->markPaidWithDiscount($settings['distid'], $charge['Invoice'], $discount);
			# Store
			$this->toSuccess("Transaction Successful–Thank you for your order!");
			foreach($charge as $key => $value) {
				$success[strtolower(str_replace([' '],['_'], $key))]	=	ltrim($value, '$');
			}
			return $success;
		}
	}
	/**
	 *	@description	Marks an invoice as paid and adds a discount item if set
	 */
	public	function markPaidWithDiscount($distid, $invoice, $discount, $alterbv = true)
	{
		# Set the final paramerters for marking paid
		$order	=	[
			'username' => $distid,
			'inv' => $invoice,
			'paid' => 'Y'
		];
		# Add the discount to the order
		if($discount > 0) {
			$order['product1']		=	'justbv';
			$order['qty1']			=	1;
			$order['alterprice1']	=	"-".$discount;
			# Add a negative BV if bv to be altered
			if($alterbv)
				$order['alterbv1']	=	"-".$finDiscount;
		}
		# Set as paid
		return $this->modifyinvoice($order);
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
						'itemcode' => (isset($match[2]))? $match[2] : false,
						'quantity' => (isset($match[1]))? $match[1] : false,
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