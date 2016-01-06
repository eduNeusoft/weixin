<?php

pdo_query('ALTER TABLE ' . tablename('we7car_order_data') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_order_fields') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_order_list') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_order_set') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_news_category') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_news') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_album') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_brand') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_series') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_type') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_message_set') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_message_list') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_set') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_care') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_services') . ' DEFAULT CHARACTER SET = UTF8');

pdo_query('ALTER TABLE ' . tablename('we7car_album_photo') . ' DEFAULT CHARACTER SET = UTF8');

if(!pdo_fieldexists('we7car_type', 'description')) {
	pdo_query("ALTER TABLE ".tablename('we7car_type')." ADD   `description` varchar(512) NOT NULL DEFAULT '' COMMENT '品牌描述';");
}