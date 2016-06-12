<html>
<head>
	<link href="monalbot.css" rel="stylesheet" type="text/css">
	<meta charset="utf-8" />
</head>
<body>  


<h1>Monitoraggio Allievi - MonAlBot, v0.4 (Beta version), FabC</h1>
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
		<td><input type="text" name="startdate" value="" size="32"></td>
		<td >Use the format yyyy-mm-ddThh:mm:ssZ, e.g. 2009-05-09T10:07:17Z
	</tr>
	</table>

	<br/>
	<input type="submit" name="submit" value="Submit">  
</form>
</body>

