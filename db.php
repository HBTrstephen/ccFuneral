<?php
//
//*********************************************************************************
//2021 07 08 Weather replaces fortune
// 2021 08 05 Add location IP
// 2021 08 08 Loaton fix. Add colur
//*********************************************************************************
// Get Site IP  ;
$locationIP=$_SERVER['REMOTE_ADDR'] ;
$locationHost=$_GET['host'];

// Variables 
$servername = "localhost";
$username = "scdb";
$password = "g30rg3";
$dbname = "ccschedule";

$i=0;
$max=0 ;
$day=array() ;
$idx=array() ;
$location=array() ;
$fortune=`/usr/games/fortune -s -n 100` ;
$nCol=7 ;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Get the location record 
$sql = "SELECT * from location where ipAddress = '" . $locationIP . "'" ;

$dataLocation = $conn->query($sql);
// No location against the IP - then go run the config.php to set it
if ($dataLocation->num_rows == 0) 
	{
	header("Location: http://" . $_SERVER['HTTP_HOST'] . "/config.php");
	}
//TODO Put an update in here to update the location IP and random hostname

// It will be one record so this is one call
$location = $dataLocation->fetch_assoc()  ;
// Set some defaults

$webRefresh=$location['webRefresh'];
$nCol=$location['webColumns'];
if ( $webRefresh < 5 ) { $webRefresh=5 ; }
if ( $nCol < 4 ) { $nCol=4 ; }

$fortune=$location['weatherParse'] ;

// base html into a string 
$html1="

<!DOCTYPE html>
<html>
<head>
<title>Work Orders</title>
<meta http-equiv='refresh' content='" . $webRefresh . "'> 
<style>
body {background-color: whitesmoke;}
.grey {
  background-color: rgba(128,128,128,.25);
}
.red {
  background-color: rgba(255,0,0,.35);
}
.blue {
  background-color: rgba(0,0,255,.25);
}
.quoteDiv {
	color: white ;
	font-size: 19px ;
  	font-family: Arial, Helvetica, sans-serif;
  	text-align: center;
  	background-color: #04AA6D;
	padding: 10px
}
.timeDiv {
	color: white ;
	font-size: 25px ;
  	font-family: Arial, Helvetica, sans-serif;
  	text-align: center;
  	background-color: #04AA6D;
  	border: 3px solid #ffffff;
}
#workorder {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
  text-align: center;
  font-size: 20px ;
}

#workorder td, #workorder th {
  border: 3px solid #ffffff;
  padding: 10px;
}

#workorder th {
  padding-top: 18px;
  padding-bottom: 18px;
  text-align: center;
  background-color: #04AA6D;
  color: white;
	}

#CSSoverdue {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 70%;
margin-left: auto;
  margin-right: auto;
  text-align: left;
  font-size: 16 ;
}

#CSSoverdue td, #CSSoverdue th {
  border: 3px solid #ffffff;
  padding: 12px;
}
#CSSoverdue tr:nth-child(odd) {
    background-color: rgba(128,128,128,.25);
	}
#CSSoverdue tr:nth-child(even) {
  background-color: rgba(0,0,255,.25);
	}
#CSSoverdue th {
    background-color: cyan;
    color: red;
    font-size: 23;
	}
}
</style>
</head>
<body>

";

// Go get data
// Note most of the date format and manipulation is completed in the SQL statement
//and location =" . $location['id'] . "  
$sql = "SELECT datediff(workDate,now()) as dateNum, 
DATE_FORMAT(workDate,'%D %M') as dateDate,
DATE_FORMAT(workDate,'%W') as dateDay,
TIME_FORMAT(workTime,'%h:%i%p - ' ) as timeDay,
deceased   , 
workDescription    , 
location , 
plot FROM workorder 
where 
datediff(workdate,now()) > -1 
and datediff(workdate,now()) < " . $nCol . "  
and complete <> 1
order by workDate  ";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    // output data of each row in day array
    // WARNING : This is a tricky pull - it builds the array for that the html
    // can work row by row. Be super careful in adjusting anything 
    while($row = $result->fetch_assoc()) {
	$dateNum=$row["dateNum"];
	$day[$dateNum][0]=$row["dateDay"]."<br>".$row["dateDate"];

	// If this is the cell for TODAY remove the <br> from the html 
	if ( $dateNum==0 ) {$day[$dateNum][0]=$row["dateDay"]." ".$row["dateDate"];} ;

	$day[$dateNum][++$idx[$dateNum]]=$row["timeDay"]  . $row["deceased"]." " . "<br>" .$row["workDescription"]   ;
	if ( $max<$idx[$dateNum]) {$max=$idx[$dateNum];}
    }
} 
$today=$day[0][0];
$day[0][0]="TODAY";

//Decided to get the overdue stuff in a seperate SQL - Trying to do all in one was getting messy.
$sql = "SELECT datediff(now(), workDate) as dayOverdue, 
	DATE_FORMAT(workDate,'%D %M') as dateDate,
	DATE_FORMAT(workDate,'%W') as dateDay, 
	deceased   , 
	workDescription    , 
	location , 
	plot 
	FROM workorder 
	where datediff(workdate,now()) < 0  
	and complete <> 1 order by workDate  
	and location = " . $location['id']   ;

$SQLoverdue = $conn->query($sql);

if ($SQLoverdue->num_rows > 0) {
    // output data of each row in day array
    while($row = $SQLoverdue->fetch_assoc()) {
	$overdue[]="<tr><td> " . $row["deceased"]."</td><td> " . $row["dateDate"] . "</td><td>" .  $row["dayOverdue"] . "</td><td> " .$row["workDescription"]  . "</td></tr>" ;
    }
} 



$conn->close();
// Got the data now
// Show it..
echo $html1 ;
echo "<br><div class='timeDiv'>" . $location['name'] . " Work Orders For Next " . $nCol . " Days<br>". $today ."&nbsp" .date('h:iA') ."</div>";
echo "<table  id='workorder' style='width=100%'> <colgroup>
    <col class='red'  /> " ;
for ( $col=0; $col < $nCol ; $col+=2 )
	{
	echo " <col class='grey' /> <col class='blue' /> ";
	}
  echo "</colgroup>" ;
echo "<tr>";

for ($loc=0;$loc < $nCol ; $loc++)
	{
if(isset($day[$loc][0]) )
                {
                echo "<th>" ;
                echo $day[$loc][0] ;
                echo "</th>";
                }else { echo "<th> &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp </th>";}
}       	
echo "</tr>";

for ($col = 1; $col < $max+1; $col++) {
  echo "<tr>";
  for ($loc = 0; $loc < $nCol; $loc++) {
    
    if(isset($day[$loc][$col]) )
		{
		echo "<td>" ;
		echo $day[$loc][$col] ;
		echo "</td>";
		}
	else {echo "<td>   </td>"; }
}
  echo "</tr>";
}
echo "</table>";
echo "<div class='quoteDiv'> <p>". $fortune ."</p></div>";

if (sizeof($overdue) >0 )
	{

	echo "<table id='CSSoverdue' style='width=100%'> " ;
	echo "<th>Deceased</th><th>Work Date</th><th>Days overdue</th><th>Work</th>";
	foreach ( $overdue as $TXToverdue )
		{
		echo $TXToverdue ;
		}
	echo "</table>" ;
	}
?>

</body>
</html>
