ALTER TABLE vicidial_statuses ADD human_answered ENUM('Y','N') default 'N';
ALTER TABLE vicidial_campaign_statuses ADD human_answered ENUM('Y','N') default 'Y';

UPDATE vicidial_statuses SET human_answered='Y' where status IN('DROP','DNC','DEC','SALE','XFER','CALLBK','NP','NI','N');

ALTER TABLE vicidial_campaigns ADD list_order_mix VARCHAR(20) default 'DISABLED';
UPDATE vicidial_campaigns SET list_order_mix='DISABLED';

 CREATE TABLE vicidial_campaigns_list_mix (
vcl_id VARCHAR(20) PRIMARY KEY NOT NULL,
vcl_name VARCHAR(50),
campaign_id VARCHAR(8),
list_mix_container TEXT,
mix_method ENUM('EVEN_MIX','IN_ORDER','RANDOM') default 'IN_ORDER',
status ENUM('ACTIVE','INACTIVE') default 'INACTIVE',
index (campaign_id)
);

 CREATE TABLE vicidial_status_categories (
vsc_id VARCHAR(20) PRIMARY KEY NOT NULL,
vsc_name VARCHAR(50),
vsc_description VARCHAR(255),
tovdad_display ENUM('Y','N') default 'N'
);

ALTER TABLE vicidial_campaign_statuses ADD category VARCHAR(20) default 'UNDEFINED';
ALTER TABLE vicidial_statuses ADD category VARCHAR(20) default 'UNDEFINED';

INSERT INTO vicidial_status_categories (vsc_id,vsc_name) values('UNDEFINED','Default Category');

ALTER TABLE vicidial_campaign_stats ADD status_category_1 VARCHAR(20);
ALTER TABLE vicidial_campaign_stats ADD status_category_count_1 INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_campaign_stats ADD status_category_2 VARCHAR(20);
ALTER TABLE vicidial_campaign_stats ADD status_category_count_2 INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_campaign_stats ADD status_category_3 VARCHAR(20);
ALTER TABLE vicidial_campaign_stats ADD status_category_count_3 INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_campaign_stats ADD status_category_4 VARCHAR(20);
ALTER TABLE vicidial_campaign_stats ADD status_category_count_4 INT(9) UNSIGNED default '0';

ALTER TABLE system_settings ADD enable_agc_xfer_log ENUM('0','1') default '0';


CREATE TABLE vicidial_ivr (
ivr_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
entry_time DATETIME,
length_in_sec SMALLINT(5) UNSIGNED default '0',
inbound_number VARCHAR(12),
recording_id INT(9) UNSIGNED,
recording_filename VARCHAR(50),
company_id VARCHAR(12),
phone_number VARCHAR(12),
lead_id INT(9) UNSIGNED,
campaign_id VARCHAR(20),			
product_code VARCHAR(20),
user VARCHAR(20),
prompt_audio_1 VARCHAR(20),
prompt_response_1 TINYINT(1) UNSIGNED default '0',
prompt_audio_2 VARCHAR(20),
prompt_response_2 TINYINT(1) UNSIGNED default '0',
prompt_audio_3 VARCHAR(20),
prompt_response_3 TINYINT(1) UNSIGNED default '0',
prompt_audio_4 VARCHAR(20),
prompt_response_4 TINYINT(1) UNSIGNED default '0',
prompt_audio_5 VARCHAR(20),
prompt_response_5 TINYINT(1) UNSIGNED default '0',
prompt_audio_6 VARCHAR(20),
prompt_response_6 TINYINT(1) UNSIGNED default '0',
prompt_audio_7 VARCHAR(20),
prompt_response_7 TINYINT(1) UNSIGNED default '0',
prompt_audio_8 VARCHAR(20),
prompt_response_8 TINYINT(1) UNSIGNED default '0',
prompt_audio_9 VARCHAR(20),
prompt_response_9 TINYINT(1) UNSIGNED default '0',
prompt_audio_10 VARCHAR(20),
prompt_response_10 TINYINT(1) UNSIGNED default '0',
index (phone_number),
index (entry_time)
);

ALTER TABLE vicidial_ivr AUTO_INCREMENT = 1000000;

#!!!!!!! CHANGES BELOW THIS LINE ARE NOT FOR PRODUCTION USE YET, DO NOT APPLY THEM!!!!!!!!!!!!!!!!!!!!!
#
#
#ALTER TABLE vicidial_hopper ADD priority TINYINT(2) default '50';
#
#ALTER TABLE vicidial_inbound_groups ADD moh_extension VARCHAR(20);
#ALTER TABLE vicidial_inbound_groups ADD prompt_interval SMALLINT(5) UNSIGNED default '60';
#ALTER TABLE vicidial_inbound_groups ADD announce_place_in_line ENUM('Y','N') default 'N';
#ALTER TABLE vicidial_inbound_groups ADD announce_estimate_hold_time ENUM('Y','N') default 'N';
#ALTER TABLE vicidial_inbound_groups ADD allow_leave_queue_place ENUM('Y','N') default 'N';
#ALTER TABLE vicidial_inbound_groups ADD allow_leave_queue_message ENUM('Y','N') default 'N';
#ALTER TABLE vicidial_inbound_groups ADD callback_number_validation ENUM('6','7','8','9','10','11','12','13','14','NORTH_AMERICA','UK');
#ALTER TABLE vicidial_inbound_groups ADD call_time VARCHAR(20);
#ALTER TABLE vicidial_inbound_groups ADD after_hours ENUM('HANGUP','VOICEMAIL','EXTENSION') default 'EXTENSION';
#ALTER TABLE vicidial_inbound_groups ADD after_hours_exten VARCHAR(20);
#
#ALTER TABLE servers ADD hold_queue_prompt_exten VARCHAR(20) default '8359';
#
#ALTER TABLE vicidial_auto_calls ADD last_prompt_time DATETIME;
#ALTER TABLE vicidial_auto_calls ADD random_id INT(9) UNSIGNED;
#ALTER TABLE vicidial_auto_calls ADD recording_id INT(10) UNSIGNED;
#
#ALTER TABLE vicidial_user_groups ADD allowable_xfer_inbound_groups TEXT
#ALTER TABLE vicidial_user_groups ADD default_xfer_inbound_group VARCHAR(20)





