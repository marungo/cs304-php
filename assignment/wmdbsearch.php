<html>
	<head>
		<meta charset="utf-8">
		<title>WMDB Search></title>
		<link rel='stylesheet' type='text/css' href='css/wmdb-search.css'>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" 
		integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
	</head>
	<style>
		.row {
			margin-top: 10px;
		}
	</style>
	<body>

	<div class='container'>
		<form action='#' method='get' class='row'>
		   <select class="form-control offset-sm-2 col-sm-1" name='type'>
		      <option value='name'>name</option>
		      <option value='title'>movie title</option>
		      <option value='both'>both</option>
		    </select>
		    <input type="text" class="form-control col-sm-6" name='sought'>
		    <button type="submit" class="btn btn-primary col-sm-1">Submit</button>
		</form>
<!-- 		<div class='row' id='singleresult'>
			<div class='offset-lg-3 col-lg-6'>
				<h4 id='name_or_title'></h4>
				<h6 id='date'></h6>
			</div>
		</div> -->
	</div>

	<?php

	require_once("/home/cs304/public_html/php/DB-functions.php");
	require_once('mngo_mhejmadi_dsn.inc');

	$dbh = db_connect($mngo_mhejmadi_dsn);

	#------------------ PREPARED QUERY TEMPLATES -----------------

	// awaits a partial name - returns name, birthday of all matches
	$sql_name = "SELECT distinct name,birthdate,nm from person  
				 where person.name like concat('%',?,'%');";

	// awaits a name id: nm - returns the name and birthdate of that person.
	$sql_single_name = "SELECT name,birthdate from person
						where person.nm=?;";

	// awaits a name id: nm - returns names of movies from particular
	$sql_name_movies = "SELECT distinct title from person,credit,movie
						where person.nm=? 
						and person.nm=credit.nm 
						and credit.tt=movie.tt;";

	// awaits a partial name - returns number of matches
	$sql_name_count = "SELECT distinct count(*) from person  
				 	   where person.name like concat('%',?,'%');";

	// awaits a partial movie title - returns movies (title and release date)
	$sql_title = "SELECT distinct title,`release`,tt from movie
				  where movie.title like concat('%',?,'%');";
	
	// awaits a movie id: tt - returns the title, release dat
	$sql_single_title = "SELECT title, `release` from
					movie where movie.tt=?;";

	// awaits a partial movie title - returns the number of movies that match
	$sql_title_count = "SELECT distinct count(*) from movie
				  where movie.title like concat('%',?,'%');";

	// awaits a movie id: tt - returns the director of that movie
	$sql_title_director = "SELECT name from movie,person
						where movie.tt=? movie.director=person.nm;";

	// awaits a movie id: tt - returns the actors in that movie
	$sql_title_actors = "SELECT distinct name,birthdate from
						 movie,credit,person
						 where movie.tt=?
						 and movie.tt=credit.tt
						 and person.nm=credit.nm;";

	$type = $_REQUEST['type'];
	$request = $_REQUEST['sought'];
	$nm = $_REQUEST['nm'];
	$tt = $_REQUEST['tt'];
	$self = $_SERVER['PHP_SELF'];

	//----------------- FUNCTIONS -----------------
	function write_name_several($resultset) {
		global $self;
		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "<li><a href='$self?nm=${row['nm']}'>${row['name']}  ${row['birthdate']}</a>";
		}
	}

	function write_name_single($resultset) {
		global $dbh, $sql_name_movies;
 		$movies = prepared_query($dbh,$sql_name_movies,array($_REQUEST['nm']));
 		//make fancier later
 		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<h3>${row['name']} \n was born on ${row['birthdate']}</h3>";
	    }

	    echo "<ul>Filmography: ";
	    while($row = $movies->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<li>${row['title']}";
	    }	
	}

	function write_title_several($resultset) {
		global $self;
		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "\t<li><a href='$self?tt=${row['tt']}'>${row['title']}  (${row['release']})</a>\n";
		 }
	}

	function write_title_single($resultset) {
		global $dbh, $sql_title_actors;
		$actors = prepared_query($dbh,$sql_title_actors,array($_REQUEST['tt']));

		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "<h3>${row['title']}  (${row['release']}) </h3>";
		    echo "<h6>directed by: ${row['name']}</h6>";
		}

		echo "<ul>Cast: ";
	    while($row = $actors->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<li>${row['name']}";
	    }
	}

	function get_count($sql_count) {
		global $dbh, $request;
		$counter = prepared_query($dbh, $sql_count, array($request));
		$count = 0;
		while ($row = $counter->fetchRow(MDB2_FETCHMODE_ASSOC)) {
			$count = $row['count(*)'];
		}
		return $count;
	}
	# ------------------------ END FUNCTIONS -------------------------



	# ------------------------- LOGIC TREE ----------------------------
	if ($type != -1) {
		if ($type == 'name' or $type == 'both') {
			$resultset_name = prepared_query($dbh,$sql_name,array($request));
			$count_name = get_count($sql_name_count);

			if ($count_name > 1) {
				echo "Here are the people that match $request:\n";
				write_name_several($resultset_name);
		 	} else if ($count_name == 1) {
		 		// echo "Here is the person that matches $request:\n";
		 		write_name_single($resultset_name);
		 	} else if ($type != 'both') {
		 		echo "No names match $request :(";
		 	}
		}  

		if ($type == 'title' or $type == 'both') {
			$resultset_title = prepared_query($dbh,$sql_title,array($request));
			$count_title = get_count($sql_title_count);

			if ($count_title > 1) {
				echo "Here are the movies that match $request:\n";
				write_title_several($resultset_title);
			} else if ($count_title == 1) {
				// echo "Here is the movie that matches $request:\n";
				write_title_single($resultset_title);
			} else if ($type != 'both') {
				echo "No movies match $request :(";
			}
		}	

		if ($type == 'both' and $count_name == 0 and $count_title == 0) {
			echo "<h2>Literally nothing matched $request :(</h2>";
		}
	}




	// } else if ($type == 'both') {
	// 	$name_resultset = prepared_query($dbh,$sql_name,array($_REQUEST['sought']));
	// 	$title_resultset = prepared_query($dbh,$sql_title,array($_REQUEST['sought']));

	// 	$name_count_set = prepared_query($dbh,$sql_name_count,array($_REQUEST['sought']));
	// 	$name_count = 0;
	// 	while ($row = $name_count_set->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	// 		$name_count = $row['count(*)'];
	// 	}

	// 	$title_count_set = prepared_query($dbh,$sql_title_count,array($_REQUEST['sought']));
	// 	$title_count = 0;
	// 	while ($row = $title_count_set->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	// 		$title_count = $row['count(*)'];
	// 	}


	// 	if ($name_count > 0) {
	// 		echo "<h3>$name_count Names matched</h3>";
	// 		if ($name_count > 1) {
	// 			write_name_several($name_resultset);
	// 		} else {
	// 			write_name_single($name_resultset);
	// 		}
	// 	}

	// 	if ($title_count > 0) {
	// 		echo "<h3>$title_count Movies matched</h3>";
	// 		if ($title_count > 1) {
	// 			write_title_several($title_resultset);
	// 		} else {
	// 			write_title_single($title_resultset);
	// 		}
	// 	}

	// 	if (($title_count == 0) && ($name_count == 0)) {
	// 		$request = $_REQUEST['sought'];
	// 		echo "<h3> No Results for $request</h3>";
	// 	}

	// }
	// The resultset object has a fetchRow method that can give us back the
	// next row of the resultset, stored in an associative array with
	// keys named for the columns we asked for (nm,name). 

	?>
	</ol>

	</body>
	<script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" 
	integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" 
	crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" 
	integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" 
	crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" 
	integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" 
	crossorigin="anonymous"></script>
</html>