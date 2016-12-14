<?php
/*
 * Copyright 2005-2012 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 * 
 * This program is free software; you can redistribute it and/or modify it under 
 * the terms of the GNU General Public License as published by the Free Software 
 * Foundation ; either version 2 of the License.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with 
 * this program; if not, see <http://www.gnu.org/licenses>.
 * 
 * Linking this program statically or dynamically with other modules is making a 
 * combined work based on this program. Thus, the terms and conditions of the GNU 
 * General Public License cover the whole combination.
 * 
 * As a special exception, the copyright holders of this program give MERETHIS 
 * permission to link this program with independent modules to produce an executable, 
 * regardless of the license terms of these independent modules, and to copy and 
 * distribute the resulting executable under terms of MERETHIS choice, provided that 
 * MERETHIS also meet, for each linked independent module, the terms  and conditions 
 * of the license of that module. An independent module is a module which is not 
 * derived from this program. If you modify this program, you may extend this 
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 * 
 * For more information : contact@centreon.com
 * 
 * Project name : Centreon Syslog
 * Module name: Centreon-Syslog-Server
 * 
 * SVN : $URL$
 * SVN : $Id$
 * 
 */
 
	include 'DBconnect.php';

	/*
	 * Get names of logs tables
	 */
	function getLogsTables() {
		global $pear_syslogDB, $syslogOpt;

		$res =& $pear_syslogDB->query("SHOW TABLES;");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}

		if ($res->numRows()){
			while($row = $res->fetchRow()) {
				if (strstr($row["Tables_in_".$syslogOpt["syslog_db_name"]],"logs")) {
					$logsTables[$row["Tables_in_".$syslogOpt["syslog_db_name"]]] = $row["Tables_in_".$syslogOpt["syslog_db_name"]];
				}
			}
		}
		print "\n";
		return $logsTables;
	}
	
	/*
	 * Drop old logs tables
	 */
	function dropOldTables($logsTables) {
		global $pear_syslogDB, $syslogOpt;
		
		$iMaxDay = $syslogOpt["syslog_db_rotate"]-1;
		
		$maxDay = "logs".date("Ymd",mktime(0,0,0,date("n"),(date("j")-$iMaxDay),date("Y")));
		
		foreach ($logsTables as $logTable) {
			if (strcmp($logTable, $maxDay) < 0) {
				if (strcmp($logTable, "logs") > 0) {
					$pear_syslogDB->query("DROP TABLE IF EXISTS ".$logTable." ;");
					if (PEAR::isError($pear_syslogDB)) {
						print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
					}
					print "DROP TABLE IF EXISTS ".$logTable."\n";
				}
			}
		}
		
		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
	}
	
	/*
	 * Rename last logstable with today date
	 */
	function archiveTodayLogs() {
		global $pear_syslogDB, $syslogOpt;
		
		$today = date("Ymd",mktime(0,0,0,date("n"),date("j")+1,date("Y")));
		
		$pear_syslogDB->query("RENAME TABLE logs TO logs".$today.";");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
		else {
			print "RENAME TABLE logs TO logs".$today."\n";
		}
		
		$newTable = "CREATE TABLE `".$syslogOpt["syslog_db_name"]."`.`logs` (";
		$newTable = $newTable." host varchar(128) default NULL,";
		$newTable = $newTable." facility varchar(10) default NULL,";
		$newTable = $newTable." priority varchar(10) default NULL,";
		$newTable = $newTable." level varchar(10) default NULL,";
		$newTable = $newTable." tag varchar(10) default NULL,";
		$newTable = $newTable." datetime datetime default NULL,";
		$newTable = $newTable." program varchar(@SYSLOG_PROGRAM_FIELD_SIZE@) default NULL,";
		$newTable = $newTable." msg text,";
		$newTable = $newTable." seq bigint(20) unsigned NOT NULL auto_increment,";
		$newTable = $newTable." counter int(11) NOT NULL default '1',";
		$newTable = $newTable." fo datetime default NULL,";
		$newTable = $newTable." lo datetime default NULL,";
		$newTable = $newTable." PRIMARY KEY  (seq),";
		$newTable = $newTable." KEY datetime (datetime),";
		$newTable = $newTable." KEY priority (priority),";
		$newTable = $newTable." KEY facility (facility),";
		$newTable = $newTable." KEY program (program),";
		$newTable = $newTable." KEY host (host),";
		$newTable = $newTable." KEY host_datetime (host,datetime)";
		$newTable = $newTable." ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		$pear_syslogDB->query($newTable);
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
		else {
			print "CREATE TABLE logs\n";
		}
		
		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
	}
	
	/*
	 * DROP TABLE IF EXISTS "all_logs"
	 */
	function dropMergeLogs() {
		global $pear_syslogDB, $syslogOpt;
		
		$pear_syslogDB->query("DROP TABLE IF EXISTS `".$syslogOpt["syslog_db_name"]."`.`all_logs`;");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";;
		}
		else {
			print "DROP TABLE IF EXISTS all_logs\n";
		}
		
		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
	}
	
	/*
	 * Rebuild merge table "all_logs"
	 */
	function mergeAllLogs() {
		global $pear_syslogDB, $syslogOpt;
		
		$mergeTable = "CREATE TABLE `".$syslogOpt["syslog_db_name"]."`.`all_logs` (";
		$mergeTable = $mergeTable." `host` varchar(128) collate utf8_unicode_ci default NULL,";
		$mergeTable = $mergeTable." `facility` varchar(10) collate utf8_unicode_ci default NULL,";
		$mergeTable = $mergeTable." `priority` varchar(10) collate utf8_unicode_ci default NULL,";
		$mergeTable = $mergeTable." `level` varchar(10) collate utf8_unicode_ci default NULL,";
		$mergeTable = $mergeTable." `tag` varchar(10) collate utf8_unicode_ci default NULL,";
		$mergeTable = $mergeTable." `datetime` datetime default NULL,";
		$mergeTable = $mergeTable." `program` varchar(@SYSLOG_PROGRAM_FIELD_SIZE@) collate utf8_unicode_ci default NULL,";
		$mergeTable = $mergeTable." `msg` text collate utf8_unicode_ci,";
		$mergeTable = $mergeTable." `seq` bigint(20) unsigned NOT NULL auto_increment,";
		$mergeTable = $mergeTable." `counter` int(11) NOT NULL default '1',";
		$mergeTable = $mergeTable." `fo` datetime default NULL,";
		$mergeTable = $mergeTable." `lo` datetime default NULL,";
		$mergeTable = $mergeTable." PRIMARY KEY  (`seq`),";
		$mergeTable = $mergeTable." KEY `datetime` (`datetime`),";
		$mergeTable = $mergeTable." KEY `priority` (`priority`),";
		$mergeTable = $mergeTable." KEY `facility` (`facility`),";
		$mergeTable = $mergeTable." KEY `program` (`program`),";
		$mergeTable = $mergeTable." KEY `host` (`host`),";
		$mergeTable = $mergeTable." KEY `host_datetime` (`host`,`datetime`)";
		$mergeTable = $mergeTable." ) ENGINE=MRG_MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci UNION=(";

		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage();
		}

		$logsAvailabbleTables = getLogsTables();
		foreach ($logsAvailabbleTables as $logTable) {
			if (strcmp($logTable, "logs") > 0) {
				$mergeTable = $mergeTable."`".$syslogOpt["syslog_db_name"]."`.`".$logTable."`, ";
			}
		}

		$mergeTable = $mergeTable."`".$syslogOpt["syslog_db_name"]."`.`logs`);";

		$pear_syslogDB->query($mergeTable);
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
		else {
			print "CREATE MERGE TABLE all_logs\n";
		}

		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage();
		}
	}

	/*
	 * Get names of cache tables
	 */
	function getCacheTables() {
		global $pear_syslogDB, $syslogOpt;

		$res =& $pear_syslogDB->query("SHOW TABLES;");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}

		if ($res->numRows()){
			while($row = $res->fetchRow()) {
				if (strstr($row["Tables_in_".$syslogOpt["syslog_db_name"]],"cache")) {
					$cacheTables[$row["Tables_in_".$syslogOpt["syslog_db_name"]]] = $row["Tables_in_".$syslogOpt["syslog_db_name"]];
				}
			}
		}
		return $cacheTables;
	}

	/*
	 * Rename last cache table with today date
	 */
	function archiveTodayCache() {
		global $pear_syslogDB, $syslogOpt;
		
		$today = date("Ymd",mktime(0,0,0,date("n"),date("j")+1,date("Y")));
		
		$pear_syslogDB->query("RENAME TABLE cache TO cache".$today.";");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		} else {
			print "RENAME TABLE cache TO cache".$today."\n";
		}
		
		$newTable = "CREATE TABLE IF NOT EXISTS `".$syslogOpt["syslog_db_name"]."`.`cache` (";
		$newTable = $newTable." `type` enum('HOST','FACILITY','PROGRAM','PRIORITY', 'TAG') collate utf8_unicode_ci default NULL,";
		$newTable = $newTable." `value` varchar(50) collate utf8_unicode_ci default NULL";
		$newTable = $newTable." ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

		$pear_syslogDB->query($newTable);
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		} else {
			print "CREATE TABLE cache\n";
		}

		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
	}
	
	/*
	 * DROP TABLE IF EXISTS "all_cache"
	 */
	function dropMergeCache() {
		global $pear_syslogDB, $syslogOpt;
		
		$pear_syslogDB->query("DROP TABLE IF EXISTS `".$syslogOpt["syslog_db_name"]."`.`all_cache`;");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";;
		}
		else {
			print "DROP TABLE IF EXISTS all_cache\n";
		}
		
		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
	}
	
	/*
	 * Drop old cache tables
	 */
	function dropOldTablesCache($cacheTables) {
		global $pear_syslogDB, $syslogOpt;
		
		$iMaxDay = $syslogOpt["syslog_db_rotate"]-1;
		
		$maxDay = "cache".date("Ymd",mktime(0,0,0,date("n"),(date("j")-$iMaxDay),date("Y")));
		
		foreach ($cacheTables as $cacheTable) {
			if (strcmp($cacheTable, $maxDay) < 0) {
				if (strcmp($cacheTable, "cache") > 0) {
					$pear_syslogDB->query("DROP TABLE IF EXISTS ".$cacheTable." ;");
					if (PEAR::isError($pear_syslogDB)) {
						print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
					}
					print "DROP TABLE IF EXISTS ".$cacheTable."\n";
				}
			}
		}
		
		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
	}
	
	/*
	 * Rebuild merge table "all_cache"
	 */	
	function mergeAllCache() {
		global $pear_syslogDB, $syslogOpt;
  
		$mergeTable = "CREATE TABLE `".$syslogOpt["syslog_db_name"]."`.`all_cache` (";
		$mergeTable = $mergeTable . "`type` enum('HOST','FACILITY','PROGRAM','PRIORITY', 'TAG') collate utf8_unicode_ci default NULL,";
		$mergeTable = $mergeTable . "`value` varchar(50) collate utf8_unicode_ci default NULL";
		$mergeTable = $mergeTable . ") ENGINE=MRG_MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci UNION=(";

		$CacheTables = getCacheTables();
  
		foreach ($CacheTables as $CacheTable) {
			if (strcmp($CacheTable, "cache") > 0) {
 				$mergeTable = $mergeTable."`".$syslogOpt["syslog_db_name"]."`.`".$CacheTable."`, ";
			}
		}

		$mergeTable = $mergeTable."`".$syslogOpt["syslog_db_name"]."`.`cache`);";
  
		$pear_syslogDB->query($mergeTable);
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
		else {
			print "CREATE MERGE TABLE all_cache\n";
		}
  
		$pear_syslogDB->query("FLUSH TABLES");
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
	}
	
	/*
	 * Check if process is running
	 * @processName name of process
	 */
	function controlProcess($processName)
	{
		global $pear_syslogDB;
		
		$query = "SELECT `status` FROM `instance` WHERE `name` = \"$processName\";";
		
		$res =& $pear_syslogDB->query($query);
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage();
		}
		
		$row = $res->fetchRow();
		if ($row["status"] == "0") {
			$query = "UPDATE `instance` SET `status` = '1', `last` = now() WHERE CONVERT( `instance`.`name` USING utf8 ) = 'tableLogRotate';";
			
			$pear_syslogDB->query($query);
			if (PEAR::isError($pear_syslogDB)) {
				print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
			}
			return 0;
		} else {
			return 1;
		}
	}

	/*
	 * Free process in database
	 */
	function freeProcess() {
		global $pear_syslogDB, $syslogOpt;
		
		$query = "UPDATE `instance` SET `status` = '0' WHERE CONVERT( `instance`.`name` USING utf8 ) = 'tableLogRotate';";
		
		$pear_syslogDB->query($query);
		if (PEAR::isError($pear_syslogDB)) {
			print "Mysql Error : ".$pear_syslogDB->getMessage()."\n";
		}
	}
		
	/*
	 * Main program
	 */
	
	$pear_syslogDB = getSyslogParameters();
	// Control if "reloadCache" is not running else wait 10 seconds
	$try = 0;
	while ((controlProcess("reloadCache") == 1) && ($try < 6)) {
		sleep(10);
		$try++;
	}
	if ($try == 6) {
		print date("Y-m-d H:i:s") . " - unable to do rotation of tables because reloadCache process seems broken\n";
		exit -1;
	}

	print "BEGIN TABLES LOGS ROTATION AT ".date("Y-m-d H:i:s")."\n";
	$logsTables = getLogsTables();
	dropMergeLogs();
	archiveTodayLogs();
	dropOldTables($logsTables);
	mergeAllLogs();
	print "END OF TABLES LOGS ROTATION AT ".date("Y-m-d H:i:s")."\n";
	print "\n";
	
	print "BEGIN TABLES CACHE ROTATION AT ".date("Y-m-d H:i:s")."\n";
    $cacheTables = getCacheTables();
    dropMergeCache();
    archiveTodayCache();
	dropOldTablesCache($cacheTables);
	mergeAllCache();
	print "END OF TABLES CACHE ROTATION AT ".date("Y-m-d H:i:s")."\n";
	print "\n";
	
	freeProcess();
 ?>
