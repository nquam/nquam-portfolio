<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Redactedapi {
	
	function Redactedapi() {
		
		/*
		if(strstr($_SERVER['HTTP_USER_AGENT'],'Android'))
            $this->site_id = 3;
        elseif(strstr($_SERVER['HTTP_USER_AGENT'],'iPhone'))
            $this->site_id = 4;
        elseif(strstr($_SERVER['HTTP_USER_AGENT'],'iPad'))
            $this->site_id = 5;
        else
            $this->site_id = 2;
        */
        
        if(!empty($_POST['site_id']) && is_numeric($_POST['site_id']))
        {
           $this->site_id = $_POST['site_id']; 
        }     
        else
        {
            $this->site_id = 1;
        }
            
        if($_SERVER['SERVER_NAME'] == 'www.redacted.com'){
            $this->username = 'xxxx';
            $this->password = 'xxxx';
            $this->url 		= 'https://api-1.redacted.com/redactedapiservice.asmx';
        } else{
            $this->username = 'xxxxx';
            $this->password = 'xxxxx';
            $this->url 	    = 'https://testapi-1.redacted.com/redactedAPIService.asmx?wsdl';
        } 
			
		$this->CI =& get_instance();
		$this->CI->load->helper('cookie');
	}

	function product_search($query_string = false, $lang = 'en_us')
	{
		//replace underscore with hyphen
		$lang = str_replace('_', '-', $lang);
		
		if (!empty($query_string))
		{
		
			$query = explode(' ', $query_string);
		
		}
		
		$content = '<?xml version="1.0" encoding="utf-8"?>
			<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
				<soap:Header>
					<ServiceAuthHeader xmlns="http://tempuri.org/">
						<UserName>' . $this->username . '</UserName>
						<Password>' . $this->password . '</Password>
					</ServiceAuthHeader>
				</soap:Header>
				<soap:Body>
					<product_search xmlns="http://tempuri.org/">';
		if (!empty($query)) {
			foreach ($query as $q) $content .= '<search_terms>' . $q . '</search_terms>';
		}
		$content .=	'<lang>'.$lang.'</lang></product_search>
				</soap:Body>
			</soap:Envelope>';
		
		$headers = array(  
			'POST /redactedapiservice.asmx HTTP/1.1',
			'Host: 000.00.000.185',
			'Content-Type: text/xml; charset=utf-8',
			'Content-Length: ' . strlen($content),
			'SOAPAction: "http://tempuri.org/product_search"',
		);
		
		return $this->_init_curl($content, $this->url, $headers);
		
	}
	
	function product_categories($lang = 'en_us')
	{
		
		//replace underscore with hyphen
		$lang = str_replace('_', '-', $lang);
		
		$content = '<?xml version="1.0" encoding="utf-8"?>
			<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
				<soap:Header>
					<ServiceAuthHeader xmlns="http://tempuri.org/">
						<UserName>' . $this->username . '</UserName>
						<Password>' . $this->password . '</Password>
					</ServiceAuthHeader>
				</soap:Header>
				<soap:Body>
					<product_categories xmlns="http://tempuri.org/">
						<lang>'.$lang.'</lang>
					</product_categories>
				</soap:Body>
			</soap:Envelope>';
		
		$headers = array(  
			'POST /redactedapiservice.asmx HTTP/1.1',
			'Host: 000.00.000.185',
			'Content-Type: text/xml; charset=utf-8',
			'Content-Length: ' . strlen($content),
			'SOAPAction: "http://tempuri.org/product_categories"',
		);
		
		return $this->_init_curl($content, $this->url, $headers);
	
	}
	
	function products($product_ids = array(), $return_all = false, $lang = 'en_us')
	{
		
		//replace underscore with hyphen
		$lang = str_replace('_', '-', $lang);
		
		$content = '<?xml version="1.0" encoding="utf-8"?>
		<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
		  <soap:Header>
		    <ServiceAuthHeader xmlns="http://tempuri.org/">
		      <UserName>' . $this->username . '</UserName>
			  <Password>' . $this->password . '</Password>
		    </ServiceAuthHeader>
		  </soap:Header>
		  <soap:Body>
		    <products xmlns="http://tempuri.org/">';
		   if ($return_all) {
		      $content .= '<return_all>true</return_all>
		      	<product_ids>
		      		<anyType xsi:type="xsd:int">123</anyType>
		      	</product_ids>';
		    } else {
		     $content .= '<return_all>false</return_all>
		      <product_ids>';
		      	if (!empty($product_ids)) {
		      	foreach ($product_ids as $id) $content .= '<anyType xsi:type="xsd:int">'.$id.'</anyType>';
		 	  }
			 $content .= '</product_ids>';
			}
		$content .= '<lang>'.$lang.'</lang></products>
		  </soap:Body>
		</soap:Envelope>';
		
		$headers = array(  
			'POST /redactedapiservice.asmx HTTP/1.1',
			'Host: 000.00.000.185',
			'Content-Type: text/xml; charset=utf-8',
			'Content-Length: ' . strlen($content),
			'SOAPAction: "http://tempuri.org/products"',
		);
		
		return $this->_init_curl($content, $this->url, $headers);
	
	}
	
	function products_by_category($category_ids = array(), $lang = 'en_us')
	{
		
		//replace underscore with hyphen
		$lang = str_replace('_', '-', $lang);
		
		$content = '<?xml version="1.0" encoding="utf-8"?>
		<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
		  <soap:Header>
		    <ServiceAuthHeader xmlns="http://tempuri.org/">
		      <UserName>' . $this->username . '</UserName>
			  <Password>' . $this->password . '</Password>
		    </ServiceAuthHeader>
		  </soap:Header>
		  <soap:Body>
		    <products_by_category xmlns="http://tempuri.org/">
		      <category_ids>';
		      
		      if (!empty($category_ids)) {
		      	foreach ($category_ids as $id) $content .= '<anyType xsi:type="xsd:int">'.$id.'</anyType>';
		 	  }

		$content .= '</category_ids>
			<lang>'.$lang.'</lang>
		    </products_by_category>
		  </soap:Body>
		</soap:Envelope>';
		
		$headers = array(  
			'POST /redactedapiservice.asmx HTTP/1.1',
			'Host: 000.00.000.185',
			'Content-Type: text/xml; charset=utf-8',
			'Content-Length: ' . strlen($content),
			'SOAPAction: "http://tempuri.org/products_by_category"',
		);
		
		return $this->_init_curl($content, $this->url, $headers);
	
	}
	
	function user_info($user_ids = array(), $lang = 'en_us', $session_id = '')
	{
		
		//replace underscore with hyphen
		$lang = str_replace('_', '-', $lang);
		
		$content = '<?xml version="1.0" encoding="utf-8"?>
		<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
		  <soap:Header>
		    <ServiceAuthHeader xmlns="http://tempuri.org/">
		      <UserName>' . $this->username . '</UserName>
			  <Password>' . $this->password . '</Password>
		    </ServiceAuthHeader>
		  </soap:Header>
		  <soap:Body>
		    <user_info xmlns="http://tempuri.org/">
		      <user_uuids>';
		      if (!empty($user_ids)) {
		      	foreach ($user_ids as $ui) $content .= '<string>' . $ui . '</string>';
		      }
		$content .= '      </user_uuids>
		      <site_id>' . $this->site_id . '</site_id>
		    </user_info>
		  </soap:Body>
		</soap:Envelope>';
		
		$headers = array(  
			'POST /redactedapiservice.asmx HTTP/1.1',
			'Host: 000.00.000.185',
			'Content-Type: text/xml; charset=utf-8',
			'Content-Length: ' . strlen($content),
			'SOAPAction: "http://tempuri.org/user_info"',
		);
		
		return $this->_init_curl($content, $this->url, $headers);
	
	}

	function _init_curl($content, $url, $headers, $header = 0)
	{

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, $header);
		curl_setopt($ch, CURLOPT_VERBOSE, '1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction: ""'));
		//curl_setopt($ch, CURLOPT_CAPATH, '/home/pps/');
		//curl_setopt($ch, CURLOPT_CAINFO,  '/home/pps/authority.pem');
		//curl_setopt($ch, CURLOPT_SSLCERT, 'PROTPLUSSOL_SSO.pem');
		//curl_setopt($ch, CURLOPT_SSLCERTPASSWD, 'xxxxxxxxxxxx');
		
		$output = curl_exec($ch);
		// Check if any error occured
		if(curl_errno($ch))
		{
		    echo 'Error no : '.curl_errno($ch).' Curl error: ' . curl_error($ch);
		}
    	
    	//print_r($output);
		$p = xml_parser_create();
		xml_parse_into_struct($p, $output, $vals, $index);
		xml_parser_free($p);
		if (!empty($vals[3]['value'])) {
			return ($vals[3]['value']);
		}
			
	}
	
	function check_user_status()
	{
	
		if(isset($_COOKIE['userinfo'])) {
            $userinfo = json_decode($_COOKIE['userinfo']);
            $remote_uuid = $userinfo->users[0]->uuid;
        }
		
		if(isset($_COOKIE['usercookie'])) {
			parse_str($_COOKIE['usercookie'],$userinfo);
			$local_uuid = $userinfo['uuid'];
			$this->site_id = $userinfo['siteid'];
		}
		
		//if both cookie uuid's match, validate login status with redacted
		if((!empty($local_uuid) && !empty($remote_uuid)) && $local_uuid == $remote_uuid){ 
            $userinfo = json_decode($this->user_info(array($local_uuid)));
            $logged_in = $userinfo->users[0]->is_logged_in;
        //otherwise check indiviual statuses
		}elseif(!empty($local_uuid)){
		  $userinfo = json_decode($this->user_info(array($local_uuid)));
		  $logged_in = ($userinfo->users[0]->is_logged_in);
		  if(ENVIRONMENT == 'test_external' && $userinfo->users[0]->uuid == '00000000-0000-0000-0000-000000000000'){
		  	setcookie('userinfo', TRUE, time()-3600, '/', '.redacted.com');
		  	setcookie('usercookie', TRUE, time()-3600, '/', '.redacted.com');
		  	echo '<script>alert("It looks like you just logged in on the test site but the cookies are not matching and your user ID cannot be found. Your cookie has been cleared, you will need to login again.");</script>';
		  }
		//if usercookie is missing, then log out entirely
		}elseif(!empty($remote_uuid)){
		  $this->delete_cookies();
		  $logged_in = FALSE;
		}
		
		//if neither are set or redacted claims they arent logged in, delete cookies
		if(!@$logged_in){
		  $this->delete_cookies();
		  return false;
		}else{
		  $this->set_cookies($userinfo);
		  return $userinfo->users[0];
		}
        
	}
	
	function delete_cookies(){
	    if(!empty($_COOKIE['userinfo']))
            $this->_delete_cookie('userinfo');
        /* DEPRECATED, and we aren't positive these aren't used on other redacted sites
        if(!empty($_COOKIE['user_id']))
            $this->_delete_cookie('user_id');
        if(!empty($_COOKIE['user_name']))
            $this->_delete_cookie('user_name');
        if(!empty($_COOKIE['is_logged_in']))
            $this->_delete_cookie('is_logged_in');  
            */ 
        if(!empty($_COOKIE['usercookie']))
            $this->_delete_cookie('usercookie');
    }
	
	function set_cookies($userinfo){
        $this->_set_cookie('userinfo', json_encode($userinfo));
        /* DEPRECATED
        $this->_set_cookie('user_id', $userinfo->users[0]->uuid);
		$this->_set_cookie('user_name', $userinfo->users[0]->user_name);
		$this->_set_cookie('is_logged_in', $userinfo->users[0]->is_logged_in);
		*/
    }
    
    function _delete_cookie($name){
        $cookie = array(
            'name'      => $name,
            'value'     => null,
            'expire'    => null,
            'domain'    => $this->get_base_domain(base_url())
        );
        delete_cookie($cookie);
    }

	function _set_cookie($name,$value = false)
	{
		
		if ($value)
		{
		
			delete_cookie($name);
			
			$cookie = array(
			
				'name'		=> $name,
				'value'		=> $value,
				'expire'    => '86500',
				'domain'    => $this->get_base_domain(base_url())
			
			);
			
			set_cookie($cookie); 
		
		}
		
	}
	
	function get_base_domain($url) 
    {
      $debug = 0;  
      $base_domain = '';
      
      // generic tlds (source: http://en.wikipedia.org/wiki/Generic_top-level_domain)
      $G_TLD = array(
        'biz','com','edu','gov','info','int','mil','name','net','org',
        'aero','asia','cat','coop','jobs','mobi','museum','pro','tel','travel',
        'arpa','root',
        'berlin','bzh','cym','gal','geo','kid','kids','lat','mail','nyc','post','sco','web','xxx',
        'nato',
        'example','invalid','localhost','test',
        'bitnet','csnet','ip','local','onion','uucp',
        'co'   // note: not technically, but used in things like co.uk
      );
      
      // country tlds (source: http://en.wikipedia.org/wiki/Country_code_top-level_domain)
      $C_TLD = array(
        // active
        'ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','ax','az',
        'ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bw','by','bz',
        'ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz',
        'de','dj','dk','dm','do','dz','ec','ee','eg','er','es','et','eu','fi','fj','fk','fm','fo',
        'fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw',
        'gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je',
        'jm','jo','jp','ke','kg','kh','ki','km','kn','kr','kw','ky','kz','la','lb','lc','li','lk',
        'lr','ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq',
        'mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np',
        'nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pn','pr','ps','pt','pw','py','qa',
        're','ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','sr','st',
        'sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tr','tt','tv','tw',
        'tz','ua','ug','uk','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yu',
        'za','zm','zw',
        // inactive
        'eh','kp','me','rs','um','bv','gb','pm','sj','so','yt','su','tp','bu','cs','dd','zr'
        );
      
      
      // get domain
      if ( !$full_domain = $this->get_url_domain($url) )
      {
        return $base_domain;
      }
      
      // now the fun
      
        // break up domain, reverse
        $DOMAIN = explode('.', $full_domain);
        if ( $debug ) print_r($DOMAIN);
        $DOMAIN = array_reverse($DOMAIN);
        if ( $debug ) print_r($DOMAIN);
        
        // first check for ip address
        if ( count($DOMAIN) == 4 && is_numeric($DOMAIN[0]) && is_numeric($DOMAIN[3]) )
        {
          return $full_domain;
        }
        
        // if only 2 domain parts, that must be our domain
        if ( count($DOMAIN) <= 2 ) return $full_domain;
        
        /* 
          finally, with 3+ domain parts: obviously D0 is tld 
          now, if D0 = ctld and D1 = gtld, we might have something like com.uk
          so, if D0 = ctld && D1 = gtld && D2 != 'www', domain = D2.D1.D0
          else if D0 = ctld && D1 = gtld && D2 == 'www', domain = D1.D0
          else domain = D1.D0
          these rules are simplified below 
        */
        if ( in_array($DOMAIN[0], $C_TLD) && in_array($DOMAIN[1], $G_TLD) && $DOMAIN[2] != 'www' )
        {
          $full_domain = $DOMAIN[2] . '.' . $DOMAIN[1] . '.' . $DOMAIN[0];
        }
        else
        {
          $full_domain = $DOMAIN[1] . '.' . $DOMAIN[0];;
        }
      
      // did we succeed?  
      return $full_domain;
    }
    
    function get_url_domain($url) 
    {
      $domain = '';
      
      $_URL = parse_url($url);
      
      // sanity check
      if ( empty($_URL) || empty($_URL['host']) )
      {
        $domain = '';
      }
      else
      {
        $domain = $_URL['host'];
      }
      
      return $domain;
    } 

}
