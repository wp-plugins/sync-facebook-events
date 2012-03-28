<?php
/*
Plugin Name: Sync Facebook Events
Plugin URI: http://pdxt.com
Description: Sync Facebook Events to The Events Calendar Plugin 
Author: Mark Nelson
Version: 1.0.4
Author URI: http://pdxt.com
*/
 
/*  Copyright 2012 PDX Technologies, LLC. (mark.nelson@pdxt.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

register_activation_hook(__FILE__,'activate_fbes');
register_deactivation_hook(__FILE__,'deactivate_fbes');
function activate_fbes() { wp_schedule_event(time(), 'daily', 'fbes_execute_sync'); }
function deactivate_fbes() { wp_clear_scheduled_hook('fbes_execute_sync'); }
add_action('fbes_execute_sync', 'fbes_process_events');

function update_schedule($fbes_frequency) {
	
		wp_clear_scheduled_hook('fbes_execute_sync');
		wp_schedule_event(time(), $fbes_frequency, 'fbes_execute_sync');
}

function fbes_add_page() { add_options_page('Sync FB Events', 'Sync FB Events', 8, __FILE__, 'fbes_options_page'); }
add_action('admin_menu', 'fbes_add_page');

function fbes_process_events() {

	#Get option values
	$fbes_api_key = get_option('fbes_api_key');
	$fbes_api_secret = get_option('fbes_api_secret');
	$fbes_api_uid = get_option('fbes_api_uid');
	$fbes_api_uids = get_option('fbes_api_uids');	
	$fbes_frequency = get_option('fbes_frequency');

	$events = fbes_get_events($fbes_api_key, $fbes_api_secret, $fbes_api_uids);
	fbes_send_events($events);
}

function fbes_get_events($fbes_api_key, $fbes_api_secret, $fbes_api_uids) {

	require 'facebook.php';
	
	$facebook = new Facebook(array(
		'appId'  =>  $fbes_api_key,
		'secret' =>  $fbes_api_secret,
		'cookie' => true,
	));

	$ret = array();
	foreach ($fbes_api_uids as $key => $value) {

		$fql = "SELECT eid, name, start_time, end_time, location, description
				FROM event WHERE eid IN ( SELECT eid FROM event_member WHERE uid = $value ) 
				ORDER BY start_time desc";

		$param  =   array(
			'method'    => 'fql.query',
			'query'     => $fql,
			'callback'  => ''
		);
	
		$result = $facebook->api($param);
		$ret = array_merge($ret, $result);
	}
	
		
	return $ret;
}

function fbes_send_events($events) {

	$i=0;
	$query = new WP_Query( 'post_type=tribe_events&showposts=99999' );
	foreach($query->posts as $post) {
		foreach($post as $key=>$value) {
			if($key=="to_ping") {
				$eids[$value] = $post->ID;
			}
		}
	}
	wp_reset_query();
		
	$i=0;
	foreach($events as $event) {
		
		$args['post_title'] = $event['name'];
		
		$offset = get_option('gmt_offset')*3600;
		
		$offsetStart = $event['start_time']+$offset;
		$offsetEnd = $event['end_time']+$offset;
				
		$args['EventStartDate'] = date("m/d/Y", $offsetStart);
		$args['EventStartHour'] = date("H", $offsetStart);
		$args['EventStartMinute'] = date("i", $offsetStart);
		
		$args['EventEndDate'] = date("m/d/Y", $offsetEnd);
		$args['EventEndHour'] = date("H", $offsetEnd);
		$args['EventEndMinute'] = date("i", $offsetEnd);

		$args['post_content'] = $event['description'];
		$args['Venue']['Venue'] = $event['location'];
		
		$args['post_status'] = "Publish";
		$args['to_ping'] = $event['eid'];
	
		if (array_key_exists($event['eid'], $eids)) {
			tribe_update_event($eids[$event['eid']],$args);
			$action = "Updating:".$eids[$event['eid']];
		} else {
			$post_id = tribe_create_event($args);
			$action = "Inserting:".$post_id;
		}
		reset($eids);
		
		print $action." ";
	}	
}

function fbes_options_page() {

	$fbes_api_uids = array();

	#Get option values
	$fbes_api_key = get_option('fbes_api_key');
	$fbes_api_secret = get_option('fbes_api_secret');
	$fbes_api_uid = get_option('fbes_api_uid');
	$fbes_api_uids = get_option('fbes_api_uids');
	$fbes_frequency = get_option('fbes_frequency');
	
	#Get new updated option values, and save them
	if( !empty($_POST['update']) ) {
	
		$fbes_api_key = $_POST['fbes_api_key'];
		update_option('fbes_api_key', $fbes_api_key);

		$fbes_api_secret = $_POST['fbes_api_secret'];
		update_option('fbes_api_secret', $fbes_api_secret);

		$fbes_api_uid = $_POST['fbes_api_uid'];
		update_option('fbes_api_uid', $fbes_api_uid);

		$fbes_frequency = $_POST['fbes_frequency'];
		update_option('fbes_frequency', $fbes_frequency);
		
		$events = fbes_get_events($fbes_api_key, $fbes_api_secret, $fbes_api_uids);

		update_schedule($fbes_frequency);

		$msg = "Syncronization of Events from Facebook Complete.";
?>
		<div id="message" class="updated fade"><p><strong><?php echo $msg; ?></strong></p></div>
<?php
	} elseif( !empty($_POST['add-uid']) ) {

		if(!in_array($_POST['fbes_api_uid'], $fbes_api_uids)) {
			$fbes_api_uids[] = $_POST['fbes_api_uid'];
			update_option('fbes_api_uids', $fbes_api_uids);
		}
		
	} elseif( !empty($_GET['r']) ) {
		
		foreach ($fbes_api_uids as $key => $value)
			if($fbes_api_uids[$key] == $_GET['r'])
				unset($fbes_api_uids[$key]);
				
		update_option('fbes_api_uids', $fbes_api_uids);
	}	
?>
	<div class="wrap">
	 	<br /><div class="icon32" id="icon-plugins"><br/></div>
		<h2 style="margin-bottom:10px;">Sync Facebook Events</h2>
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
		<input type="hidden" name="update" />
		<?php
		echo '<form action="'. $_SERVER["REQUEST_URI"] .'" method="post"><table style="width:475px;">'; 
		echo '<tr><td>Facebook App ID:</td><td><input type="text" id="fbes_api_key" name="fbes_api_key" value="'.htmlentities($fbes_api_key).'" size="35" /></td><tr>';
		echo '<tr><td>Facebook App Secret:</td><td><input type="text" id="fbes_api_secret" name="fbes_api_secret" value="'.htmlentities($fbes_api_secret) .'" size="35" /></td><tr>';

		echo '<tr><td>Update Fequency:</td><td><select id="fbes_frequency" name="fbes_frequency">';		
		if(htmlentities($fbes_frequency)=="daily") {
			echo '<option value="daily" SELECTED>Daily</option>';
		} else {
			echo '<option value="daily">Daily</option>';
		}	
		if(htmlentities($fbes_frequency)=="twicedaily") {
			echo '<option value="twicedaily" SELECTED>Twice Daily</option>';
		} else {
			echo '<option value="twicedaily">Twice Daily</option>';
		}
		if(htmlentities($fbes_frequency)=="hourly") {
			echo '<option value="hourly" SELECTED>Hourly</option>';
		} else {
			echo '<option value="hourly">Hourly</option>';
		}
		echo '</select>';
		
		echo '<tr><td>Add Facebook Page UID:</td><td><input type="text" id="fbes_api_uid" name="fbes_api_uid" value="" size="15" />';
		echo '<input type="submit" value="Add" class="button-secondary" name="add-uid" /></td></tr>';

		echo '<tr><td style="vertical-align:top;"></td><td>';

		foreach ($fbes_api_uids as $value) {
		    echo '&nbsp;&nbsp;'.$value.'&nbsp;&nbsp;<a href="'.$_SERVER["REQUEST_URI"].'&r='.$value.'">remove</a><br />';
		}
		
		echo '</td></tr>';
		
		echo '<tr><td colspan="2"></td></tr><tr><td colspan="2"><br /><input type="submit" value="Update" class="button-primary"';
		echo ' name="update" /></td></tr></table>';
		?>
		</form>
	</div>
	<?php if(isset($events)) { ?>
		<div style="margin-top:20px;font-size:14px;color:#444;border:1px solid #999;padding:15px;width:95%;font-face:couriernew;">
		<span style="color:red;">Updaing all facebook events...</span><br />
		<?php fbes_send_events($events); ?><br />
		<span style="color:red;">Events Calendar updated with current Facebook events.</span><br /><br />
		</div>
	<? } ?>
<?php	
}
?>