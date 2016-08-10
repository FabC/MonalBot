<html>
<head>
	<link href="monalbot.css" rel="stylesheet" type="text/css">
	<meta charset="utf-8" />
</head>
<body>  


<h1>Monitoraggio Allievi/Student Monitoring Tool - MonAlBot, v1.0</h1>
<p><span class="error">* required field.</span></p>

<form method="post" action="monalbot_wq.php">
	<table>  
	<tr>
		<td valign="top">List of users: </td>
		<td><textarea placeholder="Wikipedia users" cols="30" rows="10" name="listofusers"></textarea></td>
		<td valign="top" class="error">* </td>
	</tr>
	<tr>
		<td>Start date:</td>
		<td><input type="date" name="startdate" value="" size="32"></td>
		<td>(default 1-1-2016)</td>
	</tr>
	</table>

	<br/>
	<input type="submit" name="submit" value="Submit">  
</form>

<p>
<center>
    MonalBot - Monitoraggio Allievi/Student monitoring tool for the Wikipedia community - FabC, 2016</br>
    <img src="http://mirrors.creativecommons.org/presskit/buttons/88x31/png/by.png" alt="License CC-BY" height="20px">
</center>
</body>

