-- $Id: install.sql,v 1.5 2006/03/12 04:38:16 blindman1344 Exp $

CREATE TABLE mod_phpwstimelog_entries (
  id int NOT NULL default '0',
  owner varchar(20) default NULL,
  editor varchar(20) default NULL,
  ip text,
  created int NOT NULL default '0',
  updated int NOT NULL default '0',
  phase int NOT NULL default '0',
  start int NOT NULL default '0',
  end int NOT NULL default '0',
  interruption int NOT NULL default '0',
  delta int NOT NULL default '0',
  comments text NULL,
  PRIMARY KEY (id)
);

CREATE TABLE mod_phpwstimelog_phases (
  id int NOT NULL default '0',
  owner varchar(20) default NULL,
  editor varchar(20) default NULL,
  label text NOT NULL,
  created int NOT NULL default '0',
  updated int NOT NULL default '0',
  ordinal int NOT NULL default '0',
  PRIMARY KEY (id)
);

CREATE TABLE mod_phpwstimelog_settings (
  allow_anon_view smallint NOT NULL default 1
);
INSERT INTO mod_phpwstimelog_settings VALUES (1);