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
    
    // Connect
    $conn = mysql_pconnect("localhost", "sma", "<SMA_PASSWORD>") or die ("Connection Failure to Database");
    mysql_select_db("sma", $conn) or die ("Database not found.");

    // Get monthly totals
    $query="select concat(year(logdate), '-', month(logdate)), max(e_total_kwh) - min(e_total_kwh) from logged_values group by year(logdate), month(logdate) order by year(logdate), month(logdate)";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    $serMonths = "";
    $serEtot = "";
	$nbMonths = 0;
	$periodTotal = 0;
    while ($thisrow=mysql_fetch_row($result)) {
        if ($serMonths != "") {
            $serMonths = $serMonths.", ";
            $serEtot = $serEtot.", ";
        }
        $serMonths = $serMonths."'".$thisrow[0]."'";
        $serEtot = $serEtot.$thisrow[1];
		$nbMonths++;
		$periodTotal += $thisrow[1];
    }
	if ($thisrow[0] == date_format(new DateTime(), "Y-m")) {
		// Ignore this month for average production calculation (because it is probably not complete)
		$nbMonths--;
		$periodTotal -= $thisrow[1];
	}
	
    mysql_free_result($result);

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
        <script type="text/javascript" src="js/themes/gray.js"></script>
        <script type="text/javascript">

var historyChart;
$(document).ready(function() {
   
   historyChart = new Highcharts.Chart({
        chart: {
            renderTo: 'totalChart',
            defaultSeriesType: 'column'
        },
        title: {
            text: 'Monthly totals'
        },
        subtitle: {
            text: null
        },
        xAxis: {
            categories: [<?php echo $serMonths; ?>]
        },        
        yAxis: {
            min: 0,
            title: {
                text: 'Energy (kWh)'
            }
        },
        legend: {
              enabled:false
        },
        tooltip: {
            formatter: function() {
                return ''+this.x +': '+ (Math.floor(100*this.y))/100 +' kWh';
            }
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: [{
            name: 'Monthly',
            events: {
                click: function(event) {
                    // Link to details of the month
					var dateParts = event.point.category.split('-');
					// Compute end of the month
					var endDate = new Date(dateParts[0], dateParts[1], 1); // First day of this month
					endDate.setMonth(endDate.getMonth() + 1); // First day of next month
					endDate.setDate(endDate.getDate() - 1); // Last day of this month
                    window.location.replace('daily.php?startDate=' + (event.point.category).replace(/-/g,'%2F') + "%2F01&endDate=" + endDate.getFullYear() + '%2F' + (endDate.getMonth()<10?'0':'') + endDate.getMonth() + '%2F' + (endDate.getDate()<10?'0':'') + endDate.getDate());
                }
            },
            data: [<?php echo $serEtot; ?>]
        }]
    });
   
   
});


        </script>
    </head>
    <body> 
	<?php include "header.inc.php" ?>
        <form id="dateForm">
            <table width="100%"><tr>
			<td align="center">Average on this period : <?php echo number_format($periodTotal/$nbMonths, 2, ',', ' '); ?> kWh/month</td>
            </tr></table>
        </form>
        <div id="totalChart" style="width: 100%; height: 90%"></div>
    </body>
</html>