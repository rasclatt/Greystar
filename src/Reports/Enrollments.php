<?php
namespace Greystar\Reports;
/**
 *	@description	
 */
class Enrollments extends \Greystar\Reports
{
	private	$startdate,
			$enddate;
	/**
	 *	@description	This will fetch today's date only
	 */
	public	function getNew($date)
	{
		$this->startdate	=	
		$this->enddate		=	$date;
		
		$thisObj	=	$this;
		# Use this report so that it has a record of the sign up purchasing something
		$report		=	$this->getReport('newusersfirstorder',[
			'startdate' => $date,
			'enddate' => $date
		],
		false,
		function() use($thisObj) {
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
	/**
	 *	@description	This will fetch a date range, default being first of the month to today
	 */
	public	function get($func = false, $start = false, $end = false)
	{
		$this->startdate	=	(!empty($start))? $start : date('Y-m').'-01';
		$this->enddate		=	(!empty($end))? $end : date('Y-m-d');
		
		$report		=	$this->getReport('newusersfirstorder',[
			'startdate' => $this->startdate,
			'enddate' => $this->enddate
		],false, function() {
			$args	=	func_get_args();
			if(empty($args[0]))
				return [];

			$csv	=	array_map(function($v){
				return str_getcsv($v, "\t");
			}, explode(PHP_EOL, $args[0]));

			return $csv;
		});
		
		$new	=	[];
		$head	=	array_map(function($v){ return str_replace([' ','(',')'],['_','',''],strtolower($v));}, $report[0]);
		unset($report[0]);
		$c		=	count($head);
		foreach($report as $row) {
			$rowArr	=	array_slice($row, 0, $c);
			
			if(count($rowArr) != count($head))
				continue;
			
			$arr	=	array_combine($head, $rowArr);
			$arr['name']			=	$arr['first'].' '.$arr['last'];
			$arr['enroller_name']	=	$arr['enroller_first'].' '.$arr['enroller_last'];
			$arr['enroller']		=	$arr['enroller_referrer'];
			unset($arr['enroller_referrer']);
			unset($arr['first_products_product_code,_description,_amount_->']);
			ksort($arr);
			$new[]	=	$arr;
		}

		return (is_callable($func))? $func(array_filter($new), $this) : array_filter($new);
	}
	/**
	 *	@description	
	 */
	public	function getDateRange()
	{
		return [
			'start' => $this->startdate,
			'end' => $this->enddate
		];
	}
}