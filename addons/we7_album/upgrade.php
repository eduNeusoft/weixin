<?php
 if(!pdo_fieldexists('album', 'banner')) {
	pdo_query("ALTER TABLE ".tablename('album')." ADD  `banner` varchar(255) NOT NULL DEFAULT '';");
}
