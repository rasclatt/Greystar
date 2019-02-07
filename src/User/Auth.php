<?php
namespace Greystar\User;

use \Rumor\Exception as Rumor;

class Auth extends \Greystar\User
{
	/**
	*	@description	Accesses the "verifylogin" action
	*/
	public	function validate($username, $password)
	{
		return $this->verifylogin([
			'distid'=>$username,
			'pass'=>$password
		]);
	}
	
	public	function emailToUser($username)
	{
		# Strip out empties
		$username	=	trim($username);
		# If not long enough throw error
		if(strlen($username) < 3)
			throw new Rumor('Username can not be less than 3 characters.',2001);
		
		$nums	=	[
			'z',#zero
			'n',#one
			'w',#etc...
			'h',
			'f',
			'v',
			'x',
			'n',
			'e',
			'i',
			'_'=>'',
			'.'=>'',
			'@'=>'a'
		];
		
		return substr(str_replace(array_keys($nums),$nums,$username),0,30);
	}
}