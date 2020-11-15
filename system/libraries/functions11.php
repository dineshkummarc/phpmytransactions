<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Functions {
	
	var $googleAPIKey = "AIzaSyBGyLsC9P94sx7I8hA3SSuzDSVqjPSM-4Q";

    /**
     * Constructor
     */
    function Functions() {
        $this->obj = & get_instance();
    }
	
	/*
	 *Push Notification for Android
	 *Driver get notification after place order
	 *Abhijit
	 */
	function pnsMessageAndroid($registatoin_ids, $message) {
		$key=$this->googleAPIKey;
        // Set POST variables
        $url = 'https://android.googleapis.com/gcm/send';
			$fields = array(
				'registration_ids' => explode(',',$registatoin_ids),
				'data' => $message,
			);
        $headers = array(
            'Authorization: key=' . $key,
            'Content-Type: application/json'
        );		
        // Open connection
        $ch = curl_init();
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Disabling SSL Certificate support temporarly
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));		
        // Execute post
        $result = curl_exec($ch);
        //echo ($result);exit;
        if ($result === FALSE) {
        	return 'failure';
        }
        // Close connection
        curl_close($ch);
        $r = json_decode($result);
		if($r->success >= 1){
			//return 'success';
			$result=array('message' => $message);
			return $result;
		}else{
			return 'failure';
			$result=array('message' => "");
		}
		//return $result;
    }
	
	
	//Apple Push Notification
	public function pnsMessageIOS($deviceToken,$message,$badge='',$identifier=2,$orderId,$userId,$type){		
		$title		=$message['title'];
		$title	   .= " ".$message['description'];
		$passphrase = 'rocket';		
		// Put your alert message here:
		$ctx 		= stream_context_create();
		$ab			= stream_context_set_option($ctx, 'ssl', 'local_cert',BASEPATH.'libraries/cert_key.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
		$pnsLink 	= $this->getGlobalInfo('apple_pns_link');
		// Open a connection to the APNS server		
		$fp 		= stream_socket_client('ssl://'.$pnsLink, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
		if (!$fp)
			return "Failed to connect: $err $errstr" . PHP_EOL;
		$return 	= 'Connected to APNS' . PHP_EOL;
		// Create the payload body		
		$body['aps'] = array(
						'alert' => $title,
						'badge' => 1,
						'content-available' => 1,
						'sound' => 'default',
						);
		$body['custom'] = array(
							'type' => $type,
							'userId' => $userId,
							'orderId' => $orderId,
							'sounds' => 'default',
							);
		if(isset($message['unread'])){
			$body['custom']['unread'] = $message['unread'];
		}
		// Encode the payload as JSON
		$payload 		= json_encode($body);
		$exp			= explode(',',$deviceToken);
		for($i=0;$i<count($exp);$i++){
			$deviceToken[] = $exp[$i];
		}
		foreach($deviceToken as $item) {
			// Build the binary notification
			$msg = chr(0).pack('n', 32).pack('H*',$item).pack('n',strlen($payload)).$payload;		
			// Send it to the server
			$result = fwrite($fp, $msg, strlen($msg));
			if (!$result)
				$return =  'Message not delivered' . PHP_EOL;
			else
				$return = $payload;			
			// Close the connection to the server
			fclose($fp);
		}		
		return $return;
	}
	
	public function pnsMessageGlobalIOS($deviceToken,$message,$badge='',$identifier=2,$orderId,$userId,$type){
		$title	= $message['title'];
		$title.= " ".$message['description'];
		$passphrase = 'rocket';		
		// Put your alert message here:
		$ctx = stream_context_create();	
		$ab  = stream_context_set_option($ctx, 'ssl', 'local_cert',BASEPATH.'libraries/cert_key.pem');		
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);		
		// Open a connection to the APNS server
			$body['aps'] = array(
							'alert' => $title,
							//'title' => $message['title'],
							'badge' => 1,
							'content-available' => 1,
							'sound' => 'default',
							);
							
		$body['custom']  = array(
							'type' => $type,
							'unread'=>$message['unread']!=''?$message['unread']:0,
							'userId' => $userId,
							'orderId' => $orderId,
							'sounds' => 'default',
							);
		$payload 		 = json_encode($body);
		$exp			 = explode(',',$deviceToken);
		$pnsLink 		 = $this->getGlobalInfo('apple_pns_link');
		for( $i=0; $i<count($exp); $i++ ){
			$fp  = stream_socket_client('ssl://'.$pnsLink, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);			
			if (!$fp)
			exit("Failed to connect: $err $errstr" . PHP_EOL);
			$return  = 'Connected to APNS' . PHP_EOL;
				// Build the binary notification
				$msg = chr(0) . pack('n', 32) . pack('H*', $exp[$i]) . pack('n', strlen($payload)) . $payload;				
				// Send it to the server
				$result = fwrite($fp, $msg, strlen($msg));
				if (!$result)
					$return =  'Message not delivered' . PHP_EOL;
				else
					$return = $payload;				
				// Close the connection to the server
				fclose($fp);
		}
		return $return;
	}
	
	/**
	 * Function used for send sms
	 * function sendSms($phone,$sms_body)
	 * @param $phone number
	 * @param $sms_body string
	 */
	 function sendSms($phone,$sms_body){    
        $url 	= 'http://alerts.solutionsinfini.com/api/v3/index.php';
		$fields = array(
					'method' => 'sms',
					'to' => $phone,
					'api_key' => 'Aea9a647ffb66d139975b3a0fad6253e0',
					'sender' => 'rocket',
					'message' => $sms_body,
					'format' => 'json'
				);

        $ch 	= curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
		curl_close($ch);// Close connection		
        $this->addToLog($phone,$sms_body);
	 }
	 
	 /**
	  * Function for save log
	  * @param int $to
	  * @param text $body
	  * @param enum $isSent
	  */
	 function addToLog( $to, $body, $isSent='Yes' ){
	 	$sql    = "INSERT INTO ".SMS_LOG." SET to_number='".$to."', sms_body='".$body."',sms_sent='".$isSent."'";	 	
	 	$result = $this->obj->db->query($sql);
	 }
	
    /**
     * Get Content for Page
     *
     * This function takes the page-name from the segment of url
     * and queries database table "contents"
     * Returns the content and other values such as page-title, meta-tags etc for the page in an array
     *
     * @access	public
     * @param	string
     * @return	array
     */
    function getContent($url) {
        $query 	   = "SELECT * FROM " . CONTENTS . " WHERE `url` = '" . $url . "' ";
        $recordSet = $this->obj->db->query($query);
        $rs 	   = false;
        if ($recordSet->num_rows() > 0) {
            $rs    = array();
            $isEscapeArr = array('content');
            foreach ($recordSet->result_array() as $row) {
                foreach ($row as $key => $val) {
                    if (!in_array($key, $isEscapeArr)) {
                        $recordSet->fields[$key] = outputEscapeString($val);
                    } else {
                        $recordSet->fields[$key] = outputEscapeString($val, '');
                    }
                }
                $rs = $recordSet->fields;
            }
        } else {
            $rs['title'] = GLOBAL_SITE_NAME;
            $rs['meta_title'] = GLOBAL_META_TITLE;
            $rs['meta_keywords'] = GLOBAL_META_KEYWORDS;
            $rs['meta_description'] = GLOBAL_META_DESCRIPTION;
            $rs['content'] = false;
        }
        return $rs;
    }
	
    /*
     * Function used For Fetching Data
     * param $id string passing id
     * param $table string passing db table name
     * param $query array passing db fields name
     * param $fetch_key string passing db matching field name
     * purpose to Fetch a Single data row
     */

    function getSingle($id, $table, $query = false, $fetch_key = '', $isEscapeArr = array()) {
        if (!empty($query) && is_array($query))
            $query_string = implode(",", $query);
        else
            $query_string = '*';


        if (!empty($fetch_key))
            $fetch_key = $fetch_key;
        else
            $fetch_key = 'id';

        $sql = "SELECT " . $query_string . " FROM " . $table . " WHERE $fetch_key = '" . $id."'";
        $recordSet = $this->obj->db->query($sql);

        $rs = false;
        if ($recordSet->num_rows() > 0) {
            $rs = array();
            foreach ($recordSet->result_array() as $row) {
                foreach ($row as $key => $val) {
                    if (!in_array($key, $isEscapeArr)) {
                        $recordSet->fields[$key] = outputEscapeString($val);
                    } else {
                        $recordSet->fields[$key] = outputEscapeString($val, 'TEXTAREA');
                    }
                }
                $rs[] = $recordSet->fields;
            }
        }
        return $rs;
    }
	
    /**
     * Checks user for page access
     *
     * This function takes the page-name and checks for user authenticity
     * Returns true if user is authentic
     * Redirects to user login page if user is not authentic
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    function checkUser($section_name) {
        $UserID = $this->obj->nsession->userdata('member_session_id');
        if (!$UserID) {
            $cookie = array(
						'name' => 'fe_referer_path',
						'value' => $section_name,
						'expire' => '86500',
						'domain' => '',
						'path' => '/',
						'prefix' => '',
					);
            set_cookie($cookie);
            redirect(base_url() . 'member/');
        }
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Checks user for page access
     *
     * This function takes the page-name and checks for user authenticity for admin section
     * Returns true if user is authentic
     * Redirects to user login page if user is not authentic
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    function checkAdmin($section_name) {
        $UserID = $this->obj->nsession->userdata('user_session_id');

        if (!$UserID) {
            $cookie = array(
						'name' => 'admin_referer_path',
						'value' => $section_name,
						'expire' => '86500',
						'domain' => '',
						'path' => '/',
						'prefix' => '',
					  );
            set_cookie($cookie);
            redirect(base_url() . 'login/');
            exit;
        }

        return true;
    }

    /**
     * Checks user for page access
     *
     * This function takes the page-name and checks for user authenticity for admin section
     * Returns true if user is authentic
     * Redirects to user login page if user is not authentic
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    function checkAuthAdmin($section_name) {
        $UserID = $this->obj->nsession->userdata('user_session_id');

        if (!$UserID) {
            $cookie = array(
                'name' => 'admin_referer_path',
                'value' => $section_name,
                'expire' => '86500',
                'domain' => '',
                'path' => '/',
                'prefix' => '',
            );
            set_cookie($cookie);
            redirect(base_url() . 'login/');
            exit;
        }

        return true;
    }
	/**
     * Checks user for page access
     *
     * This function takes the page-name and checks for user authenticity for corporate admin section
     * Returns true if user is authentic
     * Redirects to user login page if user is not authentic
     *
     * @access public
     * @param string
     * @return bool
     */
    function checkCorporateAdmin($section_name) {
     $UserID = $this->obj->nsession->userdata('corporate_user_session_id');
    
     if (!$UserID) {
      $cookie = array(
        'name' => 'corporate_admin_referer_path',
        'value' => $section_name,
        'expire' => '86500',
        'domain' => '',
        'path' => '/',
        'prefix' => '',
      );
      set_cookie($cookie);
      redirect(base_url() . 'login/');
      exit;
     }
     return true;
    }
    /**
     * Checks merchant for page access
     *
     * This function takes the page-name and checks for merchant authenticity for merchant section
     * Returns true if merchant is authentic
     * Redirects to merchant login page if merchant is not authentic
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    function checkAuthMerchant($section_name) {
        $merchantID = $this->obj->nsession->userdata('merchant_id');

        if (!$merchantID) {
            $cookie = array(
                'name' => 'admin_referer_path',
                'value' => $section_name,
                'expire' => '86500',
                'domain' => '',
                'path' => '/',
                'prefix' => '',
            );
            set_cookie($cookie);
            redirect(base_url() . 'login/');
            exit;
        }

        return true;
    }

    function checkLogin() {
        $UserID = $this->obj->nsession->userdata('member_session_id');
        if (!$UserID) {
            redirect(base_url() . 'member/index');
            exit();
        }
        return true;
    }

    /**
     * getNameTable
     *
     * This function USED TO FETCH A single value from database 
     * param $table is the table name
     * param $col is the column  name which you want to fetch
     * param $field is the condition column  name
     * param $value is the condition column  value 
     * @access	public
     * @return	string
     * param $param other condition
     */
    function getNameTable($table, $col, $field = '', $value = '', $param = '') {
        $query = "SELECT " . $col . " FROM " . $table . " where 1 ";
        if ($field != '' && $value != '') {
            $query.="AND " . $field . "='" . $value . "' ";
        }
        if ($param) {
            $whereclause = " AND ";
            $query .=$whereclause . $param;
        }
        $query;
        $recordSet = $this->obj->db->query($query);
        if ($recordSet->num_rows() > 0) {
            $row = $recordSet->row_array();
            return $row[$col];
        } else {
            return "";
        }
    }

    /**
     * existRecords
     *
     * This function USED TO check the record is exists or not
     * param $table is the table name
     * param $col is the column  name which you want to fetch
     * param $field_name is the condition column  name
     * param $field_value is the condition column  value 
     * @access	public
     * @return	string
     * param $pk is the checking column name
     * param $pk_value is the checking column value
     * param $param other condition
     */
    function existRecords($table, $field_name, $field_value, $pk, $pk_value = 0, $field_name1 = "", $field_value1 = "") {

        $query = "SELECT COUNT(" . $pk . ") as CNT FROM " . $table . " where " . $field_name . "=" . $this->obj->db->escape($field_value) . " ";

        if ($field_name1 != "" && $field_value1 != "") {
            $query.=" AND " . $field_name1 . "='" . $field_value1 . "' ";
        }
        if ($pk_value) {
            $query.=" AND " . $pk . "!='" . $pk_value . "'";
        }

        $recordSet = $this->obj->db->query($query);
        if ($recordSet->num_rows() > 0) {
            $row = $recordSet->row();
            return $row->CNT;
        } else {
            return "";
        }
    }

    /**
     * getListTable
     * This function USED TO fetch row details 
     * param $table_name is the table name
     * param $field is the condition column  name
     * param $value is the condition column  value 
     * @access	public
     * @return	array
     * param $orderfield is the order by field
     * param $ordertype is the order by type
     * 
     */
    function getListTable($table_name, $field = '', $value = '', $orderfield = '', $ordertype = 'ASC', $param = '') {
        $sql = "SELECT * FROM `" . $table_name . "` WHERE 1";
        if ($field != '' && $value != '') {
            $sql.=" AND `" . $field . "`='" . $value . "'";
        }
        if ($param) {
            $whereclause = " AND ";
            $sql .=$whereclause . $param;
        }
        if ($orderfield != '') {
            $sql.=" ORDER BY `" . $orderfield . "` " . $ordertype . "";
        }
        $recordSet = $this->obj->db->query($sql);

        $rs = false;
        if ($recordSet->num_rows() > 0) {
            $rs = array();
            $isEscapeArr = array();
            foreach ($recordSet->result_array() as $row) {
                foreach ($row as $key => $val) {
                    if (!in_array($key, $isEscapeArr)) {
                        $recordSet->fields[$key] = outputEscapeString($val);
                    }
                }
                $rs[] = $recordSet->fields;
            }
        } else {
            return false;
        }
        return $rs;
    }

    function emailOrderTemplate($body = '', $footer = '', $path = '') {
        $template = '<style>p{line-height:15px; font:Arial, Helvetica, sans-serif; font-size: 13px; } </style>
	    <table width="802px" border="0" cellpadding="0" cellspacing="0" style="border:#CCCCCC 1px solid; padding:0px; color:#000000; font:normal 12px Arial, Helvetica, sans-serif;">
	    <tr>
	    <td align="left" style="padding:20px;">##EMAIL_BODY##</td>
	    </tr>	    
	    </table>
	    ';
        $email_body = str_replace(array("##EMAIL_BODY##", "##EMAIL_FOOTER##", "##SITEPATH##"), array($body, $footer, $path), $template);
        return $email_body;
    }

    /**
     * emailTemplate
     * This function USED TO create email structure
     * param $body is the mail body
     * param $footer is the email footer
     * param $path is the base path 
     * 
     */
    function emailTemplate($body = '', $footer = '', $path = '') {
        $template = '<style>p{line-height:15px; font:Arial, Helvetica, sans-serif; font-size: 13px; } </style>
				    <table width="984px" border="0" cellpadding="0" cellspacing="0" style="border:#CCCCCC 1px solid; padding:0px; color:#000000; font:normal 12px Arial, Helvetica, sans-serif;">
				      <tr>
					    <td align="left" style="padding:20px;">##EMAIL_BODY##</td>
				      </tr>
				      <tr>
					    <td align="left" style="padding:20px;">##EMAIL_FOOTER##</td>
				      </tr>
				      <!--<tr>
					    <td height="90" align="left" class="block" style="padding-left:20px; margin-left:20px;">
						   <p><a href="##SITEPATH##" title="Logo" ><img src="##SITEPATH##uploads/logo_image/logo.png" width="282px"></a>
						    </p>
						    <br>';


        $template .='<div style="border-left: 0px solid #737B7D; color: #444F51; float: left; font: 14px Arial,Helvetica,sans-serif; padding: 4px 4px 4px 11px;"></div>
					    </td>
				      </tr>-->
				    </table>
				    ';
        $email_body = str_replace(array("##EMAIL_BODY##", "##EMAIL_FOOTER##", "##SITEPATH##"), array($body, $footer, $path), $template);

        return $email_body;
    }

    /**
     * send_email
     * This function USED TO send email 
     * param $to_email is the  email address of the receiver
     * param $search_text is the  assinged text in email template
     * param $replace_text is the  value which have tobe replaced with the assigned text
     * param $subject is the  subject of email
     * 
     */
    function send_email_pre($to_email, $template_code, $search_text = array(), $replace_text = array(), $subject = '', $name = '', $email = '', $cc = '', $bcc = '') {
        $sql = "SELECT from_name, from_email, subject, email_body FROM " . EMAIL_TEMPLATE_SETTING . " WHERE template_code = '" . $template_code . "'"; //echo $sql;

        $recordSet = $this->obj->db->query($sql);
        $rs = false;
        if ($recordSet->num_rows() > 0) {
            $rs = array();
            $isEscapeArr = array('email_body');
            foreach ($recordSet->result_array() as $row) {
                foreach ($row as $key => $val) {
                    if (!in_array($key, $isEscapeArr)) {
                        $recordSet->fields[$key] = outputEscapeString($val);
                    } else {
                        $recordSet->fields[$key] = outputEscapeString($val, "TEXTAREA");
                    }
                }
                $rs = $recordSet->fields;
            }
            if (count($search_text) > 0) {
                $message = str_replace($search_text, $replace_text, $rs['email_body']);
            } else {
                $message = $rs['email_body'];
            }
            $from_email = $rs['from_email'];
            $from_name = $rs['from_name'];
            $this->obj->email->from($from_email, $from_name);
            $this->obj->email->to($to_email);
            if ($cc) {
                $this->obj->email->cc($cc);
            }
            if ($bcc) {
                $this->obj->email->bcc($bcc);
            }
            $this->obj->email->set_mailtype('html');
            if ($subject != '')
                $rs['subject'] = $subject;
            $this->obj->email->subject($rs['subject']);
            $path = front_base_url();
            $footer = nl2br(GLOBAL_EMAIL_SIGNATURE);
            $emailBody = $this->emailTemplate($message, $footer, $path);
            $this->obj->email->message($emailBody);
            $var = $this->obj->email->send();
            
           
            
            return true;
        }
        else {
            return false;
        }
        exit;
    }
    
    /**
     * Function for save Email log
     * @param int $to 
     * @param string $subject
     * @param text $body
     * @param enum $isSent
     */
    function addEmailLog($to,$subject,$body){
    	$sql	=	"INSERT INTO ".EMAIL_LOG." SET sent_to='".$to."',
									    	   subject='".$subject."',
									    	   body	='".$body."'";
    	 
    	$result = $this->obj->db->query($sql);
    }
    
    /**
     * Function for save Email log
     * @param int $to
     * @param string $subject
     * @param text $body
     * @param enum $isSent
     */
    function addDriverActivityLog($data){
    	$driver_id		= !empty($data['driver_id'])?$data['driver_id']:"0";
    	$order_id		= !empty($data['order_id'])?$data['order_id']:"0";
    	$activity_type	= !empty($data['activity_type'])?$data['activity_type']:"";
    	$latitude		= !empty($data['latitude'])?$data['latitude']:"";
    	$longitude		= !empty($data['longitude'])?$data['longitude']:"";
    	$nowDate		= date('Y-m-d H:i:s'); 
    	$sql = "INSERT INTO ".DRIVER_ACTIVITY_LOG." SET
					driver_id = '".$driver_id."', 
					order_id  = '".$order_id."', 
					latitude  = '".$latitude."', 
					longitude = '".$longitude."', 
					activity_type = '".$activity_type."',
					created_at = '".$nowDate."'";
    
    	$result = $this->obj->db->query($sql);
    	return $result;
    }
    /*
	 *For month
	 */
	 function getMonth($data){
        if($data=='01'){
            return "January";
        } else if($data=='02'){
            return "February";
        } else if($data=='03'){
            return "March";
        } else if($data=='04'){
            return "April";
        } else if($data=='05'){
            return "May";
        } else if($data=='06'){
            return "June";
        } else if($data=='07'){
            return "July";
        } else if($data=='08'){
            return "August";
        } else if($data=='09'){
            return "September";
        } else if($data==10){
            return "October";
        } else if($data==11){
            return "November";
        } else if($data==12){
            return "December";
        } 
    }
    // --------------------------------------------------------------------
    /**
     * getGlobalInfo
     * fetching the globalinfo from globalconfig table
     * $field_key is the value of field_key column in that table 
     * 
     */
    function getGlobalInfo($field_key) {
        $sql = "SELECT field_value FROM " . GLOBALCONFIG . " WHERE field_key = '" . $field_key . "'";
        $recordSet = $this->obj->db->query($sql);

        $rs = false;
        if ($recordSet->num_rows() > 0) {
            $isEscapeArr = array('global_email_signatur', 'global_company_info');
            foreach ($recordSet->result_array() as $row) {
                foreach ($row as $key => $val) {
                    if (!in_array($key, $isEscapeArr)) {
                        $rs = outputEscapeString($val);
                    } else {
                        $rs = outputEscapeString($val, "HTML");
                    }
                }
            }
        }
        return $rs;
    }

    function generateUrl($table, $field_name, $field_value, $pk, $pk_value = 0) {
        $field_value = preg_replace("[^A-Za-z0-9-]", "", str_replace(array(" "), '-', strtolower($field_value)));

        $existRecords = $this->existRecords($table, $field_name, $field_value, $pk, $pk_value);

        if ($existRecords > 0) {
            for ($i = 1; $i < 100; $i++) {
                $existRecords = $this->existRecords($table, $field_name, $field_value . "-" . $i, $pk, $pk_value);
                if (!$existRecords) {
                    $field_value = $field_value . "-" . $i;
                    break;
                }
            }
        }

        $url = strtolower(str_replace("--", "-", $field_value));

        return $url;
    }   
    
    /**
     * Send Email to customer
     * @param string $to_email
     * @param string $template_code
     * @param string $search_text
     * @param string $replace_text
     * @param string $subject
     * @param string $name
     * @param string $email
     * @return boolean
     */
    function send_email($to_email,$template_code,$search_text=array(),$replace_text=array(),$subject='',$name='',$email='')
    {
    	$sql = "SELECT from_name, from_email, subject, email_body FROM ".EMAIL_TEMPLATE_SETTING." WHERE template_code = '".$template_code."'" ; //echo $sql;
    
    	$recordSet = $this->obj->db->query($sql);
    
    	$rs = false;
    	if ($recordSet->num_rows() > 0)
    	{
    		$rs = array();
    		$isEscapeArr = array('email_body');
    		foreach ($recordSet->result_array() as $row)
    		{
    			foreach($row as $key=>$val){
    				if(!in_array($key,$isEscapeArr)){
    					$recordSet->fields[$key] = outputEscapeString($val);
    				}else{
    					$recordSet->fields[$key] = outputEscapeString($val,"TEXTAREA");
    				}
    			}
    			$rs = $recordSet->fields;
    		}
    		if(count($search_text)>0){
    			$message = str_replace($search_text,$replace_text,$rs['email_body']);
    		}else{
    			$message = $rs['email_body'];
    		}
    
    		$from_email	=$rs['from_email'];
    		$from_name	=$rs['from_name'];
    		
    		$path 		=  front_base_url();
    		$footer	 	=  nl2br(GLOBAL_EMAIL_SIGNATURE);
    		$emailBody = $this->emailTemplate($message,$footer,$path);
    		
    		/*$this->obj->email->from($from_email,$from_name);
    		$this->obj->email->to($to_email);
    		$this->obj->email->set_mailtype('html');
    		$this->obj->email->subject($rs['subject']);
    		$this->obj->email->message($emailBody);
    		$this->obj->email->send();*/
			
			$header  = "MIME-Version: 1.0 \r\n"; 
			$header .= "From: rocket < feedback@rocket.co.in >"."\r\n";
			$header .= "Reply-To: rocket < feedback@rocket.co.in >"."\r\n";
			$header .= "Return-Path: rocket < feedback@rocket.co.in >"."\r\n";
			$header .= "Content-type: text/html;charset=utf-8 \r\n";
			$header .= "X-Priority: 3\r\n";
			$header .= "X-Mailer: smail-PHP ".phpversion()."\r\n";
    		$sendmail = mail($to_email,$rs['subject'],$emailBody,$header,'-ffeedback@rocket.co.in');
    		$this->addEmailLog($to_email,$rs['subject'],$emailBody);
    		
    		return true;
    	}
    	else
    	{
    		return false;
    	}
    	
    }
    
    
    function createThumbnail($path_to_image_directory, $path_to_thumbs_directory, $filename,$height,$width) {

        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        }

        $ox = imagesx($im);
        $oy = imagesy($im);

        $nx = $height;
        $ny = $width;
        $nm = imagecreatetruecolor($nx, $ny);

        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);

        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory)) {
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($nm, $path_to_thumbs_directory . $filename);
    }

    /**
     * Function countrySet()
     * Function used For get country   
     */
    function countrySet() {
        $query = $this->obj->db->get_where(COUNTRY, array('deleted' => '0', 'status' => 'Active'));
        return $query->result_array();
    }
    
    /**
     * Function citySet()
     * Function used For get country   
     */
    function citySet($country_id = '') {
        if (!empty($country_id)) {
            $query = $this->obj->db->get_where(CITY, array('deleted' => '0', 'country_id' => $country_id, 'status' => 'Active'));
            return $query->result_array();
        } else {
            $query = $this->obj->db->get_where(CITY, array('deleted' => '0', 'status' => 'Active'));
            return $query->result_array();
        }
    }

    /**
     * Function regionSet()
     * Function used For get region   
     */
    function regionSet($city_id = '') {
        if (!empty($city_id)) {
            $query = $this->obj->db->get_where(REGION, array('deleted' => '0', 'city_id' => $city_id, 'status' => 'Active'));
            return $query->result_array();
        } else {
            $query = $this->obj->db->get_where(REGION, array('deleted' => '0', 'status' => 'Active'));
            return $query->result_array();
        }
    }
    /**
     * Function regionSet()
     * Function used For get location   
     */
    function locationSet($region_id = '') {
        if (!empty($region_id)) {
            $query = $this->obj->db->get_where(LOCATION, array('deleted' => '0', 'region_id' => $region_id, 'status' => 'Active'));
            return $query->result_array();
        } else {
            $query = $this->obj->db->get_where(LOCATION, array('deleted' => '0', 'status' => 'Active'));
            return $query->result_array();
        }
    }
    /**
     * Function cuisineSet()
     * Function used For get cuisine   
     */
    function cuisineSet() {
        $query = $this->obj->db->get_where(CUISINE, array('deleted' => '0', 'status' => 'Active'));
        return $query->result_array();
    }
    
     /**
     * Function preferenceSet()
     * Function used For get preference   
     */
    function preferenceSet() {
        $query = $this->obj->db->get_where(PREFERENCE, array('deleted' => '0', 'status' => 'Active'));
        return $query->result_array();
    }
     
    /**
     * Function cuisineName($id)
     * Function used For get cuisine name
     */
    function cuisineName($id) {
        $this->obj->db->select('name');
        $query = $this->obj->db->get_where(CUISINE, array('id' => $id));
        return $query->result_array();
    }
    /**
     * Function facilitySet()
     * Function used For get facility   
     */
    function facilitySet() {
        $query = $this->obj->db->get_where(ICON, array('deleted' => '0', 'status' => 'Active'));
        return $query->result_array();
    }
    /**
     * Function facilityName($id)
     * Function used For get facility name   
     */
    function facilityName($id) {
        $this->obj->db->select('name');
        $query = $this->obj->db->get_where(ICON, array('id' => $id));
        return $query->result_array();
    }
    /**
     * Function restaurantSet()
     * Function used For get restaurant   
     */
    function restaurantSet($merchant_id = '') {
        if(empty($merchant_id)){
            $query = $this->obj->db->get_where(RESTAURANT, array('deleted' => '0', 'status' => 'Active'));
            return $query->result_array();            
        }else{
            $user_type = $this->obj->nsession->userdata('merchant_user_type');
            if($user_type == 'GMU'){
                $query = $this->obj->db->get_where(RESTAURANT, array('deleted' => '0', 'status' => 'Active','merchant_id' => $merchant_id));
                return $query->result_array();                
            }else{          
                $sql="SELECT DISTINCT id,name,alias_name FROM ".RESTAURANT." WHERE id IN (SELECT restaurant_id FROM ".RESTAURANT_USER_REL." WHERE user_id='".$merchant_id."' AND deleted='0')  AND status = 'Active' AND deleted='0'";
                $query = $this->obj->db->query($sql);
                return $query->result_array(); 
            }
        }
    }
    /**
     * Function restaurantName($id)
     * Function used For get restaurant name   
     */
    function restaurantName($id) {
        $this->obj->db->select('name');
        $query = $this->obj->db->get_where(RESTAURANT, array('id' => $id));
        return $query->result_array();
    }
    /**
     * Function merchantOutletSet()
     * Function used For get outlet set against merchant set against group Merchant id   
     */
    function merchantOutletSet($parent_id = 0) {
        $rs = array();
        if($this->obj->nsession->userdata('merchant_user_type') == 'GMU'){
            $this->obj->db->select('id,group_name,name');
            $query = $this->obj->db->get_where(MERCHANT, array('deleted' => '0', 'status' => 'Active','parent_id' => $parent_id));
        }else{
            $sql="SELECT MA.id,MA.group_name,MA.name from ".MERCHANT." MA  WHERE  MA.deleted='0' AND  MA.id IN (SELECT merchant_id from ".MERCHANT_RESTAURANT_REL." WHERE restaurant_id IN (SELECT restaurant_id from ".RESTAURANT_USER_REL." WHERE user_id='".$parent_id."' AND deleted='0'))";
            $query = $this->obj->db->query($sql); 
        }
            $isEscapeArr = array();
            foreach ($query->result_array() as $row)
            {
                foreach($row as $key=>$val){
                        if(!in_array($key,$isEscapeArr)){
                            $query->fields[$key] = outputEscapeString($val);
                            if($key == 'id'){
                                $query->fields['outlet_list'] = array();                               
                                if($this->obj->nsession->userdata('merchant_user_type') == 'GMU'){
                                $this->obj->db->select('restaurant_id');
                                $outlet_query = $this->obj->db->get_where(MERCHANT_RESTAURANT_REL, array('deleted' => '0','merchant_id' => $val));
                                }else{ 
                                    $sql="Select restaurant_id FROM ".MERCHANT_RESTAURANT_REL." WHERE deleted='0' AND merchant_id='".$val."' AND restaurant_id IN(SELECT restaurant_id from ".RESTAURANT_USER_REL." WHERE user_id='".$parent_id."' AND deleted='0')";
                                    $outlet_query =$this->obj->db->query($sql); 
                                }
                                $restaurant_array = $outlet_query->result_array();
                                if(is_array($restaurant_array) && !empty($restaurant_array)){
                                    $outlets = array();
                                    $outlet_array = array();
                                    for($i = 0; $i<count($restaurant_array);$i++){
                                        $this->obj->db->select('id,name,preference_id,alias_name');
                                        $outlet_query = $this->obj->db->get_where(RESTAURANT, array('deleted' => '0', 'status' => 'Active','id' => $restaurant_array[$i]['restaurant_id']));
                                        $outlets = $outlet_query->result_array();
                                        $outlet_array[] = $outlets[0];                                        
                                    }
                                    $query->fields['outlet_list'] = $outlet_array;
                                }                           
                            }
                        }
                }
                $rs[] = $query->fields;	
            }          
        return $rs;
      
    }
    /**
     * Function merchantSet()
     * Function used For get Merchant Set   
     */
    function merchantSet() {
        $merchant_id = $this->obj->nsession->userdata('merchant_id');
        $user_type = $this->obj->nsession->userdata('merchant_user_type');
        if($user_type == 'GMU'){
            $this->obj->db->select('id,name');
            $this->obj->db->order_by("name", "asc"); 
            $query = $this->obj->db->get_where(MERCHANT, array('deleted' => '0', 'status' => 'Active','parent_id' => $merchant_id));
            $result  = $query->result_array();
            return $result;                
        }else{
            $sql="SELECT M.id,M.name FROM ".MERCHANT." M 
                    LEFT JOIN ".MERCHANT_RESTAURANT_REL." MRL ON (M.id = MRL.merchant_id) 
                    LEFT JOIN ".RESTAURANT_USER_REL." RUR ON (RUR.restaurant_id = MRL.restaurant_id) 
                    WHERE RUR.user_id='".$merchant_id."' AND RUR.deleted='0' AND MRL.deleted = '0' AND M.status = 'Active'
                    GROUP BY M.id ORDER BY M.name ASC";
            $query = $this->obj->db->query($sql);
            return $query->result_array();
        }
    }
    /**
     * Function restaurantMerchantDetail()
     * Function used For get merchant id & name against Restaurant id   
     */
    function restaurantMerchantDetail($restaurant_id = 0){
        $current_merchant_id = $this->obj->nsession->userdata('merchant_id');
        if(!empty($restaurant_id)){
            $where = " AND M.deleted = 0 AND M.parent_id = '{$current_merchant_id}' AND MRR.restaurant_id = '{$restaurant_id}'";
            $sql = " SELECT M.id,M.name FROM ".MERCHANT." M LEFT JOIN ".MERCHANT_RESTAURANT_REL." MRR ON M.id = MRR.merchant_id WHERE 1 $where";
            $recordSet = $this->obj->db->query($sql);
            if ($recordSet->num_rows() > 0) {
                $row = $recordSet->row_array();
                return $row;
            } else {
                return "";
            }
        }
    }
    
  /**
     * Function groupMerchantName()
     * Function used For get group merchant name against merchant user for user type(MU & OU)   
     */
    function groupMerchantName($merchant_user = '') {
        if (empty($merchant_user)) {
            $current_merchant_id = $this->obj->nsession->userdata('merchant_id');
        } else {
            $current_merchant_id = $merchant_user;
        }
        if (!empty($current_merchant_id)) {
            $sql = "SELECT M.group_name,M.id FROM " . MERCHANT . " M INNER JOIN " . MERCHANT_USER_REL . " MUR ON M.id = MUR.merchant_id WHERE MUR.user_id='" . $current_merchant_id . "'";
            $recordSet = $this->obj->db->query($sql);
            if ($recordSet->num_rows() > 0) {
                $row = $recordSet->row_array();
                if (empty($merchant_user)) {
                    return $row['group_name'];
                } else {
                    return $row['id'];
                }
            } else {
                return "";
            }
        }
    }
    /**
     * Function groupMerchantSet()
     * Function used For get Group Merchant Set with id & group_name  
     */
    function groupMerchantSet() {
        $sql="SELECT M.id,M.group_name
	    FROM ".MERCHANT." M INNER JOIN ".MERCHANT_USER." MU ON M.id = MU.group_merchant_id"
                . "  WHERE M.deleted = '0' AND M.status = 'Active' AND M.parent_id IS NULL ORDER BY M.group_name";
        $recordSet = $this->obj->db->query($sql);
        $rs = false;
        if ($recordSet->num_rows() > 0) {
            $rs = array();
            $isEscapeArr = array();
            foreach ($recordSet->result_array() as $row) {
                foreach ($row as $key => $val) {
                    if (!in_array($key, $isEscapeArr)) {
                        $recordSet->fields[$key] = outputEscapeString($val);
                    }
                }
                $rs[] = $recordSet->fields;
            }
        } else {
            return false;
        }
        return $rs;
    }
    
       /**
    * Function getMerchantName()
    * Function used For get Merchant name ,$id reffer to merchant id of all type user   
    */
    function getMerchantName($id)
    {
        $sql="SELECT get_user_name('".$id."') As m_name";  
        $recordSet=$this->obj->db->query($sql);
        $result=$recordSet->row_array();
	return $result['m_name'];
    }
    
    function unlinkFile($path,$name){
	//echo $path.'.'.$name;
	$unlink = unlink($path.$name);
	return $unlink;
    }
    
    function checkingTypeGMU($base_url='') {
        $user_type = $this->obj->nsession->userdata('merchant_user_type');
        if ($user_type != 'GMU') {
            redirect($base_url.'dashboard');
        }
    }
    function checkingTypeOU($base_url='') {
        $user_type = $this->obj->nsession->userdata('merchant_user_type');
        if ($user_type == 'OU') {
            redirect($base_url.'dashboard');
        }
    }
    
    function role_change($user_type,$user_id){
        if($user_type == 'Admin'){
              $sql="SELECT PUL.id,PUL.login_id,PUL.hash_password,PUL.grp_id,PUL.last_login,PUL.user_type,PUL.status,
                    PUD.*,GR.role_id,R.name AS role_name, 
                    RP.module_id,RP.add, RP.edit, RP.delete, RP.view, RP.import, RP.export, RP.created_at AS role_created_at, 
                    RP.modified_at AS role_modified_at , M.module_name,M.alias_name,G.grp_name
                   FROM ".PORTAL_USER_LOGIN." PUL INNER JOIN ".PORTAL_USER_DETAILS." PUD ON(PUD.user_id=PUL.id)
                   LEFT JOIN ".GROUP_ROLE." GR ON (GR.grp_id=PUL.grp_id)
                   LEFT JOIN ".GROUP." G ON (G.id=PUL.grp_id)
                   LEFT JOIN ".ROLE." R ON (R.role_id=GR.role_id)
                   LEFT JOIN ".ROLE_PERMISION." RP ON (R.role_id=RP.role_id)
                   LEFT JOIN ".MODULES." M ON (M.id=RP.module_id)
                   WHERE PUL.grp_id= ? ";
                    $recordSet = $this->obj->db->query($sql,array($user_id));
                    $role['role']=$recordSet->result_array();  
            }else{
                   $sql="SELECT PUL.id,PUL.login_id,PUL.hash_password,PUL.grp_id,PUL.last_login,PUL.user_type,PUL.status,
                       PUD.*, UR.role_id, R.name AS role_name, 
                       RP.module_id,RP.add, RP.edit, RP.delete, RP.view, RP.import, RP.export, RP.created_at AS role_created_at, 
                       RP.modified_at AS role_modified_at, M.module_name,M.alias_name
                        FROM ".PORTAL_USER_LOGIN." PUL INNER JOIN ".PORTAL_USER_DETAILS." PUD ON(PUD.user_id=PUL.id)
                        LEFT JOIN ".USER_ROLES." UR ON (PUD.user_id=UR.user_id)
                        LEFT JOIN ".ROLE." R ON (R.role_id=UR.role_id) 
                        LEFT JOIN ".ROLE_PERMISION." RP ON (R.role_id=RP.role_id)
                        LEFT JOIN ".MODULES." M ON (RP.module_id=M.id)
                        WHERE PUD.user_id= ? ";
                $recordSet = $this->obj->db->query($sql,array($user_id));
                $role['role'] = $recordSet->result_array();
            }
            
        // pr($role['role']);
            
            $file = server_absolute_path().'user_priviledges/qelasy_priviledge_'.$user_id.'.php';
            
            $current = file_get_contents($file);
            $userdata =  unserialize($current);
            //pr($userdata);die;
           
                    $filedata =  serialize($role['role']);
                    file_put_contents($file, $filedata);					
			
                        
            $this->obj->nsession->set_userdata('ROLE_MANAGEMENT', $role);
            $this->obj->nsession->set_userdata('user_session_id', $user_id);            
//            $this->nsession->set_userdata('user_session_username', $row->login_id);
//            $this->nsession->set_userdata('user_session_password', $row->hash_password);
//            $this->nsession->set_userdata('user_session_user_type', $row->user_type);
            $this->obj->nsession->set_userdata('user_session_first_name', $role['role'][0]['first_name']);
            $this->obj->nsession->set_userdata('user_session_last_name', $role['role'][0]['last_name']);

            return true;
    }
	/*
	 *For API
	 *Abhijit
	 *04-08-2015
	 */
	
	/*
	 * Try & Catch
	 */
	function validateRequiredField($arrFields = array()){
		$errorCounter = 0;
		//echo (count($arrFields));
		//echo count($arrFields);pr($arrFields);
		if(count($arrFields) > 0){
			foreach($arrFields AS $fieldName){
				
				if(!isset($this->request->data[$fieldName])){
					//echo ($this->request->data[$fieldName]);
					$errorCounter++;
				}
			}
			//exit;
		}
		return $errorCounter;
	}
	function validateBlankField($arrFields = array()){
		$errorCounter = 0;
		if(count($arrFields) > 0){
			foreach($arrFields AS $fieldName){
				if(isset($this->request->data[$fieldName]) && trim($this->request->data[$fieldName]) == ''){
					$errorCounter++;
				} 
			}
		}
		return $errorCounter;
	}
	
	function throwError($msg){
		if(trim($msg) == ''){	
			$msg = 'Error';
		} 
		$respArr = array(
						"ResponseCode" 		=> "201",
						"ResponseDetails" 	=> $msg
						);
		echo json_encode($respArr);
		die;
	}
    /*
	 * Response Handler
	 */
	function sendResponse($response){
        
        $newJson =  json_encode($response,JSON_UNESCAPED_SLASHES);
        //mb_convert_encoding('&#x1000;', 'UTF-8', 'HTML-ENTITIES');
        //$newJson = str_replace('\n', '', $newJson);
        //$newJson = str_replace("\n", "", $newJson);
        //$newJson = str_replace("\r", "", $newJson);
        //$newJson = str_replace("\r\n", "", $newJson);
        //$newJson = str_replace("\n",',', $newJson);
        //$newJson = rtrim($newJson, "\x00..\x1F");

        $this->response->body($newJson);
        $this->response->type('application/json');
        $this->response->header('Content-Type', 'application/json');
        return $this->response;
        exit;
    }
    
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    /*::                                                                         :*/
    /*::  This routine calculates the distance between two points (given the     :*/
    /*::  latitude/longitude of those points). It is being used to calculate     :*/
    /*::  the distance between two locations using GeoDataSource(TM) Products    :*/
    /*::                                                                         :*/
    /*::  Definitions:                                                           :*/
    /*::    South latitudes are negative, east longitudes are positive           :*/
    /*::                                                                         :*/
    /*::  Passed to function:                                                    :*/
    /*::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  :*/
    /*::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  :*/
    /*::    unit = the unit you desire for results                               :*/
    /*::           where: 'M' is statute miles (default)                         :*/
    /*::                  'K' is kilometers                                      :*/
    /*::                  'N' is nautical miles                                  :*/
    /*::  Worldwide cities and other features databases with latitude longitude  :*/
    /*::  are available at http://www.geodatasource.com                          :*/
    /*::                                                                         :*/
    /*::  For enquiries, please contact sales@geodatasource.com                  :*/
    /*::                                                                         :*/
    /*::  Official Web site: http://www.geodatasource.com                        :*/
    /*::                                                                         :*/
    /*::         GeoDataSource.com (C) All Rights Reserved 2015		   		     :*/
    /*::                                                                         :*/
    /*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
    function distance($lat1, $lon1, $lat2, $lon2, $unit) {
    
    	$theta = $lon1 - $lon2;
    	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    	$dist = acos($dist);
    	$dist = rad2deg($dist);
    	$miles = $dist * 60 * 1.1515;
    	$unit = strtoupper($unit);
    
    	if ($unit == "K") {
    		return ($miles * 1.609344);
    	} else if ($unit == "N") {
    		return ($miles * 0.8684);
    	} else {
    		return $miles;
    	}
    }
	
   /**
	* Function for get distance from google API
	* @param string $origin,$destination { address or lat/long }
	* @return string
	* @Gopal
	**/
	function get_distance($origin,$destination)
	{
		$key = $this->googleAPIKey;
		$ch  = curl_init('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.urlencode($origin).'&destinations='.urlencode($destination).'&mode=driving&language=en-US&key='.$key);
		curl_setopt_array($ch, array(
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_RETURNTRANSFER =>true,
			CURLOPT_FOLLOWLOCATION =>false,
		));

		if (false === curl_exec($ch)) {
			echo "Error while loading page: ", curl_error($ch), "\n";
		}else{
			$output = curl_exec($ch);
		}
		$result     = json_decode($output,true);		
		$distance   = 0;
		if(isset($result['rows'][0]['elements'][0]['status']) && $result['rows'][0]['elements'][0]['status']=='OK'){
		$distance   = round(($result['rows'][0]['elements'][0]['distance']['value']/1000),1);
		}
		return $distance;
	}
	
	/**
	* Function for get distance & ETA from google API
	* @param string $origin,$destination { address or lat/long }
	* @return array
	* @Gopal
	* @Modified:06-Jan-2016
	**/
	function get_distance_ETA($origin,$destination)
	{
		$key = $this->googleAPIKey;
		$ch  = curl_init('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.urlencode($origin).'&destinations='.urlencode($destination).'&mode=driving&language=en-US&key='.$key);
		curl_setopt_array($ch, array(
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_RETURNTRANSFER =>true,
			CURLOPT_FOLLOWLOCATION =>false,
		));

		if (false === curl_exec($ch)) {
			echo "Error while loading page: ", curl_error($ch), "\n";
		}else{
			$output = curl_exec($ch);
		}
		$result     = json_decode($output,true);		
		$distance   = 0;
		$duration	= '0 min';
		if(isset($result['rows'][0]['elements'][0]['status']) && $result['rows'][0]['elements'][0]['status']=='OK'){
		$distance   = round(($result['rows'][0]['elements'][0]['distance']['value']/1000),1);
		$duration   = $result['rows'][0]['elements'][0]['duration']['text'];
		}
		return array('distance'=>$distance,'duration'=>$duration);
	}

	/**
	* Function for get route lat/long
	* @param string $origin,$destination { lat/long }
	* @return array
	* @Gopal
	* @Modified:15-Jan-2016
	**/
	function google_root_bounds($origin,$destination)
	{
		$key = $this->googleAPIKey;
		$ch  = curl_init('https://maps.googleapis.com/maps/api/directions/json?origin='.urlencode($origin).'&destination='.urlencode($destination).'&sensor=false&key='.$key);
		curl_setopt_array($ch, array(
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_RETURNTRANSFER =>true,
			CURLOPT_FOLLOWLOCATION =>false,
		));

		if (false === curl_exec($ch)) {
			echo "Error while loading page: ", curl_error($ch), "\n";
		}else{
			$output = curl_exec($ch);
		}
		$result     = json_decode($output,true);
		//pr($result);
		if(isset($result['status']) && $result['status']=='OK'){
		return  $result['routes'][0]['bounds']['northeast'];
		}
		return array('lat'=>'','lng'=>'');
	}
	
	/**
	* Function for get address by lat/long from google API
	* @param string $latLong { lat,long }
	* @return string
	* @Gopal
	**/
	function getAddressByLatLong($latLong)
	{
		$key = $this->googleAPIKey;
		$ch  = curl_init("https://maps.googleapis.com/maps/api/geocode/json?latlng=".$latLong."&key".$key);
		curl_setopt_array($ch, array(
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_RETURNTRANSFER =>true,
			CURLOPT_FOLLOWLOCATION =>false,
		));

		if (false === curl_exec($ch)) {
			echo "Error while loading page: ", curl_error($ch), "\n";
		}else{
			$output = curl_exec($ch);
		}
		$result  = json_decode($output,true);		
		$address = "";
		if(isset($result['results'][0]['formatted_address']) && $result['status']=='OK'){
			$address = $result['results'][0]['formatted_address'];
		}
		return $address;
	}
    
    /**
     * Function for check address in polygon
     * @param int $got_this_address
     * @return boolean
     */
    function determine_address_in_polygon($got_this_address) {
    	//$got_this_address	=	urldecode($got_this_address);
    	$polygons  = json_decode(POLYGON);    	 
    	$address   = json_decode($this->get_address_coordinate($got_this_address));    	 
    	$address_x = 0;
    	$address_y = 0;    	 
    	
    	if(!empty($address->results)){
    		$address   = $address->results[0];
    		$address_x = $address->geometry->location->lat;
    		$address_y = $address->geometry->location->lng;
    	}	 
    	 
    	$result 	= false;    	 
    	if(!empty($polygons)) {
    		foreach($polygons as $polygon){
    			$no_of_polygons = sizeof($polygon);    			 
    			$polygons_x 	= array();
    			$polygons_y 	= array();    			 
    			foreach($polygon as $poly){
    				$polygons_x[] = $poly[0];
    				$polygons_y[] = $poly[1];
    			}    			 
    			 
    			if ($this->determine_in_polygon($no_of_polygons, $polygons_x, $polygons_y, $address_x, $address_y))
    			{
    				$result = true;
    				break;
    			}
    		}
    	}
    	return 	$result;
    }
     
     
    /**
     * Function for get adrress co-ordinate
     * @param int $address
     * @param float $polygons_x
     * @param float $polygons_y
     * @param float $address_x
     * @param float $address_y
     * @return boolean
     */
    function determine_in_polygon($no_of_polygons, $polygons_x, $polygons_y, $address_x, $address_y) {
	    	$j = $no_of_polygons - 1;
	    	$odd_nodes = 0;
	    	for ($i = 0; $i < $no_of_polygons; $i++)
	    	{
		    	if ($polygons_y[$i] < $address_y && $polygons_y[$j] >= $address_y || $polygons_y[$j] < $address_y && $polygons_y[$i] >= $address_y)
		    	{
			    	if ($polygons_x[$i] + ($address_y - $polygons_y[$i]) / ($polygons_y[$j] - $polygons_y[$i]) * ($polygons_x[$j] - $polygons_x[$i]) < $address_x)
			    	{
			    		$odd_nodes = !$odd_nodes;
			    	}
		    	}
		    	$j = $i;
	    	}
    	    return $odd_nodes;
    	}
    		 
        /**
    	* Function for get adrress co-ordinate
    	* @param string $address
    	* @return string
    	*/
    	public function get_address_coordinate($address) {
    		$key 		= 'AIzaSyBGyLsC9P94sx7I8hA3SSuzDSVqjPSM-4Q';
    		//$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false&key=".$key;
    		
    		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&sensor=false&key=".$key;
    		
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $url);
    		curl_setopt($ch, CURLOPT_HEADER, 0);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    		$output = curl_exec($ch);
    			curl_close($ch);
    			return $output;
    	}
		
		
	/**
     * getUserLatLong()
	 * Get User latest lat/long
     * @access	public
     * @param	int
     * @return	array
     */
    function getUserLatLong($userID) {
        $query = "SELECT latitude,longitude FROM `tbl_user_device_info` WHERE user_id ='" . $userID . "' ORDER BY `modified_at` DESC LIMIT 1 ";
        $recordSet = $this->obj->db->query($query);

        $rs = array('latitude'=>'', 'longitude' => '');
        if ($recordSet->num_rows() > 0) {
            $rs = array();
            $isEscapeArr = array('content');
            foreach ($recordSet->result_array() as $row) {
                foreach ($row as $key => $val) {
                    if (!in_array($key, $isEscapeArr)) {
                        $recordSet->fields[$key] = outputEscapeString($val);
                    } else {
                        $recordSet->fields[$key] = outputEscapeString($val, '');
                    }
                }
                $rs = $recordSet->fields;
            }
        } 
        return $rs;
    }
	
	function checkSendSMSOrder($orderID) {
        $query = "SELECT order_type FROM `tbl_order_master` WHERE `order_id` = '" . $orderID . "'";
        $recordSet = $this->obj->db->query($query);
        $rs 	   = 0;
        if ($recordSet->num_rows() > 0) {
            $row       = $recordSet->result_array();
			$orderType = $row[0]['order_type'];
			
			$sql = "SELECT field_key,field_value FROM `tbl_global_config`  WHERE `field_key` IN('sms_general_order','sms_corporate_order','sms_utility_order')";
			$record 	= $this->obj->db->query($sql);
			$configRow  = $record->result_array();
			foreach($configRow as $value){
				$configItems[$value['field_key']] = $value['field_value'];
			}
			
			if( $orderType == "General" && $configItems['sms_general_order'] == "Yes" ){
				$rs = 1;
			} else if( $orderType =="Corporate" && $configItems['sms_corporate_order'] == "Yes" ){
				$rs = 1;
			} else if( $orderType =="Utility" && $configItems['sms_utility_order'] == "Yes"){
				$rs = 1;
			}
            
        } 
        return $rs;
    }
    
    
    function makeRandomWord($size) {
    	$salt = "0123456789abchefghjkmnpqrstuvwxyz";
    	srand((double) microtime() * 1000000);
    	$word = '';
    	$i = 0;
    	while (strlen($word) < $size) {
    		$num = rand() % 59;
    		$tmp = substr($salt, $num, 1);
    		$word = $word . $tmp;
    		$i++;
    	}
    	return $word;
    }
	
	/**
	 * function for used to format name for SMS Sending
	 * @Gopal
	 * @modified at: 19-Jan-2016
	 **/
	function prepareName($name) {
		$nameArr 			= explode(" ",$name);
		$nameArrCount 		= count($nameArr);
		if( $nameArrCount >2 ){
			$formattedName 	= $nameArr[0]." ".$nameArr[1];
		}else{
			$formattedName 	= $nameArr[0];
		}					
		$formattedName 		= substr($formattedName, 0, 14);	
    	return $formattedName;
    }
	
	/**
	 * function for used to calculate time difference
	 * @Gopal
	 * @modified at: 09-Feb-2016
	 **/
	function dateTimeDiff( $interval='i', $dt1, $dt2, $relative=false){	   
       if( is_string( $dt1)) $dt1 = date_create( $dt1);
       if( is_string( $dt2)) $dt2 = date_create( $dt2);
       $diff = date_diff( $dt1, $dt2, ! $relative);      
       switch( $interval){
           case "y":
               $total = $diff->y + $diff->m / 12 + $diff->d / 365.25; break;
           case "m":
               $total= $diff->y * 12 + $diff->m + $diff->d/30 + $diff->h / 24;
               break;
           case "d":
               $total = $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h/24 + $diff->i / 60;
               break;
           case "h":
               $total = ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i/60;
               break;
           case "i":
               $total = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s/60;
               break;
           case "s":
               $total = ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i)*60 + $diff->s;
               break;
          }
       if( $diff->invert)
               return -1 * $total;
       else    return $total;
   }
   
	 /**
	  * Function for save order edit 
	  * 
	  */
	 function orderEditLog($data){ 
		$data['edited_at'] 			= date('Y-m-d H:i:s');
		if(!isset($data['corporate_user_id'])){
			$role 			  = $this->obj->nsession->userdata('ROLE_MANAGEMENT');
			$edited_user_name = $role['role'][0]['first_name'].' '.$role['role'][0]['last_name'];
			
			$data['edited_user_id']		= $this->obj->nsession->userdata('user_session_id');
			$data['edited_user_name'] 	= $edited_user_name;
			$data['edited_role_id']		= $role['role'][0]['role_id'];
			$data['edited_role_name']	= $role['role'][0]['role_name'];
		}
		$this->obj->db->insert('tbl_order_edit_log',$data);
		return true;
	 }
    
}

// END Functions Class

/* End of file functions.php */
/* Location: ./system/libraries/functions.php */
