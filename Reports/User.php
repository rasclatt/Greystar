<?php
namespace Greystar\Reports;

class User extends \Greystar\Reports
{
	public	static	$types	=	[
						'all',
						'inactive',
						'promoter',
						'active_promoter',
						'qualified_promoter',
						'promoter_500',
						'promoter_1k',
						'executive',
						'senior_executive',
						'managing_executive',
						'director',
						'regional_director',
						'national_director',
						'global_director',
						'global_diamond',
						'global_presidential',
						'global_ambassador'
					];
	
	public	function getTopEarners()
	{
		$args	=	func_get_args();
		$args	=	(!empty($args[0]))? $args[0] : false;
		$html	=	$this->getDataTable('topearner',$args);
		# Create a dom object
		$tr		=	$this->getTableDom($html);
		# Create an inconsistancy container
		$anom	=	[];
		# Process the data. On rows that are missing a value, it's likely enroller, so add that row manually
		$rows	=	$this->processDomTable($tr, $anom, function($row,$header,$i,&$anom){		
			$anom[]		=	$i;
			$new		=	$row;
			$row		=	[];
			$row[0]	=	$new[0];
			$row[1]	=	$new[1];
			$row[2]	=	$new[2];
			$row[3]	=	'';
			$row[4]	=	$new[3];
			$row	=	array_combine($header,$row);
			
			return $row;
		});
		
		return [
			'data' => $rows,
			'alert' => $anom
		];
	}
	/**
	*	@description	Get the top earner(s)
	*	@param	$limit	[numeric]	This is how many will return
	*	@returns	If a value greater 1, returns a numeric array, other wise associative of top earner
	*/
	public	function getTopEarner($limit = 1)
	{
		if(empty($limit) || !is_numeric($limit))
			$limit	=	1;
		
		$data	=	$this->getTopEarners(['ilimit'=>$limit])['data'];
		
		return ($limit > 1)? $data : $data[0];
	}
	
	public	function getTopEarnerByRank($rank = 2, $limit = 1)
	{
		if(!is_numeric($rank)) {
			$rank	=	array_search(str_replace(' ','_',strtolower($rank)),self::$types);
			if($rank === false)
				return false;
		}
		
		if(empty($limit) || !is_numeric($limit))
			$limit	=	1;
		
		$data	=	$this->getTopEarners([
			'ilimit'=>$limit,
			'mintitle' => $rank,
			'maxtitle' => $rank
		])['data'];
		
		return ($limit > 1)? $data : $data[0];
	}
	
	public	function getDistInfo($username)
	{
		return self::getuserdata(['distid' => $username,'returntype' => 'all']);
	}
}