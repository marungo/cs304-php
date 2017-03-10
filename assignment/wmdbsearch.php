<html>
  <head>
    <meta charset="utf-8">
    <title>WMDB Search></title>
    <link rel='stylesheet' type='text/css' href="styles.css">
    <!-- ********************** BOOTSTRAP *********************** -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css"
	  integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
  </head>
  <body>

	<!-- form at the top of the page -->
	<div class='container'>
	  <form action='#' method='get' class='row'>
	    <select class="form-control offset-sm-2 col-sm-1" name='type'>
	      <option value='both'>both</option>
	      <option value='name'>name</option>
	      <option value='title'>movie title</option>
	    </select>
	    <input type="text" class="form-control col-sm-6" name='sought'>
	    <button type="submit" class="btn btn-primary col-sm-1">Submit</button>
	  </form>
	</div>

<?php

	require_once("/home/cs304/public_html/php/DB-functions.php");
	require_once('mngo_mhejmadi_dsn.inc');
	
	// global variables
	$dbh = db_connect($mngo_mhejmadi_dsn);
	$self = $_SERVER['PHP_SELF'];

	// these will be populated depending on the request that comes in
	$nm = isset($_REQUEST['nm']) ? $_REQUEST['nm']: -1;
	$tt = isset($_REQUEST['tt']) ? $_REQUEST['tt']: -1;
	$type = isset($_REQUEST['type']) ? $_REQUEST['type']: -1;
	$request = isset($_REQUEST['sought']) ? $_REQUEST['sought']: -1;


	//------------------ PREPARED QUERY TEMPLATES -----------------

	// given user search - returns name, birthday of all matches
	$sql_name = "SELECT distinct name,birthdate,nm from person  
				 where person.name like concat('%',?,'%');";

	// given nm - returns the name and birthdate of that person.
	$sql_single_name = "SELECT name,birthdate from person
						where person.nm=?;";

	// given nm - returns names of movies from particular actor
	$sql_name_movies = "SELECT title,movie.tt as tt from person,credit,movie
						where person.nm=?
						and person.nm=credit.nm 
						and credit.tt=movie.tt;";

	// given user search - returns number of matches
	$sql_name_count = "SELECT distinct count(*) from person  
				 	   where person.name like concat('%',?,'%');";

	// given user search - returns movies (title and release date)
	$sql_title = "SELECT distinct title,`release`,tt from movie
				  where movie.title like concat('%',?,'%');";
	
	// given tt - returns the title, release dat
	$sql_single_title = "SELECT title, `release` from
						movie where movie.tt=?;";

	// given user search - returns the number of movies that match
	$sql_title_count = "SELECT distinct count(*) from movie
				  where movie.title like concat('%',?,'%');";

	// given tt - returns the director of that movie
	$sql_title_director = "SELECT name from movie,person
						where movie.tt=? and movie.director=person.nm;";

	// given tt - returns the actors in that movie
	$sql_title_actors = "SELECT distinct name,birthdate,person.nm as nm from
						 movie,credit,person
						 where movie.tt=?
						 and movie.tt=credit.tt
						 and person.nm=credit.nm;";
	
	//------------------ END PREPARED QUERY TEMPLATES -----------------

    //----------------- FUNCTIONS -----------------

	// php function for echo-ing all of the names matched by a user search
	function display_all_names($resultset) {
		global $self, $request;
	      echo "Here are the people that match \"$request\":\n<ul>";
		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "<li class = \"multi_entries\"><a href='$self?nm=${row['nm']}'>${row['name']}  ${row['birthdate']}</a>";
		}
	    echo "</ul>\n";
	}

	// php function for echo-ing detailed information from a single person
	// based on a hyperlink that was clicked, providing an id for that person (nm)
	function display_single_name($resultset) {
		global $dbh, $self, $sql_name_movies, $nm, $tt;
 		echo "<div class = \"single_entry\">";
 		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<h3>${row['name']} \n was born on ${row['birthdate']}</h3>";
	    }
	    display_filmography_of_actor();
	}

	function display_filmography_of_actor() {
		global $dbh, $sql_name_movies, $nm;
 		$movies = prepared_query($dbh,$sql_name_movies,array($nm));
	    echo "<h5>Filmography:</h5><ul> ";
	    while($row = $movies->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<li><a href='$self?tt=${row['tt']}'>${row['title']}</a>";
	    }
		echo "</ul></div>";
	}

	// php function for echo-ing all of the titles matched by a user search
	function display_all_movies($resultset) {
		global $self, $request;
	    echo "Here are the movies that match \"$request\":\n<ul>";
		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    // echo "\t<li><a href='$self?tt=${row['tt']}'>${row['title']}  (${row['release']})</a>\n";
		    echo "<li class = \"multi_entries\"><a href='$self?tt=${row['tt']}'>${row['title']}  (${row['release']})</a>";
		 }
	    echo "</ul>\n";
	}

	// php function for echo-ing detailed information for a particular movie
	// based on a hyperlink clicked which provided an id for that movie (tt)
	function display_single_movie($resultset) {
		global $dbh, $self, $sql_title_actors, $sql_title_director, $nm, $tt;

		echo "<div class = \"single_entry\">";
		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "<h3>${row['title']}  (${row['release']}) </h3>";
		}
		display_director_of_movie();
		display_actors_of_movie();
	}

	function display_director_of_movie() {
		global $dbh,$sql_title_director, $tt;
		// display director
		$director = prepared_query($dbh,$sql_title_director,array($tt));
		while ($row = $director->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "<h6>directed by: ${row['name']}</h6>";
		}
	}

	function display_actors_of_movie() {
		global $dbh, $sql_title_actors, $tt;
		// display hyperlinks for all actors that database says acted in that movie
		$actors = prepared_query($dbh,$sql_title_actors,array($tt));
		echo "<h5>Cast:</h5><ul> ";
	    while($row = $actors->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<li><a href='$self?nm=${row['nm']}'>${row['name']}</a>";
	    }
		echo "</ul></div>";
	}

	// php function to just return the count of matches for a particular query
	function get_count($sql_count) {
		global $dbh, $request;
		$counter = prepared_query($dbh, $sql_count, array($request));
		$count = 0;
		while ($row = $counter->fetchRow(MDB2_FETCHMODE_ASSOC)) {
			$count = $row['count(*)'];
		}
		return $count;
	}
	// ------------------------ END FUNCTIONS -------------------------
	
	// FIRST: User-inputted form
	if ($type != -1) {

        $both_name_count = 0;
        $both_title_count = 0;

	    if ($type == 'name' or $type == 'both') {
		    $resultset_name = prepared_query($dbh,$sql_name,array($request));
		    $count_name = get_count($sql_name_count);

		    if ($count_name > 1) {
		      display_all_names($resultset_name);
	 	    } else if ($count_name == 1) {
	               if ($type != 'both') {
	                   display_single_name($resultset_name);
	               } else {
	                   display_all_names($resultset_name);
	               }
		    } else if ($type != 'both') {
		    echo "No names match \"$request\" :(";
		    }
	    }

	    if ($type == 'title' or $type == 'both') {
		    $resultset_title = prepared_query($dbh,$sql_title,array($request));
		    $count_title = get_count($sql_title_count);

		    if ($count_title > 1) {

		    display_all_movies($resultset_title);

		    } else if ($count_title == 1) {
	                if ($type != 'both') {
	                   display_single_movie($resultset_title);
	               } else {
	                   display_single_movie($resultset_title);
	               }
		    } else if ($type != 'both') {
		    echo "No movies match \"$request\" :(";
	    	}
	    }

	    // if no results
	    if ($type == 'both'and $count_name == 0 and $count_title == 0) {
                    echo "<h2>Literally nothing matched \"$request\" :(</h2>";
		} 
	} 

	// SECOND: if user clicks on a hyperlink of a movie
	else if ($tt != -1) {
		// print "searching titles";
		$resultset_title = prepared_query($dbh,$sql_single_title,array($tt));
		display_single_movie($resultset_title);
	} 

	// THIRD: if user clicks on a hyperlink of a person (actor or director)
	else if ($nm != -1) {
		// print "searching names";
		$resultset_name = prepared_query($dbh,$sql_single_name, array($nm));
		display_single_name($resultset_name);
	}

	?>

	</body>
	<!--- **************** BOOTSTRAP ******************* -->
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
