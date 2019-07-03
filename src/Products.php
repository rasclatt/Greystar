<?php
namespace Greystar;

class Products extends \Greystar\Model
{
	public	function getProductList($args=false)
	{
		# Fetch the list from the API
		$data	=	$this->listofproducts($args, function($data){
			# Combine the header with rows
			return ArrayWorks::combineColumnRows($data);
		});
		# Stop if empty data
		if(empty($data))
			return $data;
		# Add some filtering fields to remove out the $ character
		$filter		=	['price','retail','our_cost','retail_on_autoship'];
		# Keys to lowercase the values
		$strlower	=	['hard_coded_tax','hard_coded_shipping'];
		# Loop through the main array
		foreach($data as $key => $item) {
			if(!is_array($item))
				continue;
			# Loop through each key/value and process each line if required
			foreach($item as $kVal => $value) {
				# Trim any white space
				$value	=	trim($value);
				# Remove any keys that are empty
				if(empty(trim($kVal)))
					unset($data[$key][$kVal]);
				else {
					# Switch simple values to bool
					switch($value) {
						case('Yes'):
							$value	=	true;
							break;
						case('No'):
							$value	=	false;
							break;
					}
					# Strip out keys
					$newKey	=	str_replace([' ','/','?'],['_','_or_',''],strtolower($kVal));
					# Check if they are required to be lowercase
					if(in_array($newKey,$strlower))
						$value	=	strtolower($value);
					# Remove the $ character to allow adding mathmatics
					$data[$key][$newKey]	=	(in_array($newKey,$filter))? str_replace('$','',$value) : $value;
					# Create item_code key from sku
					if($newKey == 'code')
						$data[$key]['item_code']	=	$data[$key][$newKey];
					# Remove old key now done
					unset($data[$key][$kVal]);
				}
			}
		}
		# Return data after filtered
		return $data;
	}
	
	public	function getProductsByCategory($category, $co)
	{
		$array	=	[
		  'category' => $category,
		  'country' => $co
		];
		$Products	=	$this->listofproducts($array);
		
		if(empty($Products))
			return [];
		
		$header		=	$Products['header'];
		
		ArrayWorks::convertValues($header);
		
		unset($Products['header'], $Products['row_count']);
		$new	=	[];
		foreach($Products as $row) {
			$arr	=	array_combine($header, $row);
			unset($arr['']);
			$new[$arr['code']]	=	$arr;
		}
		
		return $new;
	}
}