<?php
namespace Greystar;
/**
 *	@description	
 */
class Commission extends Model
{
	protected	$User;
	/**
	 *	@description	
	 */
	public function __construct(User $User)
	{
		$this->User	=	$User;
	}
	/**
	 *	@description	
	 */
	public function getDownlineSales()
	{
		return $this->commissioninfo([
			'username' => $this->User->getDistid()
		]);
	}
}