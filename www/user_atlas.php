<?php

	include("include/init.php");

	loadlib("privatesquare_checkins");
	loadlib("privatesquare_checkins_utils");
	loadlib("privatesquare_export");
	loadlib("foursquare_users");

	$fsq_id = get_int32("foursquare_id");

	if (! $fsq_id){
		error_404();
	}

	$status_id = get_int32("status_id");
	$status_label = get_str("status_label");

	if ((! $status_id) && (! $status_label)){
		error_404();
	}

	$status_map = privatesquare_checkins_status_map();

	if ((! $status_id) && ($status_label)){

		$status_map_str = privatesquare_checkins_status_map("string_keys");

		foreach ($status_map_str as $label => $id){

			$clean = str_replace(" ", "", $label);

			if ($status_label == $clean){
				$status_id = $id;
				break;
			}
		}
	}

	if (! isset($status_map[$status_id])){
		error_404();
	}

	if (in_array($status_id, array(0, 1))){
		error_404();
	}

	$str_status = $status_map[$status_id];
	$status_url = str_replace(" ", "", $str_status);

	$whereami = "user/{$fsq_id}/list/{$status_url}/";

	$woeid = get_int32("woeid");

	if ($woeid){
		$whereami .= "{$woeid}/";
	}

	login_ensure_loggedin($whereami);

	$fsq_user = foursquare_users_get_by_foursquare_id($fsq_id);

	if (! $fsq_user){
		error_404();
	}

	$owner = users_get_by_id($fsq_user['user_id']);
	$is_own = ($owner['id'] == $GLOBALS['cfg']['user']['id']) ? 1 : 0;

	# for now...

	if (! $is_own){
		error_403();
	}

	$more = array();

	if ($woeid){
		$more['locality'] = $woeid;
	}

	if ($page = get_int32("page")){
		$more['page'] = $page;
	}

	$rsp = privatesquare_checkins_venues_for_user_and_status($owner, $status_id, $more);
	$GLOBALS['smarty']->assign_by_ref("venues", $rsp['rows']);
	
	$geo_stats = privatesquare_checkins_utils_geo_stats($rsp['rows']);
	$GLOBALS['smarty']->assign_by_ref("geo_stats", $geo_stats);

	$GLOBALS['smarty']->assign_by_ref("owner", $owner);
	$GLOBALS['smarty']->assign_by_ref("status_map", $status_map);

	$GLOBALS['smarty']->assign("status_id", $status_id);
	$GLOBALS['smarty']->assign("str_status", $str_status);

	$pagination_url = urls_atlas_of_desire_for_user($owner) . "{$status_url}/";

	if ($woeid){

		$pagination_url .= "{$woeid}/";
		$GLOBALS['smarty']->assign("woeid", $woeid);
	}

	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);

	$export_formats = privatesquare_export_valid_formats();
	$GLOBALS['smarty']->assign("export_formats", array_keys($export_formats));

	$GLOBALS['smarty']->display("page_user_atlas.txt");
	exit();
?>
