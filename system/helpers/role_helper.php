<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('role'))
{
	
	/*
	*Function role($key='',$val='').
	* this function used for getting the role of player
	* $key is the key of the array
	* $key !='' then value will return
	* value !='' then $key will return
	*/

	function role($key='',$val='')
	{
		$role['Wicketkeeper'] = 'Wicket Keeper';
		$role['Bowler'] = 'Bowler';
		$role['Batsman'] = 'Batsman';
		$role['All rounder'] = 'All Rounder';
		if($key){
			
		return	$role[$key];
			
		}elseif($val)
		{
		
			foreach($role as $k => $v)
			{
				if($v == $val){
					
					return 	$k ;
				}
				
			}
			
			
		}else
		{
			
		return $role;
		
		}
		
	}
}