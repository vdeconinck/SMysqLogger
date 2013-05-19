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
?>

<html>
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
   <meta name="description" content="DB Values dump">
   <title>DB Values dump</title>
</head>
<body>

<?php
  $conn = mysql_pconnect("localhost", "sma", "<SMA_PASSWORD>") or die ("Connection Failure to Database");

  mysql_select_db("sma", $conn) or die ("Database not found.");
  $query="select * from logged_values";
  $result = mysql_query($query) or die("Failed Query");
  $i=0;
  echo "<table border='1'><tr>";
  while ($i < mysql_num_fields($result))
  {
    $field_name=mysql_fetch_field($result, $i);
    echo "<th>", $field_name->name, "</th>";
    $i++;
  }
  echo "</tr>";
  while ($thisrow=mysql_fetch_row($result))  //get one row at a time
  {
    echo "<tr>";
    $i=0;
    while ($i < mysql_num_fields($result))  //print all items in the row
    {
      echo "<td>", $thisrow[$i], "</td>";
      $i++;
    }
    echo "</tr>";
  }
  echo "</table>";
  mysql_free_result($result);
  mysql_close($conn);
?>

</body>
</html>

