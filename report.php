<?php

/**
 * Use this file to output reports required for the SQL Query Design test.
 * An example is provided below. You can use the `asTable` method to pass your query result to,
 * to output it as a styled HTML table.
 */

$database = 'nba2019';
require_once('vendor/autoload.php');
require_once('include/utils.php');

/*
 * Example Query
 * -------------
 * Retrieve all team codes & names
 */
echo '<h1>Example Query</h1>';
$teamSql = "SELECT * FROM team";
$teamResult = query($teamSql);
// dd($teamResult);
echo asTable($teamResult);

/*
 * Report 1
 * --------
 * Produce a query that reports on the best 3pt shooters in the database that are older than 30 years old. Only 
 * retrieve data for players who have shot 3-pointers at greater accuracy than 35%.
 * 
 * Retrieve
 *  - Player name
 *  - Full team name
 *  - Age
 *  - Player number
 *  - Position
 *  - 3-pointers made %
 *  - Number of 3-pointers made 
 *
 * Rank the data by the players with the best % accuracy first.
 */
echo '<h1>Report 1 - Best 3pt Shooters</h1>';
// write your query here
$threepoint_shooters = ("SELECT roster.name AS player_name,team.name AS Full_team_name,player_totals.age, roster.number AS Player_Number,
roster.pos AS Position,(3pt/3pt_attempted)*100 AS 3pt_Percentage,player_totals.3pt AS 3pt_Made
FROM player_totals JOIN roster ON (player_totals.player_id = roster.id) JOIN team 
ON (roster.team_code = team.code) WHERE (3pt/3pt_attempted)>0.35 AND age>30  ORDER BY 3pt_percentage DESC");

$threepoint_shooter_result = query($threepoint_shooters);

echo asTable($threepoint_shooter_result);


/*
 * Report 2
 * --------
 * Produce a query that reports on the best 3pt shooting teams. Retrieve all teams in the database and list:
 *  - Team name
 *  - 3-pointer accuracy (as 2 decimal place percentage - e.g. 33.53%) for the team as a whole,
 *  - Total 3-pointers made by the team
 *  - # of contributing players - players that scored at least 1 x 3-pointer
 *  - of attempting player - players that attempted at least 1 x 3-point shot
 *  - total # of 3-point attempts made by players who failed to make a single 3-point shot.
 * 
 * You should be able to retrieve all data in a single query, without subqueries.
 * Put the most accurate 3pt teams first.
 */
echo '<h1>Report 2 - Best 3pt Shooting Teams</h1>';
// write your query here
$best_3points_teams = ("SELECT team.name,SUM(player_totals.3pt) AS total_3pt,SUM(player_totals.3pt_attempted) AS total_Attemp,
(SUM(player_totals.3pt)/ SUM(player_totals.3pt_attempted)*100)AS Team_accuracy,
COUNT(player_totals.3pt) AS Number_players_scored,
 SUM(player_totals.3pt=0) AS failed
FROM player_totals JOIN roster ON (player_totals.player_id = roster.id) JOIN team 
ON (roster.team_code = team.code) GROUP BY team_code     
ORDER BY team_accuracy DESC");

$best_3points_teams_result = query($best_3points_teams);

echo asTable($best_3points_teams_result);

?>