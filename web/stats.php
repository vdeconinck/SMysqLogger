<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<!--
    Copyright (C) 2011 Vincent Deconinck (known on google mail as user vdeconinck)

    This file is part of the SMySqLogger project.
	
    SMySqLogger is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
	
    SMySqLogger is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with SMySqLogger.  If not, see <http://www.gnu.org/licenses/>.
-->

<?php

    session_start();

    $maxResults=30; // number of results in each category
	

    // Connect
    $conn = mysql_pconnect("localhost", "sma", "<SMA_PASSWORD>") or die ("Connection Failure to Database");
    mysql_select_db("sma", $conn) or die ("Database not found.");

	// Determine best days
	$maxDays = "";
	$i = 0;
    $query="select logdate, max(e_total_kwh) - min(e_total_kwh) total from logged_values group by logdate order by total desc";
	$result = mysql_query($query) or die("Failed Query : ".$query);	
    while ($thisrow=mysql_fetch_row($result) and $i < $maxResults) {
		$maxDays = $maxDays."<tr><td>".($i+1)."</td><td><a href='today.php?displayDate=".str_replace("-", "%2F", $thisrow[0])."'>".$thisrow[0]."</a></td><td> ".number_format($thisrow[1], 2, '.', ' ')." kWh </td></tr>\n";
		$i++;
    }	
    mysql_free_result($result);
	

    // Determine days with peak power
	$maxPowers = "";
	$uniqueDates = array();
	$i = 0;
    $query="select logdate, logtime, pac_watt from logged_values order by pac_watt desc";
	$result = mysql_query($query) or die("Failed Query : ".$query);
    while ($thisrow=mysql_fetch_row($result) and count($uniqueDates) < $maxResults) {
		$currDate = $thisrow[0];
		if ($uniqueDates[$currDate] != "true") {
			$maxPowers = $maxPowers."<tr><td>".($i+1)."</td><td><a href='today.php?displayDate=".str_replace("-", "%2F", $currDate)."'>".$currDate."</a></td><td> ".$thisrow[2]." W (".$thisrow[1].") </td></tr>\n";
			$uniqueDates[$currDate]="true";
			$i++;
		}
    }	
    mysql_free_result($result);
	
    // Determine longest logging days
	$longestDays = "";
	$i = 0;
    $query="select logdate, time_to_sec(max(logtime)) - time_to_sec(min(logtime)) total_time from logged_values group by logdate order by total_time desc";
	$result = mysql_query($query) or die("Failed Query : ".$query);
    while ($thisrow=mysql_fetch_row($result) and $i < $maxResults) {
		$sec = intval($thisrow[1]);
 		$hour = intval($sec / 3600);
		$sec = $sec % 3600;
 		$min = intval($sec / 60);
		$sec = $sec % 60;
		$hms = str_pad($hour, 2, "0", STR_PAD_LEFT).':'.str_pad($min, 2, "0", STR_PAD_LEFT).':'.str_pad($sec, 2, "0", STR_PAD_LEFT);
		$longestDays = $longestDays."<tr><td>".($i+1)."</td><td><a href='today.php?displayDate=".str_replace("-", "%2F", $thisrow[0])."'>".$thisrow[0]."</a></td><td> ".$hms."</td></tr>\n";
		$i++;
    }
    mysql_free_result($result);
    
    mysql_close($conn);
?>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <meta name="description" content="Power Graph">
        <title>Power Tops</title>
		<link type="text/css" href="css/ui-lightness/jquery-ui-1.8.13.custom.css" rel="stylesheet" />	
		<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.13.custom.min.js"></script>
        <script type="text/javascript" src="js/highcharts.js"></script>
        <script type="text/javascript" src="js/themes/gray.js"></script>
    </head>
	<style type="text/css">
	body {font-family:'Lucida Sans Unicode', 'Lucida Grande', Sans-Serif}
	.table-design{font-size:12px;text-align:center;border-collapse:collapse;border-top:7px solid #9baff1;border-bottom:7px solid #9baff1;margin:20px;}
	.table-design th{font-size:13px;font-weight:normal;background:#e8edff;border-right:1px solid #9baff1;border-left:1px solid #9baff1;color:#039;padding:8px;}
	.table-design td{background:#e8edff;border-right:1px solid #aabcfe;border-left:1px solid #aabcfe;color:#669;padding:8px;}
	</style>
    <body> 
	<?php include "header.inc.php" ?>
	<table border="0" width="100%"><tr>
	<td>
        <h2>Top days</h2>
		<table class="table-design">
			<thead><tr><th>#</th><th>Date</th><th>Energy</th></tr></thead>
<tbody><?php echo $maxDays; ?></tbody>
		</table>
	</td><td>
        <h2>Power peaks</h2>
		<table class="table-design">
			<thead><tr><th>#</th><th>Date</th><th>Power (time)</th></tr></thead>
<tbody><?php echo $maxPowers; ?></tbody>
		</table>
	</td><td>
        <h2>Longest days</h2>
		<table class="table-design">
			<thead><tr><th>#</th><th>Date</th><th>Duration</th></tr></thead>
<tbody><?php echo $longestDays; ?></tbody>
		</table>
	</td>
	</tr></table>
    </body>
</html>