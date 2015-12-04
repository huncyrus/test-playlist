CREATE TABLE mm_test_pl_playlist (
	id int(100) not null auto_increment,
	user_id int(100) not null default '0' comment 'mm_test_pl_user.id',
	item_id int(100) not null default '0' comment 'mm_test_pl_items.id',
	crdate datetime not null default '0000-00-00 00:00:00',
	PRIMARY KEY(id)
) ENGINE=InnoDB;

CREATE TABLE mm_test_pl_items (
	id int(100) not null auto_increment,
	song_hash varchar(250) not null default '' comment 'song id or hash from 3rd party api/provider',
	song_title varchar(250) not null default '',
	song_duration time not null default '00:00',
	song_artist varchar(250) not null default '',
	song_artist_hash varchar(250) not null default '',
	song_album varchar(250) not null default '',
	song_publish_date date not null default '0000-00-00',
	crdate datetime not null default '0000-00-00 00:00:00',
	PRIMARY KEY(id)
) ENGINE=InnoDB;

CREATE TABLE mm_test_pl_cache_request (
	id int(100) not null auto_increment,
	requested_term varchar(200) not null default '',
	request_type smallint(1) not null default '0' comment '0 - artist, 1 - albm',
	stored_response text not null default '' comment 'stored response from the system',
	crdate datetime not null default '0000-00-00 00:00:00',
	PRIMARY KEY(id)
) ENGINE=InnoDB;

CREATE TABLE mm_test_pl_user (
	id int(100) not null auto_increment,
	user_name varchar(250) not null default '',
	user_pass varchar(250) not null default '' comment 'some hash',
	user_email varchar(250) not null default '',
	enabled smallint(1) not null default '0',
	api_hash varchar(250) not null default '' comment '3rd party api/provider user identifier',
	crdate datetime not null default '0000-00-00 00:00:00',
	PRIMARY KEY(id)
) ENGINE=InnoDB;
