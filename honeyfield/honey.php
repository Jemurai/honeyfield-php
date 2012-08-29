<?php

/*
 General concept for usage:
	 
 Create events as appropriate and post them to HoneyField for review.
 
 		$event = new Event("Application Name", "http://whateverweknow.com/abc?123", "a = b \n b = c");
 		$honeyfield->fire_event($e1);

 See:  test-data.php for a simple usage example or make_honey.php for a script you can call from bash 
 			with parameters that get sent to HoneyField.
*/

/*
 An event is a simple object representing something that will be reported to HoneyField.
 The intended use is to create them as appropriate then send them off to the main 
 HoneyField instance.
*/
class Event{
	public $app;
	public $uri;
	public $post;
	public $remote_ip;
	public $api_key;
	
	public function __construct($app, $uri, $post, $remote_ip, $api_key){
		$this->app = $app;
		$this->uri = $uri;
		$this->post = preg_replace('/\s\s+/', ' ', $post);
		$this->remote_ip = $remote_ip;
		$this->api_key = $api_key;
	}
	
	public function to_json(){
$result = <<< EOE
{ 
	"event" : 
	{ 
		"application" : "$this->app", 
		"uri" : "$this->uri", 
		"remote_ip" : "$this->remote_ip",
		"api_key" : "$this->api_key",
		"post" : "$this->post"
	}
}
EOE;

	return $result;
	}
}

/*
 The connector is an attempt to abstract the piece that connects back to HoneyField.
 This version will just post the event to the HoneyField service.
*/
class Connector{
	private $config;
		
	public function __construct($config){
		$this->config = $config;
		if ($this->config->hf_debug_mode){
			echo "Connector with Config: $this->config\n";
		}
	}

	public function fire($event){
		if ($this->config->hf_debug_mode){
			echo "\nApp: ".$event->app;
			echo "\nURI: ".$event->uri;
			echo "\nConfig: ".$this->config;			
		}
		
		$body = $this->build_post_body($event);
		$headers = $this->build_post_headers();
		try{
//			$this->do_post_request($this->config->hf_host, $body, $headers);
			$this->do_post_curl($this->config->hf_host, $body, $headers);
			if ($this->config->hf_debug_mode){
				echo "\nPosted", $this->config->hf_host, " ", $body, " ", $headers;
			}			
		}catch(Exception $e){
			if ($this->config->hf_debug_mode){
				echo "Exception: ", $e->getMessage(), $e, "\n";
			}
		}
	}
	
	// Standard headers we need to send to tell the server we're sending json.
	protected function build_post_headers(){
		$headers = array(
			"Accept: application/json",
			"Content-Type: application/json"
		);
		return $headers;
	}
	
	// Get the body as JSON.
	protected function build_post_body($event){
		$body = $event->to_json();
		return $body;
	}
	
	// Use a curl library.  This works more reliably on older PHP but 
	// requires the library.  It does the same thing as the method 
	// below, but using that library.
	// 
	// Ideally, in the long run, one of these will prove to be more 
	// stable and the other can be eliminated.
	protected function do_post_curl($url, $data, $optional_headers){
		$data_string = $data;
		$ch = curl_init($url);                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
		curl_setopt($ch, CURLOPT_HTTPHEADER, $optional_headers);
		$result = curl_exec($ch);
	}
	
	
	// Credit to Wez Furlong - blog titled HTTP POST from PHP, without cURL
	// http://wezfurlong.org/blog/2006/nov/http-post-from-php-without-curl/
	//
	// Unfortunately, this doesn't work on some versions of php.  It seemed
	// to put junk in the post body.  It worked fine on my mac.  Its nice
	// because it doesn't require a third party library to be set up.
	protected function do_post_request($url, $data, $optional_headers = null) 
	{ 
		$params = array('http' => array( 
			'method' => 'POST',
			'content' => $data 
		)); 
		if ($optional_headers!== null) { 
			$params['http']['header'] = $optional_headers; 
		} 
		$ctx = stream_context_create($params); 
		$fp = @fopen($url, 'rb', false, $ctx); 
		if (!$fp) { 
			throw new Exception("Problem with $url, $php_errormsg"); 
		} 
		$response = @stream_get_contents($fp); 
		if ($response === false) { 
			throw new Exception("Problem reading data from $url, $php_errormsg"); 
		} 
		if ($this->config->hf_debug_mode){
			echo "Response: " . $response;
		}
		return $response; 
	}
}

/* 
   This is a base filter that just returns true.
   The idea is to make an extensible concept that can be used to throttle which 
   requests get sent to HoneyField.

   A Filter should return false when it wants the event to go to honeyfield.

*/
interface Filter{
	public function filter($event);
}

class SampleRateFilter implements Filter {	
	
	private $SAMPLE_SIZE = 100;
	private $config;
	public function __construct($config){
		$this->config = $config;
	}
	
	public function filter($event){
		$rate = $this->config->hf_sample_rate;
		if ($rate <= 0 || (! is_numeric($rate)) ){
			$rate = 0;
		} else if ($rate == null || $rate >= $this->SAMPLE_SIZE){
			$rate = $this->SAMPLE_SIZE;  # Basically, disable the filter for bad config.
		}
		
		$rand = rand(0,$this->SAMPLE_SIZE);
		if ($rand >= $rate){
			$filtered = true;   # N of 100 requests get sent.  This one is filtered.
		} else {
			$filtered = false;  # Not filtered.  Request should go to honeyfield.
		}
		if ($this->config->hf_debug_mode){
			$is_filtered = "Yes.";
			if (! $filtered){
				$is_filtered = "No.";
			}
			echo "\n\nFilter event? " . $is_filtered . " Reason: Is " . $rand . " greater than " . $rate . " ?";			
		}
		
	}
}

/* 
   The idea is to make an extensible trigger concept that will flag suspicious items
   i.e. requests to get sent to HoneyField.

   A trigger should return true when it wants the event to go to honeyfield.
   This is an extension point.
*/
interface Trigger{
	public function trigger($event);
}

/*
    This is a degenerate Trigger that basically flags requests as always on.
*/
class AlwaysOnTrigger implements Trigger{

	public function __construct(){
	}
	
	public function trigger($event){
		return true;
	}
}

/*
 The main Honey class is intended to present a single interface to interact with the 
 service.  It coordinates the connector, filters and configuration to achieve the desired
 behavior. 
*/
class Honey{

	private $config;
	private $connector;
	private $filters;
	private $triggers;
	
	public function __construct($config){
		$this->config = $config;
		$this->connector = new Connector($this->config);
		$this->filters = array(
			new SampleRateFilter($config)
		);
		$this->triggers = array(
			new AlwaysOnTrigger()
		);
	
		if ($this->config->hf_debug_mode){
			echo "Constructed Honey\n";
			echo "Config: $this->config\n";			
		}
	}
	
	public function fire_event($event){
		$should_fire = true;
		foreach($this->filters as $filter){
			$should_fire &= (! $filter->filter($event));  # Should fire if not filtered.
		}
		foreach($this->triggers as $trigger){
			$should_fire &= $trigger->trigger($event);    # Should fire if triggered.
		}
		if ($should_fire){
			$this->connector->fire($event);			
		}
	}
}

?>