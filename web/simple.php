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

    $conn = mysql_pconnect("localhost", "sma", "<SMA_PASSWORD>") or die ("Connection Failure to Database");
    mysql_select_db("sma", $conn) or die ("Database not found.");

    $lastDate = date("Y-m-d");
    $query="select max(logdate) from logged_values";
    $result = mysql_query($query) or die("Failed Query");
    if ($thisrow=mysql_fetch_row($result)) {
        $lastDate = $thisrow[0];
    }
    mysql_free_result($result);

    $query="select 60 * ((60 * time_format(logtime, '%H')) + time_format(logtime, '%i')), pac_watt from logged_values where logdate='".$lastDate."' order by logtime";
    $result = mysql_query($query) or die("Failed Query");
    $ser1 = "";
    while ($thisrow=mysql_fetch_row($result)) {
        if ($ser1 != "") {
            $ser1 = $ser1.", ";
        }
        $ser1 = $ser1."{x:".$thisrow[0].",y:".$thisrow[1]."}";
    }
    mysql_free_result($result);

    mysql_close($conn);
?>
<html>
    <head>
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>
        <script src="js/highcharts.js" type="text/javascript"></script>
        <script type="text/javascript">
$(document).ready(function() {
    new Highcharts.Chart({
        chart: {
            renderTo: 'dayChart',
        },
        series: [
            {
                type: 'scatter',
                lineWidth: 1,
                data: [<?php echo $ser1; ?>]
            }
        ]
    });
});   
        </script>
    </head>
    <body>
        <div id="dayChart" style="width: 600px; height: 400px"></div>
    </body>
</html>