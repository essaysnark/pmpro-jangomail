<?php
/*
Plugin Name: PMPro JangoMail Integration
Plugin URI: http://essaysnark.com
Description: Add new WordPress members to JangoMail lists.
Version: 0.5
Author: essaysnark
Author URI: http://essaysnark.com
*/
/*
	Copyright 2013	EssaySnark	(email : techsupport@essaysnark.com)
	Based on Stranger Studios' PMPro-MailChimp integration (thanks Jason!)
	Also see www.jangosmtp.com/developers.asp 
	GPLv2 Full license details in license.txt
*/


/*
	This plugin not working on localhost
	0.2 Now checks the list to see if user exists there before adding them
	0.5 bug fixes; delete not working
*/

//init
function pmprojm_init()
{
	//get options for below
	$options = get_option("pmprojm_options");
	
	//set up hooks for new users	
	if(!empty($options['jmusers_lists']))
	{
		add_action("user_register", "pmprojm_user_register");
		add_action("delete_user", "pmprojm_delete_user");
	}
	//setup hooks for PMPro levels
	pmprojm_getPMProLevels();
	global $pmprojm_levels;
	if(!empty($pmprojm_levels))
	{		
		add_action("pmpro_after_change_membership_level", "pmprojm_pmpro_after_change_membership_level", 10, 2);
	}
	
	
	if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}
}
add_action("init", "pmprojm_init");

//subscribe users when they register
//This code subscribes ALL new users added to WP to the specified lists regardless of how they are added.

//function pmprojm_user_register($user_id, $selected_lists) 
function pmprojm_user_register($user_id) 
{


	// create a SoapClient object for the JangoMail API
	$client = new SoapClient('https://api.jangomail.com/api.asmx?WSDL');
	
	$options = get_option("pmprojm_options");
	$pmprojm_lists = get_option("pmprojm_all_lists");
	$selected_lists = $options['jmusers_lists'];

	//should we add them to any lists? 
	if(!empty($selected_lists) && !empty($options['jmusername']) && !empty($options['jmpassword']))
	{
		//get user info
		$list_user = get_userdata($user_id);
		
		$fieldnames = array('firstname','lastname');
		$uservalues = array($list_user->user_firstname, $list_user->user_lastname);
		
		foreach($selected_lists as $currlist)
		{
			//subscribe them
			$newuserreg=array(
				'Username'=>$options['jmusername'],
				'Password'=>$options['jmpassword'],
				'GroupName'=>$currlist,
				'EmailAddress'=>$list_user->user_email,
				'FieldNames'=>$fieldnames,
				'FieldValues'=>$uservalues
			); 
			pmprojm_add_to_list($newuserreg);
		}
	}
}

function pmprojm_add_to_list($newuserreg)
{
	// create a SoapClient object for the JangoMail API
	$client = new SoapClient('https://api.jangomail.com/api.asmx?WSDL');
	
	$xmlResult = new stdClass();
	unset($xmlResult); 
	try
	{
		$xmlResult = $client->IsMemberInGroup($newuserreg);
	}
	catch(SoapFault $e)
	{
		echo $client->__getLastRequest();
	}	

	if($xmlResult->IsMemberInGroupResult)
	{
		_log('IsMemberInGroupResult in object xmlResult in add to list is boolean true.');
	} else
	{
		_log('IsMemberInGroupResult in object xmlResult in add to list is boolean false.');

		try
		{
			$xmlStrResult = $client->AddGroupMember($newuserreg);
			_log('theoretically added them to list.');
			_log($xmlStrResult);
		}
		catch(SoapFault $e)
		{
			echo $client->__getLastRequest();
		}
	} 
}

// get rid of user from JangoMail lists 
function pmprojm_delete_user($user_id) 
{
	// create a SoapClient object for the JangoMail API
	$client = new SoapClient('https://api.jangomail.com/api.asmx?WSDL');		
	
	$options = get_option("pmprojm_options");
	$pmprojm_lists = get_option("pmprojm_all_lists");

	$list_user = get_userdata($user_id);
	$xmlResult2 = new stdClass;

	foreach($pmprojm_lists as $list)
	{
		unset($xmlResult2); 
		//unsubscribe them
		$deleteme=array(
			'Username'=>$options['jmusername'],
			'Password'=>$options['jmpassword'],
			'GroupName'=>$list,
			'EmailAddress'=>$list_user->user_email
		); 
		
		try
		{
			$xmlResult2 = $client->IsMemberInGroup($deleteme);
		}
		catch(SoapFault $e)
		{
			echo $client->__getLastRequest();
		}	

		if($xmlResult2->IsMemberInGroupResult)
		{
			_log('IsMemberInGroupResult in object xmlResult in deletefrom list is boolean true.');
			try
			{
				//only delete user if they're not already in there
				$xmlStr2Result = $client->DeleteGroupMember($deleteme);	
				_log('theoretically deleted them frm list.');
				_log($xmlStrResult);
			}
			catch(SoapFault $e)
			{
				echo $client->__getLastRequest();
			}	
		}else 
		{
			_log('IsMemberInGroupResult in object xmlResult in deletefrom list is boolean false and it did not try to delete.');
		}
	}
}


//subscribe new members (PMPro) when they register
function pmprojm_pmpro_after_change_membership_level($level_id, $user_id)
{
	global $pmprojm_levels;
	$options = get_option("pmprojm_options");
	$all_lists = get_option("pmprojm_all_lists");	
	
	$list_user = get_userdata($user_id);
		
	if(!empty($options['level_' . $level_id . '_lists']) && !empty($options['jmusername']) && !empty($options['jmpassword']))
	{
		// custom JangoMail fields, they will need to be added to each list manually before this will work
		$fieldnames = array('firstname','lastname');
		$uservalues = array($list_user->user_firstname, $list_user->user_lastname);		
		
		foreach($options['level_' . $level_id . '_lists'] as $currlist) //
		{		
			//subscribe them
			$newuserreg=array(
				'Username'=>$options['jmusername'],
				'Password'=>$options['jmpassword'],
				'GroupName'=>$currlist,
				'EmailAddress'=>$list_user->user_email,
				'FieldNames'=>$fieldnames,
				'FieldValues'=>$uservalues				
			); 
			pmprojm_add_to_list($newuserreg);			
		}
	} elseif(!empty($options['jmusername']) && !empty($options['jmpassword']) && count($options) > 3)
	{
		//are there any all-users lists that they need to be added to as well?
		if(!empty($options['jmusers_lists']) && !empty($options['jmusername']) && !empty($options['jmpassword']))	
		{		
			foreach($options['jmusers_lists'] as $nextlist) 
			{
				//subscribe them
				$nextuser=array(
					'Username'=>$options['jmusername'],
					'Password'=>$options['jmpassword'],
					'GroupName'=>$currlist,
					'EmailAddress'=>$list_user->user_email,
					'FieldNames'=>$fieldnames,
					'FieldValues'=>$uservalues
				); 

				pmprojm_add_to_list($nextuser);		
			
			//unsubscribe from any list not assigned to users
			/* Taking this out because there could be other lists on JangoMail that are managed separately, outside of this WP plugin */
			/*
			foreach($all_lists as $list)
			{
				if(!in_array($list['id'], $options['users_lists']))
					$api->listUnsubscribe($list['id'], $list_user->user_email);
			} 
			*/
			}
		}
	}
}


//registers settings for JangoMail integration
function pmprojm_admin_init()
{
	//set up settings
	register_setting('pmprojm_options', 'pmprojm_options', 'pmprojm_options_validate');	
	add_settings_section('pmprojm_section_general', 'General Settings', 'pmprojm_section_general', 'pmprojm_options');	
	add_settings_field('pmprojm_option_username', 'JangoMail account username', 'pmprojm_option_jmusername', 'pmprojm_options', 'pmprojm_section_general');		
	add_settings_field('pmprojm_option_password', 'JangoMail account password', 'pmprojm_option_jmpassword', 'pmprojm_options', 'pmprojm_section_general');	
	
	//PMPro levels and JangoMail lists	
	add_settings_section('pmprojm_section_levels', 'Membership Levels and Lists', 'pmprojm_section_levels', 'pmprojm_options');		
	add_settings_field('pmprojm_option_jmusers_lists', 'Add every new user to:', 'pmprojm_option_jmusers_lists', 'pmprojm_options', 'pmprojm_section_levels');		
	
	//add options for levels
	pmprojm_getPMProLevels();
	global $pmprojm_levels;
	if(!empty($pmprojm_levels))
	{						
		foreach($pmprojm_levels as $level)
		{
			add_settings_field('pmprojm_option_memberships_lists_' . $level->id, 'Add new <b>' . $level->name . '</b> members to:', 'pmprojm_option_memberships_lists', 'pmprojm_options', 'pmprojm_section_levels', array($level));
		}
	}		
}
add_action("admin_init", "pmprojm_admin_init");

//set the pmprojm_levels array if PMPro is installed
function pmprojm_getPMProLevels()
{	
	global $pmprojm_levels, $wpdb;
	$pmprojm_levels = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_membership_levels ORDER BY id");			
}

//options sections
function pmprojm_section_general()
{	
?>
<p></p>	
<?php
}
//options sections
function pmprojm_section_levels()
{	
	global $wpdb, $pmprojm_levels;
	
	//do we have PMPro installed?
	if(class_exists("MemberOrder"))
	{
	?>
		<p>PMPro is installed.</p>
	<?php
		//do we have levels?
		if(empty($pmprojm_levels))
		{
		?>
		<p>Once you've <a href="admin.php?page=pmpro-membershiplevels">created some levels in Paid Memberships Pro</a>, you will be able to assign JangoMail lists to them here.</p>
		<?php
		}
		else
		{
		?>
		<p>For each membership level, choose the lists which new members should be subscribed to.</p>
		<p>When a user gets assigned to that membership level through purchase or by an admin, they will be added to the specified lists. However, if a user's subscription expires or an admin switches their membership level, they will not be removed from any JangoMail lists. That will need to be done manually through JangoMail. This works differently from the PMPro MailChimp integration, which auto-unsubscribes users from lists. This plugin does not unsubscribe anyone from any JangoMail lists.</p> 
		<?php
		}
	}
	else
	{
		//just deactivated or needs to be installed?
		if(file_exists(dirname(__FILE__) . "/../paid-memberships-pro/paid-memberships-pro.php"))
		{
			//just deactivated
			?>
			<p><a href="plugins.php?plugin_status=inactive">Activate Paid Memberships Pro</a> to use the integration with JangoMail lists.</p>
			<?php
		}
		else
		{
			//needs to be installed
			?>
			<p><a href="plugin-install.php?tab=search&type=term&s=paid+memberships+pro&plugin-search-input=Search+Plugins">Install Paid Memberships Pro</a> to add membership functionality to your site with integration to the JangoMail lists.</p>
			<?php
		}
	}
}

//options code
function pmprojm_option_jmusername()
{
	$options = get_option('pmprojm_options');		
	if(isset($options['jmusername']))
		$jmusername = $options['jmusername'];
	else
		$jmusername = "";
	echo "<input id='pmprojm_username' name='pmprojm_options[jmusername]' size='80' type='text' value='" . esc_attr($jmusername) . "' />";
}

function pmprojm_option_jmpassword()
{
	$options = get_option('pmprojm_options');		
	if(isset($options['jmpassword']))
		$jmpassword = $options['jmpassword'];
	else
		$jmpassword = "";
	echo "<input id='pmprojm_password' name='pmprojm_options[jmpassword]' size='80' type='password' value='" . esc_attr($jmpassword) . "' />";
}

function pmprojm_option_jmusers_lists()
{	
	global $pmprojm_lists;
	$options = get_option('pmprojm_options');
		
	if(isset($options['jmusers_lists']) && is_array($options['jmusers_lists']))
	{
		$selected_lists = $options['jmusers_lists'];
	}
	else
	{
		$selected_lists = array();
	}
	
	if(!empty($pmprojm_lists))
	{
		echo "<select multiple='yes' name=\"pmprojm_options[jmusers_lists][]\">";
		foreach($pmprojm_lists as $list)
		{
			echo $list;
			echo "<option value='" . $list['name'] . "' ";
			if(in_array($list['name'], $selected_lists))
				echo "selected='selected'";
			echo ">" . $list['name'] . "</option>";
		}
		echo "</select>";
	}
	else
	{
		echo "No lists found.";
	}	
}

function pmprojm_option_memberships_lists($level)
{	
	global $pmprojm_lists;
	$options = get_option('pmprojm_options');
	
	$level = $level[0];	//WP stores this in the first element of an array
		
	if(isset($options['level_' . $level->id . '_lists']) && is_array($options['level_' . $level->id . '_lists']))
		$selected_lists = $options['level_' . $level->id . '_lists'];
	else
		$selected_lists = array();
	
	if(!empty($pmprojm_lists))
	{
		echo "<select multiple='yes' name=\"pmprojm_options[level_" . $level->id . "_lists][]\">";
		foreach($pmprojm_lists as $list)
		{
			echo "<option value='" . $list['name'] . "' ";
			if(in_array($list['name'], $selected_lists))
				echo "selected='selected'";
			echo ">" . $list['name'] . "</option>";
		}
		echo "</select>";
	}
	else
	{
		echo "No lists found.";
	}	
}

// validate our options
function pmprojm_options_validate($input) 
{					
	//username and password
	$newinput['jmusername'] = trim(preg_replace("[^a-zA-Z0-9\-]", "", $input['jmusername']));		
	$newinput['jmpassword'] = trim(preg_replace("[^a-zA-Z0-9\-]", "", $input['jmpassword']));		
	
	//user lists
	if(!empty($input['jmusers_lists']) && is_array($input['jmusers_lists']))
	{
		$count = count($input['jmusers_lists']);
		for($i = 0; $i < $count; $i++)
			$newinput['jmusers_lists'][] = trim(preg_replace("[^a-zA-Z0-9\-]", "", $input['jmusers_lists'][$i]));	;
	}
	
	//membership lists
	global $pmprojm_levels;		
	if(!empty($pmprojm_levels))
	{
		foreach($pmprojm_levels as $level)
		{
			if(!empty($input['level_' . $level->id . '_lists']) && is_array($input['level_' . $level->id . '_lists']))
			{
				$count = count($input['level_' . $level->id . '_lists']);
				for($i = 0; $i < $count; $i++)
					$newinput['level_' . $level->id . '_lists'][] = trim(preg_replace("[^a-zA-Z0-9\-]", "", $input['level_' . $level->id . '_lists'][$i]));	;
			}
		}
	}
	
	return $newinput;
}	

// add the admin options page	
function pmprojm_admin_add_page() 
{
	add_options_page('PMPro JangoMail Options', 'PMPro JangoMail', 'manage_options', 'pmprojm_options', 'pmprojm_options_page');
}
add_action('admin_menu', 'pmprojm_admin_add_page');



//html for options page
function pmprojm_options_page()
{
	global $pmprojm_lists;
	// create a SoapClient object for the JangoMail API
	$client = new SoapClient('https://api.jangomail.com/api.asmx?WSDL');
	
	//check for a valid JangoMail account username and password 
	$options = get_option("pmprojm_options");	
	$username = $options['jmusername'];
	
	$password = $options['jmpassword'];

	
	if(!empty($username) || !empty($password))
	{
		/** Ping the JangoMail API to make sure the credentials are valid */
		$param=array(
			'Username'=>	$username,
			'Password'=>	$password
		); 
		
		try
		{
			$apiresponse = $client->AuthenticateUser($param);	
		}
		catch(SoapFault $e)
		{
			echo $client->__getLastRequest();
			$msg = sprintf( __( 'Sorry, but JangoMail was unable to verify your login credentials. Please try entering your JangoMail account username and password again.', 'pmpro-jangomail' ));
			$msgt = "error";
			add_settings_error( 'pmpro-jangomail', 'username-fail', $message, 'error' );
		}

			// set the parameter array for the Groups_GetList_String method
			$param=array(
			 'Username'=>	$username,
			 'Password'=>	$password,
			 'RowDelimiter'=>'|',
			 'ColDelimiter'=>',',
			 'TextQualifier'=>''
			); 
		try
		{
			$apiresponse = $client->Groups_GetList_String($param);	
			
			$listarray = explode('|', $apiresponse->Groups_GetList_StringResult);
			$i = 0;
			foreach ( $listarray as $list) {
				$subarray[$i] = explode(',', $list);
				$pmprojm_lists[$i]['id'] = $subarray[$i][0];
				$pmprojm_lists[$i]['name'] = $subarray[$i][1];
				$i++;
			}
		}

		catch(SoapFault $e)
		{
			echo $client->__getLastRequest();
			
		}				

		/** Save all of our new data */
		update_option( "pmprojm_all_lists", $pmprojm_lists);	
		
	}
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>PMPro JangoMail Integration Options</h2>		
	
	<?php if(!empty($msg)) { ?>
		<div class="message <?php echo $msgt; ?>"><p><?php echo $msg; ?></p></div>
	<?php } ?>
	
	<form action="options.php" method="post">
		
		<p>This plugin will integrate your site with JangoMail. You can choose one or more JangoMail lists to have users subscribed to when they sign up for a membership or are added to your site. Your JangoMail lists need to have custom fields called firstname and lastname added and then the user's name will be saved along with email address, otherwise only email address is saved in JangoMail.</p>
		<p>Be sure to turn on the JangoMail Prevent Duplicates setting on each of your lists or else this plugin may add the same person to the list more than once when you change their PMPro levels. We will try to update the plugin in the future to cover this.</p>

		
		<?php settings_fields('pmprojm_options'); ?>
		<?php do_settings_sections('pmprojm_options'); ?>

		<p><br /></p>
						
		<div class="bottom-buttons">
			<input type="hidden" name="pmprot_options[set]" value="1" />
			<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Settings'); ?>">				
		</div>
		
	</form>
</div>
<?php
}