<?php
namespace Greystar;
/**
 *	@description	
 */
class Locale extends \Greystar\Reports
{
	private	$list;
	/**
	 *	@description	
	 */
	public	function getCountryList()
	{
		foreach($this->getCountries() as $cou) {
			$countries[$cou['country_code']]	=	$cou['country_name'];
		}
		
		return	$countries;
	}
	
	public	function getCountries()
	{
		if(empty($this->list))
			$this->list	=	$this->getReport('countrylisting');
		
		return $this->list;
	}
}