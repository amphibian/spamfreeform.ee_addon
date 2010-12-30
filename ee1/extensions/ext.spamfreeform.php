<?php  if(!defined('EXT')) { exit('Invalid file request'); }

class Spamfreeform
{
	var $settings				= array();
	var $name				= 'Spam-Freeform';
	var $version				= '1.0';
	var $description		= 'Runs Freeform submissions through the Akismet anti-spam service.';
	var $settings_exist	= 'y';
	var $docs_url			= 'http://github.com/amphibian/spamfreeform.ee_addon';


	function Spamfreeform($settings='')
	{
	    $this->settings = $settings;
	    $this->site = urlencode('http://'.$_SERVER['SERVER_NAME']);
	}

	
	function settings()
	{	    
		$settings = array();
		$settings['spamfreeform_api_key'] = '';			
		$settings['spamfreeform_is_spam'] = array('t', 'Your submission appears to be spam. Please adjust your submission and try again.', 'Your submission appears to be spam. Please adjust your submission and try again.');			
		return $settings;
	}
	
	
	function freeform_module_validate_end($errors)
	{	
		global $IN;
		$content = '';
		if($IN->GBL('spamfreeform_fields', 'POST') && $IN->GBL('spamfreeform_fields', 'POST') != '')
		{
			$fields = explode('|', $IN->GBL('spamfreeform_fields', 'POST'));
			foreach($fields as $field)
			{
				$content .= ($IN->GBL($field, 'POST')) ? $IN->GBL($field, 'POST').' ' : '';
			}
		}
		
		if($this->_verify_key() == TRUE && !empty($content))
		{
			$query = array(
				'blog' => $this->site,
				'comment_content' => $content,
				'referrer' => $_SERVER['HTTP_REFERER'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT'],
				'user_ip' => $IN->IP
			);
			
			if($IN->GBL('spamfreeform_name', 'POST') && $IN->GBL('spamfreeform_name', 'POST') != '')
			{
				$query['comment_author'] = $IN->GBL($IN->GBL('spamfreeform_name', 'POST'), 'POST');
			}
	
			if($IN->GBL('spamfreeform_email', 'POST') && $IN->GBL('spamfreeform_email', 'POST') != '')
			{
				$query['comment_author_email'] = $IN->GBL($IN->GBL('spamfreeform_email', 'POST'), 'POST');
			}			
			
			$response = $this->_request('http://'.trim($this->settings['spamfreeform_api_key']).'.rest.akismet.com/1.1/comment-check', $query);
			$response = explode("\r\n\r\n", $response, 2);
			if($response[0] == 'true')
			{
				$errors[] = $this->settings['spamfreeform_is_spam'];			
			}
		}
		
		return $errors;
	}
	
	
	function _verify_key()
	{
		if(isset($this->settings['spamfreeform_api_key']))
		{
			$query = array(
				'blog' => $this->site,
				'key' => trim($this->settings['spamfreeform_api_key'])
			);
			
			$response = $this->_request('http://rest.akismet.com/1.1/verify-key', $query);
			if(trim($response) == 'valid')
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	function _request($server, $query)
	{
			$args = '';
			foreach ($query as $key => $value)
			{
    			$args .= trim($key).'='.trim($value).'&';
			}
			$args = rtrim($args, '&'); 
			
			$ch = curl_init($server);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
			$response = curl_exec($ch);
			curl_close($ch);
			return $response;
	}
	
	
	function activate_extension()
	{
	    $hooks = array(
	    	'freeform_module_validate_end' => 'freeform_module_validate_end'
	    );
	    
	    foreach($hooks as $hook => $method)
	    {
		    global $DB;
		    $DB->query($DB->insert_string('exp_extensions',
		    	array(
					'extension_id' => '',
			        'class'        => ucfirst(get_class($this)),
			        'method'       => $method,
			        'hook'         => $hook,
			        'settings'     => '',
			        'priority'     => 99,
			        'version'      => $this->version,
			        'enabled'      => "y"
					)
				)
			);
	    }		
	}


	function update_extension($current='')
	{
	    global $DB;
	    if ($current == '' OR $current == $this->version)
	    {
	        return FALSE;
	    }
	    
		$DB->query("UPDATE exp_extensions 
	     	SET version = '". $DB->escape_str($this->version)."' 
	     	WHERE class = '".ucfirst(get_class($this))."'");
	}

	
	function disable_extension()
	{	    
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '".ucfirst(get_class($this))."'");
	}

}