<?php
# admin_languages.php
# 
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen manages the optional language tables in ViciDial
#
# changes:
# 141203-2126 - First Build
# 141205-0641 - Added option to allow for more matches in the help.php file
# 141212-0923 - Added active setting
# 141227-1041 - Added flags next to country code and active column on list
#

$admin_version = '2.10-4';
$build = '141227-1041';

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["ADD"]))						{$ADD=$_GET["ADD"];}
	elseif (isset($_POST["ADD"]))				{$ADD=$_POST["ADD"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["stage"]))						{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))				{$stage=$_POST["stage"];}
if (isset($_GET["user_group"]))					{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))		{$user_group=$_POST["user_group"];}
if (isset($_GET["action"]))						{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))			{$action=$_POST["action"];}
if (isset($_GET["session_name"]))				{$session_name=$_GET["session_name"];}
	elseif (isset($_POST["session_name"]))		{$session_name=$_POST["session_name"];}
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["pass"]))						{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))				{$pass=$_POST["pass"];}

if (isset($_GET["language_id"]))				{$language_id=$_GET["language_id"];}
	elseif (isset($_POST["language_id"]))		{$language_id=$_POST["language_id"];}
if (isset($_GET["language_code"]))				{$language_code=$_GET["language_code"];}
	elseif (isset($_POST["language_code"]))		{$language_code=$_POST["language_code"];}
if (isset($_GET["language_description"]))			{$language_description=$_GET["language_description"];}
	elseif (isset($_POST["language_description"]))	{$language_description=$_POST["language_description"];}
if (isset($_GET["source_language_id"]))				{$source_language_id=$_GET["source_language_id"];}
	elseif (isset($_POST["source_language_id"]))	{$source_language_id=$_POST["source_language_id"];}
if (isset($_GET["english_text"]))				{$english_text=$_GET["english_text"];}
	elseif (isset($_POST["english_text"]))		{$english_text=$_POST["english_text"];}
if (isset($_GET["translated_text"]))			{$translated_text=$_GET["translated_text"];}
	elseif (isset($_POST["translated_text"]))	{$translated_text=$_POST["translated_text"];}
if (isset($_GET["phrase_id"]))					{$phrase_id=$_GET["phrase_id"];}
	elseif (isset($_POST["phrase_id"]))			{$phrase_id=$_POST["phrase_id"];}
if (isset($_GET["import_data"]))				{$import_data=$_GET["import_data"];}
	elseif (isset($_POST["import_data"]))		{$import_data=$_POST["import_data"];}
if (isset($_GET["active"]))						{$active=$_GET["active"];}
	elseif (isset($_POST["active"]))			{$active=$_POST["active"];}

if (isset($_GET["copy_option"]))				{$copy_option=$_GET["copy_option"];}
	elseif (isset($_POST["copy_option"]))		{$copy_option=$_POST["copy_option"];}
if (isset($_GET["ConFiRm"]))					{$ConFiRm=$_GET["ConFiRm"];}
	elseif (isset($_POST["ConFiRm"]))			{$ConFiRm=$_POST["ConFiRm"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}

if ($ADD=='AJAXtranslateSUBMIT')
	{
	$PHP_AUTH_USER=$user;
	$PHP_AUTH_PW=$pass;
	}
else
	{
	$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
	$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
	}
$PHP_SELF=$_SERVER['PHP_SELF'];

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,auto_dial_limit,user_territories_active,allow_custom_dialplan,callcard_enabled,admin_modify_refresh,nocache_admin,webroot_writable,allow_emails,active_modules,sounds_central_control_active,qc_features_active,contacts_enabled,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSauto_dial_limit =			$row[1];
	$SSuser_territories_active =	$row[2];
	$SSallow_custom_dialplan =		$row[3];
	$SScallcard_enabled =			$row[4];
	$SSadmin_modify_refresh =		$row[5];
	$SSnocache_admin =				$row[6];
	$SSwebroot_writable =			$row[7];
	$SSemail_enabled =				$row[8];
	$SSactive_modules =				$row[9];
	$sounds_central_control_active =$row[10];
	$SSqc_features_active =			$row[11];
	$SScontacts_enabled =			$row[12];
	$SSenable_languages =			$row[13];
	$SSlanguage_method =			$row[14];
	}
##### END SETTINGS LOOKUP #####
###########################################

if (strlen($DB) < 1)
	{$DB=0;}
$modify_refresh_set=0;
$import_with_missing_periods=1;
$gif='.gif';

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/','',$user);
	$pass = preg_replace('/[^-_0-9a-zA-Z]/','',$pass);

	$DB = preg_replace('/[^0-9]/','',$DB);
	$rank = preg_replace('/[^0-9]/','',$rank);
	$level = preg_replace('/[^0-9]/','',$level);
	$parent_rank = preg_replace('/[^0-9]/','',$parent_rank);
	$phrase_id = preg_replace('/[^0-9]/','',$phrase_id);

	$active = preg_replace('/[^NY]/','',$active);

	$ConFiRm = preg_replace('/[^0-9a-zA-Z]/','',$ConFiRm);

	$language_id = preg_replace('/[^-_0-9a-zA-Z]/','',$language_id);
	$source_language_id = preg_replace('/[^-_0-9a-zA-Z]/','',$source_language_id);
	$language_code = preg_replace('/[^-_0-9a-zA-Z]/','',$language_code);
	$language_description = preg_replace('/[^- _0-9a-zA-Z]/','',$language_description);
	$english_text = preg_replace("/\\\\|;|\n|\r/","",$english_text);
	$translated_text = preg_replace("/\\\\|;|\n|\r/","",$translated_text);
	$import_data = preg_replace("/\\\\|;|\r/","",$import_data);

	$copy_option = preg_replace('/[^_0-9a-zA-Z]/','',$copy_option);
	$stage = preg_replace('/[^-_0-9a-zA-Z]/', '',$stage);
	$user_group = preg_replace('/[^-_0-9a-zA-Z]/', '',$user_group);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$user = preg_replace("/'|\"|\\\\|;/","",$user);
	$pass = preg_replace("/'|\"|\\\\|;/","",$pass);
	$language_id = preg_replace("/'|\"|\\\\|;/","",$language_id);
	$english_text = preg_replace("/\\\\|;|\n|\r/","",$english_text);
	$translated_text = preg_replace("/\\\\|;|\n|\r/","",$translated_text);
	$import_data = preg_replace("/\\\\|;|\r/","",$import_data);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_TIME = date("Ymd-His");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
if (strlen($user)<2)
	{$user = $PHP_AUTH_USER;}
$US='_';
$DS='-----';

if (file_exists('options.php'))
	{require('options.php');}

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth < 1)
	{
	$VDdisplayMESSAGE = _QXZ("Login incorrect, please try again");
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Too many login attempts, try again in 15 minutes");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}

$rights_stmt = "SELECT modify_languages from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$modify_languages =		$rights_row[0];

# check their permissions
if ( ($modify_languages < 1) or ($SSenable_languages < 1) )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify languages")."\n";
	exit;
	}

$stmt="SELECT full_name,modify_scripts,user_level,user_group,qc_enabled from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGmodify_scripts =		$row[1];
$LOGuser_level =			$row[2];
$LOGuser_group =			$row[3];
$qc_auth =					$row[4];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];



################################################################################
##### BEGIN update translation from AJAX request processing #####
if ($ADD=='AJAXtranslateSUBMIT')
	{
	if ( (strlen($session_name)<2) or (strlen($stage)<3) )
		{
		echo _QXZ("ERROR: incomplete input")."\n";
		}
	else
		{
		$phrase_ary = explode('-----',$stage);
		$language_id =	$phrase_ary[1];
		$phrase_id =	$phrase_ary[2];
		$translated_text = addslashes($translated_text);

		$stmt="SELECT count(*) from vicidial_languages where language_id='$language_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] < 1)
			{echo _QXZ("LANGUAGE ENTRY DOES NOT EXIST").": $language_id";}
		else
			{
			$stmt="SELECT count(*) from vicidial_language_phrases where language_id='$language_id' and phrase_id='$phrase_id';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] < 1)
				{echo _QXZ("LANGUAGE PHRASE ENTRY DOES NOT EXIST").": $language_id $phrase_id";}
			else
				{
				$stmt="UPDATE vicidial_language_phrases set translated_text='$translated_text',source='$user' where language_id='$language_id' and phrase_id='$phrase_id';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);

				$stmtA="UPDATE vicidial_languages SET modify_date=NOW() where language_id='$language_id';";
				if ($DB) {echo "$stmtA\n";}
				$rslt=mysql_to_mysqli($stmtA, $link);

				echo _QXZ("LANGUAGE PHRASE ENTRY MODIFIED").": $language_id $phrase_id\n";

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$stmt|$stmtA|";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LANGUAGES', event_type='MODIFY', record_id='$language_id', event_code='ADMIN MODIFY LANGUAGE PHRASE', event_sql=\"$SQL_log\", event_notes='';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			}
		}
	exit;
	}
##### END update translation from AJAX request #####
################################################################################

######################
# ADD=263411111111 export language phrases
######################

if ($ADD==263411111111)
	{
	if ($modify_languages==1)
		{
		if ($stage == 'ONLY_UNTRANSLATED')
			{
			$TXTfilename = "LANGUAGE_UNTRANSLATED_$language_id$US$FILE_TIME.txt";
			$stmt="SELECT english_text,translated_text from vicidial_language_phrases where language_id='$language_id' and ( (translated_text='') or (translated_text is NULL) );";
			}
		elseif ($stage == 'ONLY_TRANSLATED')
			{
			$TXTfilename = "LANGUAGE_TRANSLATED_$language_id$US$FILE_TIME.txt";
			$stmt="SELECT english_text,translated_text from vicidial_language_phrases where language_id='$language_id' and ( (translated_text!='') and (translated_text is not NULL) );";
			}
		else
			{
			$TXTfilename = "LANGUAGE_ALL_$language_id$US$FILE_TIME.txt";
			$stmt="SELECT english_text,translated_text from vicidial_language_phrases where language_id='$language_id';";
			}

		// We'll be outputting a TXT file
		header('Content-type: application/octet-stream');

		// It will be called LANGUAGE_101_20141203-121212.txt
		header("Content-Disposition: attachment; filename=\"$TXTfilename\"");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		ob_clean();
		flush();

		$rslt=mysql_to_mysqli($stmt, $link);
		$phrases_to_print = mysqli_num_rows($rslt);
		$o=0;
		while ($phrases_to_print > $o) 
			{
			$row=mysqli_fetch_row($rslt);
			$english_text =				$row[0];
			$translated_text =			$row[1];

			if ($stage == 'ONLY_UNTRANSLATED')
				{echo "$english_text\n";}
			else
				{echo "$english_text|$translated_text||\n";}
			$o++;
			}

		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|$stmtA|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LANGUAGES', event_type='EXPORT', record_id='$language_id', event_code='ADMIN EXPORT LANGUAGE PHRASES', event_sql=\"$SQL_log\", event_notes='$TXTfilename|$stage|$o records';";
		$rslt=mysql_to_mysqli($stmt, $link);
		}
	exit;
	}
##### END ADD=263411111111 export language phrases #####
################################################################################



?>
<html>
<head>

<?php
if ($action != "HELP")
	{
?>
<link rel="stylesheet" href="calendar.css">

<script language="Javascript">
function open_help(taskspan,taskhelp) 
	{
	document.getElementById("P_" + taskspan).innerHTML = " &nbsp; <a href=\"javascript:close_help('" + taskspan + "','" + taskhelp + "');\">help-</a><BR> &nbsp; ";
	document.getElementById(taskspan).innerHTML = "<B>" + taskhelp + "</B>";
	document.getElementById(taskspan).style.background = "#FFFF99";
	}
function close_help(taskspan,taskhelp) 
	{
	document.getElementById("P_" + taskspan).innerHTML = "";
	document.getElementById(taskspan).innerHTML = " &nbsp; <a href=\"javascript:open_help('" + taskspan + "','" + taskhelp + "');\">help+</a>";
	document.getElementById(taskspan).style.background = "white";
	}
</script>

<?php
	}
if ( ($SSadmin_modify_refresh > 1) and (preg_match("/^3/",$ADD)) )
	{
	$modify_refresh_set=1;
	if (preg_match("/^3/",$ADD)) {$modify_url = "$PHP_SELF?$QUERY_STRING";}
	echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$SSadmin_modify_refresh;URL=$modify_url\">\n";
	}
?>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">

<title><?php echo _QXZ("ADMINISTRATION: Languages"); ?>
<?php 

################################################################################
##### BEGIN help section
if ($action == "HELP")
	{
	?>
	</title>
	</head>
	<body bgcolor=white>
	<center>
	<TABLE WIDTH=98% BGCOLOR=#E6E6E6 cellpadding=2 cellspacing=4><TR><TD ALIGN=LEFT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>


	<BR>
	<A NAME="languages-language_id">
	<BR>
	<B><?php echo _QXZ("Language ID"); ?> -</B> <?php echo _QXZ("The unique ID that the language will be referred to in the system. it should contain no spaces or special characters other thsn underscores or dashes."); ?>

	<BR>
	<A NAME="languages-language_description">
	<BR>
	<B><?php echo _QXZ("Language Description"); ?> -</B> <?php echo _QXZ("Description of the language entry."); ?>

	<BR>
	<A NAME="languages-language_code">
	<BR>
	<B><?php echo _QXZ("Language Code"); ?> -</B> <?php echo _QXZ("The web browser code to be used to identify the native web browser language that this language is intended for. For example, United States English is en-us. This is not a required field. If a two letter code is used and a matching image is found, it will be displayed next to this field."); ?>

	<BR>
	<A NAME="languages-user_group">
	<BR>
	<B><?php echo _QXZ("User Group"); ?> -</B> <?php echo _QXZ("Administrative User Group associated with this language entry."); ?>

	<BR>
	<A NAME="languages-active">
	<BR>
	<B><?php echo _QXZ("Active"); ?> -</B> <?php echo _QXZ("For Agents and Users to use this language, this needs to be set to Y. Default is N."); ?>

	<BR>
	<A NAME="languages-english_text">
	<BR>
	<B><?php echo _QXZ("English Text"); ?> -</B> <?php echo _QXZ("The text as it appears in the web screens in English."); ?>

	<BR>
	<A NAME="languages-populate">
	<BR>
	<B><?php echo _QXZ("Populate Language"); ?> -</B> <?php echo _QXZ("This option will populate the new Language with all of the phrases that were gathered while parsing through the web pages. Setting this to AGENT will populate only the agent screen phrases. Setting this to ADMIN will populate only the administration screen phrases. Setting this to ALL will populate all gathered phrases. Default is Leave Empty."); ?>

	<BR>
	<A NAME="languages-import_action">
	<BR>
	<B><?php echo _QXZ("Import Action"); ?> -</B> <?php echo _QXZ("The Only Add Missing Phrases option will not modify any existing phrase translations, it will only add phrases that are missing. The Only Update Existing Phrases option will not add any new phrases, it will only update the translations of matching phrases. The Add and Update Phrases option will both add missing phrases and update existing phrases. Default is Only Add Missing Phrases."); ?>

	<BR>
	<A NAME="languages-import_data">
	<BR>
	<B><?php echo _QXZ("Import Data"); ?> -</B> <?php echo _QXZ("One phrase per line, English text then a pipe then translated text."); ?>


	<BR>
	<A NAME="languages-import_action">
	<BR>
	<B><?php echo _QXZ("Export Action"); ?> -</B> <?php echo _QXZ("The Only NOT Translated Phrases option will only export phrases that have no translated phrase. The Only Translated Phrases option will only export phrases that have no translated phrase. The All Phrases option will export all phrases for the selected Language. Default is Only NOT Translated Phrases."); ?>


	</TD></TR></TABLE>
	</BODY>
	</HTML>
	<?php
	exit;
	}
### END help section


######################
# ADD=363211111111 modify language phrase record in the system iframe screen contents
######################

if ($ADD==363211111111)
	{
	if ($modify_languages==1)
		{
		echo "</title>\n";
		?>
		<script language="Javascript">
		var active_edit=0;
		function edit_translation(TEXTspan,LINKspan)
			{
			if (active_edit > 0)
				{
				alert('<?php echo _QXZ("You can only edit one phrase translation at a time"); ?>');
				}
			else
				{
				active_edit=1;
				var translated_text_value = document.getElementById(TEXTspan).innerHTML;
				document.getElementById(LINKspan).innerHTML = "<a href=\"#\" onclick=\"submit_translation('" + TEXTspan + "','" + LINKspan + "');return false;\">save</a> | <a href=\"#\" onclick=\"reset_translation('" + TEXTspan + "','" + LINKspan + "');return false;\">reset</a>";
				document.getElementById(TEXTspan).innerHTML = "<TEXTAREA name=\"active_translate\" id=\"active_translate\" ROWS=3 COLS=70>" + translated_text_value + "</TEXTAREA><input type=hidden name=\"original_translate\" id=\"original_translate\" value=\"" + translated_text_value + "\">";
				}
			}
		function reset_translation(TEXTspan,LINKspan)
			{
			active_edit=0;
			var translated_text_value = document.language_form.original_translate.value;
			document.getElementById(LINKspan).innerHTML = "<a href=\"#\" onclick=\"edit_translation('" + TEXTspan + "','" + LINKspan + "');return false;\">edit</a>";
			document.getElementById(TEXTspan).innerHTML = translated_text_value;
			}
		function submit_translation(TEXTspan,LINKspan)
			{
			active_edit=0;
			var translated_text_value = document.language_form.active_translate.value;
			//	alert('<?php echo _QXZ("Submitting translation"); ?>: ' + translated_text_value + "|" + TEXTspan);

			var xmlhttp=false;
			/*@cc_on @*/
			/*@if (@_jscript_version >= 5)
			// JScript gives us Conditional compilation, we can cope with old IE versions.
			// and security blocked creation of the objects.
			 try {
			  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			 } catch (e) {
			  try {
			   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			  } catch (E) {
			   xmlhttp = false;
			  }
			 }
			@end @*/
			if (!xmlhttp && typeof XMLHttpRequest!='undefined')
				{
				xmlhttp = new XMLHttpRequest();
				}
			if (xmlhttp) 
				{ 
				submit_translation_query = "ADD=AJAXtranslateSUBMIT&session_name=J78SWE23YUS29HW&user=<?php echo $PHP_AUTH_USER ?>&pass=<?php echo $PHP_AUTH_PW ?>&stage=" + TEXTspan + "&translated_text=" + translated_text_value;
				xmlhttp.open('POST', 'admin_languages.php'); 
				xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
				xmlhttp.send(submit_translation_query); 
				xmlhttp.onreadystatechange = function() 
					{ 
					if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
						{
						var recheck_incoming = null;
						translate_response = xmlhttp.responseText;
					//	alert(translate_response);

						document.getElementById(LINKspan).innerHTML = "<a href=\"#\" onclick=\"edit_translation('" + TEXTspan + "','" + LINKspan + "');return false;\">edit</a>";
						document.getElementById(TEXTspan).innerHTML = translated_text_value;
						}
					}
				delete xmlhttp;
				}
			}
		</script>
		<?php

		echo "</head>\n";
		echo "<body bgcolor=white>\n";
		echo "<center>\n";
		echo "<form name=language_form id=language_form onsubmit=\"return false;\">\n";
		if ( ($SSadmin_modify_refresh > 1) and ($modify_refresh_set < 1) )
			{
			$modify_url = "$PHP_SELF?ADD=363211111111&language_id=$language_id";
			$modify_footer_refresh=1;
			}

		$displaySQL='';
		if ($action=='NOTTRANSLATED')
			{
			$displaySQL="and ( (translated_text='') or (translated_text is NULL) )";
			echo _QXZ("ONLY NOT TRANSLATED PHRASES")."<BR>\n";
			}
		elseif ($action=='TRANSLATED')
			{
			$displaySQL="and ( (translated_text!='') or (translated_text is not NULL) )";
			echo _QXZ("ONLY TRANSLATED PHRASES")."<BR>\n";
			}
		else
			{
			echo _QXZ("ALL PHRASES")."<BR>\n";
			}

		echo "<TABLE>\n";

		$stmt="SELECT count(*) from vicidial_languages where language_id='$language_id' $LOGadmin_viewable_groupsSQL;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$language_exists =		$row[0];
		if ($language_exists > 0)
			{
			$stmt="SELECT phrase_id,english_text,translated_text,source,modify_date from vicidial_language_phrases where language_id='$language_id' $displaySQL $LOGadmin_viewable_groupsSQL;";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$phrases_to_print = mysqli_num_rows($rslt);
			$o=0;
			while ($phrases_to_print > $o) 
				{
				$o++;
				$row=mysqli_fetch_row($rslt);
				$phrase_id =				$row[0];
				$english_text =				$row[1];
				$translated_text =			$row[2];
				$source =					$row[3];
				$modify_date =				$row[4];

				$ttbgcolor='#B6D3FC';
				if (strlen($translated_text) < 1)
					{$ttbgcolor='#FFFF99';}
				echo "<tr bgcolor=#015B91>";
				echo "<td align=left width=100><font color=white><B>$o</B></td>";
				echo "<td align=left width=100><font color=white>"._QXZ("ID").": $phrase_id</td>";
				echo "<td align=left width=100><font color=white>"._QXZ("user").": $source</td>";
				echo "<td align=left width=300><font color=white>"._QXZ("last modified").": $modify_date</td>";
				echo "<td align=right width=200><a href=\"$PHP_SELF?ADD=463111111111&language_id=$language_id&DB=$DB&action=$action&phrase_id=$phrase_id&stage=PHRASEDELETE\" target=\"_parent\"><font color=white>"._QXZ("delete phrase")."</a></td></tr>\n";
				echo "<tr bgcolor=#9BB9FB><td align=left colspan=5>$english_text</td></tr>\n";
				echo "<tr bgcolor=$ttbgcolor><td align=left colspan=5><span id=\"TEXT-----$language_id$DS$phrase_id\">$translated_text</span>";
				echo " &nbsp; <span id=\"LINK-----$language_id$DS$phrase_id\">";
				echo "<a href=\"#\" onclick=\"edit_translation('TEXT-----$language_id$DS$phrase_id','LINK-----$language_id$DS$phrase_id');return false;\">edit</a></span></td></tr>\n";
				}
			echo "</TABLE></center>\n";
			}
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		}
	echo "</body>\n";
	echo "</html>\n";
	exit;
	}




##### BEGIN Set variables to make header show properly #####
#$ADD =					'100';
$hh =					'admin';
$sh =					'languages';
$LOGast_admin_access =	'1';
$SSoutbound_autodial_active = '1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$admin_color =		'#E6E6E6';
$languages_color =	'#C6C6C6';
$languages_font =	'BLACK';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

if ( (!isset($ADD)) or (strlen($ADD)<1) )   {$ADD="163000000000";}

if ($ADD==163111111111)	{$hh='admin';	$sh='languages';	echo _QXZ("ADD LANGUAGE ENTRY");}
if ($ADD==163211111111)	{$hh='admin';	$sh='languages';	echo _QXZ("COPY LANGUAGE ENTRY");}
if ($ADD==163311111111)	{$hh='admin';	$sh='languages';	echo _QXZ("IMPORT LANGUAGE PHRASES");}
if ($ADD==163411111111)	{$hh='admin';	$sh='languages';	echo _QXZ("EXPORT LANGUAGE PHRASES");}
if ($ADD==263111111111)	{$hh='admin';	$sh='languages';	echo _QXZ("ADDING NEW LANGUAGE ENTRY");}
if ($ADD==263211111111)	{$hh='admin';	$sh='languages';	echo _QXZ("COPYING NEW LANGUAGE ENTRY");}
if ($ADD==263311111111)	{$hh='admin';	$sh='languages';	echo _QXZ("IMPORT LANGUAGE PHRASES");}
if ($ADD==363111111111)	{$hh='admin';	$sh='languages';	echo _QXZ("MODIFY LANGUAGE ENTRY");}
if ($ADD==363211111111)	{$hh='admin';	$sh='languages';	echo _QXZ("MODIFY LANGUAGE PHRASE ENTRY");}
if ($ADD==463111111111)	{$hh='admin';	$sh='languages';	echo _QXZ("MODIFY LANGUAGE ENTRY");}
if ($ADD==563111111111)	{$hh='admin';	$sh='languages';	echo _QXZ("DELETE LANGUAGE ENTRY");}
if ($ADD==663111111111)	{$hh='admin';	$sh='languages';	echo _QXZ("DELETE LANGUAGE ENTRY");}
if ($ADD==163000000000)	{$hh='admin';	$sh='languages';	echo _QXZ("LANGUAGE LIST");}
if ($ADD==763000000000)	{$hh='admin';	$sh='languages';	echo _QXZ("ADMIN LOG");}


$NWB = " &nbsp; <a href=\"javascript:openNewWindow('$PHP_SELF?action=HELP";
$NWE = "')\"><IMG SRC=\"help.gif\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";



##### user group permissions
$admin_viewable_groupsALL=0;
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}
$regexLOGadmin_viewable_groups = " $LOGadmin_viewable_groups ";

$UUgroups_list='';
if ($admin_viewable_groupsALL > 0)
	{$UUgroups_list .= "<option value=\"---ALL---\">"._QXZ("All Admin User Groups")."</option>\n";}
$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$UUgroups_to_print = mysqli_num_rows($rslt);
$o=0;
while ($UUgroups_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$UUgroups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$o++;
	}



require("admin_header.php");

if ( ($modify_languages < 1) or ($LOGuser_level < 8) )
	{
	echo _QXZ("You are not authorized to view this section")."\n";
	exit;
	}


######################
# ADD=163111111111 display the ADD NEW LANGUAGE ENTRY SCREEN
######################

if ($ADD==163111111111)
	{
	if ($modify_languages==1)
		{
		$stmt="SELECT count(*) from www_phrases;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$gathered_phrases_TOTAL = $row[0];

		$stmt="SELECT count(*) from www_phrases where php_directory LIKE \"%agc%\";";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$gathered_phrases_AGC = $row[0];
		$gathered_phrases_ADMIN = ($gathered_phrases_TOTAL - $gathered_phrases_AGC);

		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		echo "<br>"._QXZ("ADD NEW LANGUAGE ENTRY")."<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=ADD value=263111111111>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<center><TABLE width=$section_width cellspacing=3>\n";

		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Language ID").": </td><td align=left><input type=text name=language_id size=50 maxlength=100>$NWB#languages-language_id$NWE</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right nowrap>"._QXZ("Language Description").": </td><td align=left nowrap><input type=text name=language_description size=70 maxlength=255>$NWB#languages-language_description$NWE</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Language Code").": </td><td align=left><input type=text name=language_code size=10 maxlength=20>$NWB#languages-language_code$NWE</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Admin User Group").": </td><td align=left><select size=1 name=user_group>\n";
		echo "$UUgroups_list";
		echo "<option SELECTED value=\"---ALL---\">"._QXZ("All Admin User Groups")."</option>\n";
		echo "</select>$NWB#languages-user_group$NWE</td></tr>\n";

		if ($gathered_phrases_TOTAL > 0)
			{
			echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Populate Language").": </td><td align=left><select size=1 name=stage><option SELECTED value='NONE'>"._QXZ("Leave Empty")."</option><option value='ALL'>"._QXZ("All Gathered Phrases").": $gathered_phrases_TOTAL</option><option value='AGENT'>"._QXZ("Agent Phrases Only").": $gathered_phrases_AGC</option><option value='ADMIN'>"._QXZ("Admin Phrases Only").": $gathered_phrases_ADMIN</option></select>$NWB#languages-populate$NWE</td></tr>\n";
			}
		else
			{
			echo "<tr bgcolor=#B6D3FC><td align=right><input type=hidden name=stage value=\"NONE\"></td></tr>\n";
			}

		echo "<tr bgcolor=#B6D3FC><td align=center colspan=2><input type=submit name=submit VALUE='"._QXZ("SUBMIT")."'></td></tr>\n";
		echo "</TABLE></center>\n";
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}





######################
# ADD=163211111111 display the COPY NEW LANGUAGE ENTRY SCREEN
######################

if ($ADD==163211111111)
	{
	if ($modify_languages==1)
		{
		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		echo "<br>"._QXZ("COPY A LANGUAGE ENTRY")."<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=ADD value=263211111111>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<center><TABLE width=$section_width cellspacing=3>\n";

		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Language ID").": </td><td align=left><input type=text name=language_id size=50 maxlength=100>$NWB#languages-language_id$NWE</td></tr>\n";

		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Source Language ID").": </td><td align=left><select size=1 name=source_language_id>\n";

		$stmt="SELECT language_id,language_description from vicidial_languages $whereLOGadmin_viewable_groupsSQL order by language_id;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$groups_to_print = mysqli_num_rows($rslt);
		$groups_list='';

		$o=0;
		while ($groups_to_print > $o) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$groups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
			$o++;
			}
		echo "$groups_list";
		echo "</select>$NWB#languages-language_id$NWE</td></tr>\n";

		echo "<tr bgcolor=#B6D3FC><td align=center colspan=2><input type=submit name=submit VALUE='"._QXZ("SUBMIT")."'></td></tr>\n";
		echo "</TABLE></center>\n";
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}




######################
# ADD=163311111111 display the IMPORT LANGUAGE PHRASES SCREEN
######################

if ($ADD==163311111111)
	{
	if ($modify_languages==1)
		{
		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		echo "<br>"._QXZ("IMPORT LANGUAGE PHRASES")."<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=ADD value=263311111111>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<center><TABLE width=$section_width cellspacing=3>\n";

		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Language ID").": </td><td align=left><select size=1 name=language_id>\n";

		$stmt="SELECT language_id,language_description from vicidial_languages $whereLOGadmin_viewable_groupsSQL order by language_id;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$groups_to_print = mysqli_num_rows($rslt);
		$groups_list='';

		$o=0;
		while ($groups_to_print > $o) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$groups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
			$o++;
			}
		echo "$groups_list";
		echo "</select>$NWB#languages-language_id$NWE</td></tr>\n";

		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Import Action").": </td><td align=left><select size=1 name=stage><option SELECTED value='ADD'>"._QXZ("Only Add Missing Phrases")."</option><option value='UPDATE'>"._QXZ("Only Update Existing Phrases")."</option><option value='ADD_UPDATE'>"._QXZ("Add and Update Phrases")."</option></select>$NWB#languages-import_action$NWE</td></tr>\n";

		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Import Data").": </td><td align=left><TEXTAREA name=\"import_data\" id=\"import_data\" ROWS=40 COLS=120></TEXTAREA>$NWB#languages-import_data$NWE</td></tr>\n";

		echo "<tr bgcolor=#B6D3FC><td align=center colspan=2><input type=submit name=submit VALUE='"._QXZ("SUBMIT")."'></td></tr>\n";
		echo "</TABLE></center>\n";
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}




######################
# ADD=163411111111 display the EXPORT LANGUAGE PHRASES SCREEN
######################

if ($ADD==163411111111)
	{
	if ($modify_languages==1)
		{
		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		echo "<br>"._QXZ("EXPORT LANGUAGE PHRASES")."<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=ADD value=263411111111>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<center><TABLE width=$section_width cellspacing=3>\n";

		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Language ID").": </td><td align=left><select size=1 name=language_id>\n";

		$stmt="SELECT language_id,language_description from vicidial_languages $whereLOGadmin_viewable_groupsSQL order by language_id;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$groups_to_print = mysqli_num_rows($rslt);
		$groups_list='';

		$o=0;
		while ($groups_to_print > $o) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$groups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
			$o++;
			}
		echo "$groups_list";
		echo "</select>$NWB#languages-language_id$NWE</td></tr>\n";

		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Export Action").": </td><td align=left><select size=1 name=stage><option SELECTED value='ONLY_UNTRANSLATED'>"._QXZ("Only NOT Translated Phrases")."</option><option value='ONLY_TRANSLATED'>"._QXZ("Only Translated Phrases")."</option><option value='ALL_PHRASES'>"._QXZ("All Phrases")."</option></select>$NWB#languages-export_action$NWE</td></tr>\n";

		echo "<tr bgcolor=#B6D3FC><td align=center colspan=2><input type=submit name=submit VALUE='"._QXZ("SUBMIT")."'></td></tr>\n";
		echo "</TABLE></center>\n";
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}





######################
# ADD=263111111111 adds new language entry to the system
######################

if ($ADD==263111111111)
	{
	if ($add_copy_disabled > 0)
		{
		echo "<br>"._QXZ("You do not have permission to add records on this system")." -system_settings-\n";
		}
	else
		{
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
		$stmt="SELECT count(*) from vicidial_languages where language_id='$language_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{echo "<br>"._QXZ("LANGUAGE ENTRY NOT ADDED - there is already a language entry in the system with this ID")."\n";}
		else
			{
			if ( (strlen($language_id) < 2) or (strlen($language_description) < 3) )
				{echo "<br>"._QXZ("LANGUAGE ENTRY NOT ADDED - Please go back and look at the data you entered")."\n";}
			else
				{
				echo "<br>"._QXZ("LANGUAGE ENTRY ADDED")."\n";

				$stmt="INSERT INTO vicidial_languages SET language_id='$language_id',language_description='$language_description',language_code='$language_code',user_group='$user_group';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);

				if (preg_match("/ALL|AGENT|ADMIN/",$stage))
					{
					$www_phraseSQL='';
					if ($stage == 'AGENT')
						{$www_phraseSQL='where php_directory LIKE "%agc%"';}
					if ($stage == 'ADMIN')
						{$www_phraseSQL='where php_directory NOT LIKE "%agc%"';}
					$stmtA="INSERT INTO vicidial_language_phrases (language_id,english_text) SELECT '$language_id',phrase_text from www_phrases $www_phraseSQL;";
					if ($DB) {echo "$stmtA\n";}
					$rslt=mysql_to_mysqli($stmtA, $link);
					$affected_rowsA = mysqli_affected_rows($link);

					echo "<br>"._QXZ("POPULATED WITH GATHERED PHRASES").": $affected_rowsA\n";
					}

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$stmt|$stmtA|";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LANGUAGES', event_type='ADD', record_id='$language_id', event_code='ADMIN ADD LANGUAGE', event_sql=\"$SQL_log\", event_notes='$affected_rows|$affected_rowsA';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			}
		}
	$ADD=363111111111;
	}


######################
# ADD=263211111111 adds copied language entry to the system
######################

if ($ADD==263211111111)
	{
	if ($add_copy_disabled > 0)
		{
		echo "<br>"._QXZ("You do not have permission to add records on this system")." -system_settings-\n";
		}
	else
		{
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
		$stmt="SELECT count(*) from vicidial_languages where language_id='$language_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{echo "<br>"._QXZ("LANGUAGE ENTRY NOT ADDED - there is already a language entry in the system with this ID")."\n";}
		else
			{
			if ( (strlen($language_id) < 2) or (strlen($language_description) < 3) or (strlen($source_language_id) < 2) )
				{echo "<br>"._QXZ("LANGUAGE ENTRY NOT ADDED - Please go back and look at the data you entered")."\n";}
			else
				{
				$stmt="SELECT count(*) from vicidial_languages where language_id='$source_language_id';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] < 1)
					{echo "<br>"._QXZ("LANGUAGE ENTRY NOT COPIED - invalid source language")."\n";}
				else
					{
					echo "<br>"._QXZ("LANGUAGE ENTRY COPIED")."\n";

					$stmt="INSERT INTO vicidial_languages (language_id,language_description,language_code,user_group) SELECT '$language_id',language_description,language_code,user_group from vicidial_languages where language_id='$source_language_id';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$affected_rows = mysqli_affected_rows($link);

					$stmtA="INSERT INTO vicidial_language_phrases (language_id,phrase_id,english_text,translated_text,source) SELECT '$language_id',phrase_id,english_text,translated_text,source from vicidial_language_phrases where language_id='$source_language_id';";
					if ($DB) {echo "$stmtA\n";}
					$rslt=mysql_to_mysqli($stmtA, $link);
					$affected_rowsA = mysqli_affected_rows($link);

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|$stmtA|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LANGUAGES', event_type='COPY', record_id='$language_id', event_code='ADMIN COPY LANGUAGE', event_sql=\"$SQL_log\", event_notes='$source_language_id|$affected_rows|$affected_rowsA';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					}
				}
			}
		}
	$ADD=363111111111;
	}


######################
# ADD=263311111111 processes imported language phrase data in the system
######################

if ($ADD==263311111111)
	{
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
	$stmt="SELECT count(*) from vicidial_languages where language_id='$language_id';";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$stmtLIST = $stmt;
	if ($row[0] < 1)
		{echo "<br>"._QXZ("LANGUAGE ENTRY DOES NOT EXIST")."\n";}
	else
		{
		if ( (strlen($import_data) < 2) or (strlen($stage) < 1) )
			{echo "<br>"._QXZ("LANGUAGE IMPORT NOT PROCESSED - Please go back and look at the data you entered")."\n";}
		else
			{
			$phrase_updates=0;
			$phrase_inserts=0;
			$import_data_lines = explode("\n",$import_data);
			$import_data_lines_ct = count($import_data_lines);
			$i=0;
			while ($i < $import_data_lines_ct)
				{
				$import_data_lines_ary = explode('|',$import_data_lines[$i]);
				$import_data_lines_ary_ct = count($import_data_lines_ary);
				if ($DB > 0) {echo "$i|$import_data_lines_ary_ct|$import_data_lines_ary[0]|$import_data_lines_ary[1]|\n";}

				if ( ($import_data_lines_ary_ct > 0) and (strlen($import_data_lines_ary[0]) > 0) and (!preg_match("/^\#/",$import_data_lines_ary[0])) )
					{
					$import_data_lines_ary[0] = addslashes($import_data_lines_ary[0]);
					$english_textSQL="english_text='$import_data_lines_ary[0]'";
					if ($import_with_missing_periods > 0)
						{$english_textSQL="english_text IN('$import_data_lines_ary[0]','$import_data_lines_ary[0].')";}
					$stmt="SELECT count(*) from vicidial_language_phrases where language_id='$language_id' and $english_textSQL;";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					if ($row[0] < 1)
						{
						if ( ($stage=='ADD') or ($stage=='ADD_UPDATE') )
							{
							$import_data_lines_ary[1] = addslashes($import_data_lines_ary[1]);
							$stmt="INSERT INTO vicidial_language_phrases set english_text='$import_data_lines_ary[0]',translated_text='$import_data_lines_ary[1]', language_id='$language_id',source='$user';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$stmtLIST .= "|$stmt";
							$phrase_inserts++;
							}
						}
					else
						{
						if ( ($stage=='UPDATE') or ($stage=='ADD_UPDATE') )
							{
							$import_data_lines_ary[1] = addslashes($import_data_lines_ary[1]);
							$stmt="SELECT count(*) from vicidial_language_phrases where language_id='$language_id' and $english_textSQL and translated_text='$import_data_lines_ary[1]';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							if ($row[0] < 1)
								{
								$stmt="UPDATE vicidial_language_phrases set translated_text='$import_data_lines_ary[1]',source='$user' where $english_textSQL and language_id='$language_id';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$stmtLIST .= "|$stmt";
								$phrase_updates++;
								}
							else
								{
								if ($DB > 0) {echo "IGNORED(already equal): $i|$import_data_lines_ary_ct|$import_data_lines_ary[0]|$import_data_lines_ary[1]|\n";}
								}
							}
						}
					}
				else
					{
					if ($DB > 0) {echo "IGNORED: $i|$import_data_lines_ary_ct|$import_data_lines_ary[0]|$import_data_lines_ary[1]|\n";}
					}


				$i++;
				}

			echo _QXZ("PHRASES SUBMITTED").": $i<BR>\n";
			echo _QXZ("PHRASES ADDED").": $phrase_inserts<BR>\n";
			echo _QXZ("PHRASES UPDATED").": $phrase_updates<BR>\n";

			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmtLIST|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LANGUAGES', event_type='MODIFY', record_id='$language_id', event_code='ADMIN IMPORT LANGUAGE PHRASES', event_sql=\"$SQL_log\", event_notes='PHRASES SUBMITTED: $i|PHRASES ADDED: $phrase_inserts|PHRASES UPDATED: $phrase_updates|';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		}
	$ADD=363111111111;
	}


######################
# ADD=463111111111 modify language record in the system
######################

if ($ADD==463111111111)
	{
	if ($modify_languages==1)
		{
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		if ($stage == "PHRASEDELETE")
			{
			if ( (strlen($language_id) < 2) or (strlen($phrase_id) < 1) )
				{echo "<br>"._QXZ("LANGUAGE PHRASE NOT MODIFIED - Please go back and look at the data you entered")."$language_id|$phrase_id\n";}
			else
				{
				$stmt="DELETE FROM vicidial_language_phrases where language_id='$language_id' and phrase_id='$phrase_id';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);

				$stmtA="UPDATE vicidial_languages SET modify_date=NOW() where language_id='$language_id';";
				if ($DB) {echo "$stmtA\n";}
				$rslt=mysql_to_mysqli($stmtA, $link);

				echo "<br>"._QXZ("LANGUAGE ENTRY MODIFIED").": $language_id - $audio_filename\n";

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$stmt|$stmtA|";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LANGUAGES', event_type='MODIFY', record_id='$language_id', event_code='ADMIN MODIFY LANGUAGE', event_sql=\"$SQL_log\", event_notes='PHRASE DELETE $phrase_id';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			}
		else
			{
			if ( (strlen($language_id) < 2) or (strlen($language_description) < 3) )
				{echo "<br>"._QXZ("LANGUAGE ENTRY NOT MODIFIED - Please go back and look at the data you entered")."\n";}
			else
				{
				$stmt="UPDATE vicidial_languages set language_description='$language_description',language_code='$language_code',user_group='$user_group',active='$active' where language_id='$language_id';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$stmtLIST = $stmt;

				if (strlen($english_text) > 0)
					{
					$english_text = addslashes($english_text);
					$stmt="SELECT count(*) from vicidial_language_phrases where english_text='$english_text' and language_id='$language_id';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$phrase_exists = $row[0];

					if ($phrase_exists > 0)
						{echo "<br>"._QXZ("Phrase already exists in this language ID").": $language_id, $english_text\n";}
					else
						{
						echo "<br>"._QXZ("New phrase added to this language ID").": $language_id, $english_text\n";
						$stmt="INSERT INTO vicidial_language_phrases set english_text='$english_text', language_id='$language_id',source='$user';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$stmtLIST .= "|$stmt";

						$stmtA="UPDATE vicidial_languages SET modify_date=NOW() where language_id='$language_id';";
						if ($DB) {echo "$stmtA\n";}
						$rslt=mysql_to_mysqli($stmtA, $link);
						}
					}

				echo "<br>"._QXZ("LANGUAGE ENTRY MODIFIED").": $language_id\n";

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$stmtLIST|$stmtA|";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LANGUAGES', event_type='MODIFY', record_id='$language_id', event_code='ADMIN MODIFY LANGUAGE', event_sql=\"$SQL_log\", event_notes='';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			}
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	$ADD=363111111111;	# go to language entry modification form below
	}


######################
# ADD=563111111111 confirmation before deletion of language record
######################

if ($ADD==563111111111)
	{
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	if (strlen($language_id) < 2)
		{
		echo "<br>"._QXZ("LANGUAGE ENTRY NOT DELETED - Please go back and look at the data you entered")."\n";
		echo "<br>"._QXZ("LANGUAGE ID be at least 2 characters in length")."\n";
		}
	else
		{
		echo "<br><B>"._QXZ("LANGUAGE ENTRY DELETION CONFIRMATION").": $language_id - $language_description</B>\n";
		echo "<br><br><a href=\"$PHP_SELF?ADD=663111111111&language_id=$language_id&DB=$DB&ConFiRm=YES\">"._QXZ("Click here to delete language entry")." $language_id - $language_description</a><br><br><br>\n";
		}
	$ADD='363111111111';		# go to language entry modification below
	}



######################
# ADD=663111111111 delete language record
######################

if ($ADD==663111111111)
	{
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	if ( (strlen($language_id) < 2) or ($ConFiRm != 'YES') )
		{
		echo "<br>"._QXZ("LANGUAGE ENTRY NOT DELETED - Please go back and look at the data you entered")."\n";
		echo "<br>"._QXZ("LANGUAGE ID be at least 2 characters in length")."\n";
		}
	else
		{
		$stmt="DELETE FROM vicidial_languages where language_id='$language_id' $LOGadmin_viewable_groupsSQL;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		$stmtA="DELETE from vicidial_language_phrases where language_id='$language_id';";
		if ($DB) {echo "$stmtA\n";}
		$rslt=mysql_to_mysqli($stmtA, $link);

		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|$stmtA|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LANGUAGES', event_type='DELETE', record_id='$language_id', event_code='ADMIN DELETE LANGUAGE', event_sql=\"$SQL_log\", event_notes='';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		echo "<br><B>"._QXZ("LANGUAGE DELETION COMPLETED").": $language_id</B>\n";
		echo "<br><br>\n";
		}
	$ADD='163000000000';		# go to language entry list
	}



######################
# ADD=363111111111 modify language record in the system
######################

if ($ADD==363111111111)
	{
	if ($modify_languages==1)
		{
		if ( ($SSadmin_modify_refresh > 1) and ($modify_refresh_set < 1) )
			{
			$modify_url = "$PHP_SELF?ADD=363111111111&language_id=$language_id";
			$modify_footer_refresh=1;
			}
		echo "<TABLE WIDTH=850><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		$stmt="SELECT language_id,language_description,language_code,user_group,modify_date,active from vicidial_languages where language_id='$language_id' $LOGadmin_viewable_groupsSQL;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$language_id =				$row[0];
		$language_description =		$row[1];
		$language_code =			$row[2];
		$user_group =				$row[3];
		$modify_date =				$row[4];
		$active =					$row[5];

		echo "<br>"._QXZ("MODIFY A LANGUAGE RECORD").": $language_id<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=ADD value=463111111111>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<input type=hidden name=action value=\"$action\">\n";
		echo "<input type=hidden name=language_id value=\"$language_id\">\n";

		echo "<center><TABLE width=$section_width cellspacing=3>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Language ID").": </td><td align=left><B>$language_id</B></td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Last Modified").": </td><td align=left>$modify_date</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right nowrap>"._QXZ("Language Description").": </td><td align=left nowrap><input type=text name=language_description size=70 maxlength=255 value=\"$language_description\">$NWB#languages-language_description$NWE</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Language Code").": </td><td align=left><input type=text name=language_code size=10 maxlength=20 value=\"$language_code\">$NWB#languages-language_code$NWE &nbsp; ";
		if (file_exists("../agc/images/$language_code$gif"))
			{echo "<img src=\"../agc/images/$language_code$gif\" width=30 height=20 alt='"._QXZ("flag")."' border=0>";}
		else
			{echo "<img src=\"../agc/images/xx.gif\" width=30 height=20 alt='"._QXZ("unknown code")."' border=0>";}
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Admin User Group").": </td><td align=left><select size=1 name=user_group>\n";
		echo "$UUgroups_list";
		echo "<option SELECTED value=\"$user_group\">$user_group</option>\n";
		echo "</select>$NWB#languages-user_group$NWE</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("Active").": </td><td align=left><select size=1 name=active>\n";
		echo "<option value=\"Y\">"._QXZ("Y")."</option><option value=\"N\">"._QXZ("N")."</option>";
		echo "<option SELECTED value=\"$active\">"._QXZ("$active")."</option>\n";
		echo "</select>$NWB#languages-active$NWE</td></tr>\n";

		$stmt="SELECT count(*) from vicidial_language_phrases where language_id='$language_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$phrase_count=$row[0];

		$stmt="SELECT count(*) from vicidial_language_phrases where language_id='$language_id' and ( (translated_text='') or (translated_text is NULL) );";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$null_phrase_count=$row[0];

		echo "<tr bgcolor=#B6D3FC><td align=center colspan=2>";
		echo _QXZ("Phrases").": $phrase_count &nbsp; "._QXZ("Not Translated").": $null_phrase_count";
		echo "<font size=2>";
		echo " &nbsp; <a href=\"$PHP_SELF?ADD=363111111111&language_id=$language_id&DB=$DB&action=DISPLAYALL\">"._QXZ("display all")."</a> &nbsp; |";
		echo " &nbsp; <a href=\"$PHP_SELF?ADD=363111111111&language_id=$language_id&DB=$DB&action=NOTTRANSLATED\">"._QXZ("not translated only")."</a> &nbsp; |";
		echo " &nbsp; <a href=\"$PHP_SELF?ADD=363111111111&language_id=$language_id&DB=$DB&action=TRANSLATED\">"._QXZ("translated only")."</a> &nbsp;";
		echo "</font>";
		echo "</td></tr>\n";
		echo "<tr bgcolor=#B6D3FC><td align=left colspan=2>\n";

		echo "<iframe src=\"/vicidial/admin_languages.php?ADD=363211111111&language_id=$language_id&DB=$DB&action=$action\" name=\"language_phrases\" style=\"background-color:transparent;\" scrolling=\"auto\" frameborder=\"0\" allowtransparency=\"true\" width=\"100%\" height=\"400\">\n</iframe>\n";


		echo "</td></tr>\n";

		echo "<tr bgcolor=#B9CBFD><td align=right nowrap>"._QXZ("Add A New Language Phrase").": </td><td nowrap><input type=text size=70 maxlength=10000 name=english_text id=english_text value=\"\">  $NWB#languages-english_text$NWE</td></tr>\n";

		echo "<tr bgcolor=#B6D3FC><td align=center valign=middle colspan=2><input type=submit name=submit VALUE='"._QXZ("SUBMIT CHANGES")."'> &nbsp; <a href=\"$PHP_SELF?ADD=363111111111&language_id=$language_id&DB=$DB&action=$action\">RELOAD PAGE</a></td></tr>\n";
		echo "</TABLE></center>\n";

		echo "<center><br><br><b>\n";


		if ($modify_languages > 0)
			{
			echo "<br><br><a href=\"$PHP_SELF?ADD=563111111111&language_id=$language_id&DB=$DB&language_description=$language_description\">"._QXZ("DELETE LANGUAGE ENTRY")."</a>\n";
			}
		if ( ($LOGuser_level >= 9) and ( (preg_match("/Administration Change Log/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) ) )
			{
			echo "<br><br><a href=\"admin.php?ADD=720000000000000&category=LANGUAGES&stage=$language_id\">"._QXZ("Click here to see Admin changes to this Language entry")."</a>\n";
			}
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}



######################
# ADD=163000000000 display all language entries
######################
if ($ADD==163000000000)
	{
	echo "<TABLE><TR><TD>\n";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	$stmt="SELECT language_id,language_description,language_code,user_group,modify_date,active from vicidial_languages $whereLOGadmin_viewable_groupsSQL order by language_id;";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$lang_to_print = mysqli_num_rows($rslt);

	echo "<br>"._QXZ("LANGUAGE LISTINGS").":\n";
	echo "<center><TABLE width=$section_width cellspacing=0 cellpadding=1>\n";
	echo "<tr bgcolor=black>";
	echo "<td><font size=1 color=white align=left><B>"._QXZ("LANGUAGE ID")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("Description")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("Code")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("ACT")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("ADMIN GROUP")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("MODIFY DATE")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("PHRASES")."</B></td>";
	echo "<td align=center><font size=1 color=white><B>"._QXZ("MODIFY")."</B></td></tr>\n";

	$o=0;
	while ($lang_to_print > $o) 
		{
		$row=mysqli_fetch_row($rslt);
		while(strlen($row[1]) > 40) {$row[1] = substr("$row[1]", 0, -1);}
		if(strlen($row[1]) > 37) {$row[1] = "$row[1]...";}
		$Alanguage_id[$o] =				$row[0];
		$Alanguage_description[$o] =	$row[1];
		$Alanguage_code[$o] =			$row[2];
		$Auser_group[$o] =				$row[3];
		$Amodify_date[$o] =				$row[4];
		$Aactive[$o] =					$row[5];
		$o++;
		}

	$o=0;
	while ($lang_to_print > $o) 
		{
		$stmt="SELECT count(*) from vicidial_language_phrases where language_id='$Alanguage_id[$o]';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$phrase_count=$row[0];

		$stmt="SELECT count(*) from vicidial_language_phrases where language_id='$Alanguage_id[$o]' and ( (translated_text='') or (translated_text is NULL) );";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$null_phrase_count=$row[0];
		$translated_phrases_count = ($phrase_count - $null_phrase_count);

		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='bgcolor="#B9CBFD"';} 
		else
			{$bgcolor='bgcolor="#9BB9FB"';}
		echo "<tr $bgcolor><td><font size=1><a href=\"$PHP_SELF?ADD=363111111111&language_id=$Alanguage_id[$o]&DB=$DB\">$Alanguage_id[$o]</a></td>";
		echo "<td><font size=1>$Alanguage_description[$o]</td>";
		echo "<td><font size=1>";
		if (file_exists("../agc/images/$Alanguage_code[$o]$gif"))
			{echo "<img src=\"../agc/images/$Alanguage_code[$o]$gif\" width=15 height=10 alt='"._QXZ("flag")."' border=0>";}
		else
			{echo "<img src=\"../agc/images/xx.gif\" width=15 height=10 alt='"._QXZ("unknown code")."' border=0>";}
		echo " &nbsp; $Alanguage_code[$o]</td>";
		echo "<td><font size=1>$Aactive[$o]</td>";
		echo "<td><font size=1>$Auser_group[$o]</td>";
		echo "<td><font size=1>$Amodify_date[$o]</td>";
		echo "<td><font size=1>$phrase_count($translated_phrases_count/$null_phrase_count)</td>";
		echo "<td align=center><font size=1><a href=\"$PHP_SELF?ADD=363111111111&language_id=$Alanguage_id[$o]&DB=$DB\">"._QXZ("MODIFY")."</a></td></tr>\n";
		$o++;
		}

	echo "</TABLE></center>\n";
	}




################################################################################
##### BEGIN admin log display
if ($ADD == "763000000000")
	{
	if ($LOGuser_level >= 9)
		{
		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		$stmt="SELECT admin_log_id,event_date,user,ip_address,event_section,event_type,record_id,event_code from vicidial_admin_log where event_section='LANGUAGES' and record_id='$list_id' order by event_date desc limit 10000;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		echo "<br>"._QXZ("ADMIN CHANGE LOG: Section Records")." - $category - $stage\n";
		echo "<center><TABLE width=$section_width cellspacing=0 cellpadding=1>\n";
		echo "<TR BGCOLOR=BLACK>";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("ID")."</B></TD>";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("DATE TIME")."</B></TD>";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("USER")."</B></TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("IP")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("SECTION")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("TYPE")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("RECORD ID")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("DESCRIPTION")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("GOTO")."</TD>\n";
		echo "</TR>\n";

		$logs_printed = '';
		$o=0;
		while ($logs_to_print > $o)
			{
			$row=mysqli_fetch_row($rslt);

			if (preg_match('/USER|AGENT/i', $row[4])) {$record_link = "$PHP_SELF?ADD=3&user=$row[6]";}
			if (preg_match('/CAMPAIGN/i', $row[4])) {$record_link = "$PHP_SELF?ADD=31&campaign_id=$row[6]";}
			if (preg_match('/LIST/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311&list_id=$row[6]";}
			if (preg_match('/CUSTOM_FIELDS/i', $row[4])) {$record_link = "./admin_lists_custom.php?action=MODIFY_CUSTOM_FIELDS&list_id=$row[6]";}
			if (preg_match('/SCRIPT/i', $row[4])) {$record_link = "$PHP_SELF?ADD=3111111&script_id=$row[6]";}
			if (preg_match('/FILTER/i', $row[4])) {$record_link = "$PHP_SELF?ADD=31111111&lead_filter_id=$row[6]";}
			if (preg_match('/INGROUP/i', $row[4])) {$record_link = "$PHP_SELF?ADD=3111&group_id=$row[6]";}
			if (preg_match('/DID/i', $row[4])) {$record_link = "$PHP_SELF?ADD=3311&did_id=$row[6]";}
			if (preg_match('/USERGROUP/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311111&user_group=$row[6]";}
			if (preg_match('/REMOTEAGENT/i', $row[4])) {$record_link = "$PHP_SELF?ADD=31111&remote_agent_id=$row[6]";}
			if (preg_match('/PHONE/i', $row[4])) {$record_link = "$PHP_SELF?ADD=10000000000";}
			if (preg_match('/CALLTIME/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311111111&call_time_id=$row[6]";}
			if (preg_match('/SHIFT/i', $row[4])) {$record_link = "$PHP_SELF?ADD=331111111&shift_id=$row[6]";}
			if (preg_match('/CONFTEMPLATE/i', $row[4])) {$record_link = "$PHP_SELF?ADD=331111111111&template_id=$row[6]";}
			if (preg_match('/CARRIER/i', $row[4])) {$record_link = "$PHP_SELF?ADD=341111111111&carrier_id=$row[6]";}
			if (preg_match('/SERVER/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311111111111&server_id=$row[6]";}
			if (preg_match('/CONFERENCE/i', $row[4])) {$record_link = "$PHP_SELF?ADD=1000000000000";}
			if (preg_match('/SYSTEM/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311111111111111";}
			if (preg_match('/CATEGOR/i', $row[4])) {$record_link = "$PHP_SELF?ADD=331111111111111";}
			if (preg_match('/GROUPALIAS/i', $row[4])) {$record_link = "$PHP_SELF?ADD=33111111111&group_alias_id=$row[6]";}
			if (preg_match('/AVATARS/i', $row[4])) {$record_link = "$PHP_SELF?ADD=362111111111&avatar_id=$row[6]&DB=$DB";}
			if (preg_match('/LANGUAGES/i', $row[4])) {$record_link = "$PHP_SELF?ADD=363111111111&language_id=$row[6]&DB=$DB";}

			if (preg_match('/1$|3$|5$|7$|9$/i', $o))
				{$bgcolor='bgcolor="#B9CBFD"';} 
			else
				{$bgcolor='bgcolor="#9BB9FB"';}
			echo "<tr $bgcolor><td><font size=1><a href=\"admin.php?ADD=730000000000000&stage=$row[0]&DB=$DB\">$row[0]</a></td>";
			echo "<td><font size=1> $row[1]</td>";
			echo "<td><font size=1> <a href=\"admin.php?ADD=710000000000000&stage=$row[2]&DB=$DB\">$row[2]</a></td>";
			echo "<td><font size=1> $row[3]</td>";
			echo "<td><font size=1> $row[4]</td>";
			echo "<td><font size=1> $row[5]</td>";
			echo "<td><font size=1> $row[6]</td>";
			echo "<td><font size=1> $row[7]</td>";
			echo "<td><font size=1> <a href=\"$record_link\">"._QXZ("GOTO")."</a></td>";
			echo "</tr>\n";
			$logs_printed .= "'$row[0]',";
			$o++;
			}
		echo "</TABLE><BR><BR>\n";
		echo "\n";
		echo "</center>\n";
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").": $RUNtime "._QXZ("seconds")." &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

?>

</body>
</html>
