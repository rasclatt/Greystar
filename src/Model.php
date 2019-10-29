<?php
namespace Greystar;

class Model extends \Nubersoft\nApp
{
	private	$statement,
			$endpoint,
			$settings;
	private	static	$args;
	
	protected	static	$error	=	[];
	/**
	 *	@description	Creates the endpoint
	 *	@param	$endpoint	[string|empty]	Set using string, default will call a define()
	 */
	public	function setEndpoint($endpoint = false)
	{
		$this->endpoint	=	(!empty($endpoint))? $endpoint : GS_ENDPOINT;
		
		return $this;
	}
	/**
	 *	@description	Creates initial credentials array
	 *	@param	$apikey	[string|empty]	Set using string, default will call a define()
	 *	@param	$fromapi	[string|empty]	Set using string, default will call a define()
	 *	@param	$fromuser	[string|empty]	Set using string, default will call a define()
	 */
	public	function init($apikey = false, $fromapi = false, $fromuser = false)
	{
		$this->settings	=	[
			'appkey'=> (!empty($apikey))? $apikey : GS_APIKEY,
			'fromapi'=> (!empty($fromapi))? $fromapi : GS_FROM_API,
			'fromapiuser'=> (!empty($fromuser))? $fromuser : GS_FROM_API_USER
		];
		
		return $this;
	}
	/**
	 *	@description	Sets up an endpoint based on service and parameters
	 *	@param	$service	[array|string]	This is the action name of the service
	 *	@param	$params	[array|empty]	These are any key/values that need to be sent
	 *	@param	$func	[callable|empty]	This can be a function that will process response
	 */
	protected	function createConnection($service, $params=false, $func = false)
	{
		# Set the action
		$this->settings['action']	=	$service;
		# Combine the api credentials with any attributes to send
		if(!empty($params))
			$this->settings	=	array_merge($this->settings,$params);
		# If no format is stated, make json default
		if(empty($this->settings['format']))
			$this->settings['format']	=	'json';
		# Build the query
		$query	=	http_build_query($this->settings);
		# Make sure the endpoint is set
		if(empty($this->endpoint))
			$this->setEndpoint();
		# Set the endpoint url
		$this->statement	=	$this->endpoint.'?'.$query;
		# Chain
		return $this;
	}
	/**
	 *	@description	
	 */
	public	function getStatement()
	{
		return $this->statement;
	}
	/**
	 *	@description	Sends the query to Greystar
	 *	@param	$query	[string|false]	If populated, will use this as the string to fetch content from GS
	 *	@returns	Data respomse from GS
	 */
	protected	function sendQuery($query = false)
	{
		# Allow for input directly to query
		if(!empty($query))
			$this->statement	=	$query;
		# Send for content
		$data	=	@file_get_contents($this->statement);
		
		if($data === false)
			trigger_error("An error occurred. If error persists, please contact customer support.");
		
		return $data;
	}
	/**
	 *	@description	Return all the query attributes for the current call
	 */
	public	function getAttrbutes($key = false)
	{
		if(!empty($key))
			return (isset($this->settings[$key]))? $this->settings[$key] : false;
		
		return $this->settings;
	}
	/**
	 *	@description	Main method to call and process back data from Greystar
	 *	@param	$service	[string|array]	The action name(s)
	 *	@param	$params		[array|empty]	Send any parameters that the call requires
	 *	@param	$func	[callable|empty]	Allow the data back to be processed with a custom function
	 */
	public	function doService($service, $params = false, $func = false, $start = true)
	{
		# Set the initial credentials
		# If set to false, it is assumed the init is being set before this service is called
		if($start)
			$this->init();
		# Create string from array for a multi-action service
		if(is_array($service))
			$service	=	implode(',', $service);
		self::$args	=	$params;
		# Fetch the contents from GS
		$contents	=	$this->createConnection($service, $params, $func)->sendQuery();
		# Stop if nothing is returned
		if(empty($contents))
			return false;
		# Fetch the settings to parse
		$base	=	$this->getAttrbutes();
		# Parse the kind of data
		if(isset($base['format'])) {
			switch($base['format']) {
				case('json'):
					# Parse json
					$data	=	json_decode($contents, true);
					# Send back the data, use callable if one set
					if(!isset($data['Error']))
						return (is_callable($func))? $func($data) : $data;
					else {
						# Save the error
						self::$error[$base['action']]	=	$data['Error'];
						# Return fail
						return false;
					}
				case('csv'):
					# Try to parse a <table> (not the best way to get data from GS)
					if(empty($params['textonly'])) {
						$arr	=	[];
						$parse	=	function($child){
							foreach ($child as $element) {
								$arr[]	=	trim($element->nodeValue);
							}
							return $arr;
						};
						$DOM	=	new \DOMDocument();
						$DOM->loadHTML($contents);
						$rows	=	$DOM->getElementsByTagName('tr');
						foreach ($rows as $node) {
							$arr[]	=	 $parse($node->childNodes);
						}

						if(count($arr) > 0) {
							$cols	=	array_map(function($v){
								return str_replace(' ','_',strtolower($v));
							}, $arr[0]);
							unset($arr[0]);
							$arr	=	array_map(function($v) use ($cols){
								$v	=	array_map(function($v){
									$v	=	trim($v);
									if($v == 'Â ')
										return '';

									return $v;
								}, $v);

								$v =	array_combine($cols, $v);
								if(isset($v['']))
									unset($v['']);

								return $v;

							}, $arr);
						}

						if(is_array($arr))
							$array	=	array_values($arr);
					
						return (is_callable($func))? $func($arr) : $arr;
					}
				default:
					# Send back just the raw response, use callable 
					return (is_callable($func))? $func($contents) : $contents;
			}
		}
		# Send back just the raw response, use callable 
		return (is_callable($func))? $func($contents) : $contents;
	}
	/**
	 *	@description	Checks if errors came from the API response
	 *	@param	$type	Allow ability to target specific error returns
	 */
	public	static	function hasErrors($type=false)
	{
		if(!empty($type)) {
			$type	=	strtolower($type);
			return (!empty(self::$error[$type]));
		}
		
		return (!empty(self::$error));
	}
	/**
	 *	@description	Fetches errors from the API response
	 *	@param	$type	Allow ability to target specific error returns
	 */
	public	static	function getErrors($type=false)
	{
		if(!empty($type)) {
			$type	=	strtolower($type);
			return (isset(self::$error[$type]))? self::$error[$type] : false;
		}
		
		return self::$error;
	}
	/**
	 *	@description	Dynamically call single action services
	 */
	public	function __call($name, $args = false)
	{
		$params	=	false;
		if(!empty($args))
			$params	=	(!is_array($args[0]))? [$args[0]] : $args[0];
		
		$data	=	$this->doService(strtolower($name),$params,((isset($args[1]) && is_callable($args[1]))? $args[1] : false));
		$data 	=	(is_array($data))? array_change_key_case($data,CASE_LOWER) : $data;
		
		if(!is_array($data))
			return $data;
		
		$data	=	ArrayWorks::recursive($data,function($key,$value){
			return str_replace(' ','_',$key);
		});
		
		ksort($data);
		
		return $data;
	}
	/**
	 *	@description	Dynamically call single action services staticly
	 */
	public	static	function __callStatic($name, $args=false)
	{
		$params	=	false;
		if(!empty($args))
			$params	=	(!is_array($args[0]))? [$args[0]] : $args[0];
		
		$data	=	(new Model())->doService(strtolower($name),$params,((isset($args[1]) && is_callable($args[1]))? $args[1] : false));
		$data	=	(is_array($data))? array_change_key_case($data,CASE_LOWER) : $data;
		
		if(!is_array($data))
			return $data;
		
		$data	=	ArrayWorks::recursive($data,function($key, $value){
			return str_replace(' ','_',$key);
		});
		
		ksort($data);
		
		return $data;
	}
	/**
	 *	@description	
	 */
	public	static	function getCallArgs()
	{
		return self::$args;
	}
}