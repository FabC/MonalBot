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


function get_users_list_from_URL($host, $user_translation, $page_name)
{
	// Specify the User-Agent header to identify the client

	$opts = array('http' =>
	  array(
	    'user_agent' => 'MonAlBot/0.1 (fabrizio.carrai@gmail.com)'
	  )
	);
	$context = stream_context_create($opts);

	// Query for page content

	$url = $host . '/w/api.php?action=query&format=json&prop=revisions&rvprop=content&rvlimit=1&titles=' . $page_name;
	$response = file_get_contents($url, FALSE, $context);

	$p = json_decode($response, TRUE);
	$p1=$p["query"]["pages"];

	$first_key = key ($p1);

	$page_content = $p1[$first_key]['revisions'][0]['*'];

	// Identifies all the users on the page.
	// This regex works : [[(?i)Utente:(.*?)]] (square brackets to be escaped), in human terms:
	// - Found all the text starting with "[[Utente:", ignoring pattern case (?i)
	// - Extract the text till the "]] pattern is found

	preg_match_all ("/\[\[(?i)" . $user_translation .":(.*?)\]\]/", $page_content, $users_found);

	$users_list = [];

	foreach ($users_found[1] as $user)
	{
		$user = str_replace(" ", "_", $user);		// Replace space with underscoare
		if (strpos($user, "|"))				// Trim out full/printable names
			$user = substr($user, 0, strpos($user, "|"));

		$users_list[] = $user;
	}

	return $users_list;
}
?>
