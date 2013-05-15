<?php
/**
 * MyBB 1.6
 * Copyright 2010 MyBB Group, All Rights Reserved
 *
 * Website: http://mybb.com
 * License: http://mybb.com/about/license
 *
 * $Id$
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// should also have a 'view coppa awaiting activation' view
require_once MYBB_ROOT."inc/functions_upload.php";


$page->add_breadcrumb_item($lang->users, "index.php?module=user-users");

if($mybb->input['action'] == "add" || $mybb->input['action'] == "merge" || $mybb->input['action'] == "search" || !$mybb->input['action'])
{
	$sub_tabs['browse_users'] = array(
		'title' => $lang->browse_users,
		'link' => "index.php?module=user-users",
		'description' => $lang->browse_users_desc
	);

	$sub_tabs['find_users'] = array(
		'title' => $lang->find_users,
		'link' => "index.php?module=user-users&amp;action=search",
		'description' => $lang->find_users_desc
	);

	$sub_tabs['create_user'] = array(
		'title' => $lang->create_user,
		'link' => "index.php?module=user-users&amp;action=add",
		'description' => $lang->create_user_desc
	);

	$sub_tabs['merge_users'] = array(
		'title' => $lang->merge_users,
		'link' => "index.php?module=user-users&amp;action=merge",
		'description' => $lang->merge_users_desc
	);
}

$user_view_fields = array(
	"avatar" => array(
		"title" => $lang->avatar,
		"width" => "24",
		"align" => ""
	),

	"username" => array(
		"title" => $lang->username,
		"width" => "",
		"align" => ""
	),

	"email" => array(
		"title" => $lang->email,
		"width" => "",
		"align" => "center"
	),

	"usergroup" => array(
		"title" => $lang->primary_group,
		"width" => "",
		"align" => "center"
	),

	"additionalgroups" => array(
		"title" => $lang->additional_groups,
		"width" => "",
		"align" => "center"
	),

	"regdate" => array(
		"title" => $lang->registered,
		"width" => "",
		"align" => "center"
	),

	"lastactive" => array(
		"title" => $lang->last_active,
		"width" => "",
		"align" => "center"
	),

	"postnum" => array(
		"title" => $lang->post_count,
		"width" => "",
		"align" => "center"
	),

	"reputation" => array(
		"title" => $lang->reputation,
		"width" => "",
		"align" => "center"
	),

	"warninglevel" => array(
		"title" => $lang->warning_level,
		"width" => "",
		"align" => "center"
	),

	"regip" => array(
		"title" => $lang->registration_ip,
		"width" => "",
		"align" => "center"
	),

	"lastip" => array(
		"title" => $lang->last_known_ip,
		"width" => "",
		"align" => "center"
	),

	"controls" => array(
		"title" => $lang->controls,
		"width" => "",
		"align" => "center"
	)
);

$sort_options = array(
	"username" => $lang->username,
	"regdate" => $lang->registration_date,
	"lastactive" => $lang->last_active,
	"numposts" => $lang->post_count,
	"reputation" => $lang->reputation,
	"warninglevel" => $lang->warning_level
);

$plugins->run_hooks("admin_user_users_begin");

// Initialise the views manager for user based views
require MYBB_ADMIN_DIR."inc/functions_view_manager.php";
if($mybb->input['action'] == "views")
{
	view_manager("index.php?module=user-users", "user", $user_view_fields, $sort_options, "user_search_conditions");
}

if($mybb->input['action'] == "avatar_gallery")
{
	$plugins->run_hooks("admin_user_users_avatar_gallery");

	$user = get_user($mybb->input['uid']);
	if(!$user['uid'])
	{
		exit;
	}

	// We've selected a new avatar for this user!
	if(isset($mybb->input['avatar']))
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			echo $lang->invalid_post_verify_key2;
			exit;
		}

		$mybb->input['avatar'] = str_replace(array("./", ".."), "", $mybb->input['avatar']);

		if(file_exists("../".$mybb->settings['avatardir']."/".$mybb->input['avatar']))
		{
			$dimensions = @getimagesize("../".$mybb->settings['avatardir']."/".$mybb->input['avatar']);
			$updated_avatar = array(
				"avatar" => $db->escape_string($mybb->settings['avatardir']."/".$mybb->input['avatar'].'?dateline='.TIME_NOW),
				"avatardimensions" => "{$dimensions[0]}|{$dimensions[1]}",
				"avatartype" => "gallery"
			);

			$db->update_query("users", $updated_avatar, "uid='".$user['uid']."'");

			$plugins->run_hooks("admin_user_users_avatar_gallery_commit");

			// Log admin action
			log_admin_action($user['uid'], $user['username']);
		}
		remove_avatars($user['uid']);
		// Now a tad of javascript to submit the parent window form
		echo "<script type=\"text/javascript\">window.parent.submitUserForm();</script>";
		exit;
	}

	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
	echo "<head profile=\"http://gmpg.org/xfn/1\">\n";
	echo "	<title>{$lang->avatar_gallery}</title>\n";
	echo "	<link rel=\"stylesheet\" href=\"styles/".$page->style."/main.css\" type=\"text/css\" />\n";
	echo "	<link rel=\"stylesheet\" href=\"styles/".$page->style."/avatar_gallery.css\" type=\"text/css\" />\n";
	echo "	<script type=\"text/javascript\" src=\"../jscripts/prototype.js\"></script>\n";
	echo "	<script type=\"text/javascript\" src=\"../jscripts/general.js\"></script>\n";
	echo "</head>\n";
	echo "<body id=\"avatar_gallery\">\n";

	// Sanitize incoming path if we have one
	$gallery = '';
	if(isset($mybb->input['gallery']))
	{
		$gallery = str_replace(array("..", "\x0"), "", $mybb->input['gallery']);
	}

	$breadcrumb = "<a href=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}\">Default Gallery</a>";

	$mybb->settings['avatardir'] = "../".$mybb->settings['avatardir'];

	if(!is_dir($mybb->settings['avatardir']) && is_dir(MYBB_ROOT."/images/avatars/"))
	{
		$mybb->settings['avatardir'] = "../images/avatars/";
	}

	// Within a gallery
	if(!empty($gallery))
	{
		$path = $gallery."/";
		$real_path = $mybb->settings['avatardir']."/".$path;
		if(is_dir($real_path))
		{
			// Build friendly gallery breadcrumb
			$gallery_path = explode("/", $gallery);
			foreach($gallery_path as $key => $url_bit)
			{
				if($breadcrumb_url) $breadcrumb_url .= "/";
				$breadcrumb_url .= $url_bit;
				$gallery_name = str_replace(array("_", "%20"), " ", $url_bit);
				$gallery_name = ucwords($gallery_name);

				if($gallery_path[$key+1])
				{
					$breadcrumb .= " &raquo; <a href=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}&amp;gallery={$breadcrumb_url}\">{$gallery_name}</a>";
				}
				else
				{
					$breadcrumb .= " &raquo; {$gallery_name}";
				}
			}
		}
		else
		{
			exit;
		}
	}
	else
	{
		$path = "";
		$real_path = $mybb->settings['avatardir'];
	}

	// Get a listing of avatars/directories within this gallery
	$sub_galleries = $avatars = array();
	$files = @scandir($real_path);

	if(is_array($files))
	{
		foreach($files as $file)
		{
			if($file == "." || $file == ".." || $file == ".svn")
			{
				continue;
			}

			// Build friendly name
			$friendly_name = str_replace(array("_", "%20"), " ", $file);
			$friendly_name = ucwords($friendly_name);
			if(is_dir($real_path."/".$file))
			{
				// Only add this gallery if there are avatars or galleries inside it (no empty directories!)
				$has = 0;
				$dh = @opendir($real_path."/".$file);
				while(false !== ($sub_file = readdir($dh)))
				{
					if(preg_match("#\.(jpg|jpeg|gif|bmp|png)$#i", $sub_file) || is_dir($real_path."/".$file."/".$sub_file))
					{
						$has = 1;
						break;
					}
				}
				@closedir($dh);
				if($has == 1)
				{
					$sub_galleries[] = array(
						"path" => $path.$file,
						"friendly_name" => $friendly_name
					);
				}
			}
			else if(preg_match("#\.(jpg|jpeg|gif|bmp|png)$#i", $file))
			{
				$friendly_name = preg_replace("#\.(jpg|jpeg|gif|bmp|png)$#i", "", $friendly_name);

				// Fetch dimensions
				$dimensions = @getimagesize($real_path."/".$file);

				$avatars[] = array(
					"path" => $path.$file,
					"friendly_name" => $friendly_name,
					"width" => $dimensions[0],
					"height" => $dimensions[1]
				);
			}
		}
	}

	require_once MYBB_ROOT."inc/functions_image.php";

	// Now we're done, we can simply show our gallery page
	echo "<div id=\"gallery_breadcrumb\">{$breadcrumb}</div>\n";
	echo "<div id=\"gallery\">\n";
	echo "<ul id=\"galleries\">\n";
	if(is_array($sub_galleries))
	{
		foreach($sub_galleries as $gallery)
		{
			if(!$gallery['thumb'])
			{
				$gallery['thumb'] = "styles/{$page->style}/images/avatar_gallery.gif";
				$gallery['thumb_width'] = 64;
				$gallery['thumb_height'] = 64;
			}
			else
			{
				$gallery['thumb'] = "{$mybb->settings['avatardir']}/{$gallery['thumb']}";
			}
			$scaled_dimensions = scale_image($gallery['thumb_width'], $gallery['thumb_height'], 80, 80);
			$top = ceil((80-$scaled_dimensions['height'])/2);
			$left = ceil((80-$scaled_dimensions['width'])/2);
			echo "<li><a href=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}&amp;gallery={$gallery['path']}\"><span class=\"image\"><img src=\"{$gallery['thumb']}\" alt=\"\" style=\"margin-top: {$top}px;\" height=\"{$scaled_dimensions['height']}\" width=\"{$scaled_dimensions['width']}\"></span><span class=\"title\">{$gallery['friendly_name']}</span></a></li>\n";
		}
	}
	echo "</ul>\n";
	// Build the list of any actual avatars we have
	echo "<ul id=\"avatars\">\n";
	if(is_array($avatars))
	{
		foreach($avatars as $avatar)
		{
			$scaled_dimensions = scale_image($avatar['width'], $avatar['height'], 80, 80);
			$top = ceil((80-$scaled_dimensions['height'])/2);
			$left = ceil((80-$scaled_dimensions['width'])/2);
			echo "<li><a href=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}&amp;avatar={$avatar['path']}&amp;my_post_key={$mybb->post_code}\"><span class=\"image\"><img src=\"{$mybb->settings['avatardir']}/{$avatar['path']}\" alt=\"\" style=\"margin-top: {$top}px;\" height=\"{$scaled_dimensions['height']}\" width=\"{$scaled_dimensions['width']}\" /></span><span class=\"title\">{$avatar['friendly_name']}</span></a></li>\n";
		}
	}
	echo "</ul>\n";
	echo "</div>";
	echo "</body>";
	echo "</html>";
	exit;
}

if($mybb->input['action'] == "activate_user")
{
	$plugins->run_hooks("admin_user_users_coppa_activate");

	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->invalid_post_verify_key2, 'error');
		admin_redirect("index.php?module=user-users");
	}

	$query = $db->simple_select("users", "*", "uid='".intval($mybb->input['uid'])."'");
	$user = $db->fetch_array($query);

	// Does the user not exist?
	if(!$user['uid'] || $user['usergroup'] != 5)
	{
		flash_message($lang->error_invalid_user, 'error');
		admin_redirect("index.php?module=user-users");
	}

	$updated_user['usergroup'] = $user['usergroup'];

	// Update
	if($user['coppauser'])
	{
		$updated_user = array(
			"coppauser" => 0
		);
	}
	else
	{
		$db->delete_query("awaitingactivation", "uid='{$user['uid']}'");
	}

	// Move out of awaiting activation if they're in it.
	if($user['usergroup'] == 5)
	{
		$updated_user['usergroup'] = 2;
	}

	$db->update_query("users", $updated_user, "uid='{$user['uid']}'");

	$plugins->run_hooks("admin_user_users_coppa_activate_commit");

	// Log admin action
	log_admin_action($user['uid'], $user['username']);

	if($mybb->input['from'] == "home")
	{
		if($user['coppauser'])
		{
			$message = $lang->success_coppa_activated;
		}
		else
		{
			$message = $lang->success_activated;
		}

		update_admin_session('flash_message2', array('message' => $message, 'type' => 'success'));
	}
	else
	{
		if($user['coppauser'])
		{
			flash_message($lang->success_coppa_activated, 'success');
		}
		else
		{
			flash_message($lang->success_activated, 'success');
		}
	}

	if($admin_session['data']['last_users_url'])
	{
		$url = $admin_session['data']['last_users_url'];
		update_admin_session('last_users_url', '');

		if($mybb->input['from'] == "home")
		{
			update_admin_session('from', 'home');
		}
	}
	else
	{
		$url = "index.php?module=user-users&action=edit&uid={$user['uid']}";
	}

	admin_redirect($url);
}

if($mybb->input['action'] == "add")
{
	$plugins->run_hooks("admin_user_users_add");

	if($mybb->request_method == "post")
	{
		// Determine the usergroup stuff
		if(is_array($mybb->input['additionalgroups']))
		{
			foreach($mybb->input['additionalgroups'] as $key => $gid)
			{
				if($gid == $mybb->input['usergroup'])
				{
					unset($mybb->input['additionalgroups'][$key]);
				}
			}
			$additionalgroups = implode(",", $mybb->input['additionalgroups']);
		}
		else
		{
			$additionalgroups = '';
		}

		// Set up user handler.
		require_once MYBB_ROOT."inc/datahandlers/user.php";
		$userhandler = new UserDataHandler('insert');

		// Set the data for the new user.
		$new_user = array(
			"uid" => $mybb->input['uid'],
			"username" => $mybb->input['username'],
			"password" => $mybb->input['password'],
			"password2" => $mybb->input['confirm_password'],
			"email" => $mybb->input['email'],
			"email2" => $mybb->input['email'],
			"usergroup" => $mybb->input['usergroup'],
			"additionalgroups" => $additionalgroups,
			"displaygroup" => $mybb->input['displaygroup'],
			"profile_fields" => $mybb->input['profile_fields'],
			"profile_fields_editable" => true,
		);

		// Set the data of the user in the datahandler.
		$userhandler->set_data($new_user);
		$errors = '';

		// Validate the user and get any errors that might have occurred.
		if(!$userhandler->validate_user())
		{
			$errors = $userhandler->get_friendly_errors();
		}
		else
		{
			$user_info = $userhandler->insert_user();

			$plugins->run_hooks("admin_user_users_add_commit");

			// Log admin action
			log_admin_action($user_info['uid'], $user_info['username']);

			flash_message($lang->success_user_created, 'success');
			admin_redirect("index.php?module=user-users&action=edit&uid={$user_info['uid']}");
		}
	}

	// Fetch custom profile fields - only need required profile fields here
	$query = $db->simple_select("profilefields", "*", "required=1", array('order_by' => 'disporder'));
	while($profile_field = $db->fetch_array($query))
	{
		$profile_fields['required'][] = $profile_field;
	}

	$page->add_breadcrumb_item($lang->create_user);
	$page->output_header($lang->create_user);

	$form = new Form("index.php?module=user-users&amp;action=add", "post");

	$page->output_nav_tabs($sub_tabs, 'create_user');

	// If we have any error messages, show them
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input = array(
			"usergroup" => 2
		);
	}

	$form_container = new FormContainer($lang->required_profile_info);
	$form_container->output_row($lang->username." <em>*</em>", "", $form->generate_text_box('username', $mybb->input['username'], array('id' => 'username')), 'username');
	$form_container->output_row($lang->password." <em>*</em>", "", $form->generate_password_box('password', $mybb->input['password'], array('id' => 'password', 'autocomplete' => 'off')), 'password');
	$form_container->output_row($lang->confirm_password." <em>*</em>", "", $form->generate_password_box('confirm_password', $mybb->input['confirm_password'], array('id' => 'confirm_new_password')), 'confirm_new_password');
	$form_container->output_row($lang->email_address." <em>*</em>", "", $form->generate_text_box('email', $mybb->input['email'], array('id' => 'email')), 'email');

	$display_group_options[0] = $lang->use_primary_user_group;
	$options = array();
	$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
	while($usergroup = $db->fetch_array($query))
	{
		$options[$usergroup['gid']] = $usergroup['title'];
		$display_group_options[$usergroup['gid']] = $usergroup['title'];
	}

	$form_container->output_row($lang->primary_user_group." <em>*</em>", "", $form->generate_select_box('usergroup', $options, $mybb->input['usergroup'], array('id' => 'usergroup')), 'usergroup');
	$form_container->output_row($lang->additional_user_groups, $lang->additional_user_groups_desc, $form->generate_select_box('additionalgroups[]', $options, $mybb->input['additionalgroups'], array('id' => 'additionalgroups', 'multiple' => true, 'size' => 5)), 'additionalgroups');
	$form_container->output_row($lang->display_user_group." <em>*</em>", "", $form->generate_select_box('displaygroup', $display_group_options, $mybb->input['displaygroup'], array('id' => 'displaygroup')), 'displaygroup');

	// Output custom profile fields - required
	output_custom_profile_fields($profile_fields['required'], $mybb->input['profile_fields'], $form_container, $form);

	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->save_user);
	$form->output_submit_wrapper($buttons);

	$form->end();
	$page->output_footer();
}

if($mybb->input['action'] == "edit")
{
	$plugins->run_hooks("admin_user_users_edit");

	$query = $db->simple_select("users", "*", "uid='".intval($mybb->input['uid'])."'");
	$user = $db->fetch_array($query);

	// Does the user not exist?
	if(!$user['uid'])
	{
		flash_message($lang->error_invalid_user, 'error');
		admin_redirect("index.php?module=user-users");
	}

	if($mybb->request_method == "post")
	{
		if(is_super_admin($mybb->input['uid']) && $mybb->user['uid'] != $mybb->input['uid'] && !is_super_admin($mybb->user['uid']))
		{
			flash_message($lang->error_no_perms_super_admin, 'error');
			admin_redirect("index.php?module=user-users");
		}

		// Determine the usergroup stuff
		if(is_array($mybb->input['additionalgroups']))
		{
			foreach($mybb->input['additionalgroups'] as $key => $gid)
			{
				if($gid == $mybb->input['usergroup'])
				{
					unset($mybb->input['additionalgroups'][$key]);
				}
			}
			$additionalgroups = implode(",", $mybb->input['additionalgroups']);
		}
		else
		{
			$additionalgroups = '';
		}

		// Set up user handler.
		require_once MYBB_ROOT."inc/datahandlers/user.php";
		$userhandler = new UserDataHandler('update');

		// Set the data for the new user.
		$updated_user = array(
			"uid" => $mybb->input['uid'],
			"username" => $mybb->input['username'],
			"email" => $mybb->input['email'],
			"email2" => $mybb->input['email'],
			"usergroup" => $mybb->input['usergroup'],
			"additionalgroups" => $additionalgroups,
			"displaygroup" => $mybb->input['displaygroup'],
			"postnum" => $mybb->input['postnum'],
			"usertitle" => $mybb->input['usertitle'],
			"timezone" => $mybb->input['timezone'],
			"language" => $mybb->input['language'],
			"profile_fields" => $mybb->input['profile_fields'],
			"profile_fields_editable" => true,
			"website" => $mybb->input['website'],
			"icq" => $mybb->input['icq'],
			"aim" => $mybb->input['aim'],
			"yahoo" => $mybb->input['yahoo'],
			"msn" => $mybb->input['msn'],
			"birthday" => array(
				"day" => $mybb->input['bday1'],
				"month" => $mybb->input['bday2'],
				"year" => $mybb->input['bday3']
			),
			"style" => $mybb->input['style'],
			"signature" => $mybb->input['signature'],
			"dateformat" => intval($mybb->input['dateformat']),
			"timeformat" => intval($mybb->input['timeformat']),
			"language" => $mybb->input['language'],
			"usernotes" => $mybb->input['usernotes']
		);

		if($user['usergroup'] == 5 && $mybb->input['usergroup'] != 5)
		{
			if($user['coppauser'] == 1)
			{
				$updated_user['coppa_user'] = 0;
			}
		}
		if($mybb->input['new_password'])
		{
			$updated_user['password'] = $mybb->input['new_password'];
			$updated_user['password2'] = $mybb->input['confirm_new_password'];
		}

		$updated_user['options'] = array(
			"allownotices" => $mybb->input['allownotices'],
			"hideemail" => $mybb->input['hideemail'],
			"subscriptionmethod" => $mybb->input['subscriptionmethod'],
			"invisible" => $mybb->input['invisible'],
			"dstcorrection" => $mybb->input['dstcorrection'],
			"threadmode" => $mybb->input['threadmode'],
			"showsigs" => $mybb->input['showsigs'],
			"showavatars" => $mybb->input['showavatars'],
			"showquickreply" => $mybb->input['showquickreply'],
			"receivepms" => $mybb->input['receivepms'],
			"receivefrombuddy" => $mybb->input['receivefrombuddy'],
			"pmnotice" => $mybb->input['pmnotice'],
			"daysprune" => $mybb->input['daysprune'],
			"showcodebuttons" => intval($mybb->input['showcodebuttons']),
			"pmnotify" => $mybb->input['pmnotify'],
			"showredirect" => $mybb->input['showredirect']
		);

		if($mybb->settings['usertppoptions'])
		{
			$updated_user['options']['tpp'] = intval($mybb->input['tpp']);
		}

		if($mybb->settings['userpppoptions'])
		{
			$updated_user['options']['ppp'] = intval($mybb->input['ppp']);
		}

		// Set the data of the user in the datahandler.
		$userhandler->set_data($updated_user);
		$errors = '';

		// Validate the user and get any errors that might have occurred.
		if(!$userhandler->validate_user())
		{
			$errors = $userhandler->get_friendly_errors();
		}
		else
		{
			// Are we removing an avatar from this user?
			if($mybb->input['remove_avatar'])
			{
				$extra_user_updates = array(
					"avatar" => "",
					"avatardimensions" => "",
					"avatartype" => ""
				);
				remove_avatars($user['uid']);
			}

			// Are we uploading a new avatar?
			if($_FILES['avatar_upload']['name'])
			{
				$avatar = upload_avatar($_FILES['avatar_upload'], $user['uid']);
				if($avatar['error'])
				{
					$errors = array($avatar['error']);
				}
				else
				{
					if($avatar['width'] > 0 && $avatar['height'] > 0)
					{
						$avatar_dimensions = $avatar['width']."|".$avatar['height'];
					}
					$extra_user_updates = array(
						"avatar" => $avatar['avatar'].'?dateline='.TIME_NOW,
						"avatardimensions" => $avatar_dimensions,
						"avatartype" => "upload"
					);
				}
			}
			// Are we setting a new avatar from a URL?
			else if($mybb->input['avatar_url'] && $mybb->input['avatar_url'] != $user['avatar'])
			{
				$mybb->input['avatar_url'] = preg_replace("#script:#i", "", $mybb->input['avatar_url']);
				$mybb->input['avatar_url'] = htmlspecialchars_uni($mybb->input['avatar_url']);
				$ext = get_extension($mybb->input['avatar_url']);

				// Copy the avatar to the local server (work around remote URL access disabled for getimagesize)
				$file = fetch_remote_file($mybb->input['avatar_url']);
				if(!$file)
				{
					$avatar_error = $lang->error_invalidavatarurl;
				}
				else
				{
					$tmp_name = "../".$mybb->settings['avataruploadpath']."/remote_".md5(random_str());
					$fp = @fopen($tmp_name, "wb");
					if(!$fp)
					{
						$avatar_error = $lang->error_invalidavatarurl;
					}
					else
					{
						fwrite($fp, $file);
						fclose($fp);
						list($width, $height, $type) = @getimagesize($tmp_name);
						@unlink($tmp_name);
						echo $type;
						if(!$type)
						{
							$avatar_error = $lang->error_invalidavatarurl;
						}
					}
				}

				if(empty($avatar_error))
				{
					if($width && $height && $mybb->settings['maxavatardims'] != "")
					{
						list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['maxavatardims']));
						if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
						{
							$lang->error_avatartoobig = $lang->sprintf($lang->error_avatartoobig, $maxwidth, $maxheight);
							$avatar_error = $lang->error_avatartoobig;
						}
					}
				}

				if(empty($avatar_error))
				{
					if($width > 0 && $height > 0)
					{
						$avatar_dimensions = intval($width)."|".intval($height);
					}
					$extra_user_updates = array(
						"avatar" => $db->escape_string($mybb->input['avatar_url'].'?dateline='.TIME_NOW),
						"avatardimensions" => $avatar_dimensions,
						"avatartype" => "remote"
					);
					remove_avatars($user['uid']);
				}
				else
				{
					$errors = array($avatar_error);
				}
			}

			// Moderator "Options" (suspend signature, suspend/moderate posting)
			$moderator_options = array(
				1 => array(
					"action" => "suspendsignature", // The moderator action we're performing
					"period" => "action_period", // The time period we've selected from the dropdown box
					"time" => "action_time", // The time we've entered
					"update_field" => "suspendsignature", // The field in the database to update if true
					"update_length" => "suspendsigtime" // The length of suspension field in the database
				),
				2 => array(
					"action" => "moderateposting",
					"period" => "modpost_period",
					"time" => "modpost_time",
					"update_field" => "moderateposts",
					"update_length" => "moderationtime"
				),
				3 => array(
					"action" => "suspendposting",
					"period" => "suspost_period",
					"time" => "suspost_time",
					"update_field" => "suspendposting",
					"update_length" => "suspensiontime"
				)
			);

			require_once MYBB_ROOT."inc/functions_warnings.php";
			foreach($moderator_options as $option)
			{
				if(!$mybb->input[$option['action']])
				{
					if($user[$option['update_field']] == 1)
					{
						// We're revoking the suspension
						$extra_user_updates[$option['update_field']] = 0;
						$extra_user_updates[$option['update_length']] = 0;
					}

					// Skip this option if we haven't selected it
					continue;
				}

				if($mybb->input[$option['action']])
				{
					if(intval($mybb->input[$option['time']]) == 0 && $mybb->input[$option['period']] != "never" && $user[$option['update_field']] != 1)
					{
						// User has selected a type of ban, but not entered a valid time frame
						$string = $option['action']."_error";
						$errors[] = $lang->$string;
					}

					if(!is_array($errors))
					{
						$suspend_length = fetch_time_length(intval($mybb->input[$option['time']]), $mybb->input[$option['period']]);

						if($user[$option['update_field']] == 1 && ($mybb->input[$option['time']] || $mybb->input[$option['period']] == "never"))
						{
							// We already have a suspension, but entered a new time
							if($suspend_length == "-1")
							{
								// Permanent ban on action
								$extra_user_updates[$option['update_length']] = 0;
							}
							elseif($suspend_length && $suspend_length != "-1")
							{
								// Temporary ban on action
								$extra_user_updates[$option['update_length']] = TIME_NOW + $suspend_length;
							}
						}
						elseif(!$user[$option['update_field']])
						{
							// New suspension for this user... bad user!
							$extra_user_updates[$option['update_field']] = 1;
							if($suspend_length == "-1")
							{
								$extra_user_updates[$option['update_length']] = 0;
							}
							else
							{
								$extra_user_updates[$option['update_length']] = TIME_NOW + $suspend_length;
							}
						}
					}
				}
			}

			if($extra_user_updates['moderateposts'] && $extra_user_updates['suspendposting'])
			{
				$errors[] = $lang->suspendmoderate_error;
			}

			if(!$errors)
			{
				$user_info = $userhandler->update_user();
				$db->update_query("users", $extra_user_updates, "uid='{$user['uid']}'");

				// if we're updating the user's signature preferences, do so now
				if($mybb->input['update_posts'] == 'enable' || $mybb->input['update_posts'] == 'disable')
				{
					$update_signature = array(
						'includesig' => ($mybb->input['update_posts'] == 'enable' ? 1 : 0)
					);
					$db->update_query("posts", $update_signature, "uid='{$user['uid']}'");
				}

				$plugins->run_hooks("admin_user_users_edit_commit");

				// Log admin action
				log_admin_action($user['uid'], $mybb->input['username']);

				flash_message($lang->success_user_updated, 'success');
				admin_redirect("index.php?module=user-users");
			}
		}
	}

	if(!$errors)
	{
		$user['usertitle'] = htmlspecialchars_decode($user['usertitle']);
		$mybb->input = $user;

		$options = array(
			'bday1', 'bday2', 'bday3',
			'new_password', 'confirm_new_password',
			'action_time', 'action_period',
			'modpost_period', 'moderateposting', 'modpost_time', 'suspost_period', 'suspost_time'
		);

		foreach($options as $option)
		{
			if(!isset($mybb->input[$option]))
			{
				$mybb->input[$option] = '';
			}
		}

		// We need to fetch this users profile field values
		$query = $db->simple_select("userfields", "*", "ufid='{$user['uid']}'");
		$mybb->input['profile_fields'] = $db->fetch_array($query);
	}

	if($mybb->input['bday1'] || $mybb->input['bday2'] || $mybb->input['bday3'])
	{
		$mybb->input['bday'][0] = $mybb->input['bday1'];
		$mybb->input['bday'][1] = $mybb->input['bday2'];
		$mybb->input['bday'][2] = intval($mybb->input['bday3']);
	}
	else
	{
		$mybb->input['bday'] = array(0, 0, '');

		if($user['birthday'])
		{
			$mybb->input['bday'] = explode('-', $user['birthday']);
		}
	}

	// Fetch custom profile fields
	$query = $db->simple_select("profilefields", "*", "", array('order_by' => 'disporder'));
	while($profile_field = $db->fetch_array($query))
	{
		if($profile_field['required'] == 1)
		{
			$profile_fields['required'][] = $profile_field;
		}
		else
		{
			$profile_fields['optional'][] = $profile_field;
		}
	}

	$page->add_breadcrumb_item($lang->edit_user.": ".htmlspecialchars_uni($user['username']));
	$page->output_header($lang->edit_user);

	$sub_tabs['edit_user'] = array(
		'title' => $lang->edit_user,
		'description' => $lang->edit_user_desc
	);

	$form = new Form("index.php?module=user-users&amp;action=edit&amp;uid={$user['uid']}", "post", "", 1);
	echo "<script type=\"text/javascript\">\n function submitUserForm() { $('tab_overview').up('FORM').submit(); }</script>\n";

	$page->output_nav_tabs($sub_tabs, 'edit_user');

	// If we have any error messages, show them
	if($errors)
	{
		$page->output_inline_error($errors);
	}

	// Is this user a COPPA user? We show a warning & activate link
	if($user['coppauser'])
	{
		echo $lang->sprintf($lang->warning_coppa_user, $user['uid']);
	}

	$tabs = array(
		"overview" => $lang->overview,
		"profile" => $lang->profile,
		"settings" => $lang->account_settings,
		"signature" => $lang->signature,
		"avatar" => $lang->avatar,
		"modoptions" => $lang->mod_options
	);
	$page->output_tab_control($tabs);

	//
	// OVERVIEW
	//
	echo "<div id=\"tab_overview\">\n";
	$table = new Table;
	$table->construct_header($lang->avatar, array('class' => 'align_center'));
	$table->construct_header($lang->general_account_stats, array('colspan' => '2', 'class' => 'align_center'));

	// Avatar
	$avatar_dimensions = explode("|", $user['avatardimensions']);
	if($user['avatar'])
	{
		if($user['avatardimensions'])
		{
			require_once MYBB_ROOT."inc/functions_image.php";
			list($width, $height) = explode("|", $user['avatardimensions']);
			$scaled_dimensions = scale_image($width, $height, 120, 120);
		}
		else
		{
			$scaled_dimensions = array(
				"width" => 120,
				"height" => 120
			);
		}
		if (!stristr($user['avatar'], 'http://'))
		{
			$user['avatar'] = "../{$user['avatar']}\n";
		}
	}
	else
	{
		$user['avatar'] = "styles/{$page->style}/images/default_avatar.gif";
		$scaled_dimensions = array(
			"width" => 120,
			"height" => 120
		);
	}
	$avatar_top = ceil((126-$scaled_dimensions['height'])/2);
	if($user['lastactive'])
	{
		$last_active = my_date($mybb->settings['dateformat'], $user['lastactive']).", ".my_date($mybb->settings['timeformat'], $user['lastactive']);
	}
	else
	{
		$last_active = $lang->never;
	}
	$reg_date = my_date($mybb->settings['dateformat'], $user['regdate']).", ".my_date($mybb->settings['timeformat'], $user['regdate']);
	if($user['dst'] == 1)
	{
		$timezone = $user['timezone']+1;
	}
	else
	{
		$timezone = $user['timezone'];
	}
	$local_time = gmdate($mybb->settings['dateformat'], TIME_NOW + ($timezone * 3600)).", ".gmdate($mybb->settings['timeformat'], TIME_NOW + ($timezone * 3600));
	$days_registered = (TIME_NOW - $user['regdate']) / (24*3600);
	$posts_per_day = 0;
	if($days_registered > 0)
	{
		$posts_per_day = round($user['postnum'] / $days_registered, 2);
		if($posts_per_day > $user['postnum'])
		{
			$posts_per_day = $user['postnum'];
		}
	}
	$stats = $cache->read("stats");
	$posts = $stats['numposts'];
	if($posts == 0)
	{
		$percent_posts = "0";
	}
	else
	{
		$percent_posts = round($user['postnum']*100/$posts, 2);
	}

	$user_permissions = user_permissions($user['uid']);

	// Fetch the reputation for this user
	if($user_permissions['usereputationsystem'] == 1 && $mybb->settings['enablereputation'] == 1)
	{
		$reputation = get_reputation($user['reputation']);
	}
	else
	{
		$reputation = "-";
	}

	if($mybb->settings['enablewarningsystem'] != 0 && $user_permissions['canreceivewarnings'] != 0)
	{
		$warning_level = round($user['warningpoints']/$mybb->settings['maxwarningpoints']*100);
		if($warning_level > 100)
		{
			$warning_level = 100;
		}
		$warning_level = get_colored_warning_level($warning_level);
	}

	$age = $lang->na;
	if($user['birthday'])
	{
		$age = get_age($user['birthday']);
	}

	$table->construct_cell("<div style=\"width: 126px; height: 126px;\" class=\"user_avatar\"><img src=\"".htmlspecialchars_uni($user['avatar'])."\" style=\"margin-top: {$avatar_top}px\" width=\"{$scaled_dimensions['width']}\" height=\"{$scaled_dimensions['height']}\" alt=\"\" /></div>", array('rowspan' => 6, 'width' => 1));
	$table->construct_cell("<strong>{$lang->email_address}:</strong> <a href=\"mailto:".htmlspecialchars_uni($user['email'])."\">".htmlspecialchars_uni($user['email'])."</a>");
	$table->construct_cell("<strong>{$lang->last_active}:</strong> {$last_active}");
	$table->construct_row();
	$table->construct_cell("<strong>{$lang->registration_date}:</strong> {$reg_date}");
	$table->construct_cell("<strong>{$lang->local_time}:</strong> {$local_time}");
	$table->construct_row();
	$table->construct_cell("<strong>{$lang->posts}:</strong> {$user['postnum']}");
	$table->construct_cell("<strong>{$lang->age}:</strong> {$age}");
	$table->construct_row();
	$table->construct_cell("<strong>{$lang->posts_per_day}:</strong> {$posts_per_day}");
	$table->construct_cell("<strong>{$lang->reputation}:</strong> {$reputation}");
	$table->construct_row();
	$table->construct_cell("<strong>{$lang->percent_of_total_posts}:</strong> {$percent_posts}");
	$table->construct_cell("<strong>{$lang->warning_level}:</strong> {$warning_level}");
	$table->construct_row();
	$table->construct_cell("<strong>{$lang->registration_ip}:</strong> {$user['regip']}");
	$table->construct_cell("<strong>{$lang->last_known_ip}:</strong> {$user['lastip']}");
	$table->construct_row();

	$table->output("{$lang->user_overview}: {$user['username']}");
	echo "</div>\n";

	//
	// PROFILE
	//
	echo "<div id=\"tab_profile\">\n";

	$form_container = new FormContainer($lang->required_profile_info.": {$user['username']}");
	$form_container->output_row($lang->username." <em>*</em>", "", $form->generate_text_box('username', $mybb->input['username'], array('id' => 'username')), 'username');
	$form_container->output_row($lang->new_password, $lang->new_password_desc, $form->generate_password_box('new_password', $mybb->input['new_password'], array('id' => 'new_password', 'autocomplete' => 'off')), 'new_password');
	$form_container->output_row($lang->confirm_new_password, $lang->new_password_desc, $form->generate_password_box('confirm_new_password', $mybb->input['confirm_new_password'], array('id' => 'confirm_new_password')), 'confirm_new_password');
	$form_container->output_row($lang->email_address." <em>*</em>", "", $form->generate_text_box('email', $mybb->input['email'], array('id' => 'email')), 'email');

	$display_group_options[0] = $lang->use_primary_user_group;
	$options = array();
	$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
	while($usergroup = $db->fetch_array($query))
	{
		$options[$usergroup['gid']] = $usergroup['title'];
		$display_group_options[$usergroup['gid']] = $usergroup['title'];
	}

	if(!is_array($mybb->input['additionalgroups']))
	{
		$mybb->input['additionalgroups'] = explode(',', $mybb->input['additionalgroups']);
	}

	$form_container->output_row($lang->primary_user_group." <em>*</em>", "", $form->generate_select_box('usergroup', $options, $mybb->input['usergroup'], array('id' => 'usergroup')), 'usergroup');
	$form_container->output_row($lang->additional_user_groups, $lang->additional_user_groups_desc, $form->generate_select_box('additionalgroups[]', $options, $mybb->input['additionalgroups'], array('id' => 'additionalgroups', 'multiple' => true, 'size' => 5)), 'additionalgroups');
	$form_container->output_row($lang->display_user_group." <em>*</em>", "", $form->generate_select_box('displaygroup', $display_group_options, $mybb->input['displaygroup'], array('id' => 'displaygroup')), 'displaygroup');
	$form_container->output_row($lang->post_count." <em>*</em>", "", $form->generate_text_box('postnum', $mybb->input['postnum'], array('id' => 'postnum')), 'postnum');

	// Output custom profile fields - required
	if(!isset($profile_fields['required']))
	{
		$profile_fields['required'] = array();
	}
	output_custom_profile_fields($profile_fields['required'], $mybb->input['profile_fields'], $form_container, $form);

	$form_container->end();

	$form_container = new FormContainer($lang->optional_profile_info.": {$user['username']}");
	$form_container->output_row($lang->custom_user_title, $lang->custom_user_title_desc, $form->generate_text_box('usertitle', $mybb->input['usertitle'], array('id' => 'usertitle')), 'usertitle');
	$form_container->output_row($lang->website, "", $form->generate_text_box('website', $mybb->input['website'], array('id' => 'website')), 'website');
	$form_container->output_row($lang->icq_number, "", $form->generate_text_box('icq', $mybb->input['icq'], array('id' => 'icq')), 'icq');
	$form_container->output_row($lang->aim_handle, "", $form->generate_text_box('aim', $mybb->input['aim'], array('id' => 'aim')), 'aim');
	$form_container->output_row($lang->yahoo_messanger_handle, "", $form->generate_text_box('yahoo', $mybb->input['yahoo'], array('id' => 'yahoo')), 'yahoo');
	$form_container->output_row($lang->msn_messanger_handle, "", $form->generate_text_box('msn', $mybb->input['msn'], array('id' => 'msn')), 'msn');

	// Birthday
	$birthday_days = array(0 => '');
	for($i = 1; $i <= 31; $i++)
	{
		$birthday_days[$i] = $i;
	}

	$birthday_months = array(
		0 => '',
		1 => $lang->january,
		2 => $lang->february,
		3 => $lang->march,
		4 => $lang->april,
		5 => $lang->may,
		6 => $lang->june,
		7 => $lang->july,
		8 => $lang->august,
		9 => $lang->september,
		10 => $lang->october,
		11 => $lang->november,
		12 => $lang->december
	);

	$birthday_row = $form->generate_select_box('bday1', $birthday_days, $mybb->input['bday'][0], array('id' => 'bday_day'));
	$birthday_row .= ' '.$form->generate_select_box('bday2', $birthday_months, $mybb->input['bday'][1], array('id' => 'bday_month'));
	$birthday_row .= ' '.$form->generate_text_box('bday3', $mybb->input['bday'][2], array('id' => 'bday_year', 'style' => 'width: 3em;'));

	$form_container->output_row($lang->birthday, "", $birthday_row, 'birthday');

	// Output custom profile fields - optional
	output_custom_profile_fields($profile_fields['optional'], $mybb->input['profile_fields'], $form_container, $form);

	$form_container->end();
	echo "</div>\n";

	//
	// ACCOUNT SETTINGS
	//

	// Plugin hook note - we should add hooks in above each output_row for the below so users can add their own options to each group :>

	echo "<div id=\"tab_settings\">\n";
	$form_container = new FormContainer($lang->account_settings.": {$user['username']}");
	$login_options = array(
		$form->generate_check_box("invisible", 1, $lang->hide_from_whos_online, array("checked" => $mybb->input['invisible'])),
	);
	$form_container->output_row($lang->login_cookies_privacy, "", "<div class=\"user_settings_bit\">".implode("</div><div class=\"user_settings_bit\">", $login_options)."</div>");

	if($mybb->input['pmnotice'] > 1)
	{
		$mybb->input['pmnotice'] = 1;
	}

	$messaging_options = array(
		$form->generate_check_box("allownotices", 1, $lang->recieve_admin_emails, array("checked" => $mybb->input['allownotices'])),
		$form->generate_check_box("hideemail", 1, $lang->hide_email_from_others, array("checked" => $mybb->input['hideemail'])),
		$form->generate_check_box("receivepms", 1, $lang->recieve_pms_from_others, array("checked" => $mybb->input['receivepms'])),
		$form->generate_check_box("receivefrombuddy", 1, $lang->recieve_pms_from_buddy, array("checked" => $mybb->input['receivefrombuddy'])),
		$form->generate_check_box("pmnotice", 1, $lang->alert_new_pms, array("checked" => $mybb->input['pmnotice'])),
		$form->generate_check_box("pmnotify", 1, $lang->email_notify_new_pms, array("checked" => $mybb->input['pmnotify'])),
		"<label for=\"subscriptionmethod\">{$lang->default_thread_subscription_mode}:</label><br />".$form->generate_select_box("subscriptionmethod", array($lang->do_not_subscribe, $lang->no_email_notification, $lang->instant_email_notification), $mybb->input['subscriptionmethod'], array('id' => 'subscriptionmethod'))
	);
	$form_container->output_row($lang->messaging_and_notification, "", "<div class=\"user_settings_bit\">".implode("</div><div class=\"user_settings_bit\">", $messaging_options)."</div>");

	$date_format_options = array($lang->use_default);
	foreach($date_formats as $key => $format)
	{
		$date_format_options[$key] = my_date($format, TIME_NOW, "", 0);
	}

	$time_format_options = array($lang->use_default);
	foreach($time_formats as $key => $format)
	{
		$time_format_options[$key] = my_date($format, TIME_NOW, "", 0);
	}

	$date_options = array(
		"<label for=\"dateformat\">{$lang->date_format}:</label><br />".$form->generate_select_box("dateformat", $date_format_options, $mybb->input['dateformat'], array('id' => 'dateformat')),
		"<label for=\"dateformat\">{$lang->time_format}:</label><br />".$form->generate_select_box("timeformat", $time_format_options, $mybb->input['timeformat'], array('id' => 'timeformat')),
		"<label for=\"timezone\">{$lang->time_zone}:</label><br />".build_timezone_select("timezone", $mybb->input['timezone']),
		"<label for=\"dstcorrection\">{$lang->daylight_savings_time_correction}:</label><br />".$form->generate_select_box("dstcorrection", array(2 => $lang->automatically_detect, 1 => $lang->always_use_dst_correction, 0 => $lang->never_use_dst_correction), $mybb->input['dstcorrection'], array('id' => 'dstcorrection'))
	);
	$form_container->output_row($lang->date_and_time_options, "", "<div class=\"user_settings_bit\">".implode("</div><div class=\"user_settings_bit\">", $date_options)."</div>");


	$tpp_options = array($lang->use_default);
	if($mybb->settings['usertppoptions'])
	{
		$explodedtpp = explode(",", $mybb->settings['usertppoptions']);
		if(is_array($explodedtpp))
		{
			foreach($explodedtpp as $tpp)
			{
				if($tpp <= 0) continue;
				$tpp_options[$tpp] = $tpp;
			}
		}
	}

	$thread_age_options = array(
		0 => $lang->use_default,
		1 => $lang->show_threads_last_day,
		5 => $lang->show_threads_last_5_days,
		10 => $lang->show_threads_last_10_days,
		20 => $lang->show_threads_last_20_days,
		50 => $lang->show_threads_last_50_days,
		75 => $lang->show_threads_last_75_days,
		100 => $lang->show_threads_last_100_days,
		365 => $lang->show_threads_last_year,
		9999 => $lang->show_all_threads
	);

	$forum_options = array(
		"<label for=\"tpp\">{$lang->threads_per_page}:</label><br />".$form->generate_select_box("tpp", $tpp_options, $mybb->input['tpp'], array('id' => 'tpp')),
		"<label for=\"daysprune\">{$lang->default_thread_age_view}:</label><br />".$form->generate_select_box("daysprune", $thread_age_options, $mybb->input['daysprune'], array('id' => 'daysprune'))
	);
	$form_container->output_row($lang->forum_display_options, "", "<div class=\"user_settings_bit\">".implode("</div><div class=\"user_settings_bit\">", $forum_options)."</div>");

	$ppp_options = array($lang->use_default);
	if($mybb->settings['userpppoptions'])
	{
		$explodedppp = explode(",", $mybb->settings['userpppoptions']);
		if(is_array($explodedppp))
		{
			foreach($explodedppp as $ppp)
			{
				if($ppp <= 0) continue;
				$ppp_options[$ppp] = $ppp;
			}
		}
	}

	$thread_options = array(
		$form->generate_check_box("showsigs", 1, $lang->display_users_sigs, array("checked" => $mybb->input['showsigs'])),
		$form->generate_check_box("showavatars", 1, $lang->display_users_avatars, array("checked" => $mybb->input['showavatars'])),
		$form->generate_check_box("showquickreply", 1, $lang->show_quick_reply, array("checked" => $mybb->input['showquickreply'])),
		"<label for=\"ppp\">{$lang->posts_per_page}:</label><br />".$form->generate_select_box("ppp", $ppp_options, $mybb->input['ppp'], array('id' => 'ppp')),
		"<label for=\"threadmode\">{$lang->default_thread_view_mode}:</label><br />".$form->generate_select_box("threadmode", array("" => $lang->use_default, "linear" => $lang->linear_mode, "threaded" => $lang->threaded_mode), $mybb->input['threadmode'], array('id' => 'threadmode'))
	);
	$form_container->output_row($lang->thread_view_options, "", "<div class=\"user_settings_bit\">".implode("</div><div class=\"user_settings_bit\">", $thread_options)."</div>");

	$languages = array_merge(array('' => $lang->use_default), $lang->get_languages());

	$other_options = array(
		$form->generate_check_box("showredirect", 1, $lang->show_redirect, array("checked" => $mybb->input['showredirect'])),
		$form->generate_check_box("showcodebuttons", "1", $lang->show_code_buttons, array("checked" => $mybb->input['showcodebuttons'])),
		"<label for=\"style\">{$lang->theme}:</label><br />".build_theme_select("style", $mybb->input['style'], 0, "", true),
		"<label for=\"language\">{$lang->board_language}:</label><br />".$form->generate_select_box("language", $languages, $mybb->input['language'], array('id' => 'language'))
	);
	$form_container->output_row($lang->other_options, "", "<div class=\"user_settings_bit\">".implode("</div><div class=\"user_settings_bit\">", $other_options)."</div>");

	$form_container->end();
	echo "</div>\n";

	//
	// SIGNATURE EDITOR
	//
	$signature_editor = $form->generate_text_area("signature", $mybb->input['signature'], array('id' => 'signature', 'rows' => 15, 'cols' => '70', 'style' => 'width: 95%'));
	$sig_smilies = $lang->off;
	if($mybb->settings['sigsmilies'] == 1)
	{
		$sig_smilies = $lang->on;
	}
	$sig_mycode = $lang->off;
	if($mybb->settings['sigmycode'] == 1)
	{
		$sig_mycode = $lang->on;
		$signature_editor .= build_mycode_inserter("signature");
	}
	$sig_html = $lang->off;
	if($mybb->settings['sightml'] == 1)
	{
		$sig_html = $lang->on;
	}
	$sig_imgcode = $lang->off;
	if($mybb->settings['sigimgcode'] == 1)
	{
		$sig_imgcode = $lang->on;
	}
	echo "<div id=\"tab_signature\">\n";
	$form_container = new FormContainer("{$lang->signature}: {$user['username']}");
	$form_container->output_row($lang->signature, $lang->sprintf($lang->signature_desc, $sig_mycode, $sig_smilies, $sig_imgcode, $sig_html), $signature_editor, 'signature');

	$periods = array(
		"hours" => $lang->expire_hours,
		"days" => $lang->expire_days,
		"weeks" => $lang->expire_weeks,
		"months" => $lang->expire_months,
		"never" => $lang->expire_permanent
	);

	// Are we already suspending the signature?
	if($mybb->input['suspendsignature'])
	{
		$sig_checked = 1;

		// Display how much time is left on the ban for the user to extend it
		if($user['suspendsigtime'] == "0")
		{
			// Permanent
			$lang->suspend_expire_info = $lang->suspend_sig_perm;
		}
		else
		{
			// There's a limit to the suspension!
			$expired = my_date($mybb->settings['dateformat'], $user['suspendsigtime'])." @ ".my_date($mybb->settings['timeformat'], $user['suspendsigtime']);
			$lang->suspend_expire_info = $lang->sprintf($lang->suspend_expire_info, $expired);
		}
		$user_suspend_info = '
				<tr>
					<td colspan="2">'.$lang->suspend_expire_info.'<br />'.$lang->suspend_sig_extend.'</td>
				</tr>';
	}
	else
	{
		$sig_checked = 0;
		$user_suspend_info = '';
	}

	$actions = '
	<script type="text/javascript">
	<!--
		var sig_checked = "'.$sig_checked.'";

		function toggleAction()
		{
			if($("suspend_action").visible() == true)
			{
				$("suspend_action").hide();
			}
			else
			{
				$("suspend_action").show();
			}
		}
	// -->
	</script>

	<dl style="margin-top: 0; margin-bottom: 0; width: 100%;">
		<dt>'.$form->generate_check_box("suspendsignature", 1, $lang->suspend_sig_box, array('checked' => $sig_checked, 'onclick' => 'toggleAction();')).'</dt>
		<dd style="margin-top: 4px;" id="suspend_action" class="actions">
			<table cellpadding="4">'.$user_suspend_info.'
				<tr>
					<td width="30%"><small>'.$lang->expire_length.'</small></td>
					<td>'.$form->generate_text_box('action_time', $mybb->input['action_time'], array('style' => 'width: 2em;')).' '.$form->generate_select_box('action_period', $periods, $mybb->input['action_period']).'</td>
				</tr>
			</table>
		</dd>
	</dl>

	<script type="text/javascript">
	<!--
		if(sig_checked == 0)
		{
			$("suspend_action").hide();
		}
	// -->
	</script>';

	$form_container->output_row($lang->suspend_sig, $lang->suspend_sig_info, $actions);

	$signature_options = array(
		$form->generate_radio_button("update_posts", "enable", $lang->enable_sig_in_all_posts, array("checked" => 0)),
		$form->generate_radio_button("update_posts", "disable", $lang->disable_sig_in_all_posts, array("checked" => 0)),
		$form->generate_radio_button("update_posts", "no", $lang->do_nothing, array("checked" => 1))
	);

	$form_container->output_row($lang->signature_preferences, "", implode("<br />", $signature_options));

	$form_container->end();
	echo "</div>\n";

	//
	// AVATAR MANAGER
	//
	echo "<div id=\"tab_avatar\">\n";
	$table = new Table;
	$table->construct_header($lang->current_avatar, array('colspan' => 2));

	$table->construct_cell("<div style=\"width: 126px; height: 126px;\" class=\"user_avatar\"><img src=\"".htmlspecialchars_uni($user['avatar'])."\" width=\"{$scaled_dimensions['width']}\" style=\"margin-top: {$avatar_top}px\" height=\"{$scaled_dimensions['height']}\" alt=\"\" /></div>", array('width' => 1));

	$avatar_url = '';
	if($user['avatartype'] == "upload" || stristr($user['avatar'], $mybb->settings['avataruploadpath']))
	{
		$current_avatar_msg = "<br /><strong>{$lang->user_current_using_uploaded_avatar}</strong>";
	}
	else if($user['avatartype'] == "gallery" || stristr($user['avatar'], $mybb->settings['avatardir']))
	{
		$current_avatar_msg = "<br /><strong>{$lang->user_current_using_gallery_avatar}</strong>";
	}
	elseif($user['avatartype'] == "remote" || my_strpos(my_strtolower($user['avatar']), "http://") !== false)
	{
		$current_avatar_msg = "<br /><strong>{$lang->user_current_using_remote_avatar}</strong>";
		$avatar_url = $user['avatar'];
	}

	if($errors)
	{
		$avatar_url = $mybb->input['avatar_url'];
	}

	if($mybb->settings['maxavatardims'] != "")
	{
		list($max_width, $max_height) = explode("x", my_strtolower($mybb->settings['maxavatardims']));
		$max_size = "<br />{$lang->max_dimensions_are} {$max_width}x{$max_height}";
	}

	if($mybb->settings['avatarsize'])
	{
		$maximum_size = get_friendly_size($mybb->settings['avatarsize']*1024);
		$max_size .= "<br />{$lang->avatar_max_size} {$maximum_size}";
	}

	if($user['avatar'])
	{
		$remove_avatar = "<br /><br />".$form->generate_check_box("remove_avatar", 1, "<strong>{$lang->remove_avatar}</strong>");
	}

	$table->construct_cell($lang->avatar_desc."{$remove_avatar}<br /><small>{$max_size}</small>");
	$table->construct_row();

	$table->output($lang->avatar.": {$user['username']}");

	// Custom avatar
	if($mybb->settings['avatarresizing'] == "auto")
	{
		$auto_resize = $lang->avatar_auto_resize;
	}
	else if($mybb->settings['avatarresizing'] == "user")
	{
		$auto_resize = "<input type=\"checkbox\" name=\"auto_resize\" value=\"1\" checked=\"checked\" id=\"auto_resize\" /> <label for=\"auto_resize\">{$lang->attempt_to_auto_resize}</label></span>";
	}
	$form_container = new FormContainer($lang->specify_custom_avatar);
	$form_container->output_row($lang->upload_avatar, $auto_resize, $form->generate_file_upload_box('avatar_upload', array('id' => 'avatar_upload')), 'avatar_upload');
	$form_container->output_row($lang->or_specify_avatar_url, "", $form->generate_text_box('avatar_url', $avatar_url, array('id' => 'avatar_url')), 'avatar_url');
	$form_container->end();

	// Select an image from the gallery
	echo "<div class=\"border_wrapper\">";
	echo "<div class=\"title\">.. {$lang->or_select_avatar_gallery}</div>";
	echo "<iframe src=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}\" width=\"100%\" height=\"350\" frameborder=\"0\"></iframe>";
	echo "</div>";
	echo "</div>";

	//
	// MODERATOR OPTIONS
	//
	$periods = array(
		"hours" => $lang->expire_hours,
		"days" => $lang->expire_days,
		"weeks" => $lang->expire_weeks,
		"months" => $lang->expire_months,
		"never" => $lang->expire_permanent
	);

	echo "<div id=\"tab_modoptions\">\n";
	$form_container = new FormContainer($lang->mod_options.": {$user['username']}");
	$form_container->output_row($lang->user_notes, '', $form->generate_text_area('usernotes', $mybb->input['usernotes'], array('id' => 'usernotes')), 'usernotes');

	// Mod posts
	// Generate check box
	$modpost_options = $form->generate_select_box('modpost_period', $periods, $mybb->input['modpost_period'], array('id' => 'modpost_period'));

	// Do we have any existing suspensions here?
	$existing_info = '';
	if($user['moderateposts'] || ($mybb->input['moderateposting'] && !empty($errors)))
	{
		$mybb->input['moderateposting'] = 1;
		if($user['moderationtime'] != 0)
		{
			$expired = my_date($mybb->settings['dateformat'], $user['moderationtime']).", ".my_date($mybb->settings['timeformat'], $user['moderationtime']);
			$existing_info = $lang->sprintf($lang->moderate_length, $expired);
		}
		else
		{
			$existing_info = $lang->moderated_perm;
		}
	}

	$modpost_div = '<div id="modpost">'.$existing_info.''.$lang->moderate_for.' '.$form->generate_text_box("modpost_time", $mybb->input['modpost_time'], array('style' => 'width: 2em;')).' '.$modpost_options.'</div>';
	$lang->moderate_posts_info = $lang->sprintf($lang->moderate_posts_info, $user['username']);
	$form_container->output_row($form->generate_check_box("moderateposting", 1, $lang->moderate_posts, array("id" => "moderateposting", "onclick" => "toggleBox('modpost');", "checked" => $mybb->input['moderateposting'])), $lang->moderate_posts_info, $modpost_div);

	// Suspend posts
	// Generate check box
	$suspost_options = $form->generate_select_box('suspost_period', $periods, $mybb->input['suspost_period'], array('id' => 'suspost_period'));

	// Do we have any existing suspensions here?
	if($user['suspendposting'] || ($mybb->input['suspendposting'] && !empty($errors)))
	{
		$mybb->input['suspendposting'] = 1;

		if($user['suspensiontime'] == 0 || $mybb->input['suspost_period'] == "never")
		{
			$existing_info = $lang->suspended_perm;
		}
		else
		{
			$suspost_date = my_date($mybb->settings['dateformat'], $user['suspensiontime'])." ".my_date($mybb->settings['timeformat'], $user['suspensiontime']);
			$existing_info = $lang->sprintf($lang->suspend_length, $suspost_date);
		}
	}

	$suspost_div = '<div id="suspost">'.$existing_info.''.$lang->suspend_for.' '.$form->generate_text_box("suspost_time", $mybb->input['suspost_time'], array('style' => 'width: 2em;')).' '.$suspost_options.'</div>';
	$lang->suspend_posts_info = $lang->sprintf($lang->suspend_posts_info, $user['username']);
	$form_container->output_row($form->generate_check_box("suspendposting", 1, $lang->suspend_posts, array("id" => "suspendposting", "onclick" => "toggleBox('suspost');", "checked" => $mybb->input['suspendposting'])), $lang->suspend_posts_info, $suspost_div);


	$form_container->end();
	echo "</div>\n";

	$buttons[] = $form->generate_submit_button($lang->save_user);
	$form->output_submit_wrapper($buttons);

	$form->end();

echo '<script type="text/javascript">
<!--

function toggleBox(action)
{
	if(action == "modpost")
	{
		$("suspendposting").checked = false;
		$("suspost").hide();

		if($("moderateposting").checked == true)
		{
			$("modpost").show();
		}
		else if($("moderateposting").checked == false)
		{
			$("modpost").hide();
		}
	}
	else if(action == "suspost")
	{
		$("moderateposting").checked = false;
		$("modpost").hide();

		if($("suspendposting").checked == true)
		{
			$("suspost").show();
		}
		else if($("suspendposting").checked == false)
		{
			$("suspost").hide();
		}
	}
}

if($("moderateposting").checked == false)
{
	$("modpost").hide();
}
else
{
	$("modpost").show();
}

if($("suspendposting").checked == false)
{
	$("suspost").hide();
}
else
{
	$("suspost").show();
}

// -->
</script>';

	$page->output_footer();
}

if($mybb->input['action'] == "delete")
{
	$plugins->run_hooks("admin_user_users_delete");

	$query = $db->simple_select("users", "*", "uid='".intval($mybb->input['uid'])."'");
	$user = $db->fetch_array($query);

	// Does the user not exist?
	if(!$user['uid'])
	{
		flash_message($lang->error_invalid_user, 'error');
		admin_redirect("index.php?module=user-users");
	}

	if(is_super_admin($mybb->input['uid']) && $mybb->user['uid'] != $mybb->input['uid'] && !is_super_admin($mybb->user['uid']))
	{
		flash_message($lang->error_no_perms_super_admin, 'error');
		admin_redirect("index.php?module=user-users");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=user-users");
	}

	if($mybb->request_method == "post")
	{
		// Delete the user
		$db->delete_query("userfields", "ufid='{$user['uid']}'");
		$db->delete_query("privatemessages", "uid='{$user['uid']}'");
		$db->delete_query("events", "uid='{$user['uid']}'");
		$db->delete_query("forumsubscriptions", "uid='{$user['uid']}'");
		$db->delete_query("threadsubscriptions", "uid='{$user['uid']}'");
		$db->delete_query("sessions", "uid='{$user['uid']}'");
		$db->delete_query("banned", "uid='{$user['uid']}'");
		$db->delete_query("threadratings", "uid='{$user['uid']}'");
		$db->delete_query("users", "uid='{$user['uid']}'");
		$db->delete_query("joinrequests", "uid='{$user['uid']}'");
		$db->delete_query("warnings", "uid='{$user['uid']}'");
		$db->delete_query("reputation", "uid='{$user['uid']}' OR adduid='{$user['uid']}'");
		$db->delete_query("awaitingactivation", "uid='{$user['uid']}'");
		$db->delete_query("posts", "uid = '{$user['uid']}' AND visible = '-2'");
		$db->delete_query("threads", "uid = '{$user['uid']}' AND visible = '-2'");

		// Update forum stats
		update_stats(array('numusers' => '-1'));

		// Update forums & threads if user is the lastposter
		$db->update_query("posts", array('uid' => 0), "uid='{$user['uid']}'");
		$db->update_query("forums", array("lastposteruid" => 0), "lastposteruid = '{$user['uid']}'");
		$db->update_query("threads", array("lastposteruid" => 0), "lastposteruid = '{$user['uid']}'");

		// Did this user have an uploaded avatar?
		if($user['avatartype'] == "upload")
		{
			// Removes the ./ at the beginning the timestamp on the end...
			@unlink("../".substr($user['avatar'], 2, -20));
		}

		// Was this user a moderator?
		if(is_moderator($user['uid']))
		{
			$db->delete_query("moderators", "id='{$user['uid']}' AND isgroup = '0'");
			$cache->update_moderators();
		}

		$plugins->run_hooks("admin_user_users_delete_commit");

		// Log admin action
		log_admin_action($user['uid'], $user['username']);

		flash_message($lang->success_user_deleted, 'success');
		admin_redirect("index.php?module=user-users");
	}
	else
	{
		$page->output_confirm_action("index.php?module=user-users&action=delete&uid={$user['uid']}", $lang->user_deletion_confirmation);
	}
}

if($mybb->input['action'] == "referrers")
{
	$plugins->run_hooks("admin_user_users_referrers");

	$page->add_breadcrumb_item($lang->show_referrers);
	$page->output_header($lang->show_referrers);

	$sub_tabs['referrers'] = array(
		'title' => $lang->show_referrers,
		'link' => "index.php?module=user-users&amp;action=referrers&amp;uid={$mybb->input['uid']}",
		'description' => $lang->show_referrers_desc
	);

	$page->output_nav_tabs($sub_tabs, 'referrers');

	// Fetch default admin view
	$default_view = fetch_default_view("user");
	if(!$default_view)
	{
		$default_view = "0";
	}
	$query = $db->simple_select("adminviews", "*", "type='user' AND (vid='{$default_view}' OR uid=0)", array("order_by" => "uid", "order_dir" => "desc"));
	$admin_view = $db->fetch_array($query);

	if($mybb->input['type'])
	{
		$admin_view['view_type'] = $mybb->input['type'];
	}

	$admin_view['conditions'] = unserialize($admin_view['conditions']);
	$admin_view['conditions']['referrer'] = $mybb->input['uid'];

	$view = build_users_view($admin_view);

	// No referred users
	if(!$view)
	{
		$table = new Table;
		$table->construct_cell($lang->error_no_referred_users);
		$table->construct_row();
		$table->output($lang->show_referrers);
	}
	else
	{
		echo $view;
	}

	$page->output_footer();
}

if($mybb->input['action'] == "ipaddresses")
{
	$plugins->run_hooks("admin_user_users_ipaddresses");

	$page->add_breadcrumb_item($lang->ip_addresses);
	$page->output_header($lang->ip_addresses);

	$sub_tabs['ipaddresses'] = array(
		'title' => $lang->show_ip_addresses,
		'link' => "index.php?module=user-users&amp;action=ipaddresses&amp;uid={$mybb->input['uid']}",
		'description' => $lang->show_ip_addresses_desc
	);

	$page->output_nav_tabs($sub_tabs, 'ipaddresses');

	$query = $db->simple_select("users", "uid, regip, username, lastip", "uid='{$mybb->input['uid']}'", array('limit' => 1));
	$user = $db->fetch_array($query);

	// Log admin action
	log_admin_action($user['uid'], $user['username']);

	$table = new Table;

	$table->construct_header($lang->ip_address);
	$table->construct_header($lang->controls, array('width' => 200, 'class' => "align_center"));

	if(empty($user['lastip']))
	{
		$user['lastip'] = $lang->unknown;
		$controls = '';
	}
	else
	{
		$popup = new PopupMenu("user_last", $lang->options);
		$popup->add_item($lang->show_users_regged_with_ip,
"index.php?module=user-users&amp;action=search&amp;results=1&amp;conditions=".urlencode(serialize(array("regip" => $user['lastip']))));
		$popup->add_item($lang->show_users_posted_with_ip, "index.php?module=user-users&amp;results=1&amp;action=search&amp;conditions=".urlencode(serialize(array("postip" => $user['lastip']))));
		$popup->add_item($lang->info_on_ip, "{$mybb->settings['bburl']}/modcp.php?action=iplookup&ipaddress={$user['lastip']}", "MyBB.popupWindow('{$mybb->settings['bburl']}/modcp.php?action=iplookup&ipaddress={$user['lastip']}', 'iplookup', 500, 250); return false;");
		$popup->add_item($lang->ban_ip, "index.php?module=config-banning&amp;filter={$user['lastip']}");
		$controls = $popup->fetch();
	}
	$table->construct_cell("<strong>{$lang->last_known_ip}:</strong> {$user['lastip']}");
	$table->construct_cell($controls, array('class' => "align_center"));
	$table->construct_row();

	if(empty($user['regip']))
	{
		$user['regip'] = $lang->unknown;
		$controls = '';
	}
	else
	{
		$popup = new PopupMenu("user_reg", $lang->options);
		$popup->add_item($lang->show_users_regged_with_ip, "index.php?module=user-users&amp;results=1&amp;action=search&amp;conditions=".urlencode(serialize(array("regip" => $user['regip']))));
		$popup->add_item($lang->show_users_posted_with_ip, "index.php?module=user-users&amp;results=1&amp;action=search&amp;conditions=".urlencode(serialize(array("postip" => $user['regip']))));
		$popup->add_item($lang->info_on_ip, "{$mybb->settings['bburl']}/modcp.php?action=iplookup&ipaddress={$user['regip']}", "MyBB.popupWindow('{$mybb->settings['bburl']}/modcp.php?action=iplookup&ipaddress={$user['regip']}', 'iplookup', 500, 250); return false;");
		$popup->add_item($lang->ban_ip, "index.php?module=config-banning&amp;filter={$user['regip']}");
		$controls = $popup->fetch();
	}
	$table->construct_cell("<strong>{$lang->registration_ip}:</strong> {$user['regip']}");
	$table->construct_cell($controls, array('class' => "align_center"));
	$table->construct_row();

	$counter = 0;

	$query = $db->simple_select("posts", "DISTINCT ipaddress", "uid='{$mybb->input['uid']}'");
	while($ip = $db->fetch_array($query))
	{
		++$counter;
		$popup = new PopupMenu("id_{$counter}", $lang->options);
		$popup->add_item($lang->show_users_regged_with_ip, "index.php?module=user-users&amp;results=1&amp;action=search&amp;conditions=".urlencode(serialize(array("regip" => $ip['ipaddress']))));
		$popup->add_item($lang->show_users_posted_with_ip, "index.php?module=user-users&amp;results=1&amp;action=search&amp;conditions=".urlencode(serialize(array("postip" => $ip['ipaddress']))));
		$popup->add_item($lang->info_on_ip, "{$mybb->settings['bburl']}/modcp.php?action=iplookup&ipaddress={$ip['ipaddress']}", "MyBB.popupWindow('{$mybb->settings['bburl']}/modcp.php?action=iplookup&ipaddress={$ip['ipaddress']}', 'iplookup', 500, 250); return false;");
		$popup->add_item($lang->ban_ip, "index.php?module=config-banning&amp;filter={$ip['ipaddress']}");
		$controls = $popup->fetch();

		$table->construct_cell($ip['ipaddress']);
		$table->construct_cell($controls, array('class' => "align_center"));
		$table->construct_row();
	}

	$table->output($lang->ip_address_for." {$user['username']}");

	$page->output_footer();
}

if($mybb->input['action'] == "merge")
{
	$plugins->run_hooks("admin_user_users_merge");

	if($mybb->request_method == "post")
	{
		$query = $db->simple_select("users", "*", "LOWER(username)='".$db->escape_string(my_strtolower($mybb->input['source_username']))."'");
		$source_user = $db->fetch_array($query);
		if(!$source_user['uid'])
		{
			$errors[] = $lang->error_invalid_user_source;
		}

		$query = $db->simple_select("users", "*", "LOWER(username)='".$db->escape_string(my_strtolower($mybb->input['destination_username']))."'");
		$destination_user = $db->fetch_array($query);
		if(!$destination_user['uid'])
		{
			$errors[] = $lang->error_invalid_user_destination;
		}

		// If we're not a super admin and we're merging a source super admin or a destination super admin then dissallow this action
		if(!is_super_admin($mybb->user['uid']) && (is_super_admin($source_user['uid']) || is_super_admin($destination_user['uid'])))
		{
			flash_message($lang->error_no_perms_super_admin, 'error');
			admin_redirect("index.php?module=user-users");
		}

		if($source_user['uid'] == $destination_user['uid'])
		{
			$errors[] = $lang->error_cannot_merge_same_account;
		}

		if(empty($errors))
		{
			// Begin to merge the accounts
			$uid_update = array(
				"uid" => $destination_user['uid']
			);
			$query = $db->simple_select("adminoptions", "uid", "uid='{$destination_user['uid']}'");
			$existing_admin_options = $db->fetch_field($query, "uid");

			// Only carry over admin options/permissions if we don't already have them
			if(!$existing_admin_options)
			{
				$db->update_query("adminoptions", $uid_update, "uid='{$source_user['uid']}'");
			}

			$db->update_query("adminlog", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("announcements", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("events", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("threadsubscriptions", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("forumsubscriptions", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("joinrequests", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("moderatorlog", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("pollvotes", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("posts", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("privatemessages", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("reportedposts", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("threadratings", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("threads", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("warnings", $uid_update, "uid='{$source_user['uid']}'");
			$db->update_query("warnings", array("revokedby" => $destination_user['uid']), "revokedby='{$source_user['uid']}'");
			$db->update_query("warnings", array("issuedby" => $destination_user['uid']), "issuedby='{$source_user['uid']}'");
			$db->update_query("users", array("warningpoints" => $destination_user['warningpoints']+$source_user['warningpoints']), "uid='{$source_user['uid']}'");
			$db->delete_query("sessions", "uid='{$source_user['uid']}'");

			// Is the source user a moderator?
			if($groupscache[$source_user['usergroup']]['canmodcp'])
			{
				$db->delete_query("moderators", "id='{$source_user['uid']}' AND isgroup = '0'");

				// Update the moderator cache...
				$cache->update_moderators();
			}

			// Banning
			$db->update_query("banned", array('admin' => $destination_user['uid']), "admin = '{$source_user['uid']}'");

			// Merging Reputation
			// First, let's change all the details over to our new user...
			$rep_update = array(
				"adduid" => $destination_user['uid'],
				"uid" => $destination_user['uid']
			);
			$db->update_query("reputation", $rep_update, "adduid = '".$source_user['uid']."' OR uid = '".$source_user['uid']."'");

			// Now that all the repuation is merged, figure out what to do with this user's comments...
			$options = array(
				"order_by" => "uid",
				"order_dir" => "ASC"
			);

			$to_remove = array();
			$query = $db->simple_select("reputation", "*", "adduid = '".$destination_user['uid']."'");
			while($rep = $db->fetch_array($query))
			{
				if($rep['pid'] == 0 && $mybb->settings['multirep'] == 0 && $last_result['uid'] == $rep['uid'])
				{
					// Multiple reputation is disallowed, and this isn't a post, so let's remove this comment
					$to_remove[] = $rep['rid'];
				}

				// Remove comments or posts liked by "me"
				if($last_result['uid'] == $destination_user['uid'] || $rep['uid'] == $destination_user['uid'])
				{
					if(!in_array($rep['rid'], $to_remove))
					{
						$to_remove[] = $rep['rid'];
						continue;
					}
				}

				$last_result = array(
					"rid" => $rep['rid'],
					"uid" => $rep['uid']
				);
			}

			// Remove any reputations we've selected to remove...
			if(!empty($to_remove))
			{
				$imp = implode(",", $to_remove);
				$db->delete_query("reputation", "rid IN (".$imp.")");
			}

			// Calculate the new reputation for this user...
			$query = $db->simple_select("reputation", "SUM(reputation) as total_rep", "uid='{$destination_user['uid']}'");
			$total_reputation = $db->fetch_field($query, "total_rep");

			$db->update_query("users", array('reputation' => intval($total_reputation)), "uid='{$destination_user['uid']}'");

			// Additional updates for non-uid fields
			$last_poster = array(
				"lastposteruid" => $destination_user['uid'],
				"lastposter" => $db->escape_string($destination_user['username'])
			);
			$db->update_query("forums", $last_poster, "lastposteruid='{$source_user['uid']}'");
			$db->update_query("threads", $last_poster, "lastposteruid='{$source_user['uid']}'");
			$edit_uid = array(
				"edituid" => $destination_user['uid']
			);
			$db->update_query("posts", $edit_uid, "edituid='{$source_user['uid']}'");

			$from_uid = array(
				"fromid" => $destination_user['uid']
			);
			$db->update_query("privatemessages", $from_uid, "fromid='{$source_user['uid']}'");
			$to_uid = array(
				"toid" => $destination_user['uid']
			);
			$db->update_query("privatemessages", $to_uid, "toid='{$source_user['uid']}'");

			// Delete the old user
			$db->delete_query("users", "uid='{$source_user['uid']}'");
			$db->delete_query("banned", "uid='{$source_user['uid']}'");

			// Did the old user have an uploaded avatar?
			if($source_user['avatartype'] == "upload")
			{
				// Removes the ./ at the beginning the timestamp on the end...
				@unlink("../".substr($source_user['avatar'], 2, -20));
			}

			// Get a list of forums where post count doesn't apply
			$fids = array();
			$query = $db->simple_select("forums", "fid", "usepostcounts=0");
			while($fid = $db->fetch_field($query, "fid"))
			{
				$fids[] = $fid;
			}

			$fids_not_in = '';
			if(!empty($fids))
			{
				$fids_not_in = "AND fid NOT IN(".implode(',', $fids).")";
			}

			// Update user post count
			$query = $db->simple_select("posts", "COUNT(*) AS postnum", "uid='".$destination_user['uid']."' {$fids_not_in}");
			$num = $db->fetch_array($query);
			$updated_count = array(
				"postnum" => $num['postnum']
			);
			$db->update_query("users", $updated_count, "uid='{$destination_user['uid']}'");

			// Use the earliest registration date
			if($destination_user['regdate'] > $source_user['regdate'])
			{
				$db->update_query("users", array('regdate' => $source_user['regdate']), "uid='{$destination_user['uid']}'");
			}

			update_stats(array('numusers' => '-1'));

			$plugins->run_hooks("admin_user_users_merge_commit");

			// Log admin action
			log_admin_action($source_user['uid'], $source_user['username'], $destination_user['uid'], $destination_user['username']);

			// Redirect!
			flash_message("<strong>{$source_user['username']}</strong> {$lang->success_merged} {$destination_user['username']}", "success");
			admin_redirect("index.php?module=user-users");
			exit;
		}
	}

	$page->add_breadcrumb_item($lang->merge_users);
	$page->output_header($lang->merge_users);

	$page->output_nav_tabs($sub_tabs, 'merge_users');

	// If we have any error messages, show them
	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form = new Form("index.php?module=user-users&amp;action=merge", "post");

	$form_container = new FormContainer($lang->merge_users);
	$form_container->output_row($lang->source_account." <em>*</em>", $lang->source_account_desc, $form->generate_text_box('source_username', $mybb->input['source_username'], array('id' => 'source_username')), 'source_username');
	$form_container->output_row($lang->destination_account." <em>*</em>", $lang->destination_account_desc, $form->generate_text_box('destination_username', $mybb->input['destination_username'], array('id' => 'destination_username')), 'destination_username');
	$form_container->end();

	// Autocompletion for usernames
	echo '
	<script type="text/javascript" src="../jscripts/autocomplete.js?ver=140"></script>
	<script type="text/javascript">
	<!--
		new autoComplete("source_username", "../xmlhttp.php?action=get_users", {valueSpan: "username"});
		new autoComplete("destination_username", "../xmlhttp.php?action=get_users", {valueSpan: "username"});
	// -->
	</script>';

	$buttons[] = $form->generate_submit_button($lang->merge_user_accounts);
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "search")
{
	$plugins->run_hooks("admin_user_users_search");

	if($mybb->request_method == "post" || $mybb->input['results'] == 1)
	{
		// Build view options from incoming search options
		if($mybb->input['vid'])
		{
			$query = $db->simple_select("adminviews", "*", "vid='".intval($mybb->input['vid'])."'");
			$admin_view = $db->fetch_array($query);
			// View does not exist or this view is private and does not belong to the current user
			if(!$admin_view['vid'] || ($admin_view['visibility'] == 1 && $admin_view['uid'] != $mybb->user['uid']))
			{
				unset($admin_view);
			}
		}

		if($mybb->input['search_id'] && $admin_session['data']['user_views'][$mybb->input['search_id']])
		{
			$admin_view = $admin_session['data']['user_views'][$mybb->input['search_id']];
			unset($admin_view['extra_sql']);
		}
		else
		{
			// Don't have a view? Fetch the default
			if(!$admin_view['vid'])
			{
				$default_view = fetch_default_view("user");
				if(!$default_view)
				{
					$default_view = "0";
				}
				$query = $db->simple_select("adminviews", "*", "type='user' AND (vid='{$default_view}' OR uid=0)", array("order_by" => "uid", "order_dir" => "desc"));
				$admin_view = $db->fetch_array($query);
			}
		}

		// Override specific parts of the view
		unset($admin_view['vid']);

		if($mybb->input['type'])
		{
			$admin_view['view_type'] = $mybb->input['type'];
		}

		if($mybb->input['conditions'])
		{
			$admin_view['conditions'] = $mybb->input['conditions'];
		}

		if($mybb->input['sortby'])
		{
			$admin_view['sortby'] = $mybb->input['sortby'];
		}

		if(intval($mybb->input['perpage']))
		{
			$admin_view['perpage'] = $mybb->input['perpage'];
		}

		if($mybb->input['order'])
		{
			$admin_view['sortorder'] = $mybb->input['order'];
		}

		if($mybb->input['displayas'])
		{
			$admin_view['view_type'] = $mybb->input['displayas'];
		}

		if($mybb->input['profile_fields'])
		{
			$admin_view['custom_profile_fields'] = $mybb->input['profile_fields'];
		}

		$results = build_users_view($admin_view);

		if($results)
		{
			$page->output_header($lang->find_users);
			echo "<script type=\"text/javascript\" src=\"jscripts/users.js\"></script>";
			$page->output_nav_tabs($sub_tabs, 'find_users');
			echo $results;
			$page->output_footer();
		}
		else
		{
			if($mybb->input['from'] == "home")
			{
				flash_message($lang->error_no_users_found, 'error');
				admin_redirect("index.php");
				exit;
			}
			else
			{
				$errors[] = $lang->error_no_users_found;
			}
		}
	}

	$page->add_breadcrumb_item($lang->find_users);
	$page->output_header($lang->find_users);

	$page->output_nav_tabs($sub_tabs, 'find_users');

	// If we have any error messages, show them
	if($errors)
	{
		$page->output_inline_error($errors);
	}

	if(!$mybb->input['displayas'])
	{
		$mybb->input['displayas'] = "card";
	}

	$form = new Form("index.php?module=user-users&amp;action=search", "post");

	user_search_conditions($mybb->input, $form);

	$form_container = new FormContainer($lang->display_options);
	$sort_directions = array(
		"asc" => $lang->ascending,
		"desc" => $lang->descending
	);
	$form_container->output_row($lang->sort_results_by, "", $form->generate_select_box('sortby', $sort_options, $mybb->input['sortby'], array('id' => 'sortby'))." {$lang->in} ".$form->generate_select_box('order', $sort_directions, $mybb->input['order'], array('id' => 'order')), 'sortby');
	$form_container->output_row($lang->results_per_page, "", $form->generate_text_box('perpage', $mybb->input['perpage'], array('id' => 'perpage')), 'perpage');
	$form_container->output_row($lang->display_results_as, "", $form->generate_radio_button('displayas', 'table', $lang->table, array('checked' => ($mybb->input['displayas'] != "card" ? true : false)))."<br />".$form->generate_radio_button('displayas', 'card', $lang->business_card, array('checked' => ($mybb->input['displayas'] == "card" ? true : false))));
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->find_users);
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "inline_edit")
{
	$plugins->run_hooks("admin_user_users_inline");

	if($mybb->input['vid'] || $mybb->cookies['acp_view'])
	{
		// We have a custom view
		if(!$mybb->cookies['acp_view'])
		{
			// Set a cookie
			my_setcookie("acp_view", $mybb->input['vid'], 60);
		}
		elseif($mybb->cookies['acp_view'])
		{
			// We already have a cookie, so let's use it...
			$mybb->input['vid'] = $mybb->cookies['acp_view'];
		}

		$vid_url = "&amp;vid=".$mybb->input['vid'];
	}

	// First, collect the user IDs that we're performing the moderation on
	$ids = explode("|", $mybb->cookies['inlinemod_useracp']);
	foreach($ids as $id)
	{
		if($id != '')
		{
			$selected[] = intval($id);
		}
	}

	// If there isn't anything to select, then output an error
	if(!is_array($selected))
	{
		if($mybb->input['inline_action'] != "multilift" && $mybb->request_method != "post")
		{
			$errors[] = $lang->error_inline_no_users_selected;
		}
	}

	if($errors)
	{
		// Don't show views, but show the user list if there's errors
		$inline = true;
		$mybb->input['action'] = '';
	}
	else
	{
		// Let's continue!
		// Verify incoming POST request
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=user-user");
		}
		$sub_tabs['manage_users'] = array(
			"title" => $lang->manage_users,
			"link" => "./",
			"description" => $lang->manage_users_desc
		);
		$page->add_breadcrumb_item($lang->manage_users);

		if(!is_array($selected))
		{
			// Not selected any users, show error
			flash_message($lang->error_inline_no_users_selected, 'error');
			admin_redirect("index.php?module=user-users".$vid_url);
		}

		switch($mybb->input['inline_action'])
		{
			case 'multiactivate':
				// Run through the activating users, so that users already registered (but have been selected) aren't affected
				if(is_array($selected))
				{
					$sql_array = implode(",", $selected);
					$query = $db->simple_select("users", "uid", "usergroup = '5' AND uid IN (".$sql_array.")");
					while($user = $db->fetch_array($query))
					{
						$to_update[] = $user['uid'];
					}
				}

				if(is_array($to_update))
				{
					$sql_array = implode(",", $to_update);
					$db->write_query("UPDATE ".TABLE_PREFIX."users SET usergroup = '2' WHERE uid IN (".$sql_array.")");

					// Action complete, grab stats and show success message - redirect user
					$to_update_count = count($to_update);
					$lang->inline_activated = $lang->sprintf($lang->inline_activated, my_number_format($to_update_count));

					if($to_update_count != count($selected))
					{
						// The update count is different to how many we selected!
						$not_updated_count = count($selected) - $to_update_count;
						$lang->inline_activated_more = $lang->sprintf($lang->inline_activated_more, my_number_format($not_updated_count));
						$lang->inline_activated = $lang->inline_activated."<br />".$lang->inline_activated_more; // Add these stats to the message
					}

					$mybb->input['action'] = "inline_activated"; // Force a change to the action so we can add it to the adminlog
					log_admin_action($to_update_count); // Add to adminlog
					my_unsetcookie("inlinemod_useracp"); // Unset the cookie, so that the users aren't still selected when we're redirected

					flash_message($lang->inline_activated, 'success');
					admin_redirect("index.php?module=user-users".$vid_url);
				}
				else
				{
					// Nothing was updated, show an error
					flash_message($lang->inline_activated_failed, 'error');
					admin_redirect("index.php?module=user-users".$vid_url);
				}
				break;
			case 'multilift':
				// Get the users that are banned, and check that they have been selected
				if($mybb->input['no'])
				{
					admin_redirect("index.php?module=user-users".$vid_url); // User clicked on 'No'
				}

				if($mybb->request_method == "post")
				{
					$sql_array = implode(",", $selected);
					$query = $db->simple_select("banned", "*", "uid IN (".$sql_array.")");
					$to_be_unbanned = $db->num_rows($query);
					while($ban = $db->fetch_array($query))
					{
						$updated_group = array(
							"usergroup" => $ban['oldgroup'],
							"additionalgroups" => $ban['oldadditionalgroups'],
							"displaygroup" => $ban['olddisplaygroup']
						);
						$db->update_query("users", $updated_group, "uid = '".$ban['uid']."'");
						$db->delete_query("banned", "uid = '".$ban['uid']."'");
					}

					$cache->update_banned();
					$cache->update_moderators();

					$mybb->input['action'] = "inline_lift";
					log_admin_action($to_be_unbanned);
					my_unsetcookie("inlinemod_useracp");

					$lang->success_ban_lifted = $lang->sprintf($lang->success_ban_lifted, my_number_format($to_be_unbanned));
					flash_message($lang->success_ban_lifted, 'success');
					admin_redirect("index.php?module=user-users".$vid_url);
				}
				else
				{
					$page->output_confirm_action("index.php?module=user-users&amp;action=inline_edit&amp;inline_action=multilift", $lang->confirm_multilift);
				}

				break;
			case 'multiban':
				if($mybb->input['processed'] == 1)
				{
					// We've posted ban information!
					// Build an array of users to ban, =D
					$sql_array = implode(",", $selected);
					// Build a cache array for this users that have been banned already
					$query = $db->simple_select("banned", "uid", "uid IN (".$sql_array.")");
					while($user = $db->fetch_array($query))
					{
						$bannedcache[] = "u_".$user['uid'];
					}

					// Collect the users
					$query = $db->simple_select("users", "uid, username, usergroup, additionalgroups, displaygroup", "uid IN (".$sql_array.")");

					if($mybb->input['bantime'] == '---')
					{
						$lifted = 0;
					}
					else
					{
						$lifted = ban_date2timestamp($mybb->input['bantime']);
					}

					$banned_count = 0;
					while($user = $db->fetch_array($query))
					{
						if($user['uid'] == $mybb->user['uid'] || is_super_admin($user['uid']))
						{
							// We remove ourselves and Super Admins from the mix
							continue;
						}

						if(is_array($bannedcache) && in_array("u_".$user['uid'], $bannedcache))
						{
							// User already has a ban, update it!
							$update_array = array(
								"admin" => intval($mybb->user['uid']),
								"dateline" => TIME_NOW,
								"bantime" => $db->escape_string($mybb->input['bantime']),
								"lifted" => $db->escape_string($lifted),
								"reason" => $db->escape_string($mybb->input['reason'])
							);
							$db->update_query("banned", $update_array, "uid = '".$user['uid']."'");
						}
						else
						{
							// Not currently banned - insert the ban
							$insert_array = array(
								'uid' => $user['uid'],
								'gid' => intval($mybb->input['usergroup']),
								'oldgroup' => $user['usergroup'],
								'oldadditionalgroups' => $user['additionalgroups'],
								'olddisplaygroup' => $user['displaygroup'],
								'admin' => intval($mybb->user['uid']),
								'dateline' => TIME_NOW,
								'bantime' => $db->escape_string($mybb->input['bantime']),
								'lifted' => $db->escape_string($lifted),
								'reason' => $db->escape_string($mybb->input['reason'])
							);
							$db->insert_query('banned', $insert_array);
						}

						// Moved the user to the 'Banned' Group
						$update_array = array(
							'usergroup' => 7,
							'displaygroup' => 0,
							'additionalgroups' => '',
						);
						$db->update_query('users', $update_array, "uid = '{$user['uid']}'");

						$db->delete_query("forumsubscriptions", "uid = '{$user['uid']}'");
						$db->delete_query("threadsubscriptions", "uid = '{$user['uid']}'");

						$cache->update_banned();
						++$banned_count;
					}
					$mybb->input['action'] = "inline_banned";
					log_admin_action($banned_count, $lifted);
					my_unsetcookie("inlinemod_useracp"); // Remove the cookie of selected users as we've finished with them

					$lang->users_banned = $lang->sprintf($lang->users_banned, $banned_count);
					flash_message($lang->users_banned, 'success');
					admin_redirect("index.php?module=user-users".$vid_url);
				}

				$page->output_header($lang->manage_users);
				$page->output_nav_tabs($sub_tabs, 'manage_users');

				// Provide the user with a warning of what they're about to do
				$table = new Table;
				$lang->mass_ban_info = $lang->sprintf($lang->mass_ban_info, count($selected));
				$table->construct_cell($lang->mass_ban_info);
				$table->construct_row();
				$table->output($lang->important);

				// If there's any errors, display inline
				if($errors)
				{
					$page->output_inline_error($errors);
				}

				$form = new Form("index.php?module=user-users", "post");
				echo $form->generate_hidden_field('action', 'inline_edit');
				echo $form->generate_hidden_field('inline_action', 'multiban');
				echo $form->generate_hidden_field('processed', '1');

				$form_container = new FormContainer('<div class="float_right"><a href="index.php?module=user-users&amp;action=inline_edit&amp;inline_action=multilift&amp;my_post_key='.$mybb->post_code.'">'.$lang->lift_bans.'</a></div>'.$lang->mass_ban);
				$form_container->output_row($lang->ban_reason, "", $form->generate_text_box('reason', $mybb->input['reason'], array('id' => 'reason')), 'reason');
				$ban_times = fetch_ban_times();
				foreach($ban_times as $time => $period)
				{
					if($time != '---')
					{
						$friendly_time = my_date("D, jS M Y @ g:ia", ban_date2timestamp($time));
						$period = "{$period} ({$friendly_time})";
					}
					$length_list[$time] = $period;
				}
				$form_container->output_row($lang->ban_time, "", $form->generate_select_box('bantime', $length_list, $mybb->input['bantime'], array('id' => 'bantime')), 'bantime');
				$form_container->end();

				$buttons[] = $form->generate_submit_button($lang->ban_users);
				$form->output_submit_wrapper($buttons);
				$form->end();
				$page->output_footer();
				break;
			case 'multidelete':
				if($mybb->input['no'])
				{
					admin_redirect("index.php?module=user-users".$vid_url); // User clicked on 'No
				}
				else
				{
					if($mybb->input['processed'] == 1)
					{
						// Admin wants these users, gone!
						$sql_array = implode(",", $selected);
						$query = $db->simple_select("users", "uid", "uid IN (".$sql_array.")");
						$to_be_deleted = $db->num_rows($query);
						while($user = $db->fetch_array($query))
						{
							if($user['uid'] == $mybb->user['uid'] || is_super_admin($user['uid']))
							{
								// Remove me and super admins
								continue;
							}
							else
							{
								// Run delete queries
								$db->update_query("posts", array('uid' => 0), "uid='{$user['uid']}'");
								$db->delete_query("userfields", "ufid='{$user['uid']}'");
								$db->delete_query("privatemessages", "uid='{$user['uid']}'");
								$db->delete_query("events", "uid='{$user['uid']}'");
								$db->delete_query("moderators", "id='{$user['uid']}' AND isgroup = '0'");
								$db->delete_query("forumsubscriptions", "uid='{$user['uid']}'");
								$db->delete_query("threadsubscriptions", "uid='{$user['uid']}'");
								$db->delete_query("sessions", "uid='{$user['uid']}'");
								$db->delete_query("banned", "uid='{$user['uid']}'");
								$db->delete_query("threadratings", "uid='{$user['uid']}'");
								$db->delete_query("users", "uid='{$user['uid']}'");
								$db->delete_query("joinrequests", "uid='{$user['uid']}'");
								$db->delete_query("warnings", "uid='{$user['uid']}'");
							}
						}
						// Update forum stats, remove the cookie and redirect the user
						update_stats(array('numusers' => '-'.$to_be_deleted.''));
						my_unsetcookie("inlinemod_useracp");
						$mybb->input['action'] = "inline_delete";
						log_admin_action($to_be_deleted);

						$lang->users_deleted = $lang->sprintf($lang->users_deleted, $to_be_deleted);
						flash_message($lang->users_deleted, 'success');
						admin_redirect("index.php?module=user-users".$vid_url);
					}

					$to_be_deleted = count($selected);
					$lang->confirm_multidelete = $lang->sprintf($lang->confirm_multidelete, my_number_format($to_be_deleted));
					$page->output_confirm_action("index.php?module=user-users&amp;action=inline_edit&amp;inline_action=multidelete&amp;my_post_key={$mybb->post_code}&amp;processed=1", $lang->confirm_multidelete);
				}
				break;
			case 'multiprune':
				if($mybb->input['processed'] == 1)
				{
					if(($mybb->input['day'] || $mybb->input['month'] || $mybb->input['year']) && $mybb->input['set'])
					{
						$errors[] = $lang->multi_selected_dates;
					}

					$day = intval($mybb->input['day']);
					$month = intval($mybb->input['month']);
					$year = intval($mybb->input['year']);

					// Selected a date - check if the date the user entered is valid
					if($mybb->input['day'] || $mybb->input['month'] || $mybb->input['year'])
					{
						// Is the date sort of valid?
						if($day < 1 || $day > 31 || $month < 1 || $month > 12 || ($month == 2 && $day > 29))
						{
							$errors[] = $lang->incorrect_date;
						}

						// Check the month
						$months = get_bdays($year);
						if($day > $months[$month]-1)
						{
							$errors[] = $lang->incorrect_date;
						}

						// Check the year
						if($year != 0 && ($year < (date("Y")-100)) || $year > date("Y"))
						{
							$errors[] = $lang->incorrect_date;
						}

						if(!$errors)
						{
							// No errors, so let's continue and set the date to delete from
							$date = mktime(date('H'), date('i'), date('s'), $month, $day, $year); // Generate a unix time stamp
						}
					}
					elseif($mybb->input['set'] > 0)
					{
						// Set options
						// For this purpose, 1 month = 31 days
						$base_time = 24 * 60 * 60;

						switch($mybb->input['set'])
						{
							case '1':
								$threshold = $base_time * 31; // 1 month = 31 days, in the standard terms
								break;
							case '2':
								$threshold = $base_time * 93; // 3 months = 31 days * 3
								break;
							case '3':
								$threshold = $base_time * 183; // 6 months = 365 days / 2
								break;
							case '4':
								$threshold = $base_time * 365; // 1 year = 365 days
								break;
							case '5':
								$threshold = $base_time * 548; // 18 months = 365 + 183
								break;
							case '6':
								$threshold = $base_time * 730; // 2 years = 365 * 2
								break;
						}

						if(!$threshold)
						{
							// An option was entered that isn't in the dropdown box
							$errors[] = $lang->no_set_option;
						}
						else
						{
							$date = TIME_NOW - $threshold;
						}
					}
					else
					{
						$errors[] = $lang->no_prune_option;
					}

					if(!$errors)
					{
						$sql_array = implode(",", $selected);
						$prune_array = array();
						$query = $db->simple_select("users", "uid", "uid IN (".$sql_array.")");
						while($user = $db->fetch_array($query))
						{
							// Protect Super Admins
							if(is_super_admin($user['uid']) && !is_super_admin($mybb->user['uid']))
							{
								continue;
							}

							$return_array = delete_user_posts($user['uid'], $date); // Delete user posts, and grab a list of threads to delete
							if($return_array && is_array($return_array))
							{
								$prune_array = array_merge_recursive($prune_array, $return_array);
							}
						}

						// No posts were found for the user, return error
						if(!is_array($prune_array) || count($prune_array) == 0)
						{
							flash_message($lang->prune_fail, 'error');
							admin_redirect("index.php?module=user-users".$vid_url);
						}

						// Require the rebuild functions
						require_once MYBB_ROOT.'/inc/functions.php';
						require_once MYBB_ROOT.'/inc/functions_rebuild.php';

						// We've finished deleting user's posts, so let's delete the threads
						if(is_array($prune_array['to_delete']) && count($prune_array['to_delete']) > 0)
						{
							foreach($prune_array['to_delete'] as $tid)
							{
								$db->delete_query("threads", "tid='$tid'");
								$db->delete_query("threads", "closed='moved|$tid'");
								$db->delete_query("threadsubscriptions", "tid='$tid'");
								$db->delete_query("polls", "tid='$tid'");
								$db->delete_query("threadsread", "tid='$tid'");
								$db->delete_query("threadratings", "tid='$tid'");
							}
						}

						// After deleting threads, rebuild the thread counters for the affected threads
						if(is_array($prune_array['thread_update']) && count($prune_array['thread_update']) > 0)
						{
							$sql_array = implode(",", $prune_array['thread_update']);
							$query = $db->simple_select("threads", "tid", "tid IN (".$sql_array.")", array('order_by' => 'tid', 'order_dir' => 'asc'));
							while($thread = $db->fetch_array($query))
							{
								rebuild_thread_counters($thread['tid']);
							}
						}

						// After updating thread counters, update the affected forum counters
						if(is_array($prune_array['forum_update']) && count($prune_array['forum_update']) > 0)
						{
							$sql_array = implode(",", $prune_array['forum_update']);
							$query = $db->simple_select("forums", "fid", "fid IN (".$sql_array.")", array('order_by' => 'fid', 'order_dir' => 'asc'));
							while($forum = $db->fetch_array($query))
							{
								// Because we have a recursive array merge, check to see if there isn't a duplicated forum to update
								if($looped_forum == $forum['fid'])
								{
									continue;
								}
								$looped_forum = $forum['fid'];
								rebuild_forum_counters($forum['fid']);
							}
						}

						//log_admin_action();
						my_unsetcookie("inlinemod_useracp"); // We've got our users, remove the cookie
						flash_message($lang->prune_complete, 'success');
						admin_redirect("index.php?module=user-users".$vid_url);
					}
				}

				$page->output_header($lang->manage_users);
				$page->output_nav_tabs($sub_tabs, 'manage_users');

				// Display a table warning
				$table = new Table;
				$lang->mass_prune_info = $lang->sprintf($lang->mass_prune_info, count($selected));
				$table->construct_cell($lang->mass_prune_info);
				$table->construct_row();
				$table->output($lang->important);

				if($errors)
				{
					$page->output_inline_error($errors);
				}

				// Display the prune options
				$form = new Form("index.php?module=user-users", "post");
				echo $form->generate_hidden_field('action', 'inline_edit');
				echo $form->generate_hidden_field('inline_action', 'multiprune');
				echo $form->generate_hidden_field('processed', '1');

				$form_container = new FormContainer($lang->mass_prune_posts);

				// Generate a list of days (1 - 31)
				$day_options = array();
				$day_options[] = "&nbsp;";
				for($i = 1; $i <= 31; ++$i)
				{
					$day_options[] = $i;
				}

				// Generate a list of months (1 - 12)
				$month_options = array();
				$month_options[] = "&nbsp;";
				for($i = 1; $i <= 12; ++$i)
				{
					$string = "month_{$i}";
					$month_options[] = $lang->$string;
				}
				$date_box = $form->generate_select_box('day', $day_options, $mybb->input['day']);
				$month_box = $form->generate_select_box('month', $month_options, $mybb->input['month']);
				$year_box = $form->generate_text_box('year', $mybb->input['year'], array('id' => 'year', 'style' => 'width: 50px;'));

				$prune_select = $date_box.$month_box.$year_box;
				$form_container->output_row($lang->manual_date, "", $prune_select, 'date');

				// Generate the set date box
				$set_options = array();
				$set_options[] = $lang->set_an_option;
				for($i = 1; $i <= 6; ++$i)
				{
					$string = "option_{$i}";
					$set_options[] = $lang->$string;
				}

				$form_container->output_row($lang->relative_date, "", $lang->delete_posts." ".$form->generate_select_box('set', $set_options, $mybb->input['set']), 'set');
				$form_container->end();

				$buttons[] = $form->generate_submit_button($lang->prune_posts);
				$form->output_submit_wrapper($buttons);
				$form->end();
				$page->output_footer();
				break;
			case 'multiusergroup':
				if($mybb->input['processed'] == 1)
				{
					// Determine additional usergroups
					if(is_array($mybb->input['additionalgroups']))
					{
						foreach($mybb->input['additionalgroups'] as $key => $gid)
						{
							if($gid == $mybb->input['usergroup'])
							{
								unset($mybb->input['additionalgroups'][$key]);
							}
						}
						$additionalgroups = implode(",", array_map('intval', $mybb->input['additionalgroups']));
					}
					else
					{
						$additionalgroups = '';
					}

					// Create an update array
					$update_array = array(
						"usergroup" => intval($mybb->input['usergroup']),
						"additionalgroups" => $additionalgroups,
						"displaygroup" => intval($mybb->input['displaygroup'])
					);

					// Do the usergroup update for all those selected
					// If the a selected user is a super admin, don't update that user
					foreach($selected as $user)
					{
						if(!is_super_admin($user))
						{
							$users_to_update[] = $user;
						}
					}

					$to_update_count = count($users_to_update);
					if($to_update_count > 0 && is_array($users_to_update))
					{
						// Update the users in the database
						$sql = implode(",", $users_to_update);
						$db->update_query("users", $update_array, "uid IN (".$sql.")");

						// Redirect the admin...
						$mybb->input['action'] = "inline_usergroup";
						log_admin_action($to_update_count);
						my_unsetcookie("inlinemod_useracp");
						flash_message($lang->success_mass_usergroups, 'success');
						admin_redirect("index.php?module=user-users".$vid_url);
					}
					else
					{
						// They tried to edit super admins! Uh-oh!
						$errors[] = $lang->no_usergroup_changed;
					}
				}

				$page->output_header($lang->manage_users);
				$page->output_nav_tabs($sub_tabs, 'manage_users');

				// Display a table warning
				$table = new Table;
				$lang->usergroup_info = $lang->sprintf($lang->usergroup_info, count($selected));
				$table->construct_cell($lang->usergroup_info);
				$table->construct_row();
				$table->output($lang->important);

				if($errors)
				{
					$page->output_inline_error($errors);
				}

				// Display the usergroup options
				$form = new Form("index.php?module=user-users", "post");
				echo $form->generate_hidden_field('action', 'inline_edit');
				echo $form->generate_hidden_field('inline_action', 'multiusergroup');
				echo $form->generate_hidden_field('processed', '1');

				$form_container = new FormContainer($lang->mass_usergroups);

				// Usergroups
				$display_group_options[0] = $lang->use_primary_user_group;
				$options = array();
				$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
				while($usergroup = $db->fetch_array($query))
				{
					$options[$usergroup['gid']] = $usergroup['title'];
					$display_group_options[$usergroup['gid']] = $usergroup['title'];
				}

				if(!is_array($mybb->input['additionalgroups']))
				{
					$mybb->input['additionalgroups'] = explode(',', $mybb->input['additionalgroups']);
				}

				$form_container->output_row($lang->primary_user_group, "", $form->generate_select_box('usergroup', $options, $mybb->input['usergroup'], array('id' => 'usergroup')), 'usergroup');
				$form_container->output_row($lang->additional_user_groups, $lang->additional_user_groups_desc, $form->generate_select_box('additionalgroups[]', $options, $mybb->input['additionalgroups'], array('id' => 'additionalgroups', 'multiple' => true, 'size' => 5)), 'additionalgroups');
				$form_container->output_row($lang->display_user_group, "", $form->generate_select_box('displaygroup', $display_group_options, $mybb->input['displaygroup'], array('id' => 'displaygroup')), 'displaygroup');

				$form_container->end();

				$buttons[] = $form->generate_submit_button($lang->alter_usergroups);
				$form->output_submit_wrapper($buttons);
				$form->end();
				$page->output_footer();
				break;
		}
	}
}

if(!$mybb->input['action'])
{
	$plugins->run_hooks("admin_user_users_start");

	$page->output_header($lang->browse_users);
	echo "<script type=\"text/javascript\" src=\"jscripts/users.js\"></script>";

	$page->output_nav_tabs($sub_tabs, 'browse_users');

	if(isset($mybb->input['search_id']) && $admin_session['data']['user_views'][$mybb->input['search_id']])
	{
		$admin_view = $admin_session['data']['user_views'][$mybb->input['search_id']];
		unset($admin_view['extra_sql']);
	}
	else
	{
		// Showing a specific view
		if(isset($mybb->input['vid']))
		{
			$query = $db->simple_select("adminviews", "*", "vid='".intval($mybb->input['vid'])."'");
			$admin_view = $db->fetch_array($query);
			// View does not exist or this view is private and does not belong to the current user
			if(!$admin_view['vid'] || ($admin_view['visibility'] == 1 && $admin_view['uid'] != $mybb->user['uid']))
			{
				unset($admin_view);
			}
		}

		// Don't have a view? Fetch the default
		if(!isset($admin_view))
		{
			$default_view = fetch_default_view("user");
			if(!$default_view)
			{
				$default_view = "0";
			}
			$query = $db->simple_select("adminviews", "*", "type='user' AND (vid='{$default_view}' OR uid=0)", array("order_by" => "uid", "order_dir" => "desc"));
			$admin_view = $db->fetch_array($query);
		}
	}

	// Fetch a list of all of the views for this user
	$popup = new PopupMenu("views", $lang->views);

	$query = $db->simple_select("adminviews", "*", "type='user' AND (visibility=2 OR uid={$mybb->user['uid']})", array("order_by" => "title"));
	while($view = $db->fetch_array($query))
	{
		$popup->add_item(htmlspecialchars_uni($view['title']), "index.php?module=user-users&amp;vid={$view['vid']}");
	}
	$popup->add_item("<em>{$lang->manage_views}</em>", "index.php?module=user-users&amp;action=views");
	$admin_view['popup'] = $popup->fetch();

	if(isset($mybb->input['type']))
	{
		$admin_view['view_type'] = $mybb->input['type'];
	}

	$results = build_users_view($admin_view);

	if(!$results)
	{
		// If we came from the home page and clicked on the "Activate Users" link, send them back to here
		if($admin_session['data']['from'] == "home")
		{
			flash_message($admin_session['data']['flash_message2']['message'], $admin_session['data']['flash_message2']['type']);
			update_admin_session('flash_message2', '');
			update_admin_session('from', '');
			admin_redirect("index.php");
			exit;
		}
		else
		{
			$errors[] = $lang->error_no_users_found;
		}
	}

	// If we have any error messages, show them
	if($errors)
	{
		if($inline != true)
		{
			echo "<div style=\"display: inline; float: right;\">{$admin_view['popup']}</div><br />\n";
		}
		$page->output_inline_error($errors);
	}

	echo $results;

	$page->output_footer();
}

function build_users_view($view)
{
	global $mybb, $db, $cache, $lang, $user_view_fields, $page;

	$view_title = '';
	if($view['title'])
	{
		$title_string = "view_title_{$view['vid']}";

		if($lang->$title_string)
		{
			$view['title'] = $lang->$title_string;
		}

		$view_title .= " (".htmlspecialchars_uni($view['title']).")";
	}

	// Build the URL to this view
	if(!isset($view['url']))
	{
		$view['url'] = "index.php?module=user-users";
	}
	if(!is_array($view['conditions']))
	{
		$view['conditions'] = unserialize($view['conditions']);
	}
	if(!is_array($view['fields']))
	{
		$view['fields'] = unserialize($view['fields']);
	}
	if(!is_array($view['custom_profile_fields']))
	{
		$view['custom_profile_fields'] = unserialize($view['custom_profile_fields']);
	}
	if(isset($mybb->input['username']))
	{
		$view['conditions']['username'] = $mybb->input['username'];
	}
	if($view['vid'])
	{
		$view['url'] .= "&amp;vid={$view['vid']}";
	}
	else
	{
		// If this is a custom view we need to save everything ready to pass it on from page to page
		global $admin_session;
		if(!$mybb->input['search_id'])
		{
			$search_id = md5(random_str());
			$admin_session['data']['user_views'][$search_id] = $view;
			update_admin_session('user_views', $admin_session['data']['user_views']);
			$mybb->input['search_id'] = $search_id;
		}
		$view['url'] .= "&amp;search_id=".htmlspecialchars_uni($mybb->input['search_id']);
	}

	if(isset($mybb->input['username']))
	{
		$view['url'] .= "&amp;username=".urlencode(htmlspecialchars_uni($mybb->input['username']));
	}

	if(!isset($admin_session['data']['last_users_view']) || $admin_session['data']['last_users_view'] != str_replace("&amp;", "&", $view['url']))
	{
		update_admin_session('last_users_url', str_replace("&amp;", "&", $view['url']));
	}

	if(isset($view['conditions']['referrer'])){
		$view['url'] .= "&amp;action=referrers&amp;uid=".htmlspecialchars_uni($view['conditions']['referrer']);
	}

	// Do we not have any views?
	if(empty($view))
	{
		return false;
	}

	$table = new Table;

	// Build header for table based view
	if($view['view_type'] != "card")
	{
		foreach($view['fields'] as $field)
		{
			if(!$user_view_fields[$field])
			{
				continue;
			}
			$view_field = $user_view_fields[$field];
			$field_options = array();
			if($view_field['width'])
			{
				$field_options['width'] = $view_field['width'];
			}
			if($view_field['align'])
			{
				$field_options['class'] = "align_".$view_field['align'];
			}
			$table->construct_header($view_field['title'], $field_options);
		}
		$table->construct_header("<input type=\"checkbox\" name=\"allbox\" onclick=\"inlineModeration.checkAll(this);\" />"); // Create a header for the "select" boxes
	}

	$search_sql = '1=1';

	// Build the search SQL for users

	// List of valid LIKE search fields
	$user_like_fields = array("username", "email", "website", "icq", "aim", "yahoo", "msn", "signature", "usertitle");
	foreach($user_like_fields as $search_field)
	{
		if(!empty($view['conditions'][$search_field]) && !$view['conditions'][$search_field.'_blank'])
		{
			$search_sql .= " AND u.{$search_field} LIKE '%".$db->escape_string_like($view['conditions'][$search_field])."%'";
		}
		else if(!empty($view['conditions'][$search_field.'_blank']))
		{
			$search_sql .= " AND u.{$search_field} != ''";
		}
	}

	// EXACT matching fields
	$user_exact_fields = array("referrer");
	foreach($user_exact_fields as $search_field)
	{
		if(!empty($view['conditions'][$search_field]))
		{
			$search_sql .= " AND u.{$search_field}='".$db->escape_string($view['conditions'][$search_field])."'";
		}
	}

	// LESS THAN or GREATER THAN
	$direction_fields = array("postnum");
	foreach($direction_fields as $search_field)
	{
		$direction_field = $search_field."_dir";
		if(!empty($view['conditions'][$search_field]) && ($view['conditions'][$search_field] || $view['conditions'][$search_field] === '0') && $view['conditions'][$direction_field])
		{
			switch($view['conditions'][$direction_field])
			{
				case "greater_than":
					$direction = ">";
					break;
				case "less_than":
					$direction = "<";
					break;
				default:
					$direction = "=";
			}
			$search_sql .= " AND u.{$search_field}{$direction}'".$db->escape_string($view['conditions'][$search_field])."'";
		}
	}

	// Registration searching
	$reg_fields = array("regdate");
	foreach($reg_fields as $search_field)
	{
		if(!empty($view['conditions'][$search_field]) && intval($view['conditions'][$search_field]))
		{
			$threshold = TIME_NOW - (intval($view['conditions'][$search_field]) * 24 * 60 * 60);

			$search_sql .= " AND u.{$search_field} >= '{$threshold}'";
		}
	}

	// IP searching
	$ip_fields = array("regip", "lastip");
	foreach($ip_fields as $search_field)
	{
		if(!empty($view['conditions'][$search_field]))
		{
			// IPv6 IP
			if(strpos($view['conditions'][$search_field], ":") !== false)
			{
				$view['conditions'][$search_field] = str_replace("*", "%", $view['conditions'][$search_field]);
				$ip_sql = "{$search_field} LIKE '".$db->escape_string($view['conditions'][$search_field])."'";
			}
			else
			{
				$ip_range = fetch_longipv4_range($view['conditions'][$search_field]);
				if(!is_array($ip_range))
				{
					$ip_sql = "long{$search_field}='{$ip_range}'";
				}
				else
				{
					$ip_sql = "long{$search_field} > '{$ip_range[0]}' AND long{$search_field} < '{$ip_range[1]}'";
				}
			}
			$search_sql .= " AND {$ip_sql}";
		}
	}

	// Post IP searching
	if(!empty($view['conditions']['postip']))
	{
		// IPv6 IP
		if(strpos($view['conditions']['postip'], ":") !== false)
		{
			$view['conditions']['postip'] = str_replace("*", "%", $view['conditions']['postip']);
			$ip_sql = "ipaddress LIKE '".$db->escape_string($view['conditions']['postip'])."'";
		}
		else
		{
			$ip_range = fetch_longipv4_range($view['conditions']['postip']);
			if(!is_array($ip_range))
			{
				$ip_sql = "longipaddress='{$ip_range}'";
			}
			else
			{
				$ip_sql = "longipaddress > '{$ip_range[0]}' AND longipaddress < '{$ip_range[1]}'";
			}
		}
		$ip_uids = array(0);
		$query = $db->simple_select("posts", "uid", $ip_sql);
		while($uid = $db->fetch_field($query, "uid"))
		{
			$ip_uids[] = $uid;
		}
		$search_sql .= " AND u.uid IN(".implode(',', $ip_uids).")";
		unset($ip_uids);
	}

	// Custom Profile Field searching
	if($view['custom_profile_fields'])
	{
		$userfield_sql = '1=1';
		foreach($view['custom_profile_fields'] as $column => $input)
		{
			if(is_array($input))
			{
				foreach($input as $value => $text)
				{
					if($value == $column)
					{
						$value = $text;
					}

					if($value == $lang->na)
					{
						continue;
					}

					if(strpos($column, '_blank') !== false)
					{
						$column = str_replace('_blank', '', $column);
						$userfield_sql .= ' AND '.$db->escape_string($column)." != ''";
					}
					else
					{
						$userfield_sql .= ' AND '.$db->escape_string($column)."='".$db->escape_string($value)."'";
					}
				}
			}
			else if(!empty($input))
			{
				if($input == $lang->na)
				{
					continue;
				}

				if(strpos($column, '_blank') !== false)
				{
					$column = str_replace('_blank', '', $column);
					$userfield_sql .= ' AND '.$db->escape_string($column)." != ''";
				}
				else
				{
					$userfield_sql .= ' AND '.$db->escape_string($column)." LIKE '%".$db->escape_string($input)."%'";
				}
			}
		}

		if($userfield_sql != '1=1')
		{
			$userfield_uids = array(0);
			$query = $db->simple_select("userfields", "ufid", $userfield_sql);
			while($userfield = $db->fetch_array($query))
			{
				$userfield_uids[] = $userfield['ufid'];
			}
			$search_sql .= " AND u.uid IN(".implode(',', $userfield_uids).")";
			unset($userfield_uids);
		}
	}

	// Usergroup based searching
	if(isset($view['conditions']['usergroup']))
	{
		if(!is_array($view['conditions']['usergroup']))
		{
			$view['conditions']['usergroup'] = array($view['conditions']['usergroup']);
		}

		foreach($view['conditions']['usergroup'] as $usergroup)
		{
			$usergroup = intval($usergroup);

			if(!$usergroup)
			{
				continue;
			}

			switch($db->type)
			{
				case "pgsql":
				case "sqlite":
					$additional_sql .= " OR ','||additionalgroups||',' LIKE '%,{$usergroup},%'";
					break;
				default:
					$additional_sql .= "OR CONCAT(',',additionalgroups,',') LIKE '%,{$usergroup},%'";
			}
		}

		$search_sql .= " AND (u.usergroup IN (".implode(",", array_map('intval', $view['conditions']['usergroup'])).") {$additional_sql})";
	}

	// COPPA users only?
	if(isset($view['conditions']['coppa']))
	{
		$search_sql .= " AND u.coppauser=1 AND u.usergroup=5";
	}

	// Extra SQL?
	if(isset($view['extra_sql']))
	{
		$search_sql .= $view['extra_sql'];
	}

	// Lets fetch out how many results we have
	$query = $db->query("
		SELECT COUNT(u.uid) AS num_results
		FROM ".TABLE_PREFIX."users u
		WHERE {$search_sql}
	");
	$num_results = $db->fetch_field($query, "num_results");

	// No matching results then return false
	if(!$num_results)
	{
		return false;
	}
	// Generate the list of results
	else
	{
		if(!$view['perpage'])
		{
			$view['perpage'] = 20;
		}
		$view['perpage'] = intval($view['perpage']);

		// Establish which page we're viewing and the starting index for querying
		// Establish which page we're viewing and the starting index for querying
		if(!isset($mybb->input['page']))
		{
			$mybb->input['page'] = 1;
		}
		else
		{
			$mybb->input['page'] = intval($mybb->input['page']);
		}

		if($mybb->input['page'])
		{
			$start = ($mybb->input['page'] - 1) * $view['perpage'];
		}
		else
		{
			$start = 0;
			$mybb->input['page'] = 1;
		}

		$from_bit = "";
		if(isset($mybb->input['from']) && $mybb->input['from'] == "home")
		{
			$from_bit = "&amp;from=home";
		}

		switch($view['sortby'])
		{
			case "regdate":
			case "lastactive":
			case "postnum":
			case "reputation":
				$view['sortby'] = $db->escape_string($view['sortby']);
				break;
			case "numposts":
				$view['sortby'] = "postnum";
				break;
			case "warninglevel":
				$view['sortby'] = "warningpoints";
				break;
			default:
				$view['sortby'] = "username";
		}

		if($view['sortorder'] != "desc")
		{
			$view['sortorder'] = "asc";
		}

		$usergroups = $cache->read("usergroups");

		// Fetch matching users
		$query = $db->query("
			SELECT u.*
			FROM ".TABLE_PREFIX."users u
			WHERE {$search_sql}
			ORDER BY {$view['sortby']} {$view['sortorder']}
			LIMIT {$start}, {$view['perpage']}
		");
		$users = '';
		while($user = $db->fetch_array($query))
		{
			$comma = $groups_list = '';
			$user['view']['username'] = "<a href=\"index.php?module=user-users&amp;action=edit&amp;uid={$user['uid']}\">".format_name($user['username'], $user['usergroup'], $user['displaygroup'])."</a>";
			$user['view']['usergroup'] = $usergroups[$user['usergroup']]['title'];
			if($user['additionalgroups'])
			{
				$additional_groups = explode(",", $user['additionalgroups']);

				foreach($additional_groups as $group)
				{
					$groups_list .= "{$comma}{$usergroups[$group]['title']}";
					$comma = $lang->comma;
				}
			}
			if(!$groups_list)
			{
				$groups_list = $lang->none;
			}
			$user['view']['additionalgroups'] = "<small>{$groups_list}</small>";
			$user['view']['email'] = "<a href=\"mailto:".htmlspecialchars_uni($user['email'])."\">".htmlspecialchars_uni($user['email'])."</a>";
			$user['view']['regdate'] = my_date($mybb->settings['dateformat'], $user['regdate']).", ".my_date($mybb->settings['timeformat'], $user['regdate']);
			$user['view']['lastactive'] = my_date($mybb->settings['dateformat'], $user['lastactive']).", ".my_date($mybb->settings['timeformat'], $user['lastactive']);

			// Build popup menu
			$popup = new PopupMenu("user_{$user['uid']}", $lang->options);
			$popup->add_item($lang->edit_profile_and_settings, "index.php?module=user-users&amp;action=edit&amp;uid={$user['uid']}");
			$popup->add_item($lang->ban_user, "index.php?module=user-banning&amp;uid={$user['uid']}#username");

			if($user['usergroup'] == 5)
			{
				if($user['coppauser'])
				{
					$popup->add_item($lang->approve_coppa_user, "index.php?module=user-users&amp;action=activate_user&amp;uid={$user['uid']}&amp;my_post_key={$mybb->post_code}{$from_bit}");
				}
				else
				{
					$popup->add_item($lang->approve_user, "index.php?module=user-users&amp;action=activate_user&amp;uid={$user['uid']}&amp;my_post_key={$mybb->post_code}{$from_bit}");
				}
			}

			$popup->add_item($lang->delete_user, "index.php?module=user-users&amp;action=delete&amp;uid={$user['uid']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->user_deletion_confirmation}')");
			$popup->add_item($lang->show_referred_users, "index.php?module=user-users&amp;action=referrers&amp;uid={$user['uid']}");
			$popup->add_item($lang->show_ip_addresses, "index.php?module=user-users&amp;action=ipaddresses&amp;uid={$user['uid']}");
			$popup->add_item($lang->show_attachments, "index.php?module=forum-attachments&amp;results=1&amp;username=".urlencode(htmlspecialchars_uni($user['username'])));
			$user['view']['controls'] = $popup->fetch();

			// Fetch the reputation for this user
			if($usergroups[$user['usergroup']]['usereputationsystem'] == 1 && $mybb->settings['enablereputation'] == 1)
			{
				$user['view']['reputation'] = get_reputation($user['reputation']);
			}
			else
			{
				$reputation = "-";
			}

			if($mybb->settings['enablewarningsystem'] != 0 && $usergroups[$user['usergroup']]['canreceivewarnings'] != 0)
			{
				$warning_level = round($user['warningpoints']/$mybb->settings['maxwarningpoints']*100);
				if($warning_level > 100)
				{
					$warning_level = 100;
				}
				$user['view']['warninglevel'] = get_colored_warning_level($warning_level);
			}

			if($user['avatar'] && !stristr($user['avatar'], 'http://'))
			{
				$user['avatar'] = "../{$user['avatar']}";
			}
			if($view['view_type'] == "card")
			{
				$scaled_avatar = fetch_scaled_avatar($user, 80, 80);
			}
			else
			{
				$scaled_avatar = fetch_scaled_avatar($user, 34, 34);
			}
			if(!$user['avatar'])
			{
				$user['avatar'] = "styles/{$page->style}/images/default_avatar.gif";
			}
			$user['view']['avatar'] = "<img src=\"".htmlspecialchars_uni($user['avatar'])."\" alt=\"\" width=\"{$scaled_avatar['width']}\" height=\"{$scaled_avatar['height']}\" />";

			if($view['view_type'] == "card")
			{
				$users .= build_user_view_card($user, $view, $i);
			}
			else
			{
				build_user_view_table($user, $view, $table);
			}
		}

		// If card view, we need to output the results
		if($view['view_type'] == "card")
		{
			$table->construct_cell($users);
			$table->construct_row();
		}
	}

	if(!isset($view['table_id']))
	{
		$view['table_id'] = "users_list";
	}

	$switch_view = "<div class=\"float_right\">";
	$switch_url = $view['url'];
	if($mybb->input['page'] > 0)
	{
		$switch_url .= "&amp;page=".intval($mybb->input['page']);
	}
	if($view['view_type'] != "card")
	{
		$switch_view .= "<strong>{$lang->table_view}</strong> | <a href=\"{$switch_url}&amp;type=card\" style=\"font-weight: normal;\">{$lang->card_view}</a>";
	}
	else
	{
		$switch_view .= "<a href=\"{$switch_url}&amp;type=table\" style=\"font-weight: normal;\">{$lang->table_view}</a> | <strong>{$lang->card_view}</strong>";
	}
	$switch_view .= "</div>";

	// Do we need to construct the pagination?
	if($num_results > $view['perpage'])
	{
		$pagination = draw_admin_pagination($mybb->input['page'], $view['perpage'], $num_results, $view['url']."&amp;type={$view['view_type']}");
		$search_class = "float_right";
		$search_style = "";
	}
	else
	{
		$search_class = '';
		$search_style = "text-align: right;";
	}

	$search_action = $view['url'];
	// stop &username= in the query string
	if($view_upos = strpos($search_action, '&amp;username='))
	{
		$search_action = substr($search_action, 0, $view_upos);
	}
	$search_action = str_replace("&amp;", "&", $search_action);
	$search = new Form(htmlspecialchars_uni($search_action), 'post', 'search_form', 0, '', true);
	$built_view = $search->construct_return;
	$built_view .= "<div class=\"{$search_class}\" style=\"padding-bottom: 3px; margin-top: -9px; {$search_style}\">";
	$built_view .= $search->generate_hidden_field('action', 'search')."\n";
	if(isset($view['conditions']['username']))
	{
		$default_class = '';
		$value = $view['conditions']['username'];
	}
	else
	{
		$default_class = "search_default";
		$value = $lang->search_for_user;
	}
	$built_view .= $search->generate_text_box('username', $value, array('id' => 'search_keywords', 'class' => "{$default_class} field150 field_small"))."\n";
	$built_view .= "<input type=\"submit\" class=\"search_button\" value=\"{$lang->search}\" />\n";
	if($view['popup'])
	{
		$built_view .= " <div style=\"display: inline\">{$view['popup']}</div>\n";
	}
	$built_view .= "<script type='text/javascript'>
		var form = document.getElementById('search_form');
		form.onsubmit = function() {
			var search = document.getElementById('search_keywords');
			if(search.value == '' || search.value == '".addcslashes($lang->search_for_user, "'")."')
			{
				search.focus();
				return false;
			}
		}

		var search = document.getElementById('search_keywords');
		search.onfocus = function()
		{
			if(this.value == '".addcslashes($lang->search_for_user, "'")."')
			{
				$(this).removeClassName('search_default');
				this.value = '';
			}
		}
		search.onblur = function()
		{
			if(this.value == '')
			{
				$(this).addClassName('search_default');
				this.value = '".addcslashes($lang->search_for_user, "'")."';
			}
		}
		// fix the styling used if we have a different default value
		if(search.value != '".addcslashes($lang->search_for_user, "'")."')
		{
			$(search).removeClassName('search_default');
		}
		</script>\n";
	$built_view .= "</div>\n";

	// Autocompletion for usernames
	$built_view .= '
	<script type="text/javascript" src="../jscripts/autocomplete.js?ver=140"></script>
	<script type="text/javascript">
	<!--
		new autoComplete("search_keywords", "../xmlhttp.php?action=get_users", {valueSpan: "username"});
	// -->
	</script>';

	$built_view .= $search->end();

	if(isset($pagination))
	{
		$built_view .= $pagination;
	}
	if($view['view_type'] != "card")
	{
		$checkbox = '';
	}
	else
	{
		$checkbox = "<input type=\"checkbox\" name=\"allbox\" onclick=\"inlineModeration.checkAll(this)\" /> ";
	}
	$built_view .= $table->construct_html("{$switch_view}<div>{$checkbox}{$lang->users}{$view_title}</div>", 1, "", $view['table_id']);
	if(isset($pagination))
	{
		$built_view .= $pagination;
	}

	$built_view .= '
<script type="text/javascript" src="'.$mybb->settings['bburl'].'/jscripts/inline_moderation.js?ver=1400"></script>
<form action="index.php?module=user-users" method="post">
<input type="hidden" name="my_post_key" value="'.$mybb->post_code.'" />
<input type="hidden" name="action" value="inline_edit" />
<div class="float_right"><span class="smalltext"><strong>'.$lang->inline_edit.'</strong></span>
<select name="inline_action" class="inline_select">
	<option value="multiactivate">'.$lang->inline_activate.'</option>
	<option value="multiban">'.$lang->inline_ban.'</option>
	<option value="multiusergroup">'.$lang->inline_usergroup.'</option>
	<option value="multidelete">'.$lang->inline_delete.'</option>
	<option value="multiprune">'.$lang->inline_prune.'</option>
</select>
<input type="submit" class="button" name="go" value="'.$lang->go.' (0)" id="inline_go" />&nbsp;
<input type="button" onclick="javascript:inlineModeration.clearChecked();" value="'.$lang->clear.'" class="button" />
</div>
</form>
<br style="clear: both;" />
<script type="text/javascript">
<!--
	var go_text = "'.$lang->go.'";
	var all_text = "1";
	var inlineType = "user";
	var inlineId = "acp";
// -->
</script>';

	return $built_view;
}

function build_user_view_card($user, $view, &$i)
{
	global $user_view_fields;

	++$i;
	if($i == 3)
	{
		$i = 1;
	}

	// Loop through fields user wants to show
	foreach($view['fields'] as $field)
	{
		if(!$user_view_fields[$field])
		{
			continue;
		}

		$view_field = $user_view_fields[$field];

		// Special conditions for avatar
		if($field == "avatar")
		{
			$avatar = $user['view']['avatar'];
		}
		else if($field == "controls")
		{
			$controls = $user['view']['controls'];
		}
		// Otherwise, just user data
		else if($field != "username")
		{
			if(isset($user['view'][$field]))
			{
				$value = $user['view'][$field];
			}
			else
			{
				$value = $user[$field];
			}

			if($field == "postnum")
			{
				$value = my_number_format($value);
			}

			$user_details[] = "<strong>{$view_field['title']}:</strong> {$value}";
		}

	}
	// Floated to the left or right?
	if($i == 1)
	{
		$float = "left";
	}
	else
	{
		$float = "right";
	}

	// And build the final card
	$card = "<fieldset id=\"uid_{$user['uid']}\" style=\"width: 47%; float: {$float};\">\n";
	$card .= "<legend><input type=\"checkbox\" class=\"checkbox\" name=\"inlinemod_{$user['uid']}\" id=\"inlinemod_{$user['uid']}\" value=\"1\" onclick=\"$('uid_{$user['uid']}').toggleClassName('inline_selected');\" /> {$user['view']['username']}</legend>\n";
	if($avatar)
	{
		$card .= "<div class=\"user_avatar\">{$avatar}</div>\n";
	}
	if($user_details)
	{
		$card .= "<div class=\"user_details\">".implode("<br />", $user_details)."</div>\n";
	}
	if($controls)
	{
		$card .= "<div class=\"float_right\" style=\"padding: 4px;\">{$controls}</div>\n";
	}
	$card .= "</fieldset>";
	return $card;

}

function build_user_view_table($user, $view, &$table)
{
	global $user_view_fields;

	foreach($view['fields'] as $field)
	{
		if(!$user_view_fields[$field])
		{
			continue;
		}
		$view_field = $user_view_fields[$field];
		$field_options = array();
		if($view_field['align'])
		{
			$field_options['class'] = "align_".$view_field['align'];
		}
		if($user['view'][$field])
		{
			$value = $user['view'][$field];
		}
		else
		{
			$value = $user[$field];
		}
		$table->construct_cell($value, $field_options);
	}

	$table->construct_cell("<input type=\"checkbox\" class=\"checkbox\" name=\"inlinemod_{$user['uid']}\" id=\"inlinemod_{$user['uid']}\" value=\"1\" onclick=\"$('uid_{$user['uid']}').toggleClassName('inline_selected');\" />");

	$table->construct_row();
}

function fetch_scaled_avatar($user, $max_width=80, $max_height=80)
{
	$scaled_dimensions = array(
		"width" => $max_width,
		"height" => $max_height,
	);

	if($user['avatar'])
	{
		if($user['avatardimensions'])
		{
			require_once MYBB_ROOT."inc/functions_image.php";
			list($width, $height) = explode("|", $user['avatardimensions']);
			$scaled_dimensions = scale_image($width, $height, $max_width, $max_height);
		}
	}

	return array("width" => $scaled_dimensions['width'], "height" => $scaled_dimensions['height']);
}

function output_custom_profile_fields($fields, $values, &$form_container, &$form, $search=false)
{
	global $lang;

	if(!is_array($fields))
	{
		return;
	}
	foreach($fields as $profile_field)
	{
		$profile_field['type'] = htmlspecialchars_uni($profile_field['type']);
		list($type, $options) = explode("\n", $profile_field['type'], 2);
		$type = trim($type);
		$field_name = "fid{$profile_field['fid']}";

		switch($type)
		{
			case "multiselect":
				if(!is_array($values[$field_name]))
				{
					$user_options = explode("\n", $values[$field_name]);
				}
				else
				{
					$user_options = $values[$field_name];
				}

				foreach($user_options as $val)
				{
					$selected_options[$val] = $val;
				}

				$select_options = explode("\n", $options);
				$options = array();
				if($search == true)
				{
					$select_options[''] = $lang->na;
				}

				foreach($select_options as $val)
				{
					$val = trim($val);
					$options[$val] = $val;
				}
				if(!$profile_field['length'])
				{
					$profile_field['length'] = 3;
				}
				$code = $form->generate_select_box("profile_fields[{$field_name}][]", $options, $selected_options, array('id' => "profile_field_{$field_name}", 'multiple' => true, 'size' => $profile_field['length']));
				break;
			case "select":
				$select_options = array();
				if($search == true)
				{
					$select_options[''] = $lang->na;
				}
				$select_options += explode("\n", $options);
				$options = array();
				foreach($select_options as $val)
				{
					$val = trim($val);
					$options[$val] = $val;
				}
				if(!$profile_field['length'])
				{
					$profile_field['length'] = 1;
				}
				if($search == true)
				{
					$code = $form->generate_select_box("profile_fields[{$field_name}][{$field_name}]", $options, $values[$field_name], array('id' => "profile_field_{$field_name}", 'size' => $profile_field['length']));
				}
				else
				{
					$code = $form->generate_select_box("profile_fields[{$field_name}]", $options, $values[$field_name], array('id' => "profile_field_{$field_name}", 'size' => $profile_field['length']));
				}
				break;
			case "radio":
				$radio_options = array();
				if($search == true)
				{
					$radio_options[''] = $lang->na;
				}
				$radio_options += explode("\n", $options);
				foreach($radio_options as $val)
				{
					$val = trim($val);
					$code .= $form->generate_radio_button("profile_fields[{$field_name}]", $val, $val, array('id' => "profile_field_{$field_name}", 'checked' => ($val == $values[$field_name] ? true : false)))."<br />";
				}
				break;
			case "checkbox":
				if(!is_array($values[$field_name]))
				{
					$user_options = explode("\n", $values[$field_name]);
				}
				else
				{
					$user_options = $values[$field_name];
				}
				foreach($user_options as $val)
				{
					$selected_options[$val] = $val;
				}
				$select_options = array();
				if($search == true)
				{
					$select_options[''] = $lang->na;
				}
				$select_options += explode("\n", $options);
				foreach($select_options as $val)
				{
					$val = trim($val);
					$code .= $form->generate_check_box("profile_fields[{$field_name}][]", $val, $val, array('id' => "profile_field_{$field_name}", 'checked' => ($val == $selected_options[$val] ? true : false)))."<br />";
				}
				break;
			case "textarea":
				$extra = '';
				if(isset($mybb->input['action']) && $mybb->input['action'] == "search")
				{
					$extra = " {$lang->or} ".$form->generate_check_box("profile_fields[{$field_name}_blank]", 1, $lang->is_not_blank, array('id' => "{$field_name}_blank", 'checked' => $values[$field_name.'_blank']));
				}

				$code = $form->generate_text_area("profile_fields[{$field_name}]", $values[$field_name], array('id' => "profile_field_{$field_name}", 'rows' => 6, 'cols' => 50)).$extra;
				break;
			default:
				$extra = '';
				if(isset($mybb->input['action']) && $mybb->input['action'] == "search")
				{
					$extra = " {$lang->or} ".$form->generate_check_box("profile_fields[{$field_name}_blank]", 1, $lang->is_not_blank, array('id' => "{$field_name}_blank", 'checked' => $values[$field_name.'_blank']));
				}

				$code = $form->generate_text_box("profile_fields[{$field_name}]", $values[$field_name], array('id' => "profile_field_{$field_name}", 'maxlength' => $profile_field['maxlength'], 'length' => $profile_field['length'])).$extra;
				break;
		}

		$form_container->output_row($profile_field['name'], $profile_field['description'], $code, "", array('id' => "profile_field_{$field_name}"));
		$code = $user_options = $selected_options = $radio_options = $val = $options = '';
	}
}

function user_search_conditions($input=array(), &$form)
{
	global $mybb, $db, $lang;

	if(!$input)
	{
		$input = $mybb->input;
	}

	if(!is_array($input['conditions']))
	{
		$input['conditions'] = unserialize($input['conditions']);
	}

	if(!is_array($input['profile_fields']))
	{
		$input['profile_fields'] = unserialize($input['profile_fields']);
	}

	if(!is_array($input['fields']))
	{
		$input['fields'] = unserialize($input['fields']);
	}

	$form_container = new FormContainer($lang->find_users_where);
	$form_container->output_row($lang->username_contains, "", $form->generate_text_box('conditions[username]', $input['conditions']['username'], array('id' => 'username')), 'username');
	$form_container->output_row($lang->email_address_contains, "", $form->generate_text_box('conditions[email]', $input['conditions']['email'], array('id' => 'email')), 'email');

	$options = array();
	$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
	while($usergroup = $db->fetch_array($query))
	{
		$options[$usergroup['gid']] = $usergroup['title'];
	}

	$form_container->output_row($lang->is_member_of_groups, $lang->additional_user_groups_desc, $form->generate_select_box('conditions[usergroup][]', $options, $input['conditions']['usergroup'], array('id' => 'usergroups', 'multiple' => true, 'size' => 5)), 'usergroups');

	$form_container->output_row($lang->website_contains, "", $form->generate_text_box('conditions[website]', $input['conditions']['website'], array('id' => 'website'))." {$lang->or} ".$form->generate_check_box('conditions[website_blank]', 1, $lang->is_not_blank, array('id' => 'website_blank', 'checked' => $input['conditions']['website_blank'])), 'website');
	$form_container->output_row($lang->icq_number_contains, "", $form->generate_text_box('conditions[icq]', $input['conditions']['icq'], array('id' => 'icq'))." {$lang->or} ".$form->generate_check_box('conditions[icq_blank]', 1, $lang->is_not_blank, array('id' => 'icq_blank', 'checked' => $input['conditions']['icq_blank'])), 'icq');
	$form_container->output_row($lang->aim_handle_contains, "", $form->generate_text_box('conditions[aim]', $input['conditions']['aim'], array('id' => 'aim'))." {$lang->or} ".$form->generate_check_box('conditions[aim_blank]', 1, $lang->is_not_blank, array('id' => 'aim_blank', 'checked' => $input['conditions']['aim_blank'])), 'aim');
	$form_container->output_row($lang->yahoo_contains, "", $form->generate_text_box('conditions[yahoo]', $input['conditions']['yahoo'], array('id' => 'yahoo'))." {$lang->or} ".$form->generate_check_box('conditions[yahoo_blank]', 1, $lang->is_not_blank, array('id' => 'yahoo_blank', 'checked' => $input['conditions']['yahoo_blank'])), 'yahoo');
	$form_container->output_row($lang->msn_contains, "", $form->generate_text_box('conditions[msn]', $input['conditions']['msn'], array('id' => 'msn'))." {$lang->or} ".$form->generate_check_box('conditions[msn_blank]', 1, $lang->is_not_blank, array('id' => 'msn_blank', 'checked' => $input['conditions']['msn_blank'])), 'msn');
	$form_container->output_row($lang->signature_contains, "", $form->generate_text_box('conditions[signature]', $input['conditions']['signature'], array('id' => 'signature'))." {$lang->or} ".$form->generate_check_box('conditions[signature_blank]', 1, $lang->is_not_blank, array('id' => 'signature_blank', 'checked' => $input['conditions']['signature_blank'])), 'signature');
	$form_container->output_row($lang->user_title_contains, "", $form->generate_text_box('conditions[usertitle]', $input['conditions']['usertitle'], array('id' => 'usertitle'))." {$lang->or} ".$form->generate_check_box('conditions[usertitle_blank]', 1, $lang->is_not_blank, array('id' => 'usertitle_blank', 'checked' => $input['conditions']['usertitle_blank'])), 'usertitle');
	$greater_options = array(
		"greater_than" => $lang->greater_than,
		"is_exactly" => $lang->is_exactly,
		"less_than" => $lang->less_than
	);
	$form_container->output_row($lang->post_count_is, "", $form->generate_select_box('conditions[postnum_dir]', $greater_options, $input['conditions']['postnum_dir'], array('id' => 'numposts_dir'))." ".$form->generate_text_box('conditions[postnum]', $input['conditions']['postnum'], array('id' => 'numposts')), 'numposts');

	$form_container->output_row($lang->reg_in_x_days, '', $form->generate_text_box('conditions[regdate]', $input['conditions']['regdate'], array('id' => 'regdate')).' '.$lang->days, 'regdate');
	$form_container->output_row($lang->reg_ip_matches, $lang->wildcard, $form->generate_text_box('conditions[regip]', $input['conditions']['regip'], array('id' => 'regip')), 'regip');
	$form_container->output_row($lang->last_known_ip, $lang->wildcard, $form->generate_text_box('conditions[lastip]', $input['conditions']['lastip'], array('id' => 'lastip')), 'lastip');
	$form_container->output_row($lang->posted_with_ip, $lang->wildcard, $form->generate_text_box('conditions[postip]', $input['conditions']['postip'], array('id' => 'postip')), 'postip');

	$form_container->end();

	// Custom profile fields go here
	$form_container = new FormContainer($lang->custom_profile_fields_match);

	// Fetch custom profile fields
	$query = $db->simple_select("profilefields", "*", "", array('order_by' => 'disporder'));
	while($profile_field = $db->fetch_array($query))
	{
		if($profile_field['required'] == 1)
		{
			$profile_fields['required'][] = $profile_field;
		}
		else
		{
			$profile_fields['optional'][] = $profile_field;
		}
	}

	output_custom_profile_fields($profile_fields['required'], $input['profile_fields'], $form_container, $form, true);
	output_custom_profile_fields($profile_fields['optional'], $input['profile_fields'], $form_container, $form, true);

	$form_container->end();

	// Autocompletion for usernames
	echo '
	<script type="text/javascript" src="../jscripts/autocomplete.js?ver=140"></script>
	<script type="text/javascript">
	<!--
		new autoComplete("username", "../xmlhttp.php?action=get_users", {valueSpan: "username"});
	// -->
	</script>';
}

?>