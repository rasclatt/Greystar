<?php
namespace Greystar\Reports;

class Subscription extends \Greystar\Reports
{
	const	INCEPTION	=	'2004-01-01';
	
	private	$arr;
	
	protected function sortASData($as)
	{
		$count	=	count($as);
		$avbv	=	0;
		
		foreach($as as $AS) {
			
			$dt	=	str_replace(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'],['01','02','03','04','05','06','07','08','09','10','11','12'],preg_replace('/([^-]+)-([^-]+)-([^-]+)/','$3-$2-$1',strtolower($AS['next_autoship'])));
			if(!isset($next[$dt]))
				$next[$dt]	=	1;
			else
				$next[$dt]	+=	1;
			
			$avbv	+=	$AS['autoship_bv'];
			
			if(!isset($bv[$AS['autoship_bv']]))
				$bv[$AS['autoship_bv']]	=	1;
			else
				$bv[$AS['autoship_bv']]	+=	1;
		}
		
		ksort($bv, SORT_NATURAL);
		ksort($next, SORT_NATURAL);
		
		$this->arr =	[
			'count' => $count,
			'bv_breakdown' => $bv,
			'average_bv' => round(($avbv/$count), 2, PHP_ROUND_HALF_UP),
			'next_run' => $next,
			'data' => $as
		];
		
		return $this;
	}
	
	public function getCount()
	{
		return (isset($this->arr['count']))? $this->arr['count'] : false;
	}
	
	public function getAvgBv()
	{
		return (isset($this->arr['average_bv']))? $this->arr['average_bv'] : false;
	}
	
	public function getDetails()
	{
		return (isset($this->arr['data']))? $this->arr['data'] : false;
	}
	
	public function getBvBreakdown()
	{
		return (isset($this->arr['bv_breakdown']))? $this->arr['bv_breakdown'] : false;
	}
	
	public function getAll()
	{
		return $this->arr;
	}
	
	public function getAllAutoships()
	{
		return $this->getAutoshipsByDate();
	}
	
	public function getAutoshipsByDate($start = false, $end = false)
	{
		$args	=	false;
		
		if(!empty($start))
			$args['startdate']	=	$start;
		
		if(!empty($end))
			$args['enddate']	=	$end;
		
		return $this->sortASData($this->getReport('autoshipdata',$args));
	}
	
	public function getAutoshipsWithProducts($start, $end, $filtered = false)
	{
		$thisObj	=	$this;
		return $this->getReport('autoshippendprod',['startdate' => $start,'enddate' => $end],false,function($response) use($filtered, $thisObj) {
			$csv	=	array_map(function($v){
				return str_getcsv($v,"\t");
			}, explode(PHP_EOL, $response));
			
						
			return $thisObj->buildCsvWithProducts($csv, 40, $filtered);
		});
	}
	
	public function getChangesHistory($date)
	{
		return $this->getReport('pastauto', ['startdate'=>$this->setDate($date),'enddate' => $date]);
	}
}