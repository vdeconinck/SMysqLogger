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
    
    $min_power=10; //Watt. Below that, calculating efficiency is meaningless

    // Connect
    $conn = mysql_pconnect("localhost", "sma", "<SMA_PASSWORD>") or die ("Connection Failure to Database");
    mysql_select_db("sma", $conn) or die ("Database not found.");

    // Determine date to display
    $query="select max(logdate) from logged_values";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    if ($thisrow=mysql_fetch_row($result)) {
        $latestDate = new DateTime($thisrow[0]);
    }
    else {
        $latestDate = new DateTime();
    }    
    mysql_free_result($result);
    
	// Get latest logtime
    $query="select max(logtime) from logged_values where logdate ='".date_format($latestDate, "Y-m-d")."'";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    $traceTimestamp = 0;
    if ($thisrow=mysql_fetch_row($result)) {
        $traceTimestamp=new DateTime(date_format($latestDate, "Y-m-d")." ".$thisrow[0]);
    }
    mysql_free_result($result);
	
	// Get total production at the start of the day
    $query="select min(e_total_kwh) from logged_values where logdate ='".date_format($traceTimestamp, "Y-m-d")."'";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    $minTot = 0;
    if ($thisrow=mysql_fetch_row($result)) {
        $minTot=$thisrow[0];
    }
    mysql_free_result($result);
    
    // Get latest values
    $query="select pac_watt, e_total_kwh from logged_values where logdate ='".date_format($traceTimestamp, "Y-m-d")."' and logtime='".date_format($traceTimestamp, "H:i:s")."'";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    $tracePac = "0";
    $traceDailyEnergy = "0";
    $traceTotalEnergy = "0";
    while ($thisrow=mysql_fetch_row($result)) {
        $tracePac = $thisrow[0];
        $traceTotalEnergy = $thisrow[1];
        $traceDailyEnergy = ($thisrow[1]-$minTot);
    }
    mysql_free_result($result);
			
	$pageGenerationTimestamp = new DateTime();
    
    mysql_close($conn);
?>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <meta name="description" content="Power Graph">
        <title>Power Graph</title>
		<link type="text/css" href="css/ui-lightness/jquery-ui-1.8.13.custom.css" rel="stylesheet" />	
		<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.13.custom.min.js"></script>
        <script type="text/javascript" src="js/highcharts.js"></script>
        <script type="text/javascript" src="js/highchartsdial.js"></script>
        <script type="text/javascript" src="js/themes/gray.js"></script>
        <script type="text/javascript" src="js/odometer.js"></script>
        <script type="text/javascript" src="js/countdownbar.js"></script>
        <script type="text/javascript">

			var dailyOdo, totalOdo;

			// Instanciate Javascript variables from PHP variables to be able to update the values client-side
			var pageLoadTimestamp = new Date();
			// Note : new Date('YYYY-MM-DDTHH:MI:SS') is not supported under iOS, so pass values with comas so they are different params for JS
			var pageGenerationTimestamp = new Date(<?php echo date_format($pageGenerationTimestamp, "Y,m,d,H,i,s"); ?>);
			var traceTimestamp = new Date(<?php echo date_format($traceTimestamp, "Y,m,d,H,i,s"); ?>);
			// Calculate the local client clock value when the trace was taken
			var traceTimestampInClientTime = new Date(traceTimestamp.getTime() - pageGenerationTimestamp.getTime() + pageLoadTimestamp.getTime());
			var traceDailyEnergy = <?php echo $traceDailyEnergy; ?>;
			var traceTotalEnergy = <?php echo $traceTotalEnergy; ?>;
			var tracePac = <?php echo $tracePac; ?>;
/*								
			alert("traceTimestampInClientTime=" + traceTimestampInClientTime 
			+ "\ntraceDailyEnergy=" + traceDailyEnergy 
			+ "\ntraceTotalEnergy=" + traceTotalEnergy 
			+ "\ntracePac=" + tracePac 
			);
*/				
			var increment = getEstimatedEnergyIncrement();
//				alert("Estimated increment : " + getEstimatedEnergyIncrement());

			function secsToHM(secs) {
				hours = parseInt(secs / 3600);
				minutes = parseInt(secs / 60) % 60;
				return (hours < 10 ? "0" + hours : hours) + ":" + (minutes < 10 ? "0" + minutes : minutes);
			}
					
			$(document).ready(function() {						
				// Build the dial
				var dial = drawDial({
					renderTo: 'container',
					value: <?php echo intval($tracePac); ?>,
					centerX: 200,
					centerY: 200,
					min: 0,
					max: 5500,
					minAngle: -Math.PI,
					maxAngle: 0,
					tickInterval: 500,
					ranges: [{
						from: 0,
						to: 500,
						color: '#DDDF0D'
					}, {
						from: 500,
						to: 5000,
						color: '#55BF3B'
					}, {
						from: 5000,
						to: 5500,
						color: '#DF5353'
					}]
				});


				dailyOdo = new Odometer(document.getElementById("dailyOdo"), {value: traceDailyEnergy + increment, digits: 6, decimals: 3, digitHeight: 20, digitWidth: 15});
				totalOdo = new Odometer(document.getElementById("totalOdo"), {value: traceTotalEnergy + increment, digits: 6, decimals: 0, digitHeight: 20, digitWidth: 15});

				setInterval("updateDaily()", 100);
				setInterval("updateTotal()", 30000);
				
				var bar=new Bar({
					ID:'bar'
				});

				// Reload just after the next fetch. So the delay is 5 min (+ 10 sec margin) - elapsed time since last fetch. 
				// This elapsed time is (generationTime - lastTraceTime) 
				var delay = 310 - (pageGenerationTimestamp.getTime() - traceTimestamp.getTime())/1000;
				if (delay > 0) {
					// < 0 means no recent trace. Let's stop refreshing in this case
					bar.Start(delay);
				}
			});   

			function getEstimatedEnergyIncrement() {
				var now = new Date();
				var secsSinceGeneration = (now.getTime() - traceTimestampInClientTime.getTime())/1000;
				return secsSinceGeneration * tracePac / (1000*3600); // 1000 for kW, 3600 for h
			}			

			function updateDaily () {
				var increment = getEstimatedEnergyIncrement();
				dailyOdo.set(traceDailyEnergy + increment);
			}

			function updateTotal () {
				var increment = getEstimatedEnergyIncrement();
				totalOdo.set(traceTotalEnergy + increment);
			}
			
	</script>
	<style type="text/css">
		.bar {
		  width:100%;height:2px;background-color:#ccccff;
		}

		#bar {
		  width:100%;height:2px;background-color:blue;color:white;text-align:center;white-space:nowrap;
		}
	</style>

    </head>
    <body> 
		<?php include "header.inc.php" ?>
		<div id="container" style="height: 220px; width=400px;position:relative;"></div>
		<div style="position:relative; top:0px; left:150px; width:250px;"><div id="dailyOdo"></div>&nbsp;kWh today</div>
		<br/>
		<div style="position:relative; top:0px; left:150px; width:250px;"><div id="totalOdo"></div>&nbsp;kWh since installation</div>
		<p style="font-size:x-small;text-align:right">Last trace on <?php echo date_format($traceTimestamp, "Y-m-d H:i:s").": Pac=".$tracePac."W. Total=".$traceTotalEnergy."kWh. Today=".$traceDailyEnergy."kWh."; ?></p>
		<div class="bar" >
			<div id="bar"></div>
		</div>
	</body>
</html>