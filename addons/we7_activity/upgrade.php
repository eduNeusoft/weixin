<?php





if(!pdo_fieldexists('activity', 'countlimit')) {
    pdo_query("ALTER TABLE ".tablename('activity')." ADD countlimit INT(5) NOT NULL ;");
}


if(!pdo_fieldexists('activity', 'countvirtual')) {
    pdo_query("ALTER TABLE ".tablename('activity')." ADD countvirtual INT(5) DEFAULT 0 ;");
}



if(!pdo_fieldexists('activity', 'location_p')) {
	pdo_query("ALTER TABLE ".tablename('activity')." ADD `location_p` varchar(100) NOT NULL COMMENT '所在地区_省' ;");
}

if(!pdo_fieldexists('activity', 'location_c')) {
    pdo_query("ALTER TABLE ".tablename('activity')." ADD `location_c` varchar(100) NOT NULL COMMENT '所在地区_市' ;");
}

if(!pdo_fieldexists('activity', 'location_a')) {
    pdo_query("ALTER TABLE ".tablename('activity')." ADD `location_a` varchar(100) NOT NULL COMMENT '所在地区_区' ;");
}


if(!pdo_fieldexists('activity', 'lng')) {
    pdo_query("ALTER TABLE ".tablename('activity')." `lng` decimal(18,10) NOT NULL DEFAULT '0.0000000000' ;");
}

if(!pdo_fieldexists('activity', 'lat')) {
    pdo_query("ALTER TABLE ".tablename('activity')." `lat` decimal(18,10) NOT NULL DEFAULT '0.0000000000' ;");
}



if(!pdo_fieldexists('activity', 'istip')) {
    pdo_query("ALTER TABLE ".tablename('activity')." ADD istip INT(1) DEFAULT 0 ;");
}

if(!pdo_fieldexists('activity', 'isrepeat')) {
    pdo_query("ALTER TABLE ".tablename('activity')." ADD isrepeat INT(1) DEFAULT 0 ;");
}


if(!pdo_fieldexists('activity', 'costdes')) {
    pdo_query("ALTER TABLE ".tablename('activity')." ADD costdes VARCHAR(500) DEFAULT NULL ;");
}



if(!pdo_fieldexists('activity_reply', 'new_pic')) {
    pdo_query("ALTER TABLE ".tablename('activity_reply')." ADD new_pic VARCHAR(200) NOT NULL ;");
}



if(!pdo_fieldexists('activity_reply', 'news_content')) {
    pdo_query("ALTER TABLE ".tablename('activity_reply')." ADD news_content VARCHAR(500) NOT NULL ;");
}













