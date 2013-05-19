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
    
    $min_power=10; //Watt. Below that, calculating efficiency is meaningless

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
    $displayDate = paramToDate("displayDate", $lastDate);
    $compareDate = paramToDate("compareDate", null);
    $mustFixDst = !($_GET["mustFixDst"] == "0"); // true if param is absent

	// Get total production at the start of the day
    $query="select min(e_total_kwh) from logged_values where logdate ='".date_format($displayDate, "Y-m-d")."'";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    $minTot = 0;
    if ($thisrow=mysql_fetch_row($result)) {
        $minTot=$thisrow[0];
    }
    mysql_free_result($result);
    
    // Get actual values for requested day
    $query="select time_to_sec(logtime), pac_watt, e_total_kwh, floor(upv_ist_volt * ipv_amp) from logged_values where logdate ='".date_format($displayDate, "Y-m-d")."' order by logtime";
    $result = mysql_query($query) or die("Failed Query : ".$query);
    $serPac = "";
    $serTot = "";
    $serEff = "";
    while ($thisrow=mysql_fetch_row($result)) {
        if ($serPac != "") {
            $serPac = $serPac.", ";
        }
        $serPac = $serPac."{x:".$thisrow[0].",y:".$thisrow[1]."}";

        if ($serTot != "") {
            $serTot = $serTot.", ";
        }
        $serTot = $serTot."{x:".$thisrow[0].",y:".number_format($thisrow[2]-$minTot, 3, '.', ' ')."}";

        if ($thisrow[3] > $min_power) {
			if ($serEff != "") {
				$serEff = $serEff.", ";
			}
            $serEff = $serEff."{x:".$thisrow[0].",y:".(floor(1000*$thisrow[1]/$thisrow[3])/10)."}";
        }
    }
    mysql_free_result($result);
    
    // Get actual values for "compare" day
    if ($compareDate != null) {
		// Get total production at the start of the comparison day
		$query="select min(e_total_kwh) from logged_values where logdate ='".date_format($compareDate, "Y-m-d")."'";
		$result = mysql_query($query) or die("Failed Query : ".$query);
		$minCmpTot = 0;
		if ($thisrow=mysql_fetch_row($result)) {
			$minCmpTot=$thisrow[0];
		}
		mysql_free_result($result);

        // Move the "compare" series if it has a different DST offset than the "display" one, to make them comparable.
        $displayArr = localtime(strtotime(date_format($displayDate, "Y-m-d")." 12:00:00"),TRUE);
        $compareArr = localtime(strtotime(date_format($compareDate, "Y-m-d")." 12:00:00"),TRUE);
        $dstFix = $compareArr['tm_isdst'] - $displayArr['tm_isdst'];
    
        $query="select time_to_sec(logtime), pac_watt, e_total_kwh from logged_values where logdate ='".date_format($compareDate, "Y-m-d")."' order by logtime";
		$result = mysql_query($query) or die("Failed Query : ".$query);
        $serPacCmp = "";
        $serTotCmp = "";
        while ($thisrow=mysql_fetch_row($result)) {
            $pointTime = $thisrow[0];
            if ($mustFixDst) {
                $pointTime = $pointTime - 3600 * $dstFix;
            }

            if ($serPacCmp != "") {
                $serPacCmp = $serPacCmp.", ";
            }
            $serPacCmp = $serPacCmp."{x:".$pointTime.",y:".$thisrow[1]."}";
			
	        if ($serTotCmp != "") {
				$serTotCmp = $serTotCmp.", ";
			}
			$serTotCmp = $serTotCmp."{x:".$pointTime.",y:".number_format($thisrow[2]-$minCmpTot, 3, '.', ' ')."}";
			$maxTot = $thisrow[2]-$minCmpTot;
        }
        mysql_free_result($result);
    }
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

// Function to force checkbox value to be sent back to server
// This is to distinguish between "first call of the page" and "user has unchecked the box"
$(document).ready(function () {
    $('form').submit(function() {
        $(this).find('input[type=checkbox]').each(function () {
            $(this).attr('value', $(this).is(':checked') ? '1' : '0');
            $(this).attr('checked', true);
        });
    });
});

function secsToHM(secs) {
    hours = parseInt(secs / 3600);
    minutes = parseInt(secs / 60) % 60;
    return (hours < 10 ? "0" + hours : hours) + ":" + (minutes < 10 ? "0" + minutes : minutes);
}
        
var todayChart;
$(document).ready(function() {

    todayChart = new Highcharts.Chart({
        chart: {
            renderTo: 'dayChart',
            zoomType: 'xy',
            spacingRight: 20
        },
        title: {
			align: 'left',
            text: 'Snapshot values for <?php echo date_format($displayDate, "d/m/Y"); ?>'
        },
        subtitle: {
			align: 'left',
            text: document.ontouchstart === undefined ?
                'Click and drag in the plot area to zoom in' :
                'Drag your finger over the plot to zoom in'
        },
        xAxis: {
            type: 'datetime',
            maxZoom: 1 * 3600, // 1 hours
            title: {
                text: null
            },
            labels : {
                formatter: function (){
                    return secsToHM(this.value);
                }
            }
        },
        yAxis: [
            {
                title: {
                    text: 'Power'
                },
                labels : {
                    formatter: function (){
                        return this.value + "W";
                    }
                },
                min: 0,
				max: 5000,
                startOnTick: false,
                showFirstLabel: false
            }, {
                title: {
                    text: 'Efficiency'
                },
                labels : {
                    formatter: function (){
                        return this.value + "%";
                    },
                    style: {
                        color: '#4572A7'
                    }
                },
                min: 0,
                max: 100,
                startOnTick: false,
                opposite: true
            }, {
                title: {
                    text: 'Production'
                },
                min: 0,
				max:50,
                startOnTick: false,
                opposite: true
            }
        ],
        tooltip: {
            /* 
            shared: true, 
            */
            formatter: function() {
                return secsToHM(this.x) + ' :<br/>'+ this.series.name +' = <b>'+ this.y + "</b>";
            }      
        },
        legend: {
			align: 'right',
			verticalAlign: 'top',
            floating: true
        },
        plotOptions: {
            area: {
                fillColor: {
                    linearGradient: [0, 0, 0, 300],
                    stops: [
                        [0, Highcharts.theme.colors[0]],
                        [1, 'rgba(2,0,0,0)']
                    ]
                },
                lineWidth: 1,
                marker: {
                    enabled: false,
                    states: {
                        hover: {
                            enabled: true,
                            radius: 5
                        }
                    }
                },
                shadow: false,
                states: {
                    hover: {
                        lineWidth: 1                  
                    }
                }
            }
        },   
        series: [
            {
                lineWidth: 1,
                type: 'scatter',
                name: 'Output power (W)',
                data: [
                    <?php echo $serPac; ?>		 
                ]
            }
            , {
                lineWidth: 1,
                type: 'scatter',
                name: 'Efficiency (%)',
                yAxis: 1,
                data: [
                    <?php echo $serEff; ?>		 
                ]
            }
            , {
                lineWidth: 1,
                type: 'scatter',
                name: 'Total (kWh)',
                yAxis: 2,
                data: [
                    <?php echo $serTot; ?>		 
                ]
            }
        <?php if ($compareDate != null) { ?>		 
            , {
                lineWidth: 1,
                type: 'scatter',
                name: 'Compared Power (W)',
                data: [
                    <?php echo $serPacCmp; ?>		 
                ]
            }
            , {
                lineWidth: 1,
                type: 'scatter',
                name: 'Compared Total (kWh)',
                yAxis: 2,
                data: [
                    <?php echo $serTotCmp; ?>		 
                ]
            }
        <?php } ?>		             
        ]
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
            <td align="left">Select a date: <input type="text" name="displayDate" id="displayDate" class="datepicker" value="<?php echo date_format($displayDate, "d/m/Y"); ?>" /></td>
            <td align="right">
                Compare with: <input type="text" name="compareDate" id="compareDate" class="datepicker" value="<?php if ($compareDate != null) echo date_format($compareDate, "d/m/Y"); ?>" />
                <input type="checkbox" name="mustFixDst" id="mustFixDst" <?php if ($mustFixDst) echo "checked='checked'"; ?> onChange="$('#dateForm').submit();return false;"/>Fix DST - 
                <a href="#" onClick="$('#compareDate').val('');$('#dateForm').submit();return false;">Clear</a>
            </td>
            </tr></table>
        </form>
        <div id="dayChart" style="width: 100%; height: 90%"></div>
    </body>
</html>