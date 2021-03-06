<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Spamfreeform_ext
{
	var $settings = array();
	var $site;
	var $version = '1.0';

	function __construct($settings='')
	{
	    $this->settings = $settings;
	    $this->site = urlencode('http://'.$_SERVER['SERVER_NAME']);
	}

	
	function settings()
	{	    
		$settings = array();
		$settings['spamfreeform_api_key'] = '';			
		$settings['spamfreeform_is_spam'] = array('t', '', 'Your submission appears to be spam. Please adjust your submission and try again.');			
		return $settings;
	}
	
	
	function freeform_module_validate_end($errors)
	{	
		$content = '';
		if(ee()->input->post('spamfreeform_fields') && ee()->input->post('spamfreeform_fields') != '')
		{
			$fields = explode('|', ee()->input->post('spamfreeform_fields'));
			foreach($fields as $field)
			{
				$content .= (ee()->input->post($field)) ? ee()->input->post($field).' ' : '';
			}
		}
		
		if($this->_verify_key() == TRUE && !empty($content))
		{
			$query = array(
				'blog' => $this->site,
				'comment_content' => $content,
				'referrer' => $_SERVER['HTTP_REFERER'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT'],
				'user_ip' => ee()->input->ip_address()
			);
			
			if(ee()->input->post('spamfreeform_name') && ee()->input->post('spamfreeform_name') != '')
			{
				$name = '';
				$fields = explode('|', ee()->input->post('spamfreeform_name'));
				foreach($fields as $field)
				{
					$name .= (ee()->input->post($field)) ? ee()->input->post($field).' ' : '';
				}
				$query['comment_author'] = $name;
			}			
	
			if(ee()->input->post('spamfreeform_email') && ee()->input->post('spamfreeform_email') != '')
			{
				$query['comment_author_email'] = ee()->input->post(ee()->input->post('spamfreeform_email'));
			}			
			
			$response = $this->_request('https://'.trim($this->settings['spamfreeform_api_key']).'.rest.akismet.com/1.1/comment-check', $query);
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
			
			$response = $this->_request('https://rest.akismet.com/1.1/verify-key', $query);
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
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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
		    ee()->db->query(ee()->db->insert_string('exp_extensions',
		    	array(
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
	    if ($current == '' OR $current == $this->version)
	    {
	        return FALSE;
	    }
	    
		ee()->db->query("UPDATE exp_extensions 
	     	SET version = '". ee()->db->escape_str($this->version)."' 
	     	WHERE class = '".ucfirst(get_class($this))."'");
	}

	
	function disable_extension()
	{	    
		ee()->db->query("DELETE FROM exp_extensions WHERE class = '".ucfirst(get_class($this))."'");
	}

}