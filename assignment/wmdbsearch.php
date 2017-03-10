<html>
  <head>
    <meta charset="utf-8">
    <title>WMDB Search></title>
    <link rel='stylesheet' type='text/css' href="styles.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css"
	  integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
  </head>
  <style>
    .row {
    margin-top: 10px;
    }

    li {

    }
  </style>
  <body>

<!-- Want to fix this because it doesn't shrink with page. Meh. -->
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
  <div class='row' id='singleresult'>
    <div class='offset-lg-3 col-lg-6'>
      <h4 id='name_or_title'></h4>
      <h6 id='date'></h6>
    </div>
  </div>
</div>

<?php

	require_once("/home/cs304/public_html/php/DB-functions.php");
	require_once('mngo_mhejmadi_dsn.inc');

	$dbh = db_connect($mngo_mhejmadi_dsn);

	#------------------ PREPARED QUERY TEMPLATES -----------------

	$sql_name = "SELECT distinct name,birthdate from person
				 where person.name like ?;";

	$sql_name_movies = "SELECT distinct title from person,credit,movie
			    where person.name like concat('%',?,'%')
			    and person.nm=credit.nm and credit.tt=movie.tt;";

	$sql_name_count = "SELECT distinct count(*) from person
			   where person.name like concat('%',?,'%');";

        $sql_title = "SELECT distinct title,`release`,name from
		      movie,person
		      where movie.title like concat('%',?,'%')
		      and  movie.director=person.nm;";

	$sql_title_count = "SELECT distinct count(*) from
				  movie,person as director
				  where movie.title like concat('%',?,'%')
				  and movie.director=director.nm;;";

	$sql_title_actors = "SELECT distinct name from
			  movie,credit, person
			  where movie.title like concat('%',?,'%')
			  and movie.tt=credit.tt
			  and person.nm=credit.nm;";

	$type = isset($_REQUEST['type']) ? $_REQUEST['type']: -1;
	$request = isset($_REQUEST['sought']) ? $_REQUEST['sought']: -1;
	$self = $_SERVER['PHP_SELF'];

   //----------------- FUNCTIONS -----------------

	function write_name_several($resultset) {
		global $self, $request;
	      echo "Here are the people that match \"$request\":\n<ul>";
		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "<li class = \"multi_entries\"><a href='$self?type=name&sought=${row['name']}'>${row['name']} ${row['birthdate']}</a>";
		}
	    echo "</ul>\n";
	}

	function write_name_single($resultset) {
		global $dbh, $self, $sql_name_movies;
 		$movies = prepared_query($dbh,$sql_name_movies,array($_REQUEST['sought']));
 		echo "<div class = \"single_entry\">";
 		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<h3>${row['name']} \n was born on ${row['birthdate']}</h3>";
	    }
	    echo "<h5>Filmography:</h5><ul> ";
	    while($row = $movies->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<li><a href='$self?type=title&sought=${row['title']}'>${row['title']}</a>";
	    }
		echo "</ul></div>";
	}

	function write_title_several($resultset) {
		global $self, $request;
	    echo "Here are the movies that match \"$request\":\n<ul>";
		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "\t<li class = \"multi_entries\"><a href='$self?type=title&sought=${row['title']}'>${row['title']}  (${row['release']})</a>\n";
		 }
	    echo "</ul>\n";
	}

	function write_title_single($resultset) {
		global $dbh, $self, $sql_title_actors;
		$actors = prepared_query($dbh,$sql_title_actors,array($_REQUEST['sought']));
		echo "<div class = \"single_entry\">";
		while($row = $resultset->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		    echo "<h3>${row['title']}  (${row['release']}) </h3>";
		    echo "<h6>directed by: ${row['name']}</h6>";
		}
		echo "<h5>Cast:</h5><ul> ";
	    while($row = $actors->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	    	echo "<li><a href='$self?type=name&sought=${row['name']}'>${row['name']}</a>";
	    }
		echo "</ul></div>";
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
          $both_name_count = 0;
          $both_title_count = 0;
	  if ($type == 'name' or $type == 'both') {
	    $resultset_name = prepared_query($dbh,$sql_name,array(concat('%',$request,'%')));
	    $count_name = get_count($sql_name_count);

	    if ($count_name > 1) {
	      write_name_several($resultset_name);
 	    } else if ($count_name == 1) {
               if ($type != 'both') {
                   write_name_single($resultset_name);
               } else {
                   write_name_several($resultset_name);
               }
	    } else if ($type != 'both') {
	    echo "No names match \"$request\" :(";
	    }
	    }

	    if ($type == 'title' or $type == 'both') {
	    $resultset_title = prepared_query($dbh,$sql_title,array($request));
	    $count_title = get_count($sql_title_count);

	    if ($count_title > 1) {

	    write_title_several($resultset_title);

	    } else if ($count_title == 1) {
                if ($type != 'both') {
                   write_title_single($resultset_title);
               } else {
                   write_title_single($resultset_title);
               }
	    } else if ($type != 'both') {
	    echo "No movies match \"$request\" :(";
	    }
	    }

	    if ($type == 'both'and $count_name == 0 and $count_title == 0) {
                    echo "<h2>Literally nothing matched \"$request\" :(</h2>";
	    }
	    }
	?>

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
