<?php
namespace Greystar\Order;
/**
 *	@description	
 */
class Controller extends \Greystar\Order\Model
{
	/**
	 *	@description	
	 */
	public	function getThisMonth($username = false)
	{
		$data	=	[
			'from'=>date('Y-m-d', strtotime('first day of this month')),
			'to'=>date('Y-m-d', strtotime('today'))
		];
		
		if(!empty($username))
			$data['username']	=	$username;
		
		return $this->getInvoice($data);
	}
}