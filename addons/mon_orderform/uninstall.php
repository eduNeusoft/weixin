<?php

pdo_query("DROP TABLE IF EXISTS ".tablename('mon_orderform').";");
pdo_query("DROP TABLE IF EXISTS ".tablename('mon_orderform_item').";");
pdo_query("DROP TABLE IF EXISTS ".tablename('mon_orderform_order').";");
pdo_query("DROP TABLE IF EXISTS ".tablename('mon_orderform_setting').";");
pdo_query("DROP TABLE IF EXISTS ".tablename('mon_orderform_template').";");