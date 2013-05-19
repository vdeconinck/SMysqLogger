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
    function paramToDate($paramKey, $defaultDate) {
        $paramDate = $_GET[$paramKey];
        if ($paramDate != "") {
            list($d,$m,$y)=explode("/", $paramDate);
            try {
                return new DateTime($y."-".$m."-".$d);
            }
            catch(Exception $e) {
                return $defaultDate;
            }        
        }
        else {
            return $defaultDate;
        }
    }

    session_start();
    
    // Connect
    $conn = mysql_pconnect("localhost", "sma", "<SMA_PASSWORD>") or die ("Connection Failure to Database");
    mysql_select_db("sma", $conn) or die ("Database not found.");

    // Determine date boundaries
    $query="select min(logdate), max(logdate) from logged_values";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    if ($thisrow=mysql_fetch_row($result)) {
        $firstDate = new DateTime($thisrow[0]);
        $lastDate = new DateTime($thisrow[1]);
    }
    else {
        $firstDate = new DateTime();
        $lastDate = new DateTime();
    }    
    mysql_free_result($result);
    
    // Parse parameters, if any
	// If not, show last month by default
	$defaultStart =  new DateTime(date('Y-m-d', strtotime('-1 month', strtotime(date_format($lastDate, "Y-m-d")))));
	if ($defaultStart < $firstDate) {
		$defaultStart = $firstDate;
	}
    $startDate = paramToDate("startDate", $defaultStart);
    $endDate = paramToDate("endDate", $lastDate);
    
    // Get daily totals
    $query="select logdate, max(e_total_kwh) - min(e_total_kwh) from logged_values where logdate between '".date_format($startDate, "Y-m-d")."' and '".date_format($endDate, "Y-m-d")."' group by logdate order by logdate";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    $serDays = "";
    $serEtot = "";
	$nbDays = 0;
	$periodTotal = 0;
    while ($thisrow=mysql_fetch_row($result)) {
        if ($serDays != "") {
            $serDays = $serDays.", ";
            $serEtot = $serEtot.", ";
        }
        $serDays = $serDays."'".$thisrow[0]."'";
        $serEtot = $serEtot.$thisrow[1];
		$nbDays++;
		$periodTotal += $thisrow[1];
    }
	if ($thisrow[0] == date_format(new DateTime(), "Y-m-d")) {
		// Ignore today for average production calculation (because today is probably not complete)
		$nbDays--;
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
            text: 'Daily totals'
        },
        subtitle: {
            text: null
        },
        xAxis: {
            categories: [<?php echo $serDays; ?>]
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
            name: 'Daily',
            events: {
                click: function(event) {
                    // Link to details of the day
                    window.location.replace('today.php?displayDate=' + (event.point.category).replace(/-/g,'%2F'));
                }
            },
            data: [<?php echo $serEtot; ?>]
        }]
    });
   
   
});


        </script>
        <script type="text/javascript">        
        	$(function(){
				// Datepicker
				$('.datepicker').datepicker({
                    dateFormat: 'dd/mm/yy', 
                    minDate: '<?php echo date_format($firstDate, "d/m/Y"); ?>', 
                    maxDate: '<?php echo date_format($lastDate, "d/m/Y"); ?>',
                    onSelect: function(dateText, inst) {$('#dateForm').submit();}
                    });
            });
        </script>
    </head>
    <body> 
	<?php include "header.inc.php" ?>
        <form id="dateForm">
            <table width="100%"><tr>
            <td align="left">Start date: <input type="text" name="startDate" id="startDate" class="datepicker" value="<?php echo date_format($startDate, "d/m/Y"); ?>" /></td>
			<td align="center">Average on this period : <?php echo number_format($periodTotal/$nbDays, 2, ',', ' '); ?> kWh/day</td>
            <td align="right">
                End date: <input type="text" name="endDate" id="endDate" class="datepicker" value="<?php if ($endDate != null) echo date_format($endDate, "d/m/Y"); ?>" />
                <a href="#" onClick="$('#endDate').val('');$('#dateForm').submit();return false;">Clear</a>
            </td>
            </tr></table>
        </form>
        <div id="totalChart" style="width: 100%; height: 90%"></div>
    </body>
</html>