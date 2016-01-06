<?php
/**
 * 【超人】抢楼活动模块定义
 *
 */

$res = pdo_fetchall("SELECT id FROM ".tablename('rule')." WHERE module = :module", array(':module' => 'superman_floor'));
if ($res) {
	foreach ($res as $row) {
        $rid = $row['id'];
        $tablename = tablename("superman_floor_$rid");
		$sql = "DROP TABLE `$tablename`";
		pdo_query($sql);
	}
}
pdo_delete('rule', array('module' => 'superman_floor'));
pdo_delete('rule_keyword', array('module' => 'superman_floor'));
pdo_run("DROP TABLE ".tablename('superman_floor_award'));
pdo_run("DROP TABLE ".tablename('superman_floor_winner'));
pdo_run("DROP TABLE ".tablename('superman_floor'));
