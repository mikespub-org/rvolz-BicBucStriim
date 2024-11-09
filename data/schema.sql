PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE config (id integer primary key autoincrement, name varchar(30), val varchar(255));
CREATE TABLE user (id integer primary key autoincrement, username varchar(30), password char(255), email varchar(255), languages varchar(255), tags varchar(255), role integer);
CREATE TABLE calibrething (id integer primary key autoincrement, ctype integer, cid integer, cname varchar(255), refctr integer);
CREATE TABLE artefact (id integer primary key autoincrement, atype integer, url varchar(255), calibrething_id integer, foreign key(calibrething_id) 
                                                 references calibrething(id) 
                                                 on delete set null on update set null);
CREATE TABLE link (id integer primary key autoincrement, ltype integer, label varchar(255), url varchar(255), calibrething_id integer, foreign key(calibrething_id) 
                                                 references calibrething(id) 
                                                 on delete set null on update set null);
CREATE TABLE note (id integer primary key autoincrement, ntype integer, mime varchar(255), ntext text, calibrething_id integer , foreign key(calibrething_id) 
                                                 references calibrething(id) 
                                                 on delete set null on update set null);
CREATE TABLE idtemplate (id integer primary key autoincrement, name varchar(255), val varchar(255), label varchar(255));
CREATE UNIQUE INDEX config_names on config(name);
CREATE UNIQUE INDEX user_names on user(username);
CREATE INDEX index_calibrething_cid on calibrething(cid);
CREATE INDEX index_foreignkey_artefact_calibrething on artefact(calibrething_id);
CREATE INDEX index_foreignkey_link_calibrething on link(calibrething_id);
CREATE INDEX index_foreignkey_note_calibrething on note(calibrething_id);
COMMIT;