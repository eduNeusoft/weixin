<?php

pdo_query("DROP TABLE IF EXISTS ".tablename('mon_egg').";");

pdo_query("DROP TABLE IF EXISTS ".tablename('mon_egg_user').";");
pdo_query("DROP TABLE IF EXISTS ".tablename('mon_egg_prize').";");
pdo_query("DROP TABLE IF EXISTS ".tablename('mon_egg_record').";");
pdo_query("DROP TABLE IF EXISTS ".tablename('mon_egg_share').";");