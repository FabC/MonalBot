<html>
 <head>
	<title>MONALBOT, v0.4</title>
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


// Default parameters and constants

$host = 'https://it.wikipedia.org';
$listofusers = 'https://it.wikipedia.org/wiki/Utente:Giaccai/formazione/allievi';
//$listofusers = 'FabC|Giaccai';
$startdate = '2016-01-01T00:00:00Z';

// Input parameters (default and formatting)

if (count($_POST)==0)			// Get paramaters from command line
{
	if ($argc>1)
	{
		$listofusers = $argv[1];
		if ($argc>2)
		{
			$startdate   = $argv[2];
		}
	}


}
else					// Parameters passed via the HTTP POST method
{
	$listofusers = $_POST["listofusers"] ?: $listofusers;
	$listofusers = rtrim($listofusers);
	$listofusers = str_replace("\n","|",$listofusers);
	$listofusers = str_replace(" ","_",$listofusers);

	$startdate   = $_POST["startdate"]   ?: $startdate;
}

// if the 'listofusers' is a valid URL then get the names from that page
// the URL is expected to be similar to https://it.wikipedia.org/wiki/Utente:FabC

if (filter_var($listofusers, FILTER_VALIDATE_URL) == TRUE)
{
	// Extract the page name
	$pagenameafter = "wikipedia.org/wiki/";
	$page_name = substr($listofusers, strpos($listofusers, $pagenameafter) + strlen($pagenameafter) );

	echo "Fetching data from " . $page_name . "\n";

	$v = get_users_list_from_URL($host, $page_name);

	// Concatenate the list of users and use the "|" as separator
	$listofusers = implode ("|", $v);
}


// Specify the User-Agent header to identify the client

$opts = array('http' =>
  array(
    'user_agent' => 'MonAlBot/0.4 (fabrizio.carrai@gmail.com)'
  )
);
$context = stream_context_create($opts);

// Get information about a list of users

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


echo "<h1>Monitoraggio Allievi - MonAlBot, v0.4 (Beta version), FabC</h1>";
echo "There are data for " . count($usersinfo) . " user(s):</p>";


// Loop on each user

foreach ($usersinfo as $user)
{
	echo "Name          : <span class='username'>" . $user["name"] . "</span></br>";
	echo "Registered on : " . $user["registration"] . "</br>";
	echo "Total edits   : " . $user["editcount"] . " (including edits on user pages and other pages)</br>";

	// Get user's contributes

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

	echo "Since " . $startdate. " the user has contributed on " . count($usercontrib) . " Wikipedia article(s):</p>";

?>
	<table border="1">
		<tr>
			<th>No.</th>
			<th style='width:20ch'>Date</th>
			<th>Title</th>
			<th>Comment</th>
			<th>History</th>
		</tr>

<?php

	// List the user contributes

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
}
?>
 </body>
</html>
