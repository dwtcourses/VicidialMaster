<?php
# lead_tools_advanced.php - Various tools for lead basic lead management, advanced version.
#
# Copyright (C) 2014  Matt Florell,Michael Cargile <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 131016-1948 - Initial Build based upon lead_tools.php
# 140113-0853 - Added USERONLY to ANYONE callback switcher
#

$version = '2.8-1';
$build = '140113-0853';

# This limit is to prevent data inconsistancies.
# If there are too many leads in a list this
# script might not finish before the php execution limit.
$list_lead_limit = 100000;

# maximum call count the script will work with
$max_count = 20;

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$ip = getenv("REMOTE_ADDR");
$SQLdate = date("Y-m-d H:i:s");

$DB=0;
$move_submit="";
$update_submit="";
$delete_submit="";
$callback_submit="";
$confirm_move="";
$confirm_update="";
$confirm_delete="";
$confirm_callback="";

if (isset($_GET["DB"])) {$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"])) {$DB=$_POST["DB"];}
if (isset($_GET["move_submit"])) {$move_submit=$_GET["move_submit"];}
	elseif (isset($_POST["move_submit"])) {$move_submit=$_POST["move_submit"];}
if (isset($_GET["update_submit"])) {$update_submit=$_GET["update_submit"];}
	elseif (isset($_POST["update_submit"])) {$update_submit=$_POST["update_submit"];}
if (isset($_GET["delete_submit"])) {$delete_submit=$_GET["delete_submit"];}
	elseif (isset($_POST["delete_submit"])) {$delete_submit=$_POST["delete_submit"];}
if (isset($_GET["callback_submit"])) {$callback_submit=$_GET["callback_submit"];}
	elseif (isset($_POST["callback_submit"])) {$callback_submit=$_POST["callback_submit"];}
if (isset($_GET["confirm_move"])) {$confirm_move=$_GET["confirm_move"];}
	elseif (isset($_POST["confirm_move"])) {$confirm_move=$_POST["confirm_move"];}
if (isset($_GET["confirm_update"])) {$confirm_move=$_GET["confirm_update"];}
	elseif (isset($_POST["confirm_update"])) {$confirm_update=$_POST["confirm_update"];}
if (isset($_GET["confirm_delete"])) {$confirm_delete=$_GET["confirm_delete"];}
	elseif (isset($_POST["confirm_delete"])) {$confirm_delete=$_POST["confirm_delete"];}
if (isset($_GET["confirm_callback"])) {$confirm_callback=$_GET["confirm_callback"];}
	elseif (isset($_POST["confirm_callback"])) {$confirm_callback=$_POST["confirm_callback"];}

$DB = preg_replace('/[^0-9]/','',$DB);
$move_submit = preg_replace('/[^-_0-9a-zA-Z]/','',$move_submit);
$update_submit = preg_replace('/[^-_0-9a-zA-Z]/','',$update_submit);
$delete_submit = preg_replace('/[^-_0-9a-zA-Z]/','',$delete_submit);
$callback_submit = preg_replace('/[^-_0-9a-zA-Z]/','',$callback_submit);
$confirm_move = preg_replace('/[^-_0-9a-zA-Z]/','',$confirm_move);
$confirm_update = preg_replace('/[^-_0-9a-zA-Z]/','',$confirm_update);
$confirm_delete = preg_replace('/[^-_0-9a-zA-Z]/','',$confirm_delete);
$confirm_callback = preg_replace('/[^-_0-9a-zA-Z]/','',$confirm_callback);
$delete_status = preg_replace('/[^-_0-9a-zA-Z]/','',$delete_status);

if ($DB)
	{
	echo "<p>DB = $DB | $move_submit = $move_submit | update_submit = $update_submit | delete_submit = $delete_submit | callback_submit = $callback_submit | confirm_move = $confirm_move | confirm_update = $confirm_update | confirm_delete = $confirm_delete | confirm_callback = $confirm_callback</p>";
	}


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$sys_settings_stmt = "SELECT use_non_latin, outbound_autodial_active, sounds_central_control_active FROM system_settings;";
$sys_settings_rslt=mysql_to_mysqli($sys_settings_stmt, $link);
if ($DB) {echo "$sys_settings_stmt\n";}
$num_rows = mysqli_num_rows($sys_settings_rslt);
if ($num_rows > 0)
	{
	$sys_settings_row=mysqli_fetch_row($sys_settings_rslt);
	$non_latin = $sys_settings_row[0];
	$SSoutbound_autodial_active = $sys_settings_row[1];
	$sounds_central_control_active = $sys_settings_row[2];
	}
else
	{
	# there is something really weird if there are no system settings
	exit;
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	}
$list_id_override = preg_replace('/[^0-9]/','',$list_id_override);

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth < 1)
	{
	$VDdisplayMESSAGE = "Login incorrect, please try again";
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = "Too many login attempts, try again in 15 minutes";
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");      // HTTP/1.0

# valid user
$rights_stmt = "SELECT load_leads,user_group, delete_lists, modify_leads, modify_lists from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$load_leads =      $rights_row[0];
$user_group =      $rights_row[1];
$delete_lists =  $rights_row[2];
$modify_leads =  $rights_row[3];
$modify_lists =  $rights_row[4];

# check their permissions
if ( $load_leads < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo "You do not have permissions to load leads\n";
	exit;
	}
if ( $modify_leads < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo "You do not have permissions to modify leads\n";
	exit;
	}
if ( $modify_lists < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo "You do not have permissions to modify lists\n";
	exit;
	}

echo "<html>\n";
echo "<head>\n";
echo "<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=utf-8'>\n";
echo "<!-- VERSIÓN: <?php echo $version ?>     CONSTRUCCION: <?php echo $build ?> -->\n";
?>
<script type="text/javascript">
window.onload = function() {
	// move functions initialization
	document.getElementById("enable_move_status").onclick = enableMoveStatus;
	document.getElementById("enable_move_count").onclick = enableMoveCount;
	document.getElementById("enable_move_country_code").onclick = enableMovePaísCode;
	document.getElementById("enable_move_vendor_lead_code").onclick = enableMoveVendorLeadCode;
	document.getElementById("enable_move_source_id").onclick = enableMovePaísSourceId;
	document.getElementById("enable_move_owner").onclick = enableMoveOwner;
	document.getElementById("enable_move_entry_date").onclick = enableMoveEntryDate;
	document.getElementById("enable_move_modify_date").onclick = enableMoveModifyDate;
	document.getElementById("enable_move_security_phrase").onclick = enableMoveSecurityPhrase;

	// update functions initialization
	document.getElementById("enable_update_from_status").onclick = enableUpdateFromStatus;
	document.getElementById("enable_update_count").onclick = enableUpdateCount;
	document.getElementById("enable_update_country_code").onclick = enableUpdatePaísCode;
	document.getElementById("enable_update_vendor_lead_code").onclick = enableUpdateVendorLeadCode;
	document.getElementById("enable_update_source_id").onclick = enableUpdatePaísSourceId;
	document.getElementById("enable_update_owner").onclick = enableUpdateOwner;
	document.getElementById("enable_update_entry_date").onclick = enableUpdateEntryDate;
	document.getElementById("enable_update_modify_date").onclick = enableUpdateModifyDate;
	document.getElementById("enable_update_security_phrase").onclick = enableUpdateSecurityPhrase;

	// delete functions initialization
	document.getElementById("enable_delete_count").onclick = enableDeleteCount;
	document.getElementById("enable_delete_country_code").onclick = enableDeletePaísCode;
	document.getElementById("enable_delete_vendor_lead_code").onclick = enableDeleteVendorLeadCode;
	document.getElementById("enable_delete_source_id").onclick = enableDeletePaísSourceId;
	document.getElementById("enable_delete_owner").onclick = enableDeleteOwner;
	document.getElementById("enable_delete_entry_date").onclick = enableDeleteEntryDate;
	document.getElementById("enable_delete_modify_date").onclick = enableDeleteModifyDate;
	document.getElementById("enable_delete_security_phrase").onclick = enableDeleteSecurityPhrase;
	document.getElementById("enable_delete_lead_id").onclick = enableDeleteLeadId;

	// callback functions initialization
	document.getElementById("enable_callback_entry_date").onclick = enableCallbackEntryDate;
	document.getElementById("enable_callback_callback_date").onclick = enableCallbackCallbackDate;
}

// move functions
function enableMoveStatus(){
	if (document.getElementById("enable_move_status").checked)  {
		document.getElementById("move_status").disabled = false;
	} else {
		document.getElementById("move_status").disabled = true;
	}
}
function enableMoveCountryCode(){
	if (document.getElementById("enable_move_country_code").checked)  {
		document.getElementById("move_country_code").disabled = false;
	} else {
		document.getElementById("move_country_code").disabled = true;
	}
}
function enableMoveVendorLeadCode(){
	if (document.getElementById("enable_move_vendor_lead_code").checked)  {
		document.getElementById("move_vendor_lead_code").disabled = false;
	} else {
		document.getElementById("move_vendor_lead_code").disabled = true;
	}
}
function enableMoveCountrySourceId(){
	if (document.getElementById("enable_move_source_id").checked)  {
		document.getElementById("move_source_id").disabled = false;
	} else {
		document.getElementById("move_source_id").disabled = true;
	}
}
function enableMoveOwner(){
	if (document.getElementById("enable_move_owner").checked)  {
		document.getElementById("move_owner").disabled = false;
	} else {
		document.getElementById("move_owner").disabled = true;
	}
}
function enableMoveEntryDate(){
	if (document.getElementById("enable_move_entry_date").checked)  {
		document.getElementById("move_entry_date").disabled = false;
	} else {
		document.getElementById("move_entry_date").disabled = true;
	}
}
function enableMoveModifyDate(){
	if (document.getElementById("enable_move_modify_date").checked)  {
		document.getElementById("move_modify_date").disabled = false;
	} else {
		document.getElementById("move_modify_date").disabled = true;
	}
}
function enableMoveSecurityPhrase(){
	if (document.getElementById("enable_move_security_phrase").checked)  {
		document.getElementById("move_security_phrase").disabled = false;
	} else {
		document.getElementById("move_security_phrase").disabled = true;
	}
}
function enableMoveCount(){
	if (document.getElementById("enable_move_count").checked)  {
		document.getElementById("move_count_op").disabled = false;
		document.getElementById("move_count_num").disabled = false;
	} else {
		document.getElementById("move_count_op").disabled = true;
		document.getElementById("move_count_num").disabled = true;
	}
}

// update functions
function enableUpdateFromStatus(){
	if (document.getElementById("enable_update_from_status").checked)  {
		document.getElementById("update_from_status").disabled = false;
	} else {
		document.getElementById("update_from_status").disabled = true;
	}
}
function enableUpdateCountryCode(){
	if (document.getElementById("enable_update_country_code").checked)  {
		document.getElementById("update_country_code").disabled = false;
	} else {
		document.getElementById("update_country_code").disabled = true;
	}
}
function enableUpdateVendorLeadCode(){
	if (document.getElementById("enable_update_vendor_lead_code").checked)  {
		document.getElementById("update_vendor_lead_code").disabled = false;
	} else {
		document.getElementById("update_vendor_lead_code").disabled = true;
	}
}
function enableUpdateCountrySourceId(){
	if (document.getElementById("enable_update_source_id").checked)  {
		document.getElementById("update_source_id").disabled = false;
	} else {
		document.getElementById("update_source_id").disabled = true;
	}
}
function enableUpdateOwner(){
	if (document.getElementById("enable_update_owner").checked)  {
		document.getElementById("update_owner").disabled = false;
	} else {
		document.getElementById("update_owner").disabled = true;
	}
}
function enableUpdateEntryDate(){
	if (document.getElementById("enable_update_entry_date").checked)  {
		document.getElementById("update_entry_date").disabled = false;
	} else {
		document.getElementById("update_entry_date").disabled = true;
	}
}
function enableUpdateModifyDate(){
	if (document.getElementById("enable_update_modify_date").checked)  {
		document.getElementById("update_modify_date").disabled = false;
	} else {
		document.getElementById("update_modify_date").disabled = true;
	}
}
function enableUpdateSecurityPhrase(){
	if (document.getElementById("enable_update_security_phrase").checked)  {
		document.getElementById("update_security_phrase").disabled = false;
	} else {
		document.getElementById("update_security_phrase").disabled = true;
	}
}
function enableUpdateCount(){
	if (document.getElementById("enable_update_count").checked)  {
		document.getElementById("update_count_op").disabled = false;
		document.getElementById("update_count_num").disabled = false;
	} else {
		document.getElementById("update_count_op").disabled = true;
		document.getElementById("update_count_num").disabled = true;
	}
}

// delete functions
function enableDeleteCount(){
	if (document.getElementById("enable_delete_count").checked)  {
		document.getElementById("delete_count_op").disabled = false;
		document.getElementById("delete_count_num").disabled = false;
	} else {
		document.getElementById("delete_count_op").disabled = true;
		document.getElementById("delete_count_num").disabled = true;
	}
}
function enableDeleteCountryCode(){
	if (document.getElementById("enable_delete_country_code").checked)  {
		document.getElementById("delete_country_code").disabled = false;
	} else {
		document.getElementById("delete_country_code").disabled = true;
	}
}
function enableDeleteVendorLeadCode(){
	if (document.getElementById("enable_delete_vendor_lead_code").checked)  {
		document.getElementById("delete_vendor_lead_code").disabled = false;
	} else {
		document.getElementById("delete_vendor_lead_code").disabled = true;
	}
}
function enableDeleteCountrySourceId(){
    if (document.getElementById("enable_delete_source_id").checked)  {
		document.getElementById("delete_source_id").disabled = false;
    } else {
		document.getElementById("delete_source_id").disabled = true;
    }
}
function enableDeleteOwner(){
	if (document.getElementById("enable_delete_owner").checked)  {
		document.getElementById("delete_owner").disabled = false;
	} else {
		document.getElementById("delete_owner").disabled = true;
	}
}
function enableDeleteEntryDate(){
	if (document.getElementById("enable_delete_entry_date").checked)  {
		document.getElementById("delete_entry_date").disabled = false;
	} else {
		document.getElementById("delete_entry_date").disabled = true;
	}
}
function enableDeleteModifyDate(){
	if (document.getElementById("enable_delete_modify_date").checked)  {
		document.getElementById("delete_modify_date").disabled = false;
	} else {
		document.getElementById("delete_modify_date").disabled = true;
	}
}
function enableDeleteSecurityPhrase(){
	if (document.getElementById("enable_delete_security_phrase").checked)  {
		document.getElementById("delete_security_phrase").disabled = false;
	} else {
		document.getElementById("delete_security_phrase").disabled = true;
	}
}
function enableDeleteLeadId(){
	if (document.getElementById("enable_delete_lead_id").checked)  {
		document.getElementById("delete_lead_id").disabled = false;
	} else {
		document.getElementById("delete_lead_id").disabled = true;
	}
}

// callback functions
function enableCallbackEntryDate(){
	if (document.getElementById("enable_callback_entry_date").checked)  {
		document.getElementById("callback_entry_start_date").disabled = false;
		document.getElementById("callback_entry_end_date").disabled = false;
	} else {
		document.getElementById("callback_entry_start_date").disabled = true;
		document.getElementById("callback_entry_end_date").disabled = true;
	}
}
function enableCallbackCallbackDate(){
	if (document.getElementById("enable_callback_callback_date").checked)  {
		document.getElementById("callback_callback_start_date").disabled = false;
		document.getElementById("callback_callback_end_date").disabled = false;
	} else {
		document.getElementById("callback_callback_start_date").disabled = true;
		document.getElementById("callback_callback_end_date").disabled = true;
	}
}

</script>

<?php
echo "<title>ADMINISTRATION: Lead Tools</title>\n";

##### BEGIN Set variables to make header show properly #####
$ADD =  '999998';
$hh =       'admin';
$LOGast_admin_access = '1';
$SSoutbound_autodial_active = '1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$admin_color =    '#FFFF99';
$admin_font =      'BLACK';
$admin_color =    '#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");

echo "<table width=$page_width bgcolor=#E6E6E6 cellpadding=2 cellspacing=0>\n";
echo "<tr bgcolor='#E6E6E6'>\n";
echo "<td align=left>\n";
echo "<font face='ARIAL,HELVETICA' size=2>\n";
echo "<b> &nbsp; <a href=\"lead_tools.php\">Básico Lead Tools</a> &nbsp; | &nbsp; Advanced Lead Tools</b>\n";
echo "</font>\n";
echo "</td>\n";
echo "<td align=right><font face='ARIAL,HELVETICA' size=2><b> &nbsp; </td>\n";
echo "</tr>\n";



echo "<tr bgcolor='#F0F5FE'><td align=left colspan=2><font face='ARIAL,HELVETICA' color=black size=3> &nbsp; \n";

# move confirmation page
if ($move_submit == "move" )
	{
	# get the variables
	$enable_move_status="";
	$enable_move_country_code="";
	$enable_move_vendor_lead_code="";
	$enable_move_source_id="";
	$enable_move_owner="";
	$enable_move_entry_date="";
	$enable_move_modify_date="";
	$enable_move_security_phrase="";
	$enable_move_count="";
	$move_country_code="";
	$move_vendor_lead_code="";
	$move_source_id="";
	$move_owner="";
	$move_entry_date="";
	$move_modify_date="";
	$move_security_phrase="";
	$move_from_list="";
	$move_to_list="";
	$move_status="";
	$move_count_op="";
	$move_count_num="";

	# check the get / post data for the variables
	if (isset($_GET["enable_move_status"])) {$enable_move_status=$_GET["enable_move_status"];}
		elseif (isset($_POST["enable_move_status"])) {$enable_move_status=$_POST["enable_move_status"];}
	if (isset($_GET["enable_move_country_code"])) {$enable_move_country_code=$_GET["enable_move_country_code"];}
		elseif (isset($_POST["enable_move_country_code"])) {$enable_move_country_code=$_POST["enable_move_country_code"];}
	if (isset($_GET["enable_move_vendor_lead_code"])) {$enable_move_vendor_lead_code=$_GET["enable_move_vendor_lead_code"];}
		elseif (isset($_POST["enable_move_vendor_lead_code"])) {$enable_move_vendor_lead_code=$_POST["enable_move_vendor_lead_code"];}
	if (isset($_GET["enable_move_source_id"])) {$enable_move_source_id=$_GET["enable_move_source_id"];}
		elseif (isset($_POST["enable_move_source_id"])) {$enable_move_source_id=$_POST["enable_move_source_id"];}
	if (isset($_GET["enable_move_owner"])) {$enable_move_owner=$_GET["enable_move_owner"];}
		elseif (isset($_POST["enable_move_owner"])) {$enable_move_owner=$_POST["enable_move_owner"];}
	if (isset($_GET["enable_move_entry_date"])) {$enable_move_entry_date=$_GET["enable_move_entry_date"];}
		elseif (isset($_POST["enable_move_entry_date"])) {$enable_move_entry_date=$_POST["enable_move_entry_date"];}
	if (isset($_GET["enable_move_modify_date"])) {$enable_move_modify_date=$_GET["enable_move_modify_date"];}
		elseif (isset($_POST["enable_move_modify_date"])) {$enable_move_modify_date=$_POST["enable_move_modify_date"];}
	if (isset($_GET["enable_move_security_phrase"])) {$enable_move_security_phrase=$_GET["enable_move_security_phrase"];}
		elseif (isset($_POST["enable_move_security_phrase"])) {$enable_move_security_phrase=$_POST["enable_move_security_phrase"];}
	if (isset($_GET["enable_move_count"])) {$enable_move_count=$_GET["enable_move_count"];}
		elseif (isset($_POST["enable_move_count"])) {$enable_move_count=$_POST["enable_move_count"];}
	if (isset($_GET["move_country_code"])) {$move_country_code=$_GET["move_country_code"];}
		elseif (isset($_POST["move_country_code"])) {$move_country_code=$_POST["move_country_code"];}
	if (isset($_GET["move_vendor_lead_code"])) {$move_vendor_lead_code=$_GET["move_vendor_lead_code"];}
		elseif (isset($_POST["move_vendor_lead_code"])) {$move_vendor_lead_code=$_POST["move_vendor_lead_code"];}
	if (isset($_GET["move_source_id"])) {$move_source_id=$_GET["move_source_id"];}
		elseif (isset($_POST["move_source_id"])) {$move_source_id=$_POST["move_source_id"];}
	if (isset($_GET["move_owner"])) {$move_owner=$_GET["move_owner"];}
		elseif (isset($_POST["move_owner"])) {$move_owner=$_POST["move_owner"];}
	if (isset($_GET["move_entry_date"])) {$move_entry_date=$_GET["move_entry_date"];}
		elseif (isset($_POST["move_entry_date"])) {$move_entry_date=$_POST["move_entry_date"];}
	if (isset($_GET["move_modify_date"])) {$move_modify_date=$_GET["move_modify_date"];}
		elseif (isset($_POST["move_modify_date"])) {$move_modify_date=$_POST["move_modify_date"];}
	if (isset($_GET["move_security_phrase"])) {$move_security_phrase=$_GET["move_security_phrase"];}
		elseif (isset($_POST["move_security_phrase"])) {$move_security_phrase=$_POST["move_security_phrase"];}
	if (isset($_GET["move_from_list"])) {$move_from_list=$_GET["move_from_list"];}
		elseif (isset($_POST["move_from_list"])) {$move_from_list=$_POST["move_from_list"];}
	if (isset($_GET["move_to_list"])) {$move_to_list=$_GET["move_to_list"];}
		elseif (isset($_POST["move_to_list"])) {$move_to_list=$_POST["move_to_list"];}
	if (isset($_GET["move_status"])) {$move_status=$_GET["move_status"];}
		elseif (isset($_POST["move_status"])) {$move_status=$_POST["move_status"];}
	if (isset($_GET["move_count_op"])) {$move_count_op=$_GET["move_count_op"];}
		elseif (isset($_POST["move_count_op"])) {$move_count_op=$_POST["move_count_op"];}
	if (isset($_GET["move_count_num"])) {$move_count_num=$_GET["move_count_num"];}
		elseif (isset($_POST["move_count_num"])) {$move_count_num=$_POST["move_count_num"];}

	if ($DB)
		{
		echo "<p>enable_move_status = $enable_move_status | enable_move_country_code = $enable_move_country_code | enable_move_vendor_lead_code = $enable_move_vendor_lead_code | enable_move_source_id = $enable_move_source_id | enable_move_owner = $enable_move_owner | enable_move_entry_date = $enable_move_entry_date | enable_move_modify_date = $enable_move_modify_date | enable_move_security_phrase = $enable_move_security_phrase | enable_move_count = $enable_move_count | move_country_code = $move_country_code | move_vendor_lead_code = $move_vendor_lead_code | move_source_id = $move_source_id | move_owner = $move_owner | move_owner = $move_entry_date | move_modify_date = $move_modify_date | move_security_phrase = $move_security_phrase | move_from_list = $move_from_list | move_to_list = $move_to_list | move_status = $move_status | move_count_op = $move_count_op | move_count_num = $move_count_num</p>";
		}

	# filter out anything bad
	$enable_move_status = preg_replace('/[^a-zA-Z]/','',$enable_move_status);
	$enable_move_country_code = preg_replace('/[^a-zA-Z]/','',$enable_move_country_code);
	$enable_move_vendor_lead_code = preg_replace('/[^a-zA-Z]/','',$enable_move_vendor_lead_code);
	$enable_move_source_id = preg_replace('/[^a-zA-Z]/','',$enable_move_source_id);
	$enable_move_owner = preg_replace('/[^a-zA-Z]/','',$enable_move_owner);
	$enable_move_entry_date = preg_replace('/[^a-zA-Z]/','',$enable_move_entry_date);
	$enable_move_modify_date = preg_replace('/[^a-zA-Z]/','',$enable_move_modify_date);
	$enable_move_security_phrase = preg_replace('/[^a-zA-Z]/','',$enable_move_security_phrase);
	$enable_move_count = preg_replace('/[^a-zA-Z]/','',$enable_move_count);
	$move_country_code = preg_replace('/[^-_%a-zA-Z0-9]/','',$move_country_code);
	$move_vendor_lead_code = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_vendor_lead_code);
	$move_source_id = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_source_id);
	$move_owner = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_owner);
	$move_entry_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_entry_date);
	$move_modify_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_modify_date);
	$move_security_phrase = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_security_phrase);
	$move_status = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_status);
	$move_from_list = preg_replace('/[^0-9]/','',$move_from_list);
	$move_to_list = preg_replace('/[^0-9]/','',$move_to_list);
	$move_count_num = preg_replace('/[^0-9]/','',$move_count_num);
	$move_count_op = preg_replace('/[^<>=]/','',$move_count_op);

	if ($DB)
		{
		echo "<p>enable_move_status = $enable_move_status | enable_move_country_code = $enable_move_country_code | enable_move_vendor_lead_code = $enable_move_vendor_lead_code | enable_move_source_id = $enable_move_source_id | enable_move_owner = $enable_move_owner | enable_move_entry_date = $enable_move_entry_date | enable_move_modify_date = $enable_move_modify_date | enable_move_security_phrase = $enable_move_security_phrase | enable_move_count = $enable_move_count | move_country_code = $move_country_code | move_vendor_lead_code = $move_vendor_lead_code | move_source_id = $move_source_id | move_owner = $move_owner | move_owner = $move_entry_date | move_modify_date = $move_modify_date | move_security_phrase = $move_security_phrase | move_from_list = $move_from_list | move_to_list = $move_to_list | move_status = $move_status | move_count_op = $move_count_op | move_count_num = $move_count_num</p>";
		}

	# build the count operation phrase
	$move_count_op_phrase="";
	if ( $move_count_op == "<" )
		{
		$move_count_op_phrase= "less than ";
		}
	elseif ( $move_count_op == "<=" )
		{
		$move_count_op_phrase= "less than or equal to ";
		}
	elseif ( $move_count_op == ">" )
		{
		$move_count_op_phrase= "greater than ";
		}
	elseif ( $move_count_op == ">=" )
		{
		$move_count_op_phrase= "greater than or equal to ";
		}

	# make sure the required fields are set
	if ($move_from_list == '') { missing_required_field('From List'); }
	if ($move_to_list == '') { missing_required_field('To List'); }


	# build the sql query's where phrase and the move phrase
	$sql_where = "";
	$move_parm = "";
	if (($enable_move_status == "enabled") && ($move_status != ''))
		{
		if ($move_status == '---BLANK---') {$move_status = '';}
		$sql_where = $sql_where . " and status like '$move_status' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;status is like $move_status<br />";
		if ($move_status == '') {$move_status = '---BLANK---';}
		}
	elseif ($enable_move_status == "enabled")
		{
		blank_field('Status',true);
		}
	if (($enable_move_country_code == "enabled") && ($move_country_code != ''))
		{
		if ($move_country_code == '---BLANK---') {$move_country_code = '';}
		$sql_where = $sql_where . " and country_code like '$move_country_code' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;country code is like $move_country_code<br />";
		if ($move_country_code == '') {$move_country_code = '---BLANK---';}
		}
	elseif ($enable_move_country_code == "enabled")
		{
		blank_field('País Code',true);
		}
	if (($enable_move_vendor_lead_code == "enabled") && ($move_vendor_lead_code != ''))
		{
		if ($move_vendor_lead_code == '---BLANK---') {$move_vendor_lead_code = '';}
		$sql_where = $sql_where . " and vendor_lead_code like '$move_vendor_lead_code' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;Código del Proveedor del contactos is like $move_vendor_lead_code<br />";
		if ($move_vendor_lead_code == '') {$move_vendor_lead_code = '---BLANK---';}
		}
	elseif ($enable_move_vendor_lead_code == "enabled")
		{
		blank_field('Vendor Lead Code',true);
		}
	if (($enable_move_source_id == "enabled") && ( $move_source_id != ''))
		{
		if ($move_source_id == '---BLANK---') {$move_source_id = '';}
		$sql_where = $sql_where . " and source_id like '$move_source_id' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;source id is like $move_source_id<br />";
		if ($move_source_id == '') {$move_source_id = '---BLANK---';}
		}
	elseif ($enable_move_source_id == "enabled")
		{
		blank_field('Source ID',true);
		}
	if (($enable_move_owner == "enabled") && ($move_owner != ''))
		{
		if ($move_owner == '---BLANK---') {$move_owner = '';}
		$sql_where = $sql_where . " and owner like '$move_owner' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;owner is like $move_owner<br />";
		if ($move_owner == '') {$move_owner = '---BLANK---';}
		}
	elseif ($enable_move_owner == "enabled")
		{
		blank_field('Owner',true);
		}
	if (($enable_move_security_phrase == "enabled") && ($move_security_phrase != ''))
		{
		if ($move_security_phrase == '---BLANK---') {$move_security_phrase = '';}
		$sql_where = $sql_where . " and security_phrase like '$move_security_phrase' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;secuirty_phrase is like $move_security_phrase<br />";
		if ($move_security_phrase == '') {$move_security_phrase = '---BLANK---';}
		}
	elseif ($enable_move_security_phrase == "enabled")
		{
		blank_field('Frase de Seguridad',true);
		}
	if (($enable_move_entry_date == "enabled") && ($move_entry_date != ''))
		{
		if ($move_entry_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and entry_date == '' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and entry_date >= '$move_entry_date 00:00:00' and entry_date <= '$move_entry_date 23:59:59' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date was on $move_entry_date<br />";
			}
		}
	elseif ($enable_move_entry_date == "enabled")
		{
		blank_field('Entry Date',true);
		}
	if (($enable_move_modify_date == "enabled") && ($move_modify_date != ''))
		{
		if ($move_modify_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and modify_date == '' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;modify date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and modify_date >= '$move_modify_date 00:00:00' and modify_date <= '$move_modify_date 23:59:59' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;last modify date was on $move_modify_date<br />";
			}
		}
	elseif ($enable_move_modify_date == "enabled")
		{
		blank_field('ModificarDate',true);
		}
	if (($enable_move_count == "enabled") && ($move_count_op != '') && ($move_count_num != ''))
		{
		if ($move_count_op == '---BLANK---') {$move_count_op = '';}
		if ($move_count_num == '---BLANK---') {$move_count_num = '';}
		$sql_where = $sql_where . " and called_count $move_count_op $move_count_num";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;called count is $move_count_op_phrase $move_count_num<br />";
		if ($move_count_op == '') {$move_count_op = '---BLANK---';}
		if ($move_count_num == '') {$move_count_num = '---BLANK---';}
		}
	elseif ($enable_move_count == "enabled")
		{
		blank_field('Move Count',true);
		}

	# get the number of leads this action will move
	$move_lead_count=0;
	$move_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$move_from_list' $sql_where";
	if ($DB) { echo "|$move_lead_count_stmt|\n"; }
	$move_lead_count_rslt = mysql_to_mysqli($move_lead_count_stmt, $link);
	$move_lead_count_row = mysqli_fetch_row($move_lead_count_rslt);
	$move_lead_count = $move_lead_count_row[0];

	# get the number of leads in the list this action will move to
	$to_list_lead_count=0;
	$to_list_lead_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$move_to_list'";
	if ($DB) { echo "|$to_list_lead_stmt|\n"; }
	$to_list_lead_rslt = mysql_to_mysqli($to_list_lead_stmt, $link);
	$to_list_lead_row = mysqli_fetch_row($to_list_lead_rslt);
	$to_list_lead_count = $to_list_lead_row[0];

	# check to see if we will exceed list_lead_limit in the move to list
	if ( $to_list_lead_count + $move_lead_count > $list_lead_limit )
		{
		echo "<html>\n";
		echo "<head>\n";
		echo "<!-- VERSIÓN: $version     CONSTRUCCION: $build -->\n";
		echo "</head>\n";
		echo "<body>\n";
		echo "<p>Sorry. This operation will cause list $move_to_list to exceed $list_lead_limit leads which is not allowed.</p>\n";
		echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
		echo "</body>\n</html>\n";
		}
	else
		{
		echo "<p>You are about to move $move_lead_count leads from list $move_from_list to $move_to_list with the following parameters:<br /><br />$move_parm <br />Please press confirm to continue.</p>\n";
		echo "<center><form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=enable_move_status value='$enable_move_status'>\n";
		echo "<input type=hidden name=enable_move_country_code value='$enable_move_country_code'>\n";
		echo "<input type=hidden name=enable_move_vendor_lead_code value='$enable_move_vendor_lead_code'>\n";
		echo "<input type=hidden name=enable_move_source_id value='$enable_move_source_id'>\n";
		echo "<input type=hidden name=enable_move_owner value='$enable_move_owner'>\n";
		echo "<input type=hidden name=enable_move_entry_date value='$enable_move_entry_date'>\n";
		echo "<input type=hidden name=enable_move_modify_date value='$enable_move_modify_date'>\n";
		echo "<input type=hidden name=enable_move_security_phrase value='$enable_move_security_phrase'>\n";
		echo "<input type=hidden name=enable_move_count value='$enable_move_count'>\n";
		echo "<input type=hidden name=move_country_code value='$move_country_code'>\n";
		echo "<input type=hidden name=move_vendor_lead_code value='$move_vendor_lead_code'>\n";
		echo "<input type=hidden name=move_source_id value='$move_source_id'>\n";
		echo "<input type=hidden name=move_owner value='$move_owner'>\n";
		echo "<input type=hidden name=move_entry_date value='$move_entry_date'>\n";
		echo "<input type=hidden name=move_modify_date value='$move_modify_date'>\n";
		echo "<input type=hidden name=move_security_phrase value='$move_security_phrase'>\n";
		echo "<input type=hidden name=move_from_list value='$move_from_list'>\n";
		echo "<input type=hidden name=move_to_list value='$move_to_list'>\n";
		echo "<input type=hidden name=move_status value='$move_status'>\n";
		echo "<input type=hidden name=move_count_op value='$move_count_op'>\n";
		echo "<input type=hidden name=move_count_num value='$move_count_num'>\n";
		echo "<input type=hidden name=DB value='$DB'>\n";
		echo "<input type=submit name=confirm_move value=confirm>\n";
		echo "</form></center>\n";
		echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
		echo "</body>\n</html>\n";
		}
	}

# actually do the move
if ($confirm_move == "confirm")
	{
	# get the variables
	$enable_move_status="";
	$enable_move_country_code="";
	$enable_move_vendor_lead_code="";
	$enable_move_source_id="";
	$enable_move_owner="";
	$enable_move_entry_date="";
	$enable_move_modify_date="";
	$enable_move_security_phrase="";
	$enable_move_count="";
	$move_country_code="";
	$move_vendor_lead_code="";
	$move_source_id="";
	$move_owner="";
	$move_entry_date="";
	$move_modify_date="";
	$move_security_phrase="";
	$move_from_list="";
	$move_to_list="";
	$move_status="";
	$move_count_op="";
	$move_count_num="";

	# check the get / post data for the variables
	if (isset($_GET["enable_move_status"])) {$enable_move_status=$_GET["enable_move_status"];}
		elseif (isset($_POST["enable_move_status"])) {$enable_move_status=$_POST["enable_move_status"];}
	if (isset($_GET["enable_move_country_code"])) {$enable_move_country_code=$_GET["enable_move_country_code"];}
		elseif (isset($_POST["enable_move_country_code"])) {$enable_move_country_code=$_POST["enable_move_country_code"];}
	if (isset($_GET["enable_move_vendor_lead_code"])) {$enable_move_vendor_lead_code=$_GET["enable_move_vendor_lead_code"];}
		elseif (isset($_POST["enable_move_vendor_lead_code"])) {$enable_move_vendor_lead_code=$_POST["enable_move_vendor_lead_code"];}
	if (isset($_GET["enable_move_source_id"])) {$enable_move_source_id=$_GET["enable_move_source_id"];}
		elseif (isset($_POST["enable_move_source_id"])) {$enable_move_source_id=$_POST["enable_move_source_id"];}
	if (isset($_GET["enable_move_owner"])) {$enable_move_owner=$_GET["enable_move_owner"];}
		elseif (isset($_POST["enable_move_owner"])) {$enable_move_owner=$_POST["enable_move_owner"];}
	if (isset($_GET["enable_move_entry_date"])) {$enable_move_entry_date=$_GET["enable_move_entry_date"];}
		elseif (isset($_POST["enable_move_entry_date"])) {$enable_move_entry_date=$_POST["enable_move_entry_date"];}
	if (isset($_GET["enable_move_modify_date"])) {$enable_move_modify_date=$_GET["enable_move_modify_date"];}
		elseif (isset($_POST["enable_move_modify_date"])) {$enable_move_modify_date=$_POST["enable_move_modify_date"];}
	if (isset($_GET["enable_move_security_phrase"])) {$enable_move_security_phrase=$_GET["enable_move_security_phrase"];}
		elseif (isset($_POST["enable_move_security_phrase"])) {$enable_move_security_phrase=$_POST["enable_move_security_phrase"];}
	if (isset($_GET["enable_move_count"])) {$enable_move_count=$_GET["enable_move_count"];}
		elseif (isset($_POST["enable_move_count"])) {$enable_move_count=$_POST["enable_move_count"];}
	if (isset($_GET["move_country_code"])) {$move_country_code=$_GET["move_country_code"];}
		elseif (isset($_POST["move_country_code"])) {$move_country_code=$_POST["move_country_code"];}
	if (isset($_GET["move_vendor_lead_code"])) {$move_vendor_lead_code=$_GET["move_vendor_lead_code"];}
		elseif (isset($_POST["move_vendor_lead_code"])) {$move_vendor_lead_code=$_POST["move_vendor_lead_code"];}
	if (isset($_GET["move_source_id"])) {$move_source_id=$_GET["move_source_id"];}
		elseif (isset($_POST["move_source_id"])) {$move_source_id=$_POST["move_source_id"];}
	if (isset($_GET["move_owner"])) {$move_owner=$_GET["move_owner"];}
		elseif (isset($_POST["move_owner"])) {$move_owner=$_POST["move_owner"];}
	if (isset($_GET["move_entry_date"])) {$move_entry_date=$_GET["move_entry_date"];}
		elseif (isset($_POST["move_entry_date"])) {$move_entry_date=$_POST["move_entry_date"];}
	if (isset($_GET["move_modify_date"])) {$move_modify_date=$_GET["move_modify_date"];}
		elseif (isset($_POST["move_modify_date"])) {$move_modify_date=$_POST["move_modify_date"];}
	if (isset($_GET["move_security_phrase"])) {$move_security_phrase=$_GET["move_security_phrase"];}
		elseif (isset($_POST["move_security_phrase"])) {$move_security_phrase=$_POST["move_security_phrase"];}
	if (isset($_GET["move_from_list"])) {$move_from_list=$_GET["move_from_list"];}
		elseif (isset($_POST["move_from_list"])) {$move_from_list=$_POST["move_from_list"];}
	if (isset($_GET["move_to_list"])) {$move_to_list=$_GET["move_to_list"];}
		elseif (isset($_POST["move_to_list"])) {$move_to_list=$_POST["move_to_list"];}
	if (isset($_GET["move_status"])) {$move_status=$_GET["move_status"];}
		elseif (isset($_POST["move_status"])) {$move_status=$_POST["move_status"];}
	if (isset($_GET["move_count_op"])) {$move_count_op=$_GET["move_count_op"];}
		elseif (isset($_POST["move_count_op"])) {$move_count_op=$_POST["move_count_op"];}
	if (isset($_GET["move_count_num"])) {$move_count_num=$_GET["move_count_num"];}
		elseif (isset($_POST["move_count_num"])) {$move_count_num=$_POST["move_count_num"];}

	if ($DB)
		{
		echo "<p>enable_move_status = $enable_move_status | enable_move_country_code = $enable_move_country_code | enable_move_vendor_lead_code = $enable_move_vendor_lead_code | enable_move_source_id = $enable_move_source_id | enable_move_owner = $enable_move_owner | enable_move_entry_date = $enable_move_entry_date | enable_move_modify_date = $enable_move_modify_date | enable_move_security_phrase = $enable_move_security_phrase | enable_move_count = $enable_move_count | move_country_code = $move_country_code | move_vendor_lead_code = $move_vendor_lead_code | move_source_id = $move_source_id | move_owner = $move_owner | move_owner = $move_entry_date | move_modify_date = $move_modify_date | move_security_phrase = $move_security_phrase | move_from_list = $move_from_list | move_to_list = $move_to_list | move_status = $move_status | move_count_op = $move_count_op | move_count_num = $move_count_num</p>";
		}

	# filter out anything bad
	$enable_move_status = preg_replace('/[^a-zA-Z]/','',$enable_move_status);
	$enable_move_country_code = preg_replace('/[^a-zA-Z]/','',$enable_move_country_code);
	$enable_move_vendor_lead_code = preg_replace('/[^a-zA-Z]/','',$enable_move_vendor_lead_code);
	$enable_move_source_id = preg_replace('/[^a-zA-Z]/','',$enable_move_source_id);
	$enable_move_owner = preg_replace('/[^a-zA-Z]/','',$enable_move_owner);
	$enable_move_entry_date = preg_replace('/[^a-zA-Z]/','',$enable_move_entry_date);
	$enable_move_modify_date = preg_replace('/[^a-zA-Z]/','',$enable_move_modify_date);
	$enable_move_security_phrase = preg_replace('/[^a-zA-Z]/','',$enable_move_security_phrase);
	$enable_move_count = preg_replace('/[^a-zA-Z]/','',$enable_move_count);
	$move_country_code = preg_replace('/[^-_%a-zA-Z0-9]/','',$move_country_code);
	$move_vendor_lead_code = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_vendor_lead_code);
	$move_source_id = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_source_id);
	$move_owner = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_owner);
	$move_entry_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_entry_date);
	$move_modify_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_modify_date);
	$move_security_phrase = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_security_phrase);
	$move_status = preg_replace('/[^-_%0-9a-zA-Z]/','',$move_status);
	$move_from_list = preg_replace('/[^0-9]/','',$move_from_list);
	$move_to_list = preg_replace('/[^0-9]/','',$move_to_list);
	$move_count_num = preg_replace('/[^0-9]/','',$move_count_num);
	$move_count_op = preg_replace('/[^<>=]/','',$move_count_op);

	if ($DB)
		{
		echo "<p>enable_move_status = $enable_move_status | enable_move_country_code = $enable_move_country_code | enable_move_vendor_lead_code = $enable_move_vendor_lead_code | enable_move_source_id = $enable_move_source_id | enable_move_owner = $enable_move_owner | enable_move_entry_date = $enable_move_entry_date | enable_move_modify_date = $enable_move_modify_date | enable_move_security_phrase = $enable_move_security_phrase | enable_move_count = $enable_move_count | move_country_code = $move_country_code | move_vendor_lead_code = $move_vendor_lead_code | move_source_id = $move_source_id | move_owner = $move_owner | move_owner = $move_entry_date | move_modify_date = $move_modify_date | move_security_phrase = $move_security_phrase | move_from_list = $move_from_list | move_to_list = $move_to_list | move_status = $move_status | move_count_op = $move_count_op | move_count_num = $move_count_num</p>";
		}

	$move_count_op_phrase="";
	if ( $move_count_op == "<" )
		{
		$move_count_op_phrase= "less than ";
		}
	elseif ( $move_count_op == "<=" )
		{
		$move_count_op_phrase= "less than or equal to ";
		}
	elseif ( $move_count_op == ">" )
		{
		$move_count_op_phrase= "greater than ";
		}
	elseif ( $move_count_op == ">=" )
		{
		$move_count_op_phrase= "greater than or equal to ";
		}

	# make sure the required fields are set
	if ($move_from_list == '') { missing_required_field('From List'); }
	if ($move_to_list == '') { missing_required_field('To List'); }

	# build the sql query's where phrase and the move phrase
	$sql_where = "";
	$move_parm = "";
	if (($enable_move_status == "enabled") && ($move_status != ''))
		{
		if ($move_status == '---BLANK---') {$move_status = '';}
		$sql_where = $sql_where . " and status like '$move_status' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;status is like $move_status<br />";
		if ($move_status == '') {$move_status = '---BLANK---';}
		}
	elseif ($enable_move_status == "enabled")
		{
		blank_field('Status',true);
		}
	if (($enable_move_country_code == "enabled") && ($move_country_code != ''))
		{
		if ($move_country_code == '---BLANK---') {$move_country_code = '';}
		$sql_where = $sql_where . " and country_code like '$move_country_code' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;country code is like $move_country_code<br />";
		if ($move_country_code == '') {$move_country_code = '---BLANK---';}
		}
	elseif ($enable_move_country_code == "enabled")
		{
		blank_field('País Code',true);
		}
	if (($enable_move_vendor_lead_code == "enabled") && ($move_vendor_lead_code != ''))
		{
		if ($move_vendor_lead_code == '---BLANK---') {$move_vendor_lead_code = '';}
		$sql_where = $sql_where . " and vendor_lead_code like '$move_vendor_lead_code' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;Código del Proveedor del contactos is like $move_vendor_lead_code<br />";
		if ($move_vendor_lead_code == '') {$move_vendor_lead_code = '---BLANK---';}
		}
	elseif ($enable_move_vendor_lead_code == "enabled")
		{
		blank_field('Vendor Lead Code',true);
		}
	if (($enable_move_source_id == "enabled") && ( $move_source_id != ''))
		{
		if ($move_source_id == '---BLANK---') {$move_source_id = '';}
		$sql_where = $sql_where . " and source_id like '$move_source_id' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;source id is like $move_source_id<br />";
		if ($move_source_id == '') {$move_source_id = '---BLANK---';}
		}
	elseif($enable_move_source_id == "enabled")
		{
		blank_field('Source ID',true);
		}
	if (($enable_move_owner == "enabled") && ($move_owner != ''))
		{
		if ($move_owner == '---BLANK---') {$move_owner = '';}
		$sql_where = $sql_where . " and owner like '$move_owner' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;owner is like $move_owner<br />";
		if ($move_owner == '') {$move_owner = '---BLANK---';}
		}
	elseif ($enable_move_owner == "enabled")
		{
		blank_field('Owner',true);
		}
	if (($enable_move_security_phrase == "enabled") && ($move_security_phrase != ''))
		{
		if ($move_security_phrase == '---BLANK---') {$move_security_phrase = '';}
		$sql_where = $sql_where . " and security_phrase like '$move_security_phrase' ";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;secuirty_phrase is like $move_security_phrase<br />";
		if ($move_security_phrase == '') {$move_security_phrase = '---BLANK---';}
		}
	elseif ($enable_move_security_phrase == "enabled")
		{
		blank_field('Frase de Seguridad',true);
		}
	if (($enable_move_entry_date == "enabled") && ($move_entry_date != ''))
		{
		if ($move_entry_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and entry_date == '' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and entry_date >= '$move_entry_date 00:00:00' and entry_date <= '$move_entry_date 23:59:59' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date was on $move_entry_date<br />";
			}
		}
	elseif ($enable_move_entry_date == "enabled")
		{
		blank_field('Entry Date',true);
		}
	if (($enable_move_modify_date == "enabled") && ($move_modify_date != ''))
		{
		if ($move_modify_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and modify_date == '' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;modify date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and modify_date >= '$move_modify_date 00:00:00' and modify_date <= '$move_modify_date 23:59:59' ";
			$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;last modify date was on $move_modify_date<br />";
			}
		}
	elseif ($enable_move_modify_date == "enabled")
		{
		blank_field('ModificarDate',true);
		}
	if (($enable_move_count == "enabled") && ($move_count_op != '') && ($move_count_num != ''))
		{
		if ($move_count_op == '---BLANK---') {$move_count_op = '';}
		if ($move_count_num == '---BLANK---') {$move_count_num = '';}
		$sql_where = $sql_where . " and called_count $move_count_op $move_count_num";
		$move_parm = $move_parm . "&nbsp;&nbsp;&nbsp;&nbsp;called count is $move_count_op_phrase $move_count_num<br />";
		if ($move_count_op == '') {$move_count_op = '---BLANK---';}
		if ($move_count_num == '') {$move_count_num = '---BLANK---';}
		}
	elseif ($enable_move_count == "enabled")
		{
		blank_field('Move Count',true);
		}

	$move_lead_stmt = "UPDATE vicidial_list SET list_id = '$move_to_list' WHERE list_id = '$move_from_list' $sql_where";
	if ($DB) { echo "|$move_lead_stmt|\n"; }
	$move_lead_rslt = mysql_to_mysqli($move_lead_stmt, $link);
	$move_lead_count = mysqli_affected_rows($link);

	$move_sentence = "$move_lead_count leads have been moved from list $move_from_list to $move_to_list with the following parameters:<br /><br />$move_parm <br />";

	$SQL_log = "$move_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$move_from_list', event_code='ADMIN MOVE LEADS', event_sql=\"$SQL_log\", event_notes='$move_sentence';";
	if ($DB) {echo "|$admin_log_stmt|\n";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$move_sentence</p>";
	echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
	}


# update confirmation page
if ($update_submit == "update" )
	{
	# get the variables
	$enable_update_from_status="";
	$enable_update_country_code="";
	$enable_update_vendor_lead_code="";
	$enable_update_source_id="";
	$enable_update_owner="";
	$enable_update_entry_date="";
	$enable_update_modify_date="";
	$enable_update_security_phrase="";
	$enable_update_count="";
	$update_country_code="";
	$update_vendor_lead_code="";
	$update_source_id="";
	$update_owner="";
	$update_entry_date="";
	$update_modify_date="";
	$update_security_phrase="";
	$update_list="";
	$update_to_status="";
	$update_from_status="";
	$update_count_op="";
	$update_count_num="";

	# check the get / post data for the variables
	if (isset($_GET["enable_update_from_status"])) {$enable_update_from_status=$_GET["enable_update_from_status"];}
		elseif (isset($_POST["enable_update_from_status"])) {$enable_update_from_status=$_POST["enable_update_from_status"];}
	if (isset($_GET["enable_update_country_code"])) {$enable_update_country_code=$_GET["enable_update_country_code"];}
		elseif (isset($_POST["enable_update_country_code"])) {$enable_update_country_code=$_POST["enable_update_country_code"];}
	if (isset($_GET["enable_update_vendor_lead_code"])) {$enable_update_vendor_lead_code=$_GET["enable_update_vendor_lead_code"];}
		elseif (isset($_POST["enable_update_vendor_lead_code"])) {$enable_update_vendor_lead_code=$_POST["enable_update_vendor_lead_code"];}
	if (isset($_GET["enable_update_source_id"])) {$enable_update_source_id=$_GET["enable_update_source_id"];}
		elseif (isset($_POST["enable_update_source_id"])) {$enable_update_source_id=$_POST["enable_update_source_id"];}
	if (isset($_GET["enable_update_owner"])) {$enable_update_owner=$_GET["enable_update_owner"];}
		elseif (isset($_POST["enable_update_owner"])) {$enable_update_owner=$_POST["enable_update_owner"];}
	if (isset($_GET["enable_update_entry_date"])) {$enable_update_entry_date=$_GET["enable_update_entry_date"];}
		elseif (isset($_POST["enable_update_entry_date"])) {$enable_update_entry_date=$_POST["enable_update_entry_date"];}
	if (isset($_GET["enable_update_modify_date"])) {$enable_update_modify_date=$_GET["enable_update_modify_date"];}
		elseif (isset($_POST["enable_update_modify_date"])) {$enable_update_modify_date=$_POST["enable_update_modify_date"];}
	if (isset($_GET["enable_update_security_phrase"])) {$enable_update_security_phrase=$_GET["enable_update_security_phrase"];}
		elseif (isset($_POST["enable_update_security_phrase"])) {$enable_update_security_phrase=$_POST["enable_update_security_phrase"];}
	if (isset($_GET["enable_update_count"])) {$enable_update_count=$_GET["enable_update_count"];}
		elseif (isset($_POST["enable_update_count"])) {$enable_update_count=$_POST["enable_update_count"];}
	if (isset($_GET["update_country_code"])) {$update_country_code=$_GET["update_country_code"];}
		elseif (isset($_POST["update_country_code"])) {$update_country_code=$_POST["update_country_code"];}
	if (isset($_GET["update_vendor_lead_code"])) {$update_vendor_lead_code=$_GET["update_vendor_lead_code"];}
		elseif (isset($_POST["update_vendor_lead_code"])) {$update_vendor_lead_code=$_POST["update_vendor_lead_code"];}
	if (isset($_GET["update_source_id"])) {$update_source_id=$_GET["update_source_id"];}
		elseif (isset($_POST["update_source_id"])) {$update_source_id=$_POST["update_source_id"];}
	if (isset($_GET["update_owner"])) {$update_owner=$_GET["update_owner"];}
		elseif (isset($_POST["update_owner"])) {$update_owner=$_POST["update_owner"];}
	if (isset($_GET["update_entry_date"])) {$update_entry_date=$_GET["update_entry_date"];}
		elseif (isset($_POST["update_entry_date"])) {$update_entry_date=$_POST["update_entry_date"];}
	if (isset($_GET["update_modify_date"])) {$update_modify_date=$_GET["update_modify_date"];}
		elseif (isset($_POST["update_modify_date"])) {$update_modify_date=$_POST["update_modify_date"];}
	if (isset($_GET["update_security_phrase"])) {$update_security_phrase=$_GET["update_security_phrase"];}
		elseif (isset($_POST["update_security_phrase"])) {$update_security_phrase=$_POST["update_security_phrase"];}
	if (isset($_GET["update_list"])) {$update_list=$_GET["update_list"];}
		elseif (isset($_POST["update_list"])) {$update_list=$_POST["update_list"];}
	if (isset($_GET["update_from_status"])) {$update_from_status=$_GET["update_from_status"];}
		elseif (isset($_POST["update_from_status"])) {$update_from_status=$_POST["update_from_status"];}
	if (isset($_GET["update_to_status"])) {$update_to_status=$_GET["update_to_status"];}
		elseif (isset($_POST["update_to_status"])) {$update_to_status=$_POST["update_to_status"];}
	if (isset($_GET["update_count_op"])) {$update_count_op=$_GET["update_count_op"];}
		elseif (isset($_POST["update_count_op"])) {$update_count_op=$_POST["update_count_op"];}
	if (isset($_GET["update_count_num"])) {$update_count_num=$_GET["update_count_num"];}
		elseif (isset($_POST["update_count_num"])) {$update_count_num=$_POST["update_count_num"];}

	if ($DB)
		{
		echo "<p>enable_update_from_status = $enable_update_from_status | enable_update_country_code = $enable_update_country_code | enable_update_vendor_lead_code = $enable_update_vendor_lead_code | enable_update_source_id = $enable_update_source_id | enable_update_owner = $enable_update_owner | enable_update_entry_date = $enable_update_entry_date | enable_update_modify_date = $enable_update_modify_date | enable_update_security_phrase = $enable_update_security_phrase | enable_update_count = $enable_update_count | update_country_code = $update_country_code | update_vendor_lead_code = $update_vendor_lead_code | update_source_id = $update_source_id | update_owner = $update_owner | update_owner = $update_entry_date | update_modify_date = $update_modify_date | update_security_phrase = $update_security_phrase | update_list = $update_list | update_to_status = $ update_to_status | update_from_status = $update_from_status | update_count_op = $update_count_op | update_count_num = $update_count_num</p>";
		}

	# filter out anything bad
	$enable_update_from_status = preg_replace('/[^a-zA-Z]/','',$enable_update_from_status);
	$enable_update_country_code = preg_replace('/[^a-zA-Z]/','',$enable_update_country_code);
	$enable_update_vendor_lead_code = preg_replace('/[^a-zA-Z]/','',$enable_update_vendor_lead_code);
	$enable_update_source_id = preg_replace('/[^a-zA-Z]/','',$enable_update_source_id);
	$enable_update_owner = preg_replace('/[^a-zA-Z]/','',$enable_update_owner);
	$enable_update_entry_date = preg_replace('/[^a-zA-Z]/','',$enable_update_entry_date);
	$enable_update_modify_date = preg_replace('/[^a-zA-Z]/','',$enable_update_modify_date);
	$enable_update_security_phrase = preg_replace('/[^a-zA-Z]/','',$enable_update_security_phrase);
	$enable_update_count = preg_replace('/[^a-zA-Z]/','',$enable_update_count);
	$update_country_code = preg_replace('/[^-_%a-zA-Z0-9]/','',$update_country_code);
	$update_vendor_lead_code = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_vendor_lead_code);
	$update_source_id = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_source_id);
	$update_owner = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_owner);
	$update_entry_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_entry_date);
	$update_modify_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_modify_date);
	$update_security_phrase = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_security_phrase);
	$update_to_status = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_to_status);
	$update_from_status = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_from_status);
	$update_list = preg_replace('/[^0-9]/','',$update_list);
	$update_count_num = preg_replace('/[^0-9]/','',$update_count_num);
	$update_count_op = preg_replace('/[^<>=]/','',$update_count_op);

	if ($DB)
		{
		echo "<p>enable_update_from_status = $enable_update_from_status | enable_update_country_code = $enable_update_country_code | enable_update_vendor_lead_code = $enable_update_vendor_lead_code | enable_update_source_id = $enable_update_source_id | enable_update_owner = $enable_update_owner | enable_update_entry_date = $enable_update_entry_date | enable_update_modify_date = $enable_update_modify_date | enable_update_security_phrase = $enable_update_security_phrase | enable_update_count = $enable_update_count | update_country_code = $update_country_code | update_vendor_lead_code = $update_vendor_lead_code | update_source_id = $update_source_id | update_owner = $update_owner | update_owner = $update_entry_date | update_modify_date = $update_modify_date | update_security_phrase = $update_security_phrase | update_list = $update_list | update_to_status = $ update_to_status | update_from_status = $update_from_status | update_count_op = $update_count_op | update_count_num = $update_count_num</p>";
		}

	$update_count_op_phrase="";
	if ( $update_count_op == "<" )
		{
		$update_count_op_phrase= "less than ";
		}
	elseif ( $update_count_op == "<=" )
		{
		$update_count_op_phrase= "less than or equal to ";
		}
	elseif ( $update_count_op == ">" )
		{
		$update_count_op_phrase= "greater than ";
		}
	elseif ( $update_count_op == ">=" )
		{
		$update_count_op_phrase= "greater than or equal to ";
		}

	# make sure the required fields are set
	if ($update_to_status == '') { missing_required_field('To Status'); }
	if ($update_list == '') { missing_required_field('ID de Lista'); }

	# build the sql query's where phrase and the move phrase
	$sql_where = "";
	$update_parm = "";
	if (($enable_update_from_status == "enabled") && ($update_from_status != ''))
		{
		if ($update_from_status == '---BLANK---') {$update_from_status = '';}
		$sql_where = $sql_where . " and status like '$update_from_status' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;status is like $update_from_status<br />";
		if ($update_from_status == '') {$update_from_status = '---BLANK---';}
		}
	elseif ($enable_update_from_status == "enabled")
		{
		blank_field('Status',true);
		}
	if (($enable_update_country_code == "enabled") && ($update_country_code != ''))
		{
		if ($update_country_code == '---BLANK---') {$update_country_code = '';}
		$sql_where = $sql_where . " and country_code like '$update_country_code' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;country code is like $update_country_code<br />";
				if ($update_country_code == '') {$update_country_code = '---BLANK---';}
		}
	elseif ($enable_update_country_code == "enabled")
		{
		blank_field('País Code',true);
		}
	if (($enable_update_vendor_lead_code == "enabled") && ($update_vendor_lead_code != ''))
		{
		if ($update_vendor_lead_code == '---BLANK---') {$update_vendor_lead_code = '';}
		$sql_where = $sql_where . " and vendor_lead_code like '$update_vendor_lead_code' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;Código del Proveedor del contactos is like $update_vendor_lead_code<br />";
		if ($update_vendor_lead_code == '') {$update_vendor_lead_code = '---BLANK---';}
		}
	elseif ($enable_update_vendor_lead_code == "enabled")
		{
		blank_field('Vendor Lead Code',true);
		}
	if (($enable_update_source_id == "enabled") && ( $update_source_id != ''))
		{
		if ($update_source_id == '---BLANK---') {$update_source_id = '';}
		$sql_where = $sql_where . " and source_id like '$update_source_id' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;source id is like $update_source_id<br />";
		if ($update_source_id == '') {$update_source_id = '---BLANK---';}
		}
	elseif ($enable_update_source_id == "enabled")
		{
		blank_field('Source ID',true);
		}
	if (($enable_update_owner == "enabled") && ($update_owner != ''))
		{
		if ($update_owner == '---BLANK---') {$update_owner = '';}
		$sql_where = $sql_where . " and owner like '$update_owner' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;owner is like $update_owner<br />";
		if ($update_owner == '') {$update_owner = '---BLANK---';}
		}
	elseif ($enable_update_owner == "enabled")
		{
		blank_field('Owner',true);
		}
	if (($enable_update_security_phrase == "enabled") && ($update_security_phrase != ''))
		{
		if ($update_security_phrase == '---BLANK---') {$update_security_phrase = '';}
		$sql_where = $sql_where . " and security_phrase like '$update_security_phrase' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;secuirty_phrase is like $update_security_phrase<br />";
		if ($update_security_phrase == '') {$update_security_phrase = '---BLANK---';}
		}
	elseif ($enable_update_security_phrase == "enabled")
		{
		blank_field('Frase de Seguridad',true);
		}
	if (($enable_update_entry_date == "enabled") && ($update_entry_date != ''))
		{
		if ($update_entry_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and entry_date == '' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and entry_date >= '$update_entry_date 00:00:00' and entry_date <= '$update_entry_date 23:59:59' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date was on $update_entry_date<br />";
			}
		}
	elseif ($enable_update_entry_date == "enabled")
		{
		blank_field('Entry Date',true);
		}
	if (($enable_update_modify_date == "enabled") && ($update_modify_date != ''))
		{
		if ($update_modify_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and modify_date == '' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;modify date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and modify_date >= '$update_modify_date 00:00:00' and modify_date <= '$update_modify_date 23:59:59' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;last modify date was on $update_modify_date<br />";
			}
		}
	elseif ($enable_update_modify_date == "enabled")
		{
		blank_field('ModificarDate',true);
		}
	if (($enable_update_count == "enabled") && ($update_count_op != '') && ($update_count_num != ''))
		{
		if ($update_count_op == '---BLANK---') {$update_count_op = '';}
		if ($update_count_num == '---BLANK---') {$update_count_num = '';}
		$sql_where = $sql_where . " and called_count $update_count_op $update_count_num";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;called count is $update_count_op_phrase $update_count_num<br />";
		if ($update_count_op == '') {$update_count_op = '---BLANK---';}
		if ($update_count_num == '') {$update_count_num = '---BLANK---';}
		}
	elseif ($enable_update_count == "enabled")
		{
		blank_field('Move Count');
		}

	# get the number of leads this action will move
	$update_lead_count=0;
	$update_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$update_list' $sql_where";
	if ($DB) { echo "|$update_lead_count_stmt|\n"; }
	$update_lead_count_rslt = mysql_to_mysqli($update_lead_count_stmt, $link);
	$update_lead_count_row = mysqli_fetch_row($update_lead_count_rslt);
	$update_lead_count = $update_lead_count_row[0];

	echo "<p>You are about to update $update_lead_count leads in list $update_list to the status $update_to_status with the following parameters:<br /><br />$update_parm<br />Please press confirm to continue.</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=enable_update_from_status value='$enable_update_from_status'>\n";
	echo "<input type=hidden name=enable_update_country_code value='$enable_update_country_code'>\n";
	echo "<input type=hidden name=enable_update_vendor_lead_code value='$enable_update_vendor_lead_code'>\n";
	echo "<input type=hidden name=enable_update_source_id value='$enable_update_source_id'>\n";
	echo "<input type=hidden name=enable_update_owner value='$enable_update_owner'>\n";
	echo "<input type=hidden name=enable_update_entry_date value='$enable_update_entry_date'>\n";
	echo "<input type=hidden name=enable_update_modify_date value='$enable_update_modify_date'>\n";
	echo "<input type=hidden name=enable_update_security_phrase value='$enable_update_security_phrase'>\n";
	echo "<input type=hidden name=enable_update_count value='$enable_update_count'>\n";
	echo "<input type=hidden name=update_country_code value='$update_country_code'>\n";
	echo "<input type=hidden name=update_vendor_lead_code value='$update_vendor_lead_code'>\n";
	echo "<input type=hidden name=update_source_id value='$update_source_id'>\n";
	echo "<input type=hidden name=update_owner value='$update_owner'>\n";
	echo "<input type=hidden name=update_entry_date value='$update_entry_date'>\n";
	echo "<input type=hidden name=update_modify_date value='$update_modify_date'>\n";
	echo "<input type=hidden name=update_security_phrase value='$update_security_phrase'>\n";
	echo "<input type=hidden name=update_list value='$update_list'>\n";
	echo "<input type=hidden name=update_to_status value='$update_to_status'>\n";
	echo "<input type=hidden name=update_from_status value='$update_from_status'>\n";
	echo "<input type=hidden name=update_count_op value='$update_count_op'>\n";
	echo "<input type=hidden name=update_count_num value='$update_count_num'>\n";
	echo "<input type=hidden name=DB value='$DB'>\n";
	echo "<input type=submit name=confirm_update value=confirm>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
	echo "</body>\n</html>\n";

	}

# actually do the update
if ($confirm_update == "confirm")
	{
	# get the variables
	$enable_update_from_status="";
	$enable_update_country_code="";
	$enable_update_vendor_lead_code="";
	$enable_update_source_id="";
	$enable_update_owner="";
	$enable_update_entry_date="";
	$enable_update_modify_date="";
	$enable_update_security_phrase="";
	$enable_update_count="";
	$update_country_code="";
	$update_vendor_lead_code="";
	$update_source_id="";
	$update_owner="";
	$update_entry_date="";
	$update_modify_date="";
	$update_security_phrase="";
	$update_list="";
	$update_to_status="";
	$update_from_status="";
	$update_count_op="";
	$update_count_num="";

	# check the get / post data for the variables
	if (isset($_GET["enable_update_from_status"])) {$enable_update_from_status=$_GET["enable_update_from_status"];}
		elseif (isset($_POST["enable_update_from_status"])) {$enable_update_from_status=$_POST["enable_update_from_status"];}
	if (isset($_GET["enable_update_country_code"])) {$enable_update_country_code=$_GET["enable_update_country_code"];}
		elseif (isset($_POST["enable_update_country_code"])) {$enable_update_country_code=$_POST["enable_update_country_code"];}
	if (isset($_GET["enable_update_vendor_lead_code"])) {$enable_update_vendor_lead_code=$_GET["enable_update_vendor_lead_code"];}
		elseif (isset($_POST["enable_update_vendor_lead_code"])) {$enable_update_vendor_lead_code=$_POST["enable_update_vendor_lead_code"];}
	if (isset($_GET["enable_update_source_id"])) {$enable_update_source_id=$_GET["enable_update_source_id"];}
		elseif (isset($_POST["enable_update_source_id"])) {$enable_update_source_id=$_POST["enable_update_source_id"];}
	if (isset($_GET["enable_update_owner"])) {$enable_update_owner=$_GET["enable_update_owner"];}
		elseif (isset($_POST["enable_update_owner"])) {$enable_update_owner=$_POST["enable_update_owner"];}
	if (isset($_GET["enable_update_entry_date"])) {$enable_update_entry_date=$_GET["enable_update_entry_date"];}
		elseif (isset($_POST["enable_update_entry_date"])) {$enable_update_entry_date=$_POST["enable_update_entry_date"];}
	if (isset($_GET["enable_update_modify_date"])) {$enable_update_modify_date=$_GET["enable_update_modify_date"];}
		elseif (isset($_POST["enable_update_modify_date"])) {$enable_update_modify_date=$_POST["enable_update_modify_date"];}
	if (isset($_GET["enable_update_security_phrase"])) {$enable_update_security_phrase=$_GET["enable_update_security_phrase"];}
		elseif (isset($_POST["enable_update_security_phrase"])) {$enable_update_security_phrase=$_POST["enable_update_security_phrase"];}
	if (isset($_GET["enable_update_count"])) {$enable_update_count=$_GET["enable_update_count"];}
		elseif (isset($_POST["enable_update_count"])) {$enable_update_count=$_POST["enable_update_count"];}
	if (isset($_GET["update_country_code"])) {$update_country_code=$_GET["update_country_code"];}
		elseif (isset($_POST["update_country_code"])) {$update_country_code=$_POST["update_country_code"];}
	if (isset($_GET["update_vendor_lead_code"])) {$update_vendor_lead_code=$_GET["update_vendor_lead_code"];}
		elseif (isset($_POST["update_vendor_lead_code"])) {$update_vendor_lead_code=$_POST["update_vendor_lead_code"];}
	if (isset($_GET["update_source_id"])) {$update_source_id=$_GET["update_source_id"];}
		elseif (isset($_POST["update_source_id"])) {$update_source_id=$_POST["update_source_id"];}
	if (isset($_GET["update_owner"])) {$update_owner=$_GET["update_owner"];}
		elseif (isset($_POST["update_owner"])) {$update_owner=$_POST["update_owner"];}
	if (isset($_GET["update_entry_date"])) {$update_entry_date=$_GET["update_entry_date"];}
		elseif (isset($_POST["update_entry_date"])) {$update_entry_date=$_POST["update_entry_date"];}
	if (isset($_GET["update_modify_date"])) {$update_modify_date=$_GET["update_modify_date"];}
		elseif (isset($_POST["update_modify_date"])) {$update_modify_date=$_POST["update_modify_date"];}
	if (isset($_GET["update_security_phrase"])) {$update_security_phrase=$_GET["update_security_phrase"];}
		elseif (isset($_POST["update_security_phrase"])) {$update_security_phrase=$_POST["update_security_phrase"];}
	if (isset($_GET["update_list"])) {$update_list=$_GET["update_list"];}
		elseif (isset($_POST["update_list"])) {$update_list=$_POST["update_list"];}
	if (isset($_GET["update_from_status"])) {$update_from_status=$_GET["update_from_status"];}
		elseif (isset($_POST["update_from_status"])) {$update_from_status=$_POST["update_from_status"];}
	if (isset($_GET["update_to_status"])) {$update_to_status=$_GET["update_to_status"];}
		elseif (isset($_POST["update_to_status"])) {$update_to_status=$_POST["update_to_status"];}
	if (isset($_GET["update_count_op"])) {$update_count_op=$_GET["update_count_op"];}
		elseif (isset($_POST["update_count_op"])) {$update_count_op=$_POST["update_count_op"];}
	if (isset($_GET["update_count_num"])) {$update_count_num=$_GET["update_count_num"];}
		elseif (isset($_POST["update_count_num"])) {$update_count_num=$_POST["update_count_num"];}

	if ($DB)
		{
		echo "<p>enable_update_from_status = $enable_update_from_status | enable_update_country_code = $enable_update_country_code | enable_update_vendor_lead_code = $enable_update_vendor_lead_code | enable_update_source_id = $enable_update_source_id | enable_update_owner = $enable_update_owner | enable_update_entry_date = $enable_update_entry_date | enable_update_modify_date = $enable_update_modify_date | enable_update_security_phrase = $enable_update_security_phrase | enable_update_count = $enable_update_count | update_country_code = $update_country_code | update_vendor_lead_code = $update_vendor_lead_code | update_source_id = $update_source_id | update_owner = $update_owner | update_owner = $update_entry_date | update_modify_date = $update_modify_date | update_security_phrase = $update_security_phrase | update_list = $update_list | update_to_status = $ update_to_status | update_from_status = $update_from_status | update_count_op = $update_count_op | update_count_num = $update_count_num</p>";
		}

	# filter out anything bad
	$enable_update_from_status = preg_replace('/[^a-zA-Z]/','',$enable_update_from_status);
	$enable_update_country_code = preg_replace('/[^a-zA-Z]/','',$enable_update_country_code);
	$enable_update_vendor_lead_code = preg_replace('/[^a-zA-Z]/','',$enable_update_vendor_lead_code);
	$enable_update_source_id = preg_replace('/[^a-zA-Z]/','',$enable_update_source_id);
	$enable_update_owner = preg_replace('/[^a-zA-Z]/','',$enable_update_owner);
	$enable_update_entry_date = preg_replace('/[^a-zA-Z]/','',$enable_update_entry_date);
	$enable_update_modify_date = preg_replace('/[^a-zA-Z]/','',$enable_update_modify_date);
	$enable_update_security_phrase = preg_replace('/[^a-zA-Z]/','',$enable_update_security_phrase);
	$enable_update_count = preg_replace('/[^a-zA-Z]/','',$enable_update_count);
	$update_country_code = preg_replace('/[^-_%a-zA-Z0-9]/','',$update_country_code);
	$update_vendor_lead_code = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_vendor_lead_code);
	$update_source_id = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_source_id);
	$update_owner = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_owner);
	$update_entry_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_entry_date);
	$update_modify_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_modify_date);
	$update_security_phrase = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_security_phrase);
	$update_to_status = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_to_status);
	$update_from_status = preg_replace('/[^-_%0-9a-zA-Z]/','',$update_from_status);
	$update_list = preg_replace('/[^0-9]/','',$update_list);
	$update_count_num = preg_replace('/[^0-9]/','',$update_count_num);
	$update_count_op = preg_replace('/[^<>=]/','',$update_count_op);

	if ($DB)
		{
		echo "<p>enable_update_from_status = $enable_update_from_status | enable_update_country_code = $enable_update_country_code | enable_update_vendor_lead_code = $enable_update_vendor_lead_code | enable_update_source_id = $enable_update_source_id | enable_update_owner = $enable_update_owner | enable_update_entry_date = $enable_update_entry_date | enable_update_modify_date = $enable_update_modify_date | enable_update_security_phrase = $enable_update_security_phrase | enable_update_count = $enable_update_count | update_country_code = $update_country_code | update_vendor_lead_code = $update_vendor_lead_code | update_source_id = $update_source_id | update_owner = $update_owner | update_owner = $update_entry_date | update_modify_date = $update_modify_date | update_security_phrase = $update_security_phrase | update_list = $update_list | update_to_status = $ update_to_status | update_from_status = $update_from_status | update_count_op = $update_count_op | update_count_num = $update_count_num</p>";
		}

	$update_count_op_phrase="";
	if ( $update_count_op == "<" )
		{
		$update_count_op_phrase= "less than ";
		}
	elseif ( $update_count_op == "<=" )
		{
		$update_count_op_phrase= "less than or equal to ";
		}
	elseif ( $update_count_op == ">" )
		{
		$update_count_op_phrase= "greater than ";
		}
	elseif ( $update_count_op == ">=" )
		{
		$update_count_op_phrase= "greater than or equal to ";
		}

	# make sure the required fields are set
	if ($update_to_status == '') { missing_required_field('To Status'); }
	if ($update_list == '') { missing_required_field('ID de Lista'); }

	# build the sql query's where phrase and the move phrase
	$sql_where = "";
	$update_parm = "";
	if (($enable_update_from_status == "enabled") && ($update_from_status != ''))
		{
		if ($update_from_status == '---BLANK---') {$update_from_status = '';}
		$sql_where = $sql_where . " and status like '$update_from_status' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;status is like $update_from_status<br />";
		if ($update_from_status == '') {$update_from_status = '---BLANK---';}
		}
	elseif ($enable_update_from_status == "enabled")
		{
		blank_field('Status',true);
		}
	if (($enable_update_country_code == "enabled") && ($update_country_code != ''))
		{
		if ($update_country_code == '---BLANK---') {$update_country_code = '';}
		$sql_where = $sql_where . " and country_code like '$update_country_code' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;country code is like $update_country_code<br />";
		if ($update_country_code == '') {$update_country_code = '---BLANK---';}
		}
	elseif ($enable_update_country_code == "enabled")
		{
		blank_field('País Code',true);
		}
	if (($enable_update_vendor_lead_code == "enabled") && ($update_vendor_lead_code != ''))
		{
		if ($update_vendor_lead_code == '---BLANK---') {$update_vendor_lead_code = '';}
		$sql_where = $sql_where . " and vendor_lead_code like '$update_vendor_lead_code' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;Código del Proveedor del contactos is like $update_vendor_lead_code<br />";
		if ($update_vendor_lead_code == '') {$update_vendor_lead_code = '---BLANK---';}
		}
	elseif ($enable_update_vendor_lead_code == "enabled")
		{
		blank_field('Vendor Lead Code',true);
		}
	if (($enable_update_source_id == "enabled") && ( $update_source_id != ''))
		{
		if ($update_source_id == '---BLANK---') {$update_source_id = '';}
		$sql_where = $sql_where . " and source_id like '$update_source_id' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;source id is like $update_source_id<br />";
		if ($update_source_id == '') {$update_source_id = '---BLANK---';}
		}
	elseif ($enable_update_source_id == "enabled")
		{
		blank_field('Source ID',true);
		}
	if (($enable_update_owner == "enabled") && ($update_owner != ''))
		{
		if ($update_owner == '---BLANK---') {$update_owner = '';}
		$sql_where = $sql_where . " and owner like '$update_owner' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;owner is like $update_owner<br />";
		if ($update_owner == '') {$update_owner = '---BLANK---';}
		}
	elseif ($enable_update_owner == "enabled")
		{
		blank_field('Owner',true);
		}
	if (($enable_update_security_phrase == "enabled") && ($update_security_phrase != ''))
		{
		if ($update_security_phrase == '---BLANK---') {$update_security_phrase = '';}
		$sql_where = $sql_where . " and security_phrase like '$update_security_phrase' ";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;secuirty_phrase is like $update_security_phrase<br />";
		if ($update_security_phrase == '') {$update_security_phrase = '---BLANK---';}
		}
	elseif ($enable_update_security_phrase == "enabled")
		{
		blank_field('Frase de Seguridad',true);
		}
	if (($enable_update_entry_date == "enabled") && ($update_entry_date != ''))
		{
		if ($update_entry_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and entry_date == '' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and entry_date >= '$update_entry_date 00:00:00' and entry_date <= '$update_entry_date 23:59:59' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date was on $update_entry_date<br />";
			}
		}
	elseif ($enable_update_entry_date == "enabled")
		{
		blank_field('Entry Date',true);
		}
	if (($enable_update_modify_date == "enabled") && ($update_modify_date != ''))
		{
		if ($update_modify_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and modify_date == '' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;modify date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and modify_date >= '$update_modify_date 00:00:00' and modify_date <= '$update_modify_date 23:59:59' ";
			$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;last modify date was on $update_modify_date<br />";
			}
		}
	elseif ($enable_update_modify_date == "enabled")
		{
		blank_field('ModificarDate',true);
		}
	if (($enable_update_count == "enabled") && ($update_count_op != '') && ($update_count_num != ''))
		{
		if ($update_count_op == '---BLANK---') {$update_count_op = '';}
		if ($update_count_num == '---BLANK---') {$update_count_num = '';}
		$sql_where = $sql_where . " and called_count $update_count_op $update_count_num";
		$update_parm = $update_parm . "&nbsp;&nbsp;&nbsp;&nbsp;called count is $update_count_op_phrase $update_count_num<br />";
		if ($update_count_op == '') {$update_count_op = '---BLANK---';}
		if ($update_count_num == '') {$update_count_num = '---BLANK---';}
		}
	elseif ($enable_update_count == "enabled")
		{
		blank_field('Move Count',true);
		}

	$update_lead_stmt = "UPDATE vicidial_list SET status = '$update_to_status' WHERE list_id = '$update_list' $sql_where";
	if ($DB) { echo "|$update_lead_stmt|\n"; }
	$update_lead_rslt = mysql_to_mysqli($update_lead_stmt, $link);
	$update_lead_count = mysqli_affected_rows($link);

	$update_sentence = "$update_lead_count leads in list $update_list had their status changed to $update_to_status with the following parameters:<br /><br />$update_parm";

	$SQL_log = "$update_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='MODIFY', record_id='$update_list', event_code='ADMIN UPDATE LEADS', event_sql=\"$SQL_log\", event_notes='$update_sentence';";
	if ($DB) {echo "|$admin_log_stmt|\n";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$update_sentence</p>";
	echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
	}


# delete confirmation page
if ( ( $delete_submit == "delete" ) && ( $delete_lists > 0 ) )
	{
	# get the variables
	$enable_delete_lead_id="";
	$enable_delete_country_code="";
	$enable_delete_vendor_lead_code="";
	$enable_delete_source_id="";
	$enable_delete_owner="";
	$enable_delete_entry_date="";
	$enable_delete_modify_date="";
	$enable_delete_security_phrase="";
	$enable_delete_count="";
	$delete_country_code="";
	$delete_vendor_lead_code="";
	$delete_source_id="";
	$delete_owner="";
	$delete_entry_date="";
	$delete_modify_date="";
	$delete_security_phrase="";
	$delete_list="";
	$delete_status="";
	$delete_count_op="";
	$delete_count_num="";
	$delete_lead_id="";

	# check the get / post data for the variables
	if (isset($_GET["enable_delete_lead_id"])) {$enable_delete_lead_id=$_GET["enable_delete_lead_id"];}
		elseif (isset($_POST["enable_delete_lead_id"])) {$enable_delete_lead_id=$_POST["enable_delete_lead_id"];}
	if (isset($_GET["enable_delete_country_code"])) {$enable_delete_country_code=$_GET["enable_delete_country_code"];}
		elseif (isset($_POST["enable_delete_country_code"])) {$enable_delete_country_code=$_POST["enable_delete_country_code"];}
	if (isset($_GET["enable_delete_vendor_lead_code"])) {$enable_delete_vendor_lead_code=$_GET["enable_delete_vendor_lead_code"];}
		elseif (isset($_POST["enable_delete_vendor_lead_code"])) {$enable_delete_vendor_lead_code=$_POST["enable_delete_vendor_lead_code"];}
	if (isset($_GET["enable_delete_source_id"])) {$enable_delete_source_id=$_GET["enable_delete_source_id"];}
		elseif (isset($_POST["enable_delete_source_id"])) {$enable_delete_source_id=$_POST["enable_delete_source_id"];}
	if (isset($_GET["enable_delete_owner"])) {$enable_delete_owner=$_GET["enable_delete_owner"];}
		elseif (isset($_POST["enable_delete_owner"])) {$enable_delete_owner=$_POST["enable_delete_owner"];}
	if (isset($_GET["enable_delete_entry_date"])) {$enable_delete_entry_date=$_GET["enable_delete_entry_date"];}
		elseif (isset($_POST["enable_delete_entry_date"])) {$enable_delete_entry_date=$_POST["enable_delete_entry_date"];}
	if (isset($_GET["enable_delete_modify_date"])) {$enable_delete_modify_date=$_GET["enable_delete_modify_date"];}
		elseif (isset($_POST["enable_delete_modify_date"])) {$enable_delete_modify_date=$_POST["enable_delete_modify_date"];}
	if (isset($_GET["enable_delete_security_phrase"])) {$enable_delete_security_phrase=$_GET["enable_delete_security_phrase"];}
		elseif (isset($_POST["enable_delete_security_phrase"])) {$enable_delete_security_phrase=$_POST["enable_delete_security_phrase"];}
	if (isset($_GET["enable_delete_count"])) {$enable_delete_count=$_GET["enable_delete_count"];}
		elseif (isset($_POST["enable_delete_count"])) {$enable_delete_count=$_POST["enable_delete_count"];}
	if (isset($_GET["delete_country_code"])) {$delete_country_code=$_GET["delete_country_code"];}
		elseif (isset($_POST["delete_country_code"])) {$delete_country_code=$_POST["delete_country_code"];}
	if (isset($_GET["delete_vendor_lead_code"])) {$delete_vendor_lead_code=$_GET["delete_vendor_lead_code"];}
		elseif (isset($_POST["delete_vendor_lead_code"])) {$delete_vendor_lead_code=$_POST["delete_vendor_lead_code"];}
	if (isset($_GET["delete_source_id"])) {$delete_source_id=$_GET["delete_source_id"];}
		elseif (isset($_POST["delete_source_id"])) {$delete_source_id=$_POST["delete_source_id"];}
	if (isset($_GET["delete_owner"])) {$delete_owner=$_GET["delete_owner"];}
		elseif (isset($_POST["delete_owner"])) {$delete_owner=$_POST["delete_owner"];}
	if (isset($_GET["delete_entry_date"])) {$delete_entry_date=$_GET["delete_entry_date"];}
		elseif (isset($_POST["delete_entry_date"])) {$delete_entry_date=$_POST["delete_entry_date"];}
	if (isset($_GET["delete_modify_date"])) {$delete_modify_date=$_GET["delete_modify_date"];}
		elseif (isset($_POST["delete_modify_date"])) {$delete_modify_date=$_POST["delete_modify_date"];}
	if (isset($_GET["delete_security_phrase"])) {$delete_security_phrase=$_GET["delete_security_phrase"];}
		elseif (isset($_POST["delete_security_phrase"])) {$delete_security_phrase=$_POST["delete_security_phrase"];}
	if (isset($_GET["delete_list"])) {$delete_list=$_GET["delete_list"];}
		elseif (isset($_POST["delete_list"])) {$delete_list=$_POST["delete_list"];}
	if (isset($_GET["delete_status"])) {$delete_status=$_GET["delete_status"];}
		elseif (isset($_POST["delete_status"])) {$delete_status=$_POST["delete_status"];}
	if (isset($_GET["delete_lead_id"])) {$delete_lead_id=$_GET["delete_lead_id"];}
		elseif (isset($_POST["delete_lead_id"])) {$delete_lead_id=$_POST["delete_lead_id"];}
	if (isset($_GET["delete_count_op"])) {$delete_count_op=$_GET["delete_count_op"];}
		elseif (isset($_POST["delete_count_op"])) {$delete_count_op=$_POST["delete_count_op"];}
	if (isset($_GET["delete_count_num"])) {$delete_count_num=$_GET["delete_count_num"];}
		elseif (isset($_POST["delete_count_num"])) {$delete_count_num=$_POST["delete_count_num"];}

	if ($DB)
		{
		echo "<p>enable_delete_country_code = $enable_delete_country_code | enable_delete_vendor_lead_code = $enable_delete_vendor_lead_code | enable_delete_source_id = $enable_delete_source_id | enable_delete_owner = $enable_delete_owner | enable_delete_entry_date = $enable_delete_entry_date | enable_delete_modify_date = $enable_delete_modify_date | enable_delete_security_phrase = $enable_delete_security_phrase | enable_delete_count = $enable_delete_count | delete_country_code = $delete_country_code | delete_vendor_lead_code = $delete_vendor_lead_code | delete_source_id = $delete_source_id | delete_owner = $delete_owner | delete_owner = $delete_entry_date | delete_modify_date = $delete_modify_date | delete_security_phrase = $delete_security_phrase | delete_list = $delete_list | delete_status = $delete_status | delete_count_op = $delete_count_op | delete_count_num = $delete_count_num | delete_lead_id = $delete_lead_id</p>";
		}

	# filter out anything bad
	$enable_delete_status = preg_replace('/[^a-zA-Z]/','',$enable_delete_status);
	$enable_delete_country_code = preg_replace('/[^a-zA-Z]/','',$enable_delete_country_code);
	$enable_delete_vendor_lead_code = preg_replace('/[^a-zA-Z]/','',$enable_delete_vendor_lead_code);
	$enable_delete_source_id = preg_replace('/[^a-zA-Z]/','',$enable_delete_source_id);
	$enable_delete_owner = preg_replace('/[^a-zA-Z]/','',$enable_delete_owner);
	$enable_delete_entry_date = preg_replace('/[^a-zA-Z]/','',$enable_delete_entry_date);
	$enable_delete_modify_date = preg_replace('/[^a-zA-Z]/','',$enable_delete_modify_date);
	$enable_delete_security_phrase = preg_replace('/[^a-zA-Z]/','',$enable_delete_security_phrase);
	$enable_delete_count = preg_replace('/[^a-zA-Z]/','',$enable_delete_count);
	$delete_country_code = preg_replace('/[^-_%a-zA-Z0-9]/','',$delete_country_code);
	$delete_vendor_lead_code = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_vendor_lead_code);
	$delete_source_id = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_source_id);
	$delete_owner = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_owner);
	$delete_entry_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_entry_date);
	$delete_modify_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_modify_date);
	$delete_security_phrase = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_security_phrase);
	$delete_status = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_status);
	$delete_lead_id = preg_replace('/[^0-9]/','',$delete_lead_id);
	$delete_list = preg_replace('/[^0-9]/','',$delete_list);
	$delete_count_num = preg_replace('/[^0-9]/','',$delete_count_num);
	$delete_count_op = preg_replace('/[^<>=]/','',$delete_count_op);

	if ($DB)
		{
		echo "<p>enable_delete_country_code = $enable_delete_country_code | enable_delete_vendor_lead_code = $enable_delete_vendor_lead_code | enable_delete_source_id = $enable_delete_source_id | enable_delete_owner = $enable_delete_owner | enable_delete_entry_date = $enable_delete_entry_date | enable_delete_modify_date = $enable_delete_modify_date | enable_delete_security_phrase = $enable_delete_security_phrase | enable_delete_count = $enable_delete_count | delete_country_code = $delete_country_code | delete_vendor_lead_code = $delete_vendor_lead_code | delete_source_id = $delete_source_id | delete_owner = $delete_owner | delete_owner = $delete_entry_date | delete_modify_date = $delete_modify_date | delete_security_phrase = $delete_security_phrase | delete_list = $delete_list | delete_status = $delete_status | delete_count_op = $delete_count_op | delete_count_num = $delete_count_num | delete_lead_id = $delete_lead_id</p>";
		}

	$delete_count_op_phrase="";
	if ( $delete_count_op == "<" )
		{
		$delete_count_op_phrase= "less than ";
		}
	elseif ( $delete_count_op == "<=" )
		{
		$delete_count_op_phrase= "less than or equal to ";
		}
	elseif ( $delete_count_op == ">" )
		{
		$delete_count_op_phrase= "greater than ";
		}
	elseif ( $delete_count_op == ">=" )
		{
		$delete_count_op_phrase= "greater than or equal to ";
		}

	# make sure the required fields are set
	if ($delete_status == '') { missing_required_field('Status'); }
	if ($delete_list == '') { missing_required_field('ID de Lista'); }

	# build the sql query's where phrase and the delete phrase
	$sql_where = "";
	$sql_where = $sql_where . " and status like '$delete_status' ";
	$delete_parm = "";
	$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;status is like $delete_status<br />";
	if (($enable_delete_lead_id == "enabled") && ($delete_lead_id != ''))
		{
		if ($delete_lead_id == '---BLANK---') {$delete_lead_id = '';}
		$sql_where = $sql_where . " and lead_id like '$delete_lead_id' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;lead_id is like $delete_lead_id<br />";
		if ($delete_lead_id == '') {$delete_lead_id = '---BLANK---';}
		}
	elseif ($enable_delete_lead_id == "enabled")
		{
		blank_field('Lead ID',true);
		}
	if (($enable_delete_country_code == "enabled") && ($delete_country_code != ''))
		{
		if ($delete_country_code == '---BLANK---') {$delete_country_code = '';}
		$sql_where = $sql_where . " and country_code like '$delete_country_code' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;country code is like $delete_country_code<br />";
		if ($delete_country_code == '') {$delete_country_code = '---BLANK---';}
		}
	elseif ($enable_delete_country_code == "enabled")
		{
		blank_field('País Code',true);
		}
	if (($enable_delete_vendor_lead_code == "enabled") && ($delete_vendor_lead_code != ''))
		{
		if ($delete_vendor_lead_code == '---BLANK---') {$delete_vendor_lead_code = '';}
		$sql_where = $sql_where . " and vendor_lead_code like '$delete_vendor_lead_code' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;Código del Proveedor del contactos is like $delete_vendor_lead_code<br />";
		if ($delete_vendor_lead_code == '') {$delete_vendor_lead_code = '---BLANK---';}
		}
	elseif ($enable_delete_vendor_lead_code == "enabled")
		{
		blank_field('Vendor Lead Code',true);
		}
	if (($enable_delete_source_id == "enabled") && ($delete_source_id != ''))
		{
		if ($delete_source_id == '---BLANK---') {$delete_source_id = '';}
		$sql_where = $sql_where . " and source_id like '$delete_source_id' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;source id code is like $delete_source_id<br />";
		if ($delete_source_id == '') {$delete_source_id = '---BLANK---';}
		}
	elseif ($enable_delete_source_id == "enabled")
		{
		blank_field('Source ID',true);
		}
	if (($enable_delete_security_phrase == "enabled") && ($delete_security_phrase != ''))
		{
		if ($delete_security_phrase == '---BLANK---') {$delete_security_phrase = '';}
		$sql_where = $sql_where . " and security_phrase like '$delete_security_phrase' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;secuirty phrase is like $delete_security_phrase<br />";
		if ($delete_security_phrase == '') {$delete_security_phrase = '---BLANK---';}
		}
	elseif ($enable_delete_security_phrase == "enabled")
		{
		blank_field('Frase de Seguridad',true);
		}
	if (($enable_delete_owner == "enabled") && ($delete_owner != ''))
		{
		if ($delete_owner == '---BLANK---') {$delete_owner = '';}
		$sql_where = $sql_where . " and owner like '$delete_owner' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;owner is like $delete_owner<br />";
		if ($delete_owner == '') {$delete_owner = '---BLANK---';}
		}
	elseif ($enable_delete_owner == "enabled")
		{
		blank_field('Owner',true);
		}
	if (($enable_delete_entry_date == "enabled") && ($delete_entry_date != ''))
		{
		if ($delete_entry_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and entry_date == '' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and entry_date >= '$delete_entry_date 00:00:00' and entry_date <= '$delete_entry_date 23:59:59' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date was on $delete_entry_date<br />";
			}
		}
	elseif ($enable_delete_entry_date == "enabled")
		{
		blank_field('Entry Date',true);
		}
	if (($enable_delete_modify_date == "enabled") && ($delete_modify_date != ''))
		{
		if ($delete_modify_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and modify_date == '' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;modify date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and modify_date >= '$delete_modify_date 00:00:00' and modify_date <= '$delete_modify_date 23:59:59' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;last modify date was on $delete_modify_date<br />";
			}
		}
	elseif ($enable_delete_modify_date == "enabled")
		{
		blank_field('ModificarDate',true);
		}
	if (($enable_delete_count == "enabled") && ($delete_count_op != '') && ($delete_count_num != ''))
		{
		if ($delete_count_op == '---BLANK---') {$delete_count_op = '';}
		if ($delete_count_num == '---BLANK---') {$delete_count_num = '';}
		$sql_where = $sql_where . " and called_count $delete_count_op $delete_count_num";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;called count is $update_count_op_phrase $delete_count_num<br />";
		if ($delete_count_op == '') {$delete_count_op = '---BLANK---';}
		if ($delete_count_num == '') {$delete_count_num = '---BLANK---';}
		}
	elseif ($enable_delete_count == "enabled")
		{
		blank_field('Move Count',true);
		}

	# get the number of leads this action will move
	$delete_lead_count=0;
	$delete_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$delete_list' $sql_where";
	if ($DB) { echo "|$delete_lead_count_stmt|\n"; }
	$delete_lead_count_rslt = mysql_to_mysqli($delete_lead_count_stmt, $link);
	$delete_lead_count_row = mysqli_fetch_row($delete_lead_count_rslt);
	$delete_lead_count = $delete_lead_count_row[0];

	echo "<p>You are about to delete $delete_lead_count leads in list $delete_list with the following parameters:<br /><br />$delete_parm<br />Please press confirm to continue.</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=enable_delete_lead_id value='$enable_delete_lead_id'>\n";
	echo "<input type=hidden name=enable_delete_country_code value='$enable_delete_country_code'>\n";
	echo "<input type=hidden name=enable_delete_vendor_lead_code value='$enable_delete_vendor_lead_code'>\n";
	echo "<input type=hidden name=enable_delete_source_id value='$enable_delete_source_id'>\n";
	echo "<input type=hidden name=enable_delete_owner value='$enable_delete_owner'>\n";
	echo "<input type=hidden name=enable_delete_entry_date value='$enable_delete_entry_date'>\n";
	echo "<input type=hidden name=enable_delete_modify_date value='$enable_delete_modify_date'>\n";
	echo "<input type=hidden name=enable_delete_security_phrase value='$enable_delete_security_phrase'>\n";
	echo "<input type=hidden name=enable_delete_count value='$enable_delete_count'>\n";
	echo "<input type=hidden name=delete_country_code value='$delete_country_code'>\n";
	echo "<input type=hidden name=delete_vendor_lead_code value='$delete_vendor_lead_code'>\n";
	echo "<input type=hidden name=delete_source_id value='$delete_source_id'>\n";
	echo "<input type=hidden name=delete_owner value='$delete_owner'>\n";
	echo "<input type=hidden name=delete_entry_date value='$delete_entry_date'>\n";
	echo "<input type=hidden name=delete_modify_date value='$delete_modify_date'>\n";
	echo "<input type=hidden name=delete_security_phrase value='$delete_security_phrase'>\n";
	echo "<input type=hidden name=delete_list value='$delete_list'>\n";
	echo "<input type=hidden name=delete_status value='$delete_status'>\n";
	echo "<input type=hidden name=delete_count_op value='$delete_count_op'>\n";
	echo "<input type=hidden name=delete_count_num value='$delete_count_num'>\n";
	echo "<input type=hidden name=delete_lead_id value='$delete_lead_id'>\n";
	echo "<input type=hidden name=DB value='$DB'>\n";
	echo "<input type=submit name=confirm_delete value=confirm>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
	echo "</body>\n</html>\n";

	}

# actually do the delete
if ( ( $confirm_delete == "confirm" ) && ( $delete_lists > 0 ) )
	{
	# get the variables
	$enable_delete_lead_id="";
	$enable_delete_country_code="";
	$enable_delete_vendor_lead_code="";
	$enable_delete_source_id="";
	$enable_delete_owner="";
	$enable_delete_entry_date="";
	$enable_delete_modify_date="";
	$enable_delete_security_phrase="";
	$enable_delete_count="";
	$delete_country_code="";
	$delete_vendor_lead_code="";
	$delete_source_id="";
	$delete_owner="";
	$delete_entry_date="";
	$delete_modify_date="";
	$delete_security_phrase="";
	$delete_list="";
	$delete_status="";
	$delete_count_op="";
	$delete_count_num="";
	$delete_lead_id="";

	# check the get / post data for the variables
	if (isset($_GET["enable_delete_lead_id"])) {$enable_delete_lead_id=$_GET["enable_delete_lead_id"];}
		elseif (isset($_POST["enable_delete_lead_id"])) {$enable_delete_lead_id=$_POST["enable_delete_lead_id"];}
	if (isset($_GET["enable_delete_country_code"])) {$enable_delete_country_code=$_GET["enable_delete_country_code"];}
		elseif (isset($_POST["enable_delete_country_code"])) {$enable_delete_country_code=$_POST["enable_delete_country_code"];}
	if (isset($_GET["enable_delete_vendor_lead_code"])) {$enable_delete_vendor_lead_code=$_GET["enable_delete_vendor_lead_code"];}
		elseif (isset($_POST["enable_delete_vendor_lead_code"])) {$enable_delete_vendor_lead_code=$_POST["enable_delete_vendor_lead_code"];}
	if (isset($_GET["enable_delete_source_id"])) {$enable_delete_source_id=$_GET["enable_delete_source_id"];}
		elseif (isset($_POST["enable_delete_source_id"])) {$enable_delete_source_id=$_POST["enable_delete_source_id"];}
	if (isset($_GET["enable_delete_owner"])) {$enable_delete_owner=$_GET["enable_delete_owner"];}
		elseif (isset($_POST["enable_delete_owner"])) {$enable_delete_owner=$_POST["enable_delete_owner"];}
	if (isset($_GET["enable_delete_entry_date"])) {$enable_delete_entry_date=$_GET["enable_delete_entry_date"];}
		elseif (isset($_POST["enable_delete_entry_date"])) {$enable_delete_entry_date=$_POST["enable_delete_entry_date"];}
	if (isset($_GET["enable_delete_modify_date"])) {$enable_delete_modify_date=$_GET["enable_delete_modify_date"];}
		elseif (isset($_POST["enable_delete_modify_date"])) {$enable_delete_modify_date=$_POST["enable_delete_modify_date"];}
	if (isset($_GET["enable_delete_security_phrase"])) {$enable_delete_security_phrase=$_GET["enable_delete_security_phrase"];}
		elseif (isset($_POST["enable_delete_security_phrase"])) {$enable_delete_security_phrase=$_POST["enable_delete_security_phrase"];}
	if (isset($_GET["enable_delete_count"])) {$enable_delete_count=$_GET["enable_delete_count"];}
		elseif (isset($_POST["enable_delete_count"])) {$enable_delete_count=$_POST["enable_delete_count"];}
	if (isset($_GET["delete_country_code"])) {$delete_country_code=$_GET["delete_country_code"];}
		elseif (isset($_POST["delete_country_code"])) {$delete_country_code=$_POST["delete_country_code"];}
	if (isset($_GET["delete_vendor_lead_code"])) {$delete_vendor_lead_code=$_GET["delete_vendor_lead_code"];}
		elseif (isset($_POST["delete_vendor_lead_code"])) {$delete_vendor_lead_code=$_POST["delete_vendor_lead_code"];}
	if (isset($_GET["delete_source_id"])) {$delete_source_id=$_GET["delete_source_id"];}
		elseif (isset($_POST["delete_source_id"])) {$delete_source_id=$_POST["delete_source_id"];}
	if (isset($_GET["delete_owner"])) {$delete_owner=$_GET["delete_owner"];}
		elseif (isset($_POST["delete_owner"])) {$delete_owner=$_POST["delete_owner"];}
	if (isset($_GET["delete_entry_date"])) {$delete_entry_date=$_GET["delete_entry_date"];}
		elseif (isset($_POST["delete_entry_date"])) {$delete_entry_date=$_POST["delete_entry_date"];}
	if (isset($_GET["delete_modify_date"])) {$delete_modify_date=$_GET["delete_modify_date"];}
		elseif (isset($_POST["delete_modify_date"])) {$delete_modify_date=$_POST["delete_modify_date"];}
	if (isset($_GET["delete_security_phrase"])) {$delete_security_phrase=$_GET["delete_security_phrase"];}
		elseif (isset($_POST["delete_security_phrase"])) {$delete_security_phrase=$_POST["delete_security_phrase"];}
	if (isset($_GET["delete_list"])) {$delete_list=$_GET["delete_list"];}
		elseif (isset($_POST["delete_list"])) {$delete_list=$_POST["delete_list"];}
	if (isset($_GET["delete_status"])) {$delete_status=$_GET["delete_status"];}
		elseif (isset($_POST["delete_status"])) {$delete_status=$_POST["delete_status"];}
	if (isset($_GET["delete_lead_id"])) {$delete_lead_id=$_GET["delete_lead_id"];}
		elseif (isset($_POST["delete_lead_id"])) {$delete_lead_id=$_POST["delete_lead_id"];}
	if (isset($_GET["delete_count_op"])) {$delete_count_op=$_GET["delete_count_op"];}
		elseif (isset($_POST["delete_count_op"])) {$delete_count_op=$_POST["delete_count_op"];}
	if (isset($_GET["delete_count_num"])) {$delete_count_num=$_GET["delete_count_num"];}
		elseif (isset($_POST["delete_count_num"])) {$delete_count_num=$_POST["delete_count_num"];}

	if ($DB)
		{
		echo "<p>enable_delete_country_code = $enable_delete_country_code | enable_delete_vendor_lead_code = $enable_delete_vendor_lead_code | enable_delete_source_id = $enable_delete_source_id | enable_delete_owner = $enable_delete_owner | enable_delete_entry_date = $enable_delete_entry_date | enable_delete_modify_date = $enable_delete_modify_date | enable_delete_security_phrase = $enable_delete_security_phrase | enable_delete_count = $enable_delete_count | delete_country_code = $delete_country_code | delete_vendor_lead_code = $delete_vendor_lead_code | delete_source_id = $delete_source_id | delete_owner = $delete_owner | delete_owner = $delete_entry_date | delete_modify_date = $delete_modify_date | delete_security_phrase = $delete_security_phrase | delete_list = $delete_list | delete_status = $delete_status | delete_count_op = $delete_count_op | delete_count_num = $delete_count_num | delete_lead_id = $delete_lead_id</p>";
		}

	# filter out anything bad
	$enable_delete_status = preg_replace('/[^a-zA-Z]/','',$enable_delete_status);
	$enable_delete_country_code = preg_replace('/[^a-zA-Z]/','',$enable_delete_country_code);
	$enable_delete_vendor_lead_code = preg_replace('/[^a-zA-Z]/','',$enable_delete_vendor_lead_code);
	$enable_delete_source_id = preg_replace('/[^a-zA-Z]/','',$enable_delete_source_id);
	$enable_delete_owner = preg_replace('/[^a-zA-Z]/','',$enable_delete_owner);
	$enable_delete_entry_date = preg_replace('/[^a-zA-Z]/','',$enable_delete_entry_date);
	$enable_delete_modify_date = preg_replace('/[^a-zA-Z]/','',$enable_delete_modify_date);
	$enable_delete_security_phrase = preg_replace('/[^a-zA-Z]/','',$enable_delete_security_phrase);
	$enable_delete_count = preg_replace('/[^a-zA-Z]/','',$enable_delete_count);
	$delete_country_code = preg_replace('/[^-_%a-zA-Z0-9]/','',$delete_country_code);
	$delete_vendor_lead_code = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_vendor_lead_code);
	$delete_source_id = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_source_id);
	$delete_owner = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_owner);
	$delete_entry_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_entry_date);
	$delete_modify_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_modify_date);
	$delete_security_phrase = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_security_phrase);
	$delete_status = preg_replace('/[^-_%0-9a-zA-Z]/','',$delete_status);
	$delete_lead_id = preg_replace('/[^0-9]/','',$delete_lead_id);
	$delete_list = preg_replace('/[^0-9]/','',$delete_list);
	$delete_count_num = preg_replace('/[^0-9]/','',$delete_count_num);
	$delete_count_op = preg_replace('/[^<>=]/','',$delete_count_op);

	if ($DB)
		{
		echo "<p>enable_delete_country_code = $enable_delete_country_code | enable_delete_vendor_lead_code = $enable_delete_vendor_lead_code | enable_delete_source_id = $enable_delete_source_id | enable_delete_owner = $enable_delete_owner | enable_delete_entry_date = $enable_delete_entry_date | enable_delete_modify_date = $enable_delete_modify_date | enable_delete_security_phrase = $enable_delete_security_phrase | enable_delete_count = $enable_delete_count | delete_country_code = $delete_country_code | delete_vendor_lead_code = $delete_vendor_lead_code | delete_source_id = $delete_source_id | delete_owner = $delete_owner | delete_owner = $delete_entry_date | delete_modify_date = $delete_modify_date | delete_security_phrase = $delete_security_phrase | delete_list = $delete_list | delete_status = $delete_status | delete_count_op = $delete_count_op | delete_count_num = $delete_count_num | delete_lead_id = $delete_lead_id</p>";
		}

	$delete_count_op_phrase="";
	if ( $delete_count_op == "<" )
		{
		$delete_count_op_phrase= "less than ";
		}
	elseif ( $delete_count_op == "<=" )
		{
		$delete_count_op_phrase= "less than or equal to ";
		}
	elseif ( $delete_count_op == ">" )
		{
		$delete_count_op_phrase= "greater than ";
		}
	elseif ( $delete_count_op == ">=" )
		{
		$delete_count_op_phrase= "greater than or equal to ";
		}

	# make sure the required fields are set
	if ($delete_status == '') { missing_required_field('Status'); }
	if ($delete_list == '') { missing_required_field('ID de Lista'); }

	# build the sql query's where phrase and the delete phrase
	$sql_where = "";
	$sql_where = $sql_where . " and status like '$delete_status' ";
	$delete_parm = "";
	$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;status is like $delete_status<br />";
	if (($enable_delete_lead_id == "enabled") && ($delete_lead_id != ''))
		{
		if ($delete_lead_id == '---BLANK---') {$delete_lead_id = '';}
		$sql_where = $sql_where . " and lead_id like '$delete_lead_id' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;lead_id is like $delete_lead_id<br />";
		if ($delete_lead_id == '') {$delete_lead_id = '---BLANK---';}
		}
	elseif ($enable_delete_lead_id == "enabled")
		{
		blank_field('Lead ID',true);
		}
	if (($enable_delete_country_code == "enabled") && ($delete_country_code != ''))
		{
		if ($delete_country_code == '---BLANK---') {$delete_country_code = '';}
		$sql_where = $sql_where . " and country_code like '$delete_country_code' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;country code is like $delete_country_code<br />";
		if ($delete_country_code == '') {$delete_country_code = '---BLANK---';}
		}
	elseif ($enable_delete_country_code == "enabled")
		{
		blank_field('País Code',true);
		}
	if (($enable_delete_vendor_lead_code == "enabled") && ($delete_vendor_lead_code != ''))
		{
		if ($delete_vendor_lead_code == '---BLANK---') {$delete_vendor_lead_code = '';}
		$sql_where = $sql_where . " and vendor_lead_code like '$delete_vendor_lead_code' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;Código del Proveedor del contactos is like $delete_vendor_lead_code<br />";
		if ($delete_vendor_lead_code == '') {$delete_vendor_lead_code = '---BLANK---';}
		}
	elseif ($enable_delete_vendor_lead_code == "enabled")
		{
		blank_field('Vendor Lead Code',true);
		}
	if (($enable_delete_source_id == "enabled") && ($delete_source_id != ''))
		{
		if ($delete_source_id == '---BLANK---') {$delete_source_id = '';}
		$sql_where = $sql_where . " and source_id like '$delete_source_id' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;source id code is like $delete_source_id<br />";
		if ($delete_source_id == '') {$delete_source_id = '---BLANK---';}
		}
	elseif ($enable_delete_source_id == "enabled")
		{
		blank_field('Source ID',true);
		}
	if (($enable_delete_security_phrase == "enabled") && ($delete_security_phrase != ''))
		{
		if ($delete_security_phrase == '---BLANK---') {$delete_security_phrase = '';}
		$sql_where = $sql_where . " and security_phrase like '$delete_security_phrase' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;secuirty phrase is like $delete_security_phrase<br />";
		if ($delete_security_phrase == '') {$delete_security_phrase = '---BLANK---';}
		}
	elseif ($enable_delete_security_phrase == "enabled")
		{
		blank_field('Frase de Seguridad',true);
		}
	if (($enable_delete_owner == "enabled") && ($delete_owner != ''))
		{
		if ($delete_owner == '---BLANK---') {$delete_owner = '';}
		$sql_where = $sql_where . " and owner like '$delete_owner' ";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;;owner is like $delete_owner<br />";
		if ($delete_owner == '') {$delete_owner = '---BLANK---';}
		}
	elseif ($enable_delete_owner == "enabled")
		{
		blank_field('Owner',true);
		}
	if (($enable_delete_entry_date == "enabled") && ($delete_entry_date != ''))
		{
		if ($delete_entry_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and entry_date == '' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and entry_date >= '$delete_entry_date 00:00:00' and entry_date <= '$delete_entry_date 23:59:59' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry date was on $delete_entry_date<br />";
			}
		}
	elseif ($enable_delete_entry_date == "enabled")
		{
		blank_field('Entry Date',true);
		}
	if (($enable_delete_modify_date == "enabled") && ($delete_modify_date != ''))
		{
		if ($delete_modify_date == '---BLANK---')
			{
			$sql_where = $sql_where . " and modify_date == '' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;modify date is blank<br />";
			}
		else
			{
			$sql_where = $sql_where . " and modify_date >= '$delete_modify_date 00:00:00' and modify_date <= '$delete_modify_date 23:59:59' ";
			$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;last modify date was on $delete_modify_date<br />";
			}
		}
	elseif ($enable_delete_modify_date == "enabled")
		{
		blank_field('ModificarDate',true);
		}
	if (($enable_delete_count == "enabled") && ($delete_count_op != '') && ($delete_count_num != ''))
		{
		if ($delete_count_op == '---BLANK---') {$delete_count_op = '';}
		if ($delete_count_num == '---BLANK---') {$delete_count_num = '';}
		$sql_where = $sql_where . " and called_count $delete_count_op $delete_count_num";
		$delete_parm = $delete_parm . "&nbsp;&nbsp;&nbsp;&nbsp;called count is $update_count_op_phrase $delete_count_num<br />";
		if ($delete_count_op == '') {$delete_count_op = '---BLANK---';}
		if ($delete_count_num == '') {$delete_count_num = '---BLANK---';}
		}
	elseif ($enable_delete_count == "enabled")
		{
		blank_field('Move Count',true);
		}

	$delete_lead_stmt = "DELETE FROM vicidial_list WHERE list_id = '$delete_list' $sql_where";
	if ($DB) { echo "|$delete_lead_stmt|\n"; }
	$delete_lead_rslt = mysql_to_mysqli($delete_lead_stmt, $link);
	$delete_lead_count = mysqli_affected_rows($link);

	$delete_sentence = "$delete_lead_count leads delete from list $delete_list with the following parameters:<br /><br />$delete_parm<br />";

	$SQL_log = "$delete_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='DELETE', record_id='$delete_list', event_code='ADMIN DELETE LEADS', event_sql=\"$SQL_log\", event_notes='$delete_sentence';";
	if ($DB) {echo "|$admin_log_stmt|\n";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$delete_sentence</p>";
	echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
	}



# callback confirmation page
if ($callback_submit == "switchcallbacks" )
	{
	# get the variables
	$enable_callback_entry_date="";
	$enable_callback_callback_date="";
	$callback_entry_start_date="";
	$callback_entry_end_date="";
	$callback_callback_start_date="";
	$callback_callback_end_date="";
	$callback_list="";

	# check the get / post data for the variables
	if (isset($_GET["enable_callback_entry_date"])) {$enable_callback_entry_date=$_GET["enable_callback_entry_date"];}
		elseif (isset($_POST["enable_callback_entry_date"])) {$enable_callback_entry_date=$_POST["enable_callback_entry_date"];}
	if (isset($_GET["enable_callback_callback_date"])) {$enable_callback_callback_date=$_GET["enable_callback_callback_date"];}
		elseif (isset($_POST["enable_callback_callback_date"])) {$enable_callback_callback_date=$_POST["enable_callback_callback_date"];}
	if (isset($_GET["callback_entry_start_date"])) {$callback_entry_start_date=$_GET["callback_entry_start_date"];}
		elseif (isset($_POST["callback_entry_start_date"])) {$callback_entry_start_date=$_POST["callback_entry_start_date"];}
	if (isset($_GET["callback_entry_end_date"])) {$callback_entry_end_date=$_GET["callback_entry_end_date"];}
		elseif (isset($_POST["callback_entry_end_date"])) {$callback_entry_end_date=$_POST["callback_entry_end_date"];}
	if (isset($_GET["callback_callback_start_date"])) {$callback_callback_start_date=$_GET["callback_callback_start_date"];}
		elseif (isset($_POST["callback_callback_start_date"])) {$callback_callback_start_date=$_POST["callback_callback_start_date"];}
	if (isset($_GET["callback_callback_end_date"])) {$callback_callback_end_date=$_GET["callback_callback_end_date"];}
		elseif (isset($_POST["callback_callback_end_date"])) {$callback_callback_end_date=$_POST["callback_callback_end_date"];}
	if (isset($_GET["callback_list"])) {$callback_list=$_GET["callback_list"];}
		elseif (isset($_POST["callback_list"])) {$callback_list=$_POST["callback_list"];}

	if ($DB)
		{
		echo "<p>enable_callback_entry_date = $enable_callback_entry_date | enable_callback_callback_date = $enable_callback_callback_date | callback_entry_start_date = $callback_entry_start_date | callback_entry_end_date = $callback_entry_end_date | callback_callback_start_date = $callback_callback_start_date | callback_callback_end_date = $callback_callback_end_date | callback_list = $callback_list</p>";
		}

	# filter out anything bad
	$enable_callback_entry_date = preg_replace('/[^a-zA-Z]/','',$enable_callback_entry_date);
	$enable_callback_callback_date = preg_replace('/[^a-zA-Z]/','',$enable_callback_callback_date);
	$callback_entry_start_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$callback_entry_start_date);
	$callback_entry_end_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$callback_entry_end_date);
	$callback_callback_start_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$callback_callback_start_date);
	$callback_callback_end_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$callback_callback_end_date);
	$callback_list = preg_replace('/[^0-9]/','',$callback_list);

	if ($DB)
		{
		echo "<p>enable_callback_entry_date = $enable_callback_entry_date | enable_callback_callback_date = $enable_callback_callback_date | callback_entry_start_date = $callback_entry_start_date | callback_entry_end_date = $callback_entry_end_date | callback_callback_start_date = $callback_callback_start_date | callback_callback_end_date = $callback_callback_end_date | callback_list = $callback_list</p>";
		}


	# make sure the required fields are set
	if ($callback_list == '') { callback_list('List'); }


	# build the sql query's where phrase and the callback phrase
	$sql_where = "";
	$callback_parm = "";



	if (($enable_callback_entry_date == "enabled") && ($callback_entry_start_date != ''))
		{
		$sql_where = $sql_where . " and entry_time >= '$callback_entry_start_date 00:00:00' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry time greater than $callback_entry_start_date 00:00:00<br />";
		}
	elseif ($enable_callback_entry_date == "enabled")
		{
		blank_field('Entry Start Date',false);
		}
	if (($enable_callback_entry_date == "enabled") && ($callback_entry_end_date != ''))
		{
		$sql_where = $sql_where . " and entry_time <= '$callback_entry_end_date 23:59:59' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry time less than $callback_entry_end_date 23:59:59<br />";
		}
	elseif ($enable_callback_entry_date == "enabled")
		{
		blank_field('Entry End Date',false);
		}

	if (($enable_callback_callback_date == "enabled") && ($callback_callback_start_date != ''))
		{
		$sql_where = $sql_where . " and callback_time >= '$callback_callback_start_date 00:00:00' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;callback time greater than $callback_callback_start_date 00:00:00<br />";
		}
	elseif ($enable_callback_callback_date == "enabled")
		{
		blank_field('Callback Start Date',false);
		}
	if (($enable_callback_callback_date == "enabled") && ($callback_callback_end_date != ''))
		{
		$sql_where = $sql_where . " and callback_time <= '$callback_callback_end_date 23:59:59' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;callback time less than $callback_callback_end_date 23:59:59<br />";
		}
	elseif ($enable_callback_callback_date == "enabled")
		{
		blank_field('Callback End Date',false);
		}


	# get the number of call backs that will be switched
	$callback_lead_count=0;
	$callback_lead_count_stmt = "SELECT count(1) FROM vicidial_callbacks WHERE list_id = '$callback_list' and recipient = 'USERONLY' $sql_where";
	if ($DB) { echo "|$callback_lead_count_stmt|\n"; }
	$callback_lead_count_rslt = mysql_to_mysqli($callback_lead_count_stmt, $link);
	$callback_lead_count_row = mysqli_fetch_row($callback_lead_count_rslt);
	$callback_lead_count = $callback_lead_count_row[0];

		echo "<p>You are about to switch $callback_lead_count call backs in list $callback_list from USERONLY callbacks to EVERYONE callbacks with these parameters:<br /><br />$callback_parm <br />Please press confirm to continue.</p>\n";
		echo "<center><form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=enable_callback_entry_date value='$enable_callback_entry_date'>\n";
		echo "<input type=hidden name=enable_callback_callback_date value='$enable_callback_callback_date'>\n";
		echo "<input type=hidden name=callback_entry_start_date value='$callback_entry_start_date'>\n";
		echo "<input type=hidden name=callback_entry_end_date value='$callback_entry_end_date'>\n";
		echo "<input type=hidden name=callback_callback_start_date value='$callback_callback_start_date'>\n";
		echo "<input type=hidden name=callback_callback_end_date value='$callback_callback_end_date'>\n";
		echo "<input type=hidden name=callback_list value='$callback_list'>\n";
		echo "<input type=hidden name=DB value='$DB'>\n";
		echo "<input type=submit name=confirm_callback value=confirm>\n";
		echo "</form></center>\n";
		echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
		echo "</body>\n</html>\n";
	}

# actually do the callback
if ($confirm_callback == "confirm")
	{
	# get the variables
	$enable_callback_entry_date="";
	$enable_callback_callback_date="";
	$callback_entry_start_date="";
	$callback_entry_end_date="";
	$callback_callback_start_date="";
	$callback_callback_end_date="";
	$callback_list="";

	# check the get / post data for the variables
	if (isset($_GET["enable_callback_entry_date"])) {$enable_callback_entry_date=$_GET["enable_callback_entry_date"];}
		elseif (isset($_POST["enable_callback_entry_date"])) {$enable_callback_entry_date=$_POST["enable_callback_entry_date"];}
	if (isset($_GET["enable_callback_callback_date"])) {$enable_callback_callback_date=$_GET["enable_callback_callback_date"];}
		elseif (isset($_POST["enable_callback_callback_date"])) {$enable_callback_callback_date=$_POST["enable_callback_callback_date"];}
	if (isset($_GET["callback_entry_start_date"])) {$callback_entry_start_date=$_GET["callback_entry_start_date"];}
		elseif (isset($_POST["callback_entry_start_date"])) {$callback_entry_start_date=$_POST["callback_entry_start_date"];}
	if (isset($_GET["callback_entry_end_date"])) {$callback_entry_end_date=$_GET["callback_entry_end_date"];}
		elseif (isset($_POST["callback_entry_end_date"])) {$callback_entry_end_date=$_POST["callback_entry_end_date"];}
	if (isset($_GET["callback_callback_start_date"])) {$callback_callback_start_date=$_GET["callback_callback_start_date"];}
		elseif (isset($_POST["callback_callback_start_date"])) {$callback_callback_start_date=$_POST["callback_callback_start_date"];}
	if (isset($_GET["callback_callback_end_date"])) {$callback_callback_end_date=$_GET["callback_callback_end_date"];}
		elseif (isset($_POST["callback_callback_end_date"])) {$callback_callback_end_date=$_POST["callback_callback_end_date"];}
	if (isset($_GET["callback_list"])) {$callback_list=$_GET["callback_list"];}
		elseif (isset($_POST["callback_list"])) {$callback_list=$_POST["callback_list"];}

	if ($DB)
		{
		echo "<p>enable_callback_entry_date = $enable_callback_entry_date | enable_callback_callback_date = $enable_callback_callback_date | callback_entry_start_date = $callback_entry_start_date | callback_entry_end_date = $callback_entry_end_date | callback_callback_start_date = $callback_callback_start_date | callback_callback_end_date = $callback_callback_end_date | callback_list = $callback_list</p>";
		}

	# filter out anything bad
	$enable_callback_entry_date = preg_replace('/[^a-zA-Z]/','',$enable_callback_entry_date);
	$enable_callback_callback_date = preg_replace('/[^a-zA-Z]/','',$enable_callback_callback_date);
	$callback_entry_start_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$callback_entry_start_date);
	$callback_entry_end_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$callback_entry_end_date);
	$callback_callback_start_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$callback_callback_start_date);
	$callback_callback_end_date = preg_replace('/[^-_%0-9a-zA-Z]/','',$callback_callback_end_date);
	$callback_list = preg_replace('/[^0-9]/','',$callback_list);

	if ($DB)
		{
		echo "<p>enable_callback_entry_date = $enable_callback_entry_date | enable_callback_callback_date = $enable_callback_callback_date | callback_entry_start_date = $callback_entry_start_date | callback_entry_end_date = $callback_entry_end_date | callback_callback_start_date = $callback_callback_start_date | callback_callback_end_date = $callback_callback_end_date | callback_list = $callback_list</p>";
		}


	# make sure the required fields are set
	if ($callback_list == '') { callback_list('List'); }


	# build the sql query's where phrase and the callback phrase
	$sql_where = "";
	$callback_parm = "";



	if (($enable_callback_entry_date == "enabled") && ($callback_entry_start_date != ''))
		{
		$sql_where = $sql_where . " and entry_time >= '$callback_entry_start_date 00:00:00' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry time greater than $callback_entry_start_date 00:00:00<br />";
		}
	elseif ($enable_callback_entry_date == "enabled")
		{
		blank_field('Entry Start Date',false);
		}
	if (($enable_callback_entry_date == "enabled") && ($callback_entry_end_date != ''))
		{
		$sql_where = $sql_where . " and entry_time <= '$callback_entry_end_date 23:59:59' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;entry time less than $callback_entry_end_date 23:59:59<br />";
		}
	elseif ($enable_callback_entry_date == "enabled")
		{
		blank_field('Entry End Date',false);
		}

	if (($enable_callback_callback_date == "enabled") && ($callback_callback_start_date != ''))
		{
		$sql_where = $sql_where . " and callback_time >= '$callback_callback_start_date 00:00:00' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;callback time greater than $callback_callback_start_date 00:00:00<br />";
		}
	elseif ($enable_callback_callback_date == "enabled")
		{
		blank_field('Callback Start Date',false);
		}
	if (($enable_callback_callback_date == "enabled") && ($callback_callback_end_date != ''))
		{
		$sql_where = $sql_where . " and callback_time <= '$callback_callback_end_date 23:59:59' ";
		$callback_parm = $callback_parm . "&nbsp;&nbsp;&nbsp;&nbsp;callback time less than $callback_callback_end_date 23:59:59<br />";
		}
	elseif ($enable_callback_callback_date == "enabled")
		{
		blank_field('Callback End Date',false);
		}

	$callback_lead_stmt = "UPDATE vicidial_callbacks SET recipient = 'ANYONE' WHERE list_id = '$callback_list' and recipient = 'USERONLY' $sql_where";
	if ($DB) { echo "|$callback_lead_stmt|\n"; }
	$callback_lead_rslt = mysql_to_mysqli($callback_lead_stmt, $link);
	$callback_lead_count = mysqli_affected_rows($link);

	$callback_sentence = "$callback_lead_count leads have been set to ANYONE callbacks from list $callback_list with the following parameters:<br /><br />$callback_parm <br />";

	$SQL_log = "$callback_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$callback_from_list', event_code='ADMIN SWITCH CALLBACKS', event_sql=\"$SQL_log\", event_notes='$callback_sentence';";
	if ($DB) {echo "|$admin_log_stmt|\n";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$callback_sentence</p>";
	echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
	}





# main page display
if (
		($move_submit != "move" ) && ($update_submit != "update") && ($delete_submit != "delete") && ($callback_submit != "switchcallbacks") &&
		($confirm_move != "confirm") && ($confirm_update != "confirm") && ($confirm_delete != "confirm") && ($confirm_callback != "confirm")
	)
	{
	# figure out which campaigns this user is allowed to work on
	$allowed_campaigns_stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$user_group';";
	if ($DB) { echo "|$allowed_campaigns_stmt|\n"; }
	$rslt = mysql_to_mysqli($allowed_campaigns_stmt, $link);
	$allowed_campaigns_row = mysqli_fetch_row($rslt);
	$allowed_campaigns = $allowed_campaigns_row[0];
	if ($DB) { echo "|$allowed_campaigns|\n"; }
	$allowed_campaigns_sql = "";
	if ( preg_match("/ALL\-CAMPAIGNS/i",$allowed_campaigns) )
		{
		if ($DB) { echo "|Procesando All Campañas|\n"; }
		$campaign_id_stmt = "SELECT campaign_id FROM vicidial_campaigns";
		$campaign_id_rslt = mysql_to_mysqli($campaign_id_stmt, $link);
		$campaign_id_num_rows = mysqli_num_rows($campaign_id_rslt);
		if ($DB) { echo "|campaign_id_num_rows = $campaign_id_num_rows|\n"; }
		if ($campaign_id_num_rows > 0)
			{
			$i = 0;
			while ( $i < $campaign_id_num_rows )
				{
				$campaign_id_row = mysqli_fetch_row($campaign_id_rslt);
				if ( $i == 0 )
					{
					$allowed_campaigns_sql = "'$campaign_id_row[0]'";
					}
				else
					{
					$allowed_campaigns_sql = "$allowed_campaigns_sql, '$campaign_id_row[0]'";
					}
				$i++;
				}
			}
		}
	else
		{
		$allowed_campaigns_sql = preg_replace("/ -/",'',$allowed_campaigns);
		$allowed_campaigns_sql = preg_replace("/^ /",'',$allowed_campaigns_sql);
		$allowed_campaigns_sql = preg_replace("/ $/",'',$allowed_campaigns_sql);
		$allowed_campaigns_sql = preg_replace("/ /","','",$allowed_campaigns_sql);
		$allowed_campaigns_sql = "'$allowed_campaigns_sql'";
		}

	# figure out which lists they are allowed to see
	$lists_stmt = "SELECT list_id, list_name FROM vicidial_lists WHERE campaign_id IN ($allowed_campaigns_sql) and active = 'N' ORDER BY list_id";
	if ($DB) { echo "|$lists_stmt|\n"; }
	$lists_rslt = mysql_to_mysqli($lists_stmt, $link);
	$num_rows = mysqli_num_rows($lists_rslt);
	$i = 0;
	$allowed_lists_count = 0;
	while ( $i < $num_rows )
		{
		$lists_row = mysqli_fetch_row($lists_rslt);

		# check how many leads are in the list
		$lead_count_stmt = "SELECT count(1)  FROM vicidial_list WHERE list_id = '$lists_row[0]'";
		if ($DB) { echo "|$lead_count_stmt|\n"; }
		$lead_count_rslt = mysql_to_mysqli($lead_count_stmt, $link);
		$lead_count_row = mysqli_fetch_row($lead_count_rslt);
		$lead_count = $lead_count_row[0];

		# only show lists that are under the list_lead_limit
		if ( $lead_count <= $list_lead_limit )
			{
			$list_ary[$allowed_lists_count] = $lists_row[0];
			$list_name_ary[$allowed_lists_count] = $lists_row[1];
			$list_lead_count_ary[$allowed_lists_count] = $lead_count_row[0];

			if ($allowed_lists_count == 0)
				{
				$allowed_lists_sql = "'$lists_row[0]'";
				}
			else
				{
				$allowed_lists_sql = "$allowed_lists_sql, '$lists_row[0]'";
				}

			$allowed_lists_count++;
			}
		$i++;
		}

	# figure out which statuses are in the lists they are allowed to look at
	$status_stmt = "SELECT DISTINCT status FROM vicidial_list WHERE list_id IN ( $allowed_lists_sql ) ORDER BY status";
	if ($DB) { echo "|$status_stmt|\n"; }
	$status_rslt = mysql_to_mysqli($status_stmt, $link);
	$status_count=mysqli_num_rows($status_rslt);
	$i = 0;
	while ( $i < $status_count )
		{
		$status_row = mysqli_fetch_row($status_rslt);
		$statuses[$i] = $status_row[0];
		$i++;
		}

	# figure out which statuses are in the lists they are allowed to look at
	$sys_status_stmt = "SELECT status FROM vicidial_statuses ORDER BY status";
	if ($DB) { echo "|$sys_status_stmt|\n"; }
	$sys_status_rslt = mysql_to_mysqli($sys_status_stmt, $link);
	$sys_status_count=mysqli_num_rows($sys_status_rslt);
	$i = 0;
	while ( $i < $sys_status_count )
		{
		$sys_status_row = mysqli_fetch_row($sys_status_rslt);
		$sys_statuses[$i] = $sys_status_row[0];
		$i++;
		}


	echo "<p>The following are advanced lead management tools.  They will only work on listas inactivas with less than $list_lead_limit leads in them. This is to avoid data inconsistencies.</p>";
	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<center><table width=$section_width cellspacing=3>\n";

	# BEGIN lead move
	echo "<tr bgcolor=#015B91><td colspan=2 align=center><font color=white><b>Move Leads</b></font></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>From List</td><td align=left>\n";
	echo "<select size=1 name=move_from_list>\n";
	echo "<option value='-'>Select A List</option>\n";

	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] leads)</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>To List</td><td align=left>\n";
	echo "<select size=1 name=move_to_list>\n";
	echo "<option value='-'>Select A List</option>\n";

	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] leads)</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Status</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_status' id='enable_move_status' value='enabled'>\n";
	echo "<select size=1 name='move_status' id='move_status' disabled=true>\n";
	echo "<option value='-'>Select A Status</option>\n";
	echo "<option value='%'>All Estados</option>\n";

	$i = 0;
	while ( $i < $status_count )
		{
		echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>País    Code</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_country_code' id='enable_move_country_code' value='enabled'>\n";
	echo "<input type='text' name='move_country_code' id='move_country_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Vendor Lead Code</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_vendor_lead_code' id='enable_move_vendor_lead_code' value='enabled'>\n";
	echo "<input type='text' name='move_vendor_lead_code' id='move_vendor_lead_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Source ID</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_source_id' id='enable_move_source_id' value='enabled'>\n";
	echo "<input type='text' name='move_source_id' id='move_source_id' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Owner</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_owner' id='enable_move_owner' value='enabled'>\n";
	echo "<input type='text' name='move_owner' id='move_owner' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Entry Date</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_entry_date' id='enable_move_entry_date' value='enabled'>\n";
	echo "<input type='text' name='move_entry_date' id='move_entry_date' value='' disabled=true> (YYYY-MM-DD)\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>ModificarDate</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_modify_date' id='enable_move_modify_date' value='enabled'>\n";
	echo "<input type='text' name='move_modify_date' id='move_modify_date' value='' disabled=true> (YYYY-MM-DD)\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Frase de Seguridad</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_security_phrase' id='enable_move_security_phrase' value='enabled'>\n";
	echo "<input type='text' name='move_security_phrase' id='move_security_phrase' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Called Count</td><td align=left>\n";
	echo "<input type='checkbox' name='enable_move_count' id='enable_move_count' value='enabled'>\n";
	echo "<select size=1 name=move_count_op id='move_count_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='='>=</option>\n";
	echo "</select>\n";
	echo "<select size=1 name=move_count_num id='move_count_num' disabled=true>\n";
	$i=0;
	while ( $i <= $max_count )
		{
		echo "<option value='$i'>$i</option>\n";
		$i++;
		}
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td colspan=2 align=center><input type=submit name=move_submit value=move></td></tr>\n";
	echo "</table></center>\n";
	# END lead move

	# BEGIN Status Update
	echo "<br /><center><table width=$section_width cellspacing=3>\n";
	echo "<tr bgcolor=#015B91><td colspan=2 align=center><font color=white><b>Update Lead Estados</b></font></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>List</td><td align=left>\n";
	echo "<select size=1 name=update_list>\n";
	echo "<option value='-'>Select A List</option>\n";

	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] leads)</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>To Status</td><td align=left>\n";
	echo "<select size=1 name=update_to_status>\n";
	echo "<option value='-'>Select A Status</option>\n";

	$i = 0;
	while ( $i < $sys_status_count )
		{
		echo "<option value='$sys_statuses[$i]'>$sys_statuses[$i]</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>From Status</td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_from_status' id='enable_update_from_status' value='enabled'>\n";
	echo "<select size=1 name=update_from_status id='update_from_status' disabled=true>\n";
	echo "<option value='-'>Select A Status</option>\n";

	$i = 0;
	while ( $i < $status_count )
		{
		echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
		$i++;
	}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>País    Code</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_country_code' id='enable_update_country_code' value='enabled'>\n";
	echo "<input type='text' name='update_country_code' id='update_country_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Vendor Lead Code</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_vendor_lead_code' id='enable_update_vendor_lead_code' value='enabled'>\n";
	echo "<input type='text' name='update_vendor_lead_code' id='update_vendor_lead_code' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Source ID</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_source_id' id='enable_update_source_id' value='enabled'>\n";
	echo "<input type='text' name='update_source_id' id='update_source_id' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Owner</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_owner' id='enable_update_owner' value='enabled'>\n";
	echo "<input type='text' name='update_owner' id='update_owner' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Entry Date</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_entry_date' id='enable_update_entry_date' value='enabled'>\n";
	echo "<input type='text' name='update_entry_date' id='update_entry_date' value='' disabled=true> (YYYY-MM-DD)\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>ModificarDate</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_modify_date' id='enable_update_modify_date' value='enabled'>\n";
	echo "<input type='text' name='update_modify_date' id='update_modify_date' value='' disabled=true> (YYYY-MM-DD)\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Frase de Seguridad</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_security_phrase' id='enable_update_security_phrase' value='enabled'>\n";
	echo "<input type='text' name='update_security_phrase' id='update_security_phrase' value='' disabled=true>\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Called Count</td><td align=left>\n";
	echo "<input type='checkbox' name='enable_update_count' id='enable_update_count' value='enabled'>\n";
	echo "<select size=1 name='update_count_op' id='update_count_op' disabled=true>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='='>=</option>\n";
	echo "</select>\n";
	echo "<select size=1 name='update_count_num' id='update_count_num' disabled=true>\n";
	$i=0;
	while ( $i <= $max_count )
		{
		echo "<option value='$i'>$i</option>\n";
		$i++;
		}
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td colspan=2 align=center><input type=submit name=update_submit value=update></td></tr>\n";
	# END Status Update

	if ( $delete_lists > 0 )
		{
		# BEGIN BorrarLeads
		echo "</table></center>\n";
		echo "<br /><center><table width=$section_width cellspacing=3>\n";
		echo "<tr bgcolor=#015B91><td colspan=2 align=center><font color=white><b>BorrarLeads</b></font></td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>List</td><td align=left>\n";
		echo "<select size=1 name=delete_list>\n";
		echo "<option value='-'>Select A List</option>\n";

		$i = 0;
		while ( $i < $allowed_lists_count )
			{
			echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] leads)</option>\n";
			$i++;
			}

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>Status</td><td align=left>\n";
		echo "<select size=1 name=delete_status>\n";
		echo "<option value='-'>Select A Status</option>\n";

		$i = 0;
		while ( $i < $status_count )
			{
			echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
			$i++;
			}

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>País    Code</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_country_code' id='enable_delete_country_code' value='enabled'>\n";
		echo "<input type='text' name='delete_country_code' id='delete_country_code' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>Vendor Lead Code</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_vendor_lead_code' id='enable_delete_vendor_lead_code' value='enabled'>\n";
		echo "<input type='text' name='delete_vendor_lead_code' id='delete_vendor_lead_code' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>Source ID</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_source_id' id='enable_delete_source_id' value='enabled'>\n";
		echo "<input type='text' name='delete_source_id' id='delete_source_id' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>Owner</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_owner' id='enable_delete_owner' value='enabled'>\n";
		echo "<input type='text' name='delete_owner' id='delete_owner' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>Entry Date</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_entry_date' id='enable_delete_entry_date' value='enabled'>\n";
		echo "<input type='text' name='delete_entry_date' id='delete_entry_date' value='' disabled=true> (YYYY-MM-DD)\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>ModificarDate</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_modify_date' id='enable_delete_modify_date' value='enabled'>\n";
		echo "<input type='text' name='delete_modify_date' id='delete_modify_date' value='' disabled=true> (YYYY-MM-DD)\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>Frase de Seguridad</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_security_phrase' id='enable_delete_security_phrase' value='enabled'>\n";
		echo "<input type='text' name='delete_security_phrase' id='delete_security_phrase' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>Lead ID</td></td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_lead_id' id='enable_delete_lead_id' value='enabled'>\n";
		echo "<input type='text' name='delete_lead_id' id='delete_lead_id' value='' disabled=true>\n";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>Called Count</td><td align=left>\n";
		echo "<input type='checkbox' name='enable_delete_count' id='enable_delete_count' value='enabled'>\n";
		echo "<select size=1 name='delete_count_op' id='delete_count_op' disabled=true>\n";
		echo "<option value='<'><</option>\n";
		echo "<option value='<='><=</option>\n";
		echo "<option value='>'>></option>\n";
		echo "<option value='>='>>=</option>\n";
		echo "<option value='='>=</option>\n";
		echo "</select>\n";
		echo "<input type=hidden name=DB value='$DB'>\n";
		echo "<select size=1 name='delete_count_num' id='delete_count_num' disabled=true>\n";
		$i=0;
		while ( $i <= $max_count )
			{
			echo "<option value='$i'>$i</option>\n";
			$i++;
			}
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td colspan=2 align=center><input type=submit name=delete_submit value=delete></td></tr>\n";
		# END BorrarLeads
		}

	# BEGIN Callback Convert
	echo "</table></center>\n";
	echo "<br /><center><table width=$section_width cellspacing=3>\n";
	echo "<tr bgcolor=#015B91><td colspan=2 align=center><font color=white><b>Switch Callbacks</b></font></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>List</td><td align=left>\n";
	echo "<select size=1 name=callback_list>\n";
	echo "<option value='-'>Select A List</option>\n";

	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] leads)</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Entry Date</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_callback_entry_date' id='enable_callback_entry_date' value='enabled'>\n";
	echo "<input type='text' name='callback_entry_start_date' id='callback_entry_start_date' value='' disabled=true> to ";
	echo "<input type='text' name='callback_entry_end_date' id='callback_entry_end_date' value='' disabled=true> (YYYY-MM-DD)\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=right>Callback Date</td></td><td align=left>\n";
	echo "<input type='checkbox' name='enable_callback_callback_date' id='enable_callback_callback_date' value='enabled'>\n";
	echo "<input type='text' name='callback_callback_start_date' id='callback_callback_start_date' value='' disabled=true> to ";
	echo "<input type='text' name='callback_callback_end_date' id='callback_callback_end_date' value='' disabled=true> (YYYY-MM-DD)\n";
	echo "</td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td colspan=2 align=center><input type=submit name=callback_submit value='switch callbacks'></td></tr>\n";
	# END Callback Convert


	echo "</table></center>\n";
	echo "</form>\n";
	echo "</body></html>\n";
	}

echo "</td></tr></table>\n";

function blank_field($field_name, $allow_blank)
	{
	echo "<p>$field_name cannot be blank. ";
	if ($allow_blank)
		{
		echo "If you wish to search for an empty field use ---BLANK--- instead.</p>";
		}
	echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
	exit();
	}

function missing_required_field($field_name)
	{
	echo "<p>The field '$field_name' must have a value.</p>";
	echo "<p><a href='$PHP_SELF'>Click here to start over.</a></p>\n";
	exit();
	}

?>