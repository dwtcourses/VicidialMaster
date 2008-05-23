ALTER TABLE vicidial_closer_log ADD xfercallid INT(9) UNSIGNED;

ALTER TABLE vicidial_campaign_server_stats ENGINE=HEAP;

ALTER TABLE live_channels ENGINE=HEAP;

ALTER TABLE live_sip_channels ENGINE=HEAP;

ALTER TABLE parked_channels ENGINE=HEAP;

ALTER TABLE server_updater ENGINE=HEAP;

ALTER TABLE web_client_sessions ENGINE=HEAP;


ALTER TABLE vicidial_campaigns MODIFY lead_order VARCHAR(30);

DROP index user on vicidial_users;
ALTER TABLE vicidial_users MODIFY user VARCHAR(20) NOT NULL;
CREATE UNIQUE INDEX user ON vicidial_users (user);
ALTER TABLE vicidial_users MODIFY pass VARCHAR(20) NOT NULL;
ALTER TABLE vicidial_users MODIFY user_level TINYINT(2) NOT NULL default '1';

 CREATE TABLE vicidial_user_closer_log (
user VARCHAR(20),
campaign_id VARCHAR(20),
event_date DATETIME,
blended ENUM('1','0') default '0',
closer_campaigns TEXT,
index (user),
index (event_date)
);

ALTER TABLE vicidial_users ADD qc_enabled ENUM('1','0') default '0';
ALTER TABLE vicidial_users ADD qc_user_level INT(2) default '1';
ALTER TABLE vicidial_users ADD qc_pass ENUM('1','0') default '0';
ALTER TABLE vicidial_users ADD qc_finish ENUM('1','0') default '0';
ALTER TABLE vicidial_users ADD qc_commit ENUM('1','0') default '0';

ALTER TABLE vicidial_user_groups ADD qc_allowed_campaigns TEXT;
ALTER TABLE vicidial_user_groups ADD qc_allowed_inbound_groups TEXT;

ALTER TABLE system_settings ADD db_schema_version INT(8) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1074', version='2.0.5b0.5';

ALTER TABLE live_inbound MODIFY uniqueid VARCHAR(20) NOT NULL;
ALTER TABLE live_inbound_log MODIFY uniqueid VARCHAR(20) NOT NULL;
ALTER TABLE live_inbound_log MODIFY uniqueid VARCHAR(20) NOT NULL;
ALTER TABLE vicidial_manager MODIFY uniqueid VARCHAR(20) NOT NULL;
ALTER TABLE vicidial_live_agents MODIFY uniqueid VARCHAR(20) NOT NULL;
ALTER TABLE vicidial_auto_calls MODIFY uniqueid VARCHAR(20) NOT NULL;
ALTER TABLE call_log DROP PRIMARY KEY;
ALTER TABLE call_log DROP INDEX uniqueid;
ALTER TABLE call_log MODIFY uniqueid VARCHAR(20) PRIMARY KEY UNIQUE NOT NULL;
ALTER TABLE park_log DROP PRIMARY KEY;
ALTER TABLE park_log DROP INDEX uniqueid;
ALTER TABLE park_log MODIFY uniqueid VARCHAR(20) PRIMARY KEY UNIQUE NOT NULL;
ALTER TABLE vicidial_log DROP PRIMARY KEY;
ALTER TABLE vicidial_log DROP INDEX uniqueid;
ALTER TABLE vicidial_log MODIFY uniqueid VARCHAR(20) PRIMARY KEY UNIQUE NOT NULL;

UPDATE system_settings SET db_schema_version='1075';

ALTER TABLE vicidial_auto_calls ADD queue_priority TINYINT(2) default '0';
ALTER TABLE vicidial_campaigns ADD queue_priority TINYINT(2) default '50';
ALTER TABLE vicidial_inbound_groups ADD queue_priority TINYINT(2) default '0';

UPDATE system_settings SET db_schema_version='1076';

ALTER TABLE vicidial_inbound_groups CHANGE drop_message drop_action ENUM('HANGUP','MESSAGE','VOICEMAIL','IN_GROUP') default 'MESSAGE';
ALTER TABLE vicidial_inbound_groups ADD drop_inbound_group VARCHAR(20) default '---NONE---';
UPDATE vicidial_inbound_groups SET drop_action='MESSAGE';

ALTER TABLE vicidial_campaigns CHANGE safe_harbor_message drop_action ENUM('HANGUP','MESSAGE','VOICEMAIL','IN_GROUP') default 'MESSAGE';
ALTER TABLE vicidial_campaigns ADD drop_inbound_group VARCHAR(20) default '---NONE---';
UPDATE vicidial_campaigns SET drop_action='MESSAGE';

UPDATE system_settings SET db_schema_version='1077';

ALTER TABLE vicidial_campaigns ADD qc_enabled ENUM('Y','N') default 'N';
ALTER TABLE vicidial_campaigns ADD qc_statuses TEXT;
ALTER TABLE vicidial_campaigns ADD qc_lists TEXT;
ALTER TABLE vicidial_campaigns ADD campaign_shift_start_time VARCHAR(4) default '0900';
ALTER TABLE vicidial_campaigns ADD campaign_shift_length VARCHAR(5) default '16:00';
ALTER TABLE vicidial_campaigns ADD campaign_day_start_time VARCHAR(4) default '0100';

UPDATE system_settings SET db_schema_version='1078';

ALTER TABLE vicidial_campaigns ADD qc_web_form_address VARCHAR(255);
ALTER TABLE vicidial_campaigns ADD qc_script VARCHAR(10);

UPDATE system_settings SET db_schema_version='1079';

ALTER TABLE vicidial_inbound_groups ADD ingroup_recording_override  ENUM('DISABLED','NEVER','ONDEMAND','ALLCALLS','ALLFORCE') default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD ingroup_rec_filename VARCHAR(50) default 'NONE';

UPDATE system_settings SET db_schema_version='1080';

 CREATE TABLE vicidial_qc_codes (
code VARCHAR(8) PRIMARY KEY NOT NULL,
code_name VARCHAR(30)
);

UPDATE system_settings SET db_schema_version='1081';

 CREATE TABLE vicidial_agent_sph (
campaign_group_id VARCHAR(20) NOT NULL,
stat_date DATE NOT NULL,
shift VARCHAR(20) NOT NULL,
role ENUM('FRONTER','CLOSER') default 'FRONTER',
user VARCHAR(20) NOT NULL,
calls MEDIUMINT(8) UNSIGNED default '0',
sales MEDIUMINT(8) UNSIGNED default '0',
login_sec MEDIUMINT(8) UNSIGNED default '0',
login_hours DECIMAL(5,2) DEFAULT '0.00',
sph DECIMAL(6,2) DEFAULT '0.00',
index (campaign_group_id),
index (stat_date)
);

ALTER TABLE vicidial_log ADD term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE') default 'NONE';
ALTER TABLE vicidial_closer_log ADD term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE') default 'NONE';

ALTER TABLE vicidial_inbound_groups MODIFY after_hours_action ENUM('HANGUP','MESSAGE','EXTENSION','VOICEMAIL','IN_GROUP') default 'MESSAGE';
ALTER TABLE vicidial_inbound_groups ADD afterhours_xfer_group VARCHAR(20) default '---NONE---';

UPDATE system_settings SET db_schema_version='1082';

 CREATE TABLE phones_alias (
alias_id VARCHAR(20) NOT NULL UNIQUE PRIMARY KEY,
alias_name VARCHAR(50),
logins_list VARCHAR(255)
);

UPDATE system_settings SET db_schema_version='1083';

ALTER TABLE system_settings ADD auto_user_add_value INT(9) UNSIGNED default '101';
UPDATE system_settings SET auto_user_add_value='1101';

 CREATE TABLE vicidial_shifts (
shift_id VARCHAR(20) NOT NULL,
shift_name VARCHAR(50),
shift_start_time VARCHAR(4) default '0900',
shift_length VARCHAR(5) default '16:00',
shift_weekdays VARCHAR(7) default '0123456',
index (shift_id)
);

ALTER TABLE vicidial_user_groups ADD group_shifts TEXT;

UPDATE system_settings SET db_schema_version='1084';

CREATE INDEX lead_id ON vicidial_agent_log (lead_id);

UPDATE system_settings SET db_schema_version='1085';

 CREATE TABLE vicidial_timeclock_log (
timeclock_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
event_epoch INT(10) UNSIGNED NOT NULL,
event_date TIMESTAMP NOT NULL,
login_sec INT(10) UNSIGNED,
event VARCHAR(50) NOT NULL,
user VARCHAR(20) NOT NULL,
user_group VARCHAR(20) NOT NULL,
ip_address VARCHAR(15),
shift_id VARCHAR(20),
notes VARCHAR(255),
manager_user VARCHAR(20),
manager_ip VARCHAR(15),
index (user)
);

 CREATE TABLE vicidial_timeclock_status (
user VARCHAR(20) UNIQUE NOT NULL,
user_group VARCHAR(20) NOT NULL,
event_epoch INT(10) UNSIGNED,
event_date TIMESTAMP,
status VARCHAR(50),
ip_address VARCHAR(15),
shift_id VARCHAR(20),
index (user)
);

UPDATE system_settings SET db_schema_version='1086';
