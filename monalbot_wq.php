<html>
 <head>
	<title>MONALBOT, v1.0</title>
	<link href="monalbot.css" rel="stylesheet" type="text/css">
	<meta charset="utf-8" />
 </head>
 <body>

<?php
/*
    This file is part of MonAlBot.

    MonAlBot is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MonAlBot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with MonAlBot.  If not, see <http://www.gnu.org/licenses/>.
*/

include 'get_user_list_from_wiki_page.php';


/*
	Default parameters and constants
	
	$host		Valid Wikipedia host name, normally it dependes from the language in use: e.g.
			https://it.wikipedia.org, https://en.wikipedia.org, ...

	$user_translation	Translation of the word "user" in the used wikipedia language.
			
	$listofusers	URL of a wiki page with user ID to query for. Users can be in any order, but
			must be in the format [[Utente:ID]] or [[Utente:ID|Full user name]]

			OR

			List of users separated by "|"
	$startdate	Initial date of the analysis
*/

$host = 'https://it.wikipedia.org';
$user_translation = "Utente";

//$listofusers = 'https://it.wikipedia.org/wiki/Utente:Giaccai/formazione/allievi';
//$listofusers = 'FabC|Giaccai';
$startdate = '2016-01-01';

/*
===========================================================================
	Get input parameters (set default, data formatting)
===========================================================================
*/
if (count($_POST)==0)
{
	// No HTTP POST parameter, get command line parameters (useful to debug the module)

	if ($argc>1)
	{
		$listofusers = $argv[1];
		if ($argc>2)
		{
			$startdate   = $argv[2];
		}
	}


}
else					
{
	// Parameters passed via the HTTP POST method

	$listofusers = $_POST["listofusers"] ?: $listofusers;
	$listofusers = rtrim($listofusers);
	$listofusers = str_replace("\n","|",$listofusers);
	$listofusers = str_replace(" ","_",$listofusers);

	$startdate   = $_POST["startdate"] ?: $startdate;
}

$startdate   .= "T00:00:00Z";	// Specify time  

// if the 'listofusers' is a valid URL then get the names from that page
// the URL is expected to be similar to https://it.wikipedia.org/wiki/Utente:FabC

if (filter_var($listofusers, FILTER_VALIDATE_URL) == TRUE)
{
	// Extract the page name

	$pagenameafter = "wikipedia.org/wiki/";
	$page_name = substr($listofusers, strpos($listofusers, $pagenameafter) + strlen($pagenameafter) );

	// echo "Fetching data from " . $page_name . "\n";

	$v = get_users_list_from_URL($host, $user_translation, $page_name);


	// Concatenate the list of users and use the "|" as separator

	$listofusers = implode ("|", $v);
}


// Specify the User-Agent header to identify the client

$opts = array('http' =>
  array(
    'user_agent' => 'MonAlBot/1.0 (fabrizio.carrai@gmail.com)'
  )
);
$context = stream_context_create($opts);


/*
===========================================================================
		Get users' information
===========================================================================
*/

$usersinfo = [];
$cont_key = "";
do
{
	$url = $host . '/w/api.php?action=query&list=users&ususers='. $listofusers. '&usprop=blockinfo|groups|editcount|registration|emailable|gender&format=json';
	$response = file_get_contents($url, FALSE, $context);
	$t = json_decode($response, TRUE);

	$cont_key = isset($t["continue"]) ? $cont_key = $t["continue"]["uccontinue"] : "";

	$usersinfo = array_merge ($usersinfo, $t["query"]["users"]);
} while ($cont_key<>"");


echo "<h1>Monitoraggio Allievi/Student monitoring tool - MonAlBot, v1.0</h1>";
echo "There are data for " . count($usersinfo) . " user(s):</p>";


?>
<table border="0">
<?php

$i=0;
foreach ($usersinfo as $user)
{
	if ($i==0)
		echo "<tr>";
		
	echo "<td width='10%'><a href='#" . $user["name"] .  "'><span class='userindex'>" . $user["name"] . "</span></a></td>";
	$i++;

	if ($i>=10)
	{
		echo "</tr>";
		$i=0;
	}
}

if ($i!=0)
	echo "</tr>";
?>
</table>
</p>
<?php


/*
===========================================================================
		Scan the users list
===========================================================================
*/

foreach ($usersinfo as $user)
{
	echo "</p><h1>";
	echo "<a name='" . $user["name"] . "'</a>";
	echo "Name          : <span class='username'>" . $user["name"] . "</span></br>";
	echo "</h1>";

	echo "Registered on : " . $user["registration"] . "</br>";
	echo "Total edits   : " . $user["editcount"] . " (including edits on user pages and other pages)</br>";


	// Get user's contributes to Wikipedia (ucnamespace = 0 , https://en.wikipedia.org/wiki/Wikipedia:Namespace)

	$usercontrib = [];
	$cont_key = "";
	do
	{
		$user["name"] = str_replace(" ","_",$user["name"]);
		$url = $host . '/w/api.php?action=query&format=json&list=usercontribs&uclimit=250&ucstart=' . $startdate . '&ucuser=' . $user["name"] . '&ucdir=newer&ucnamespace=0';
		if ($cont_key<>"") $url = $url . '&uccontinue=' . $cont_key;

		$response = file_get_contents($url, FALSE, $context);
		$t = json_decode($response, TRUE);

		// Test if the query could not report all the results in one call

		if (isset($t["continue"]))
		{
			$cont_key = $t["continue"]["uccontinue"];
		}
		else
			$cont_key = "";

		$usercontrib = array_merge($usercontrib, $t["query"]["usercontribs"]);

	} while ($cont_key<>"");

	echo "Since " . $startdate. " the user has contributed on " . count($usercontrib) . " Wikipedia article(s)";


	// Get user edits (ucnamespace = 2 , https://en.wikipedia.org/wiki/Wikipedia:Namespace)


	$userpagesedit = [];
	$cont_key = "";
	do
	{
		$user["name"] = str_replace(" ","_",$user["name"]);
		$url = $host . '/w/api.php?action=query&format=json&list=usercontribs&uclimit=250&ucstart=' . $startdate . '&ucuser=' . $user["name"] . '&ucdir=newer&ucnamespace=2';
		if ($cont_key<>"") $url = $url . '&uccontinue=' . $cont_key;

		$response = file_get_contents($url, FALSE, $context);
		$t = json_decode($response, TRUE);

		// Test if the query could not report all the results in one call

		if (isset($t["continue"]))
		{
			$cont_key = $t["continue"]["uccontinue"];
		}
		else
			$cont_key = "";

		$userpagesedit = array_merge($userpagesedit, $t["query"]["usercontribs"]);

	} while ($cont_key<>"");

	echo " and " . count($userpagesedit) . " user pages(s):</p>";

	// *********************************************************************************************

	// List the user contributes to Wikipedia
?>
	<h3>Contributes to Wikipedia</h3>

	<table border="1">
		<tr>
			<th>No.</th>
			<th style='width:20ch'>Date</th>
			<th>Title</th>
			<th>Comment</th>
			<th>History</th>
		</tr>

<?php

	for($i=0; $i<count($usercontrib); $i++)
	{
		$wikipage=$usercontrib[$i];
		$linecount = $i+1;

		echo "<tr>\n\n";
		echo "<td>" . $linecount . "</td>";
		echo "<td>" . $wikipage["timestamp"] . "</td>";
		echo "<td>" . "<a href=\"" .$host . "/wiki/" . $wikipage["title"] .  "\" target=\"_blank\">" . $wikipage["title"] . "</a></td>";
		echo "<td>" . htmlspecialchars($wikipage["comment"]) . "</td>";
		echo "<td>" . "<a href=\"" .$host . "/w/index.php?title=" . $wikipage["title"] . "&action=history\">" . "History</a></td>";
		echo "</tr>";
	}
?>
	</table></p>
<?php

	// List the user edits
?>

	<h3>User page edits</h3>

	<table border="1">
		<tr>
			<th>No.</th>
			<th style='width:20ch'>Date</th>
			<th>Title</th>
			<th>Comment</th>
			<th>History</th>
		</tr>

<?php

	for($i=0; $i<count($userpagesedit); $i++)
	{
		$wikipage=$userpagesedit[$i];
		$linecount = $i+1;

		echo "<tr>\n\n";
		echo "<td>" . $linecount . "</td>";
		echo "<td>" . $wikipage["timestamp"] . "</td>";
		echo "<td>" . "<a href=\"" .$host . "/wiki/" . $wikipage["title"] .  "\" target=\"_blank\">" . $wikipage["title"] . "</a></td>";
		echo "<td>" . htmlspecialchars($wikipage["comment"]) . "</td>";
		echo "<td>" . "<a href=\"" .$host . "/w/index.php?title=" . $wikipage["title"] . "&action=history\">" . "History</a></td>";
		echo "</tr>";
	}
?>
	</table></p>


<?php
}
?>
    </p></p></hr><center>
    MonalBot - Monitoraggio Allievi/Student monitoring tool for the Wikipedia community - FabC, 2016</br>
	<img src="http://mirrors.creativecommons.org/presskit/buttons/88x31/png/by.png" alt="License CC-BY" height="20px"/>
	</center>
  </body>
</html>
