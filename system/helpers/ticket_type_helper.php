<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('ticket_type'))
{
	/*
	*Function ticket_type($key='',$val='').
	* this function used for getting the ticket type
	* $key !='' then value will return
	* value !='' then $key will return
	*/

	function ticket_type($key='',$val='')
	{
		$ticket_type['system'] = 'System';
                $ticket_type['network'] = 'Network';
                $ticket_type['accounting'] = 'Accounting';
                $ticket_type['qa'] = 'Quality Assurance';
               
                $ticket_type['admin'] = 'Admin';
                if($key){
			
		return	$ticket_type[$key];
			
		}elseif($val){
		
			foreach($ticket_type as $k => $v)
			{
				if($v == $val){
					
					return 	$k ;
				}
				
			}
			
			
		}else{
			
		return $ticket_type;
		
		}
		
                
		
	}
}