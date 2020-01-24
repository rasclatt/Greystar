<?php
namespace Greystar;
/**
 *	@description	
 */
class Volume extends Model
{
	protected	$User,
				$threshold;
	/**
	 *	@description	
	 */
	public	function __construct(User $User, $threshold)
	{
		$this->User			=	$User;
		$this->threshold	=	$threshold;
	}
	/**
	 *	@description	This feature will not exist in most compensation plans.
	 */
	public	function setEnrollerVolumeAdjustments()
	{
		# Fetch the raw data list
		$data	=	$this->volchain([
			'distid' => $this->User->getDistId(),
			'fromdate' => date('Y-m-d'),
			'summary' => 'Y'
		]);
		# Fetch the user data
		$info		=	$this->User->getDistInfo();
		# Stop if no information available
		if(empty($info['user']['current_rank']))
			return false;
		# Set current rank
		$currRank	=	$info['user']['current_rank'];
		# Get the current rank of the user
		$current	=	Ranks::getVolumeByRank($currRank);
		# Set the filtered enrollment list, keep track of the unaltered data
		$base		=	
		$data		=	(!empty($data))? array_map(function($v) {
			return preg_replace('/[^\d\.]/','',$v);
		}, array_filter($data)) : false;
		# Stop if nothing exists
		if(empty($data))
			return false;
		# Fetch the next rank from current
		$nextRank	=	Ranks::getNextRank($currRank);
		# Set the max allowed from enrollment tree leg
		$max		=	($nextRank['volume'] * $this->threshold);
		# Loop contributors and set the threshold
		$data		=	array_map(function($v) use ($max){
			return ($v > $max)? $max : $v;
		}, $data);
		# Sum the final values
		$sum		=	array_sum($data);
		# Report the overview
		$this->data	=	[
			'enrollment_tree_leg_max' => $max,
			'base_list' => $base,
			'base_volume' => array_sum($base),
			'adjusted_list' => array_diff_assoc($data, $base),
			'adjusted_volume' => $sum,
			'percent_to_next' => $perc = round((($sum / $nextRank['volume']) * 100), 2),
			'percent_left' => (100 - $perc),
			'current_rank' => Ranks::getRank($info['user']['current_rank']),
			'next_rank' => $nextRank,
		];
		return $this;
	}
	/**
	 *	@description	This feature will not exist in most compensation plans.
	 */
	public	function getEnrollerVolume()
	{
		return (isset($this->data['adjusted_volume']))? $this->data['adjusted_volume'] : 0;
	}
	/**
	 *	@description	
	 */
	public	function getData()
	{
		return $this->data;
	}
}