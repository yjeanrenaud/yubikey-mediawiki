CREATE TABLE /*$wgDBprefix*/user_yubikey (
  yk_prefix varchar(255) NOT NULL,
  yk_user int(5) unsigned NOT NULL,
  
  PRIMARY KEY yk_prefix (yk_prefix),
  UNIQUE INDEX yk_user (yk_user)
) TYPE=InnoDB;
