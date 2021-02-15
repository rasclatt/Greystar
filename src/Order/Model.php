<?php
namespace Greystar\Order;

class Model extends \Greystar\Model
{
	private	$distid;
	private	static	$order;
	
	public function __construct($distid = false)
	{
		$this->distid	=	$distid;
	}
	/**
	 *	@description	Creates an invoice
	 */
	public function create(array $input)
	{
		# Scrubs shipping and billing keys of "ing" incase they are set as such
		$this->cleanKeys($input);
		# Set the raw form data
		self::$order['raw']			=	$input;
		# Create and set response
		self::$order['response']	=	$this->invoice($input);
		# Send back for chaining
		return $this;
	}
	/**
	 *	@description	Scrubs shipping and billing keys of "ing" incase they are set as such
	 */
	public function cleanKeys(&$array)
	{
		if(!is_array($array))
			return $array;
		
		$new	=	[];
		foreach($array as $key => $value) {
			
			if(stripos($key, 'billing') !== false) {
				$new[str_replace('billing', 'bill', $key)]	=	$value;
			}
			elseif(stripos($key, 'shipping') !== false) {
				$new[str_replace('shipping', 'ship', $key)]	=	$value;
			}
			else
				$new[$key]	=	$value;
		}
		$array	=	$new;
		return $this;
	}
	/**
	 *	@description	Returns values from an array if set
	 */
	public function getArrayValue($array, $key, $default = false)
	{
		if(!is_array($key))
			$key	=	[$key];
		
		foreach($key as $k) {
			if(isset($array[$k]))
				return $array[$k];
		}
		
		return $default;
	}
	/**
	 *	@description	
	 */
	public function createWithAutoShip(array $array, array $products, $discount = 0, $shipping = 0)
	{
		$settings	=	[
			'amount' => $this->getArrayValue($array, 'amount'),
			'billaddress1' => $this->getArrayValue($array, ['billaddress1', 'ccaddr']),
			'billaddress2' => $this->getArrayValue($array, ['billaddress2','ccaddr2']),
			'billcity' => $this->getArrayValue($array, ['billcity','cccity']),
			'billcountry' => $this->getArrayValue($array, ['billcountry','cccountry']),
			'billstate' => $this->getArrayValue($array, ['billstate','ccstate']),
			'billzip' => $this->getArrayValue($array, ['billzip','cczip']),
			'billphone' => $this->getArrayValue($array, ['billphone','ccphone']),
			'ccaddr' => $this->getArrayValue($array, 'ccaddr'),
			'ccaddr2' => $this->getArrayValue($array, 'ccaddr2'),
			'cccity' => $this->getArrayValue($array, 'cccity'),
			'cccountry' => $this->getArrayValue($array, 'cccountry'),
			'ccexpmo' => $this->getArrayValue($array, 'ccexpmo'),
			'ccexpyr' => $this->getArrayValue($array, 'ccexpyr'),
			'ccexp' => $this->getArrayValue($array, 'ccexpmo').$this->getArrayValue($array, 'ccexpyr'),
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
		$asorder	=	$settings;
		$products	=	array_values($products);
		$a	=	1;
		foreach($products as $key => $product) {
			if($a == 1) {		
 				$asorder['autoshipproduct'.$a]	=	$product['itemcode'];		
 				$asorder['autoshipq'.$a]		=	(!empty($product['qty']))? $product['qty'] : $product['quantity'];		
 			}
			$settings['product'.$a]	=	$product['itemcode'];
			$settings['qty'.$a]		=
			/*$settings['quantity'.$a]	=	*/(!empty($product['qty']))? $product['qty'] : $product['quantity'];
			
			$a++;
		}
		self::$order['raw']		=	$settings;
		
		# Create Autoship
		$ascreate			=	$this->doService(['autoshipcreditcard','createautoship'], $asorder);
		$settings['inv']	=	$array['inv'];
		# Create the invoice order
		self::$order['response']	=	
		$charge	=	$this->doService(['creditcardcharge'], $settings);
		# Stop if fail
		if(!empty($charge['error']) || (!empty($charge['result']) && stripos($charge['result'], 'fail') !== false)) {
			$this->doService('deleteautoship', ['distid' => $this->userGet('distid')]);
			$this->toError((!empty($charge['error']))? $charge['error'] : $charge['result']);
			return false;
		}
		else {
			$settings['invoice']	=	$settings['inv'];
			# Set the next run date for the autoship
			$this->autoshipdatechange([
				'nextcharge'=> date('Y-m-d', strtotime("next Friday + 4 weeks")),
				'distid' => $settings['distid']
			]);
			# Mark as paid and and add the discount
			$this->markPaidWithDiscount($settings['distid'], $settings['inv'], $discount);
			# Store
			$this->toSuccess("Transaction Successful–Thank you for your order!");
			foreach($settings as $key => $value) {
				$success[strtolower(str_replace([' '],['_'], $key))]	=	ltrim($value, '$');
			}
			return $success;
		}
	}
	/**
	 *	@description	Marks an invoice as paid and adds a discount item if set
	 */
	public function markPaidWithDiscountedItems($distid, $invoice, $discount)
	{
		$rawOrder	=	$this->getInvoice(['inv' => $invoice, 'username' => $distid]);
		
		# Set the final paramerters for marking paid
		$order	=	[
			'username' => $distid,
			'inv' => $invoice,
			'paid' => 'Y'
		];
		
		$prod	=	[];
		foreach($rawOrder['products'] as $products) {
			
		}
		# Add the discount to the order
		if($discount > 0) {
			$order['product1']		=	'justbv';
			$order['qty1']			=	1;
			$order['alterprice1']	=	"-".$discount;
			# Add a negative BV if bv to be altered
			if($alterbv)
				$order['alterbv1']	=	"-".$discount;
		}
		//if($distid == 275025) {
		//	die(printpre($order,['backtrace'=>false]));
		//}
		# Set as paid
		return $this->modifyinvoice($order);
	}
	/**
	 *	@description	Marks an invoice as paid and adds a discount item if set
	 */
	public function markPaidWithDiscount($distid, $invoice, $discount, $alterbv = true)
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
				$order['alterbv1']	=	"-".$discount;
		}
		
		//if($distid == 275025) {
		//	die(printpre($order,['backtrace'=>false]));
		//}
		
		# Set as paid
		return $this->modifyinvoice($order);
	}
	
	public function markInvoicePaid($inv, $paid = true, $type = false)
	{
		$invoice			=	$this->getInvoice($inv, $type);
		$invoice['paid']	=	$paid;
		return $this->modify($invoice);
	}
	
	public function update(array $input)
	{
		# Sample method
		$sdata	=	$this->modifyinvoice($input);
	}
	
	public function getInvoice(...$args)
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
		
		if(empty($array['username'])) { echo printpre();
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
	
	public function getOrderDetails($type = 'response')
	{
		if(!empty($type))
			return (isset(self::$order[$type]))? self::$order[$type] : false;
		
		return self::$order;
	}
	/**
	 *	@description	
	 */
	public function hasPurchased($sku)
	{
		$data	=	$this->hasproduct(['distid' => $this->distid, 'product' => $sku]);
		return key($data) == 'yes';
	}
}