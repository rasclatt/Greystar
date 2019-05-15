<?php
namespace Greystar;

class Reports extends \Greystar\Model
{	
	public	function getDataTable($report,$args=false)
	{
		$def	=	[
			'reportcode' => $report,
			'format' => 'csv',
			'textonly' => 'text'
		];

		if(!empty($args))
			$def	=	array_merge($def,$args);
		# Fetch the data (in html format apparently)
		$data	=	$this->doService('report', $def);

		return $data;
	}
	
	public	function getTableDom($html)
	{
		$DOM	=	new \DOMDocument('1.0', 'UTF-8');
		# Stop errors
		$internalErrors = libxml_use_internal_errors(true);
		$DOM->loadHTML($html);
		$tr	=	$DOM->getElementsByTagName('tr');
		// Restore error level
		libxml_use_internal_errors($internalErrors);

		return $tr;
	}
	
	protected	function processDomTable($tr, &$anom, $func = false)
	{
		$rows	=	[];
		if($tr->length > 0) {
			$i	=	0;
			foreach($tr as $element) {
				$nodes = $element->childNodes;
				foreach ($nodes as $node) {
					$value		=	trim($node->nodeValue);
					if(empty($value))
						continue;

					$rows[$i][]	=	$value;
				}

				if(isset($header)) {
					if(count($header) == count($rows[$i]))
						$rows[$i]	=	array_combine($header,$rows[$i]);
					else {
						if(is_callable($func)) {
							$rows[$i]	=	$func($rows[$i],$header,$i,$anom);
						}
						else {
							$anom[]	=	$rows[$i];
						}
					}
				}
				
				if($i==0) {
					$header	=	array_map(function($v){
						return str_replace([' ','#'],['_','num'],strtolower($v));
					},$rows[$i]);
				}
				
				$i++;
			}
		}
		
		if(!empty($rows[0])) {
			unset($rows[0]);
			$rows	=	array_values($rows);
		}
		
		return $rows;
	}
	
	protected	function processCsvTable($tr, &$anom, $func = false)
	{
		$rows	=	[];
		
		if(!is_array($tr))
			return $rows;
		
		if(count($tr) > 0) {
			$i	=	0;
			
			foreach($tr as $value) {
				$rows[$i]=	$value;
				
				if(isset($header)) {
					if(count($header) == count($rows[$i]))
						$rows[$i]	=	array_combine($header,$rows[$i]);
					else {
						if(is_callable($func)) {
							$rows[$i]	=	$func($rows[$i],$header,$i,$anom);
						}
						else {
							$anom[]	=	$rows[$i];
						}
					}
				}
				
				if($i==0) {
					$header	=	array_map(function($v){
						return str_replace([' ','#'],['_','num'],strtolower($v));
					},$rows[$i]);
				}
				
				$i++;
			}
		}
		
		if(!empty($rows[0])) {
			unset($rows[0]);
			$rows	=	array_values($rows);
		}
		
		return $rows;
	}
	
	public	function setDate($str,$mins = false)
	{
		return date('Y-m-d'.(($mins)? " H:i:s":""),strtotime($str));
	}
	
	public	function getReport($title, $args = false, $func = false, $override = false)
	{
		$anon	=	[];
		$html	=	$this->getDataTable($title, $args);
		
		if(empty($override)) {
			$rows	=	explode(PHP_EOL, $html);
			$rows	=	array_map(function($v){
					return str_getcsv($v,"\t");
				}, $rows);

			if(empty($rows))
				return (is_callable($func))? $func(false) : false;

			$keys	=	array_map(function($v){
				return trim(str_replace(' ','_',strtolower($v)));
			},$rows[0]);
		
			foreach($rows as $key => $value) {
				if($key == 0) {
					continue;
				}
				
				if(!isset($count)) {
					$count	=	count($keys);
					$cvals	=	count($value);
				}
				if($count > $cvals) {
					$ccount	=	($count-$cvals);
					for($i = 0; $i < $ccount; $i++) {
						$value[]	=	'';
					}
				}
				else {
					if(empty(array_filter($value)))
						continue;
				}
				
				if(count($keys) != count($value)) {
					//echo(printpre([$keys,$value]));
					continue;
				}
				
				$anon[]	=	array_combine($keys,$value);
			}

			return (is_callable($func))? $func(array_filter($anon)) : array_filter($anon);
		}
		else
			return $override($html);
	}
	
	public	function buildCsvWithProducts($csv, $cut, $filtered = false)
	{
		$header	=	array_map(function($v){
			return str_replace(' ', '_', strtolower($v));
		},array_slice($csv[0], 0, $cut));
		unset($csv[0]);

		foreach($csv as $row) {
			$core		=	array_slice($row, 0, $cut);
			$prod		=	array_diff($row, $core);

			if(count($core) != count($header))
				continue;

			$product	=	array_combine($header, array_map(function($v){
				return ltrim(ltrim($v, '\''),'$');
			},$core));
			$product['products']	=	array_map(function($v){
				return [
					'itemcode' => $v[0],
					'description' => $v[1],
					'qty' => $v[2]
				];
			},array_chunk($prod, 3));

			if(isset($product['id_number'])) {
				$product['distid']	=	$product['id_number'];
				unset($product['id_number']);
			}
			ksort($product);

			$item[]	=	($filtered)? array_filter($product) : $product;
		}

		return $item;
	}
}