<?php
/*
Plugin Name: Sync Facebook Events
Plugin URI: http://pdxt.com
Description: Sync Facebook Events to The Events Calendar Plugin
Author: Mark Nelson
Version: 1.0.1
Author URI: http://pdxt.com
*/
 

/*  Copyright 2011 PDX Technologies, LLC. (mark.nelson@pdxt.com)

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

function fbes_init(){
}

add_action("plugins_loaded", "fbes_init");

function fbes_add_page() {
	add_options_page('Sync FB Events', 'Sync FB Events', 8, __FILE__, 'fbes_options_page');
}
add_action('admin_menu', 'fbes_add_page');

function fbes_get_events($fbes_api_key, $fbes_api_secret, $fbes_api_uid) {
	
	require 'facebook.php';
	
	$facebook = new Facebook(array(
		'appId'  =>  $fbes_api_key,
		'secret' =>  $fbes_api_secret,
		'cookie' => true,
	));

	$fql = "SELECT eid, name, start_time, end_time, location, description
			FROM event WHERE eid IN ( SELECT eid FROM event_member WHERE uid = $fbes_api_uid ) 
			ORDER BY start_time desc";

	$param  =   array(
		'method'    => 'fql.query',
		'query'     => $fql,
		'callback'  => ''
	);

	$ret = $facebook->api($param);
	return $ret;
}

function fbes_options_page() {

	#Get option values
	$fbes_api_key = get_option('fbes_api_key');
	$fbes_api_secret = get_option('fbes_api_secret');
	$fbes_api_uid = get_option('fbes_api_uid');
	
	#Get new updated option values, and save them
	if( !empty($_POST['update']) ) {
	
		$fbes_api_key = $_POST['fbes_api_key'];
		update_option('fbes_api_key', $fbes_api_key);

		$fbes_api_secret = $_POST['fbes_api_secret'];
		update_option('fbes_api_secret', $fbes_api_secret);

		$fbes_api_uid = $_POST['fbes_api_uid'];
		update_option('fbes_api_uid', $fbes_api_uid);
		
		$events = fbes_get_events($fbes_api_key, $fbes_api_secret, $fbes_api_uid);

		$msg = "Syncronization Completed.";
?>
		<div id="message" class="updated fade"><p><strong><?php echo $msg; ?></strong></p></div>
<?php
	}
	
?>
	<div class="wrap">
	 	<br /><div class="icon32" id="icon-plugins"><br/></div>
		<h2 style="margin-bottom:10px;">Sync Facebook Events</h2>
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
		<input type="hidden" name="update" />
		<?php
		echo '<form action="'. $_SERVER["REQUEST_URI"] .'" method="post"><table style="width:500px;">'; 
		echo '<tr><td>Facebook App ID:</td><td><input type="text" id="fbes_api_key" name="fbes_api_key" value="'.htmlentities($fbes_api_key).'" size="35" /></td><tr>';
		echo '<tr><td>Facebook App Secret:</td><td><input type="text" id="fbes_api_secret" name="fbes_api_secret" value="'.htmlentities($fbes_api_secret) .'" size="35" /></td><tr>';
		echo '<tr><td>Facebook Events UID:</td><td><input type="text" id="fbes_api_uid" name="fbes_api_uid" value="'.htmlentities($fbes_api_uid) .'" size="15" /></td></tr>';
		echo '<tr><td colspan="2"></td></tr><tr><td colspan="2"><br /><input type="submit" value="Update" class="button-primary"';
		      echo ' name="update" /></td></tr></table>';
		?>
		</form>
	</div>
<?php if(isset($events)) { ?>
	<div style="margin-top:20px;font-size:14px;color:#444;border:1px solid #999;padding:15px;width:95%;font-face:couriernew;">
	<span style="color:red;">Updaing all facebook events...</span><br />
	<?
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
			
			//$args['EventStartDate'] = date("Y-m-d H:i", $event['start_time']);
			
			$args['EventStartDate'] = date("m/d/Y", $event['start_time']);
			$args['EventStartHour'] = date("H", $event['start_time']);
			$args['EventStartMinute'] = date("i", $event['start_time']);
			
			$args['EventEndDate'] = date("m/d/Y", $event['end_time']);
			$args['EventEndHour'] = date("H", $event['end_time']);
			$args['EventEndMinute'] = date("i", $event['end_time']);

			$args['post_content'] = $event['description'];
			$args['Venue']['Venue'] = $event['location'];
			
			$args['post_status'] = "Publish";
			$args['to_ping'] = $event['eid'];
		
			if (array_key_exists($event['eid'], $eids)) {
				tribe_update_event($args);
				$action = "Updating: ".$eids[$event['eid']];
			} else {
				$post_id = tribe_create_event($args);
				$action = "Inserting: ".$post_id;
			}
			reset($eids);
			
			print $action;
		}
		fclose($fp);
		print "<br />";
	?>
	<span style="color:red;">Events Calendar updated with current Facebook events.</span><br /><br />
	</div>
<? } ?>	
<?php	
}
?>