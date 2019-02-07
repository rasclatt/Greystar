<?php
namespace Greystar\Reports;
/**
 *	@description	
 */
class Enrollments extends \Greystar\Reports
{
	/**
	 *	@description	
	 */
	public	function getNew($date)
	{
		$thisObj	=	$this;
		$report		=	$this->getReport('newusersfirstorder',['startdate' => $date,'enddate' => $date],false, function() use($thisObj) {
			$args	=	func_get_args();
			if(empty($args[0]))
				return [];

			$csv	=	array_map(function($v){
				return str_getcsv($v, "\t");
			}, explode(PHP_EOL, $args[0]));
			
			return $thisObj->buildCsvWithProducts($csv, 36);
		});
		
		return \Nubersoft\ArrayWorks::organizeByKey($report, 'first_invoice');
	}
}