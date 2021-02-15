<?php
namespace Greystar;
/**
 *	@description	
 */
class Transaction extends \Greystar\Order\Model
{
	public function chargeCard(array $input)
	{
		$response	=	$this->creditcardcharge($array);
		$success	=	(stripos($response['result'], 'success') !== false);
		
		if(!$success)
			self::$error	=	$response['result'];
		
		return $success;
	}
}