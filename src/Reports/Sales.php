<?php
namespace Greystar\Reports;

use \iTrack\Model as iTrack;

class Sales	extends \Greystar\Reports
{
	protected	$sales_totals	=	[];
	protected	$order_by		=	"";
	/**
	*	@description	Fetches orders and calculates summary data
	*	@param	$start	[string|null]	String to get the START date of the query
	*	@param	$end	[string|null]	String to get the END date of the query
	*/
	public function getSalesByItems()
	{
		$args	=	func_get_args();
		$start	=	(!empty($args[0]))? $args[0] : $this->setDate('today - 1 month');
		$end	=	(!empty($args[1]))? $args[1] : $this->setDate('now');
		$return	=	(!empty($args[2]))? $args[2] : false;
		# Just get the default CSV data, not the HTML version (was default)
		$txt	=	true;
		# Fetch the sales data (paid)
		$data	=	$this->get('salesitems',[
			'startdate' => $start,
			'enddate' => $end
		], false);
		
		# Set storage for total data
		$prodlist	=	[];
		# If the report is pulling the html version, pop totals off end
		$last		=	(!$txt)? array_pop($data) : false;
		# Use for anonymous function
		$thisObj	=	$this;
		# Sort the array by the invoice number
		$data		=	\Nubersoft\ArrayWorks::organizeByKey($data, 'invoice_num', ['multi'=>true]);
		# Loop each the invoices
		$data		=	array_map(function($v) use ($thisObj, $txt, &$prodlist) {
			# Set the default key based on the data type
			$prodkey			=	((!$txt)? 'product' : 'item_code');
			# Storage for reseting the data sort
			$new				=	[];
			# Make the entire array the items
			$new['items']		=	$v;
			# Count how many items there are
			$new['item_count']	=	count($new['items']);
			# Loop the invoice items
			foreach($new['items'] as $itmKey => $row) {
				# Loop the current item
				foreach($row as $key => $value) {
					# Put these keys into the main array (they are be duplicated in all invoice items)
					if(preg_match('/'.implode('|',[
						'username',
						'placement',
						'enroller',
						'date',
						'type',
						'first',
						'last',
						'email',
						'country',
						'state',
						'city',
						'address',
						'zip',
						'name',
						'log',
						'phone'
					]).'/i',$key)) {
						# Save to main array, don't overwrite if already set
						if(!isset($new[$key]))
							$new[$key]	=	$value;
						# Remove the key from this item
						unset($new['items'][$itmKey][$key]);
					}
					else {
						# If the BV is not set (used for the HTML version of the parse)
						if($key == 'bv' && !is_numeric($value))
							$new['items'][$itmKey][$key]	=	0;
						# Just assign the item value
						else
							$new['items'][$itmKey][$key]	=	$value;
					}
				}
				# Save instances into this data array
				$prodlist[$row[$prodkey]]['qty'][]			=	$row['count'];
				$prodlist[$row[$prodkey]]['amount'][]		=	$row['amount'];
				$prodlist[$row[$prodkey]]['bv'][]			=	$row['bv'];
				$prodlist[$row[$prodkey]]['shipping'][]		=	$row['shipping'];
				$prodlist[$row[$prodkey]]['sales_tax'][]	=	$row['sales_tax'];
				# Create summary array
				$prodlist['totals'][$row[$prodkey]]['qty']			=	array_sum($prodlist[$row[$prodkey]]['qty']);
				$prodlist['totals'][$row[$prodkey]]['amount']		=	array_sum($prodlist[$row[$prodkey]]['amount']);
				$prodlist['totals'][$row[$prodkey]]['bv']			=	array_sum($prodlist[$row[$prodkey]]['bv']);
				$prodlist['totals'][$row[$prodkey]]['shipping']		=	array_sum($prodlist[$row[$prodkey]]['shipping']);
				$prodlist['totals'][$row[$prodkey]]['sales_tax']	=	array_sum($prodlist[$row[$prodkey]]['sales_tax']);
				
				if(!isset($prodlist['totals'][$row[$prodkey]]['description']))
					$prodlist['totals'][$row[$prodkey]]['description']	=	$row['description'];
			}
			# Organize the items by their invoice number
			$new['items']	=	 \Nubersoft\ArrayWorks::organizeByKey($new['items'],$prodkey);
			# Sort so values are in order
			asort($new);
			# Send back for further processing
			return $new;
		},$data);
		
		$summary	=	($txt)? $prodlist['totals'] : @array_merge($this->sales_totals, @array_filter(array_map(function($v){
				return (empty(preg_replace('/[^0-9\.]+/','',$v)))? '' : $v;
			},$last)));
		
		if(!empty($prodlist))
			$prodlist	=	null;
		# Set final data
		$data	=	[
			'data' => $data,
			'rows' => count($data),
			'totals' => $summary,
			'dates' => [
				'from' => $start,
				'to' => $end
			]
		];
		# Process data with function or send back a key from data array
		if(!empty($return)) {
			# Send back key
			if(!is_callable($return)) {
				return (isset($data[$return]))? $data[$return] : false;
			}
			else {
				# Process with anon function, return results
				return $return($data);
			}
		}
		# Just return data
		return $data;
	}
	
	public function get()
	{
		$args		=	func_get_args();
		$def		=	(!empty($args[1]))? $args[1] : false;
		$func		=	(!empty($args[2]) && is_callable($args[2]))? $args[2] : false;
		$txt		=	(!isset($args[3]))? true : $args[3];
		$anon		=	[];
		$html		=	$this->getDataTable($args[0],$def);
		
		if($txt) {
			return	$this->processCsvTable(array_map(function($v){
				return str_getcsv($v,"\t");
			}, explode(PHP_EOL,$html)), $anon,$func);
		}
		else {
			preg_match('/total.*/i',$html,$match);

			if(!empty($match[0])) {
				$totaldata	=	array_filter(array_map(function($v){
					$data	=	trim(strip_tags($v));
					if(empty($data))
						return false;

					$data	=	array_values(array_map('trim',array_filter(explode(':',$data))));

					if(count($data) == 2)
						return [$data[0] => $data[1]];

					return false;

				},explode('<br>',strtolower($match[0]))));

				foreach($totaldata as $row) {
					$this->sales_totals	=	array_merge($this->sales_totals,[str_replace(' ','_',key($row)) => $row[key($row)]]);
				}
			}

			$DOM		=	$this->getTableDom($html);
			return $this->processDomTable($DOM, $anom, $func);
		}
	}
	
	public function remoteFileExists($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);
		$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// $retcode >= 400 -> not found, $retcode = 200, found.
		curl_close($ch);
		
		return $retcode;
	}
}