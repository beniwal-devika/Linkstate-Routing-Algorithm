<?php

/*
 * @Author : Devika Beniwal
 * Desc : Worker which performs various functions of Linkstate algorithm on terminal
 */


include "linkstateAlgorithm.php";

$handle = fopen('php://stdin', 'r'); // open the file 
label:
print "MENU : \n";
print "1. Create a Network Topology.\n";
print "2. Build a Connection Table.\n";
print "3. Shortest Path to Destination Router.\n";
print "4. Modify a Topology.\n";
print "5. Best Router for Broadcast.\n";
print "6. Exit.\n";


print "MASTER COMMAND : ";
fscanf($handle, "%d", $case); // get the input from the user 
print "\n";

switch ($case) {

    case 1:
        print "Input original network topology matrix data file\n";
        fscanf($handle, "%s", $fileName);
        if (empty($fileName)) {
            echo 'File name cannot be empty', "\n";
            break;
        }
        $matrix = getInputFileAndprintData($fileName);
        print_r($matrix); // print the matrix 
        print "\n";
        break;
    case 2:
        print "Select a source router: \n";
        print "Source Router : ";
        fscanf($handle, "%d", $sourceRouter);
        if (empty($matrix)) {
            print "\nPlease perform MASTER COMMAND 1 ";
            break;
        }

        if (empty($sourceRouter)) {
            echo 'Source Router is not valid', "\n";
            break;
        }

        $graph = getGraph($matrix);
        $totalRouters = count($graph);
        getTheConnectionTable($graph, $sourceRouter, $totalRouters);
        print "\n";
        break;
    case 3:
        if (empty($graph) || empty($sourceRouter)) {
            print "\nPlease select Source Router : ";
            fscanf($handle, "%d", $sourceRouter);
            if (empty($sourceRouter)) {
                echo 'Source Router is not valid', "\n";
                break;
            }
            $graph = getGraph($matrix);
            $totalRouters = count($graph);
        }
        print "Select the destination router: \n";
        print "Destination Router : ";
        fscanf($handle, "%d", $destinationRouter);

        if (empty($destinationRouter)) {
            echo 'Destination Router is not valid', "\n";
            break;
        }

        getTheShortestPath($destinationRouter, $graph, $sourceRouter);
        print "\n";
        break;
    case 4:
        if (empty($graph) || empty($sourceRouter)) {
            print "\nPlease select Source Router : ";
            fscanf($handle, "%d", $sourceRouter);
            $graph = getGraph($matrix);
            $totalRouters = count($graph);
        }

        if (empty($destinationRouter)) {
            print "\nSelect the destination router first: \n";
            print "Destination Router : ";
            fscanf($handle, "%d", $destinationRouter);
        }

        print "Select a router to be removed: \n";
        print "Down Router : ";
        fscanf($handle, "%d", $downRouter);

        if (empty($downRouter)) {
            echo 'Down Router is not valid', "\n";
            break;
        }

        if ($downRouter == $sourceRouter) {
            print "Source Router is down input it again\n";
            print "Source Router : ";
            fscanf($handle, "%d", $sourceRouter);
        } else if ($downRouter == $destinationRouter) {
            print "Destination Router is down input it again\n";
            print "Destination Router : ";
            fscanf($handle, "%d", $destinationRouter);
        }


        $graph = getNewGraphAfterRemovingRouter($graph, $downRouter);
        removeRouter($downRouter, $graph, $sourceRouter, $destinationRouter, $totalRouters);
        print "\n";
        break;
    case 5:
        if (empty($graph)) {
            $graph = getGraph($matrix);
            $totalRouters = count($graph);
        }
        getTheShortestFromAllRouters($graph);
        print "\n";
        break;
    case 6:
        print "Exit Good Bye!";
        exit;
        break;
    default:
        print "You have chosen a wrong option";
        break;
}

goto label;


/*
 * This method open the particular file and read it from the file.
 * Arguments : Filename 
 * Returns : Matrix 
 */

function getInputFileAndprintData($fileName) {
    $file = fopen($fileName, "r") or die("Unable to open file!");
    print "Review original topology matrix \n";
    $matrix = fread($file, filesize($fileName));
    return $matrix;
}

/*
 * This method creates the graph read from the file in a human readable from.
 * Arguments : Matrix
 * Returns : The customised graph with R1 , R2 as routers 
 */

function getGraph($matrix) {
    $data = explode("\n", $matrix);
    $countNoOfRouters = count($data);

    for ($i = 0; $i < $countNoOfRouters; $i++) {
        $customisedArray[$i] = 'R' . ($i + 1);
    }
    foreach ($data as $index => $row) {
        $data[$index] = explode(" ", $row);
    }
    $graph = array();
    foreach ($data as $row => $rowData) {
        foreach ($rowData as $col => $colData) {
            if (trim($colData) != '') {
                $colData = ($colData == -1) ? 0 : $colData;
                $graph[$customisedArray[$row]][$customisedArray[$col]] = $colData;
            }
        }
    }
    return $graph;
}

/*
 * This method creates the three sets  : key , mset and parent array to be used to 
 * perform Dijkstra's algorithm. 
 * Arguments : Graph , SourceRouter
 * Returns : 2-D Array with key, mset and parent as inner arrays
 */

function setKeyAndMset($graph, $source) {
    $return = array();
    foreach ($graph as $index => $row) {
        $key[$index] = INF;
        $parent[$index] = NULL;
        if ($source == $index) {
            $key[$index] = 0;
        }
        $mset[$index] = 'false';
    }
    $parent[$source] = $source;

    $return['key'] = $key;
    $return['mset'] = $mset;
    $return['parent'] = $parent;
    return $return;
}

/*
 * This method uses the  "dijkstrasAlgorithm", a function of linkstateAlgorithm class to
 * get connection table from tge defined source.
 * Arguments : Graph , SourceRouter
 * Display : Connection Table 
 */

function getTheConnectionTable($graph, $source, $totalRouters) {
    $source = 'R' . $source;
    $object = new linkstateAlgorithm();
    $return = setKeyAndMset($graph, $source);
    $key = $return['key'];
    $mset = $return['mset'];
    $parent = $return['parent'];
    $connectionTable = $object->dijkstrasAlgorithm($graph, $mset, $key, $source, $parent, 'connection_table');
    displayTable($connectionTable['connection_table'], $totalRouters);
}

/*
 * This method finds the shortest path and cost from source to destination.
 * Arguments : Graph , SourceRouter, DestinationRouter
 * Display : Path and Cost 
 */

function getTheShortestPath($destinationRouter, $graph, $source) {

    $destinationRouter = 'R' . $destinationRouter;
    $source = 'R' . $source;

    $return = setKeyAndMset($graph, $source);

    $key = $return['key'];
    $mset = $return['mset'];
    $parent = $return['parent'];

    $object = new linkstateAlgorithm();
    $returnData = $object->dijkstrasAlgorithm($graph, $mset, $key, $source, $parent, 'shortest_path', $destinationRouter);

    $path = $returnData['path'];
    $cost = $returnData['cost'];
    print "\nThe shortest path from Source " . $source . " to " . $destinationRouter . " is :  ";
    diplayPath($path);
    print "\n" . "Total cost is : " . $cost;
}

/*
 * This method creates the new grapg by removing a router.
 * Arguments : Graph , DownRouter
 * Returns : Modified Graph
 */

function getNewGraphAfterRemovingRouter($graph, $downRouter) {
    $downRouter = 'R' . $downRouter;
    $newGraph = $graph;
    unset($newGraph[$downRouter]);

    foreach ($newGraph as $row => $rowData) {
        foreach ($rowData as $col => $colData) {
            if ($downRouter == $col) {
                unset($newGraph[$row][$col]);
            }
        }
    }
    return $newGraph;
}

/*
 * This method removes a router from the network and returns new shortest path, cost and connection table
 * Arguments : Graph , DownRouter, SourceRouter, Destination Router
 * Display : Path, Cost, Connection Table
 */

function removeRouter($downRouter, $graph, $source, $destinationRouter, $totalRouters) {
    $object = new linkstateAlgorithm();
    $downRouter = 'R' . $downRouter;
    $source = 'R' . $source;
    $destinationRouter = 'R' . $destinationRouter;
    $return = setKeyAndMset($graph, $source);
    $key = $return['key'];
    $mset = $return['mset'];
    $parent = $return['parent'];
    $returnData = $object->dijkstrasAlgorithm($graph, $mset, $key, $source, $parent, 'connection_table', $destinationRouter);
    $path = $returnData['path'];
    $table = $returnData['connection_table'];
    $cost = !empty($returnData['cost']) ? $returnData['cost'] : 'Cannot Find Cost';
    displayTable($table, $totalRouters);
    print "\n";
    echo 'Path is : ';
    if (!empty($path)) {
        diplayPath($path);
    } else {
        echo 'No Path Exist', "\n";
    }

    if ($returnData['cost'] == INF) {
        $cost = 'Cannot Find Cost';
    }

    print "Total cost is : " . $cost;
}

/*
 * This method displays the path from source to destination
 */

function diplayPath($path) {

    for ($i = count($path) - 1; $i > 0; $i--) {
        echo $path[$i] . " -> ";
    }
    echo $path[0], "\n";
}

/*
 * This method finds the Broadcast router whose path summation from all other routers is minimum
 * Arguments : Graph
 * Displays : Broadcast Router , Sum
 */

function getTheShortestFromAllRouters($graph) {

    $object = new linkstateAlgorithm();
    $sum = array();
    foreach ($graph as $row => $data) {
        $source = $row;
        $return = setKeyAndMset($graph, $source);
        $key = $return['key'];
        $mset = $return['mset'];
        $parent = $return['parent'];
        $ret = $object->dijkstrasAlgorithm($graph, $mset, $key, $source, $parent, 'summation');
        $sum[$source] = $ret['sum'];
    }
    $retSum = findMinimumSum($sum);
    echo 'Broadcast Router : ' . $retSum['router'], "\n";

    if ($retSum['min_sum'] == '' || $retSum['min_sum'] == INF) {
        $retSum['min_sum'] = 'Cannot Find Cost';
    }
    echo 'Cost : ' . $retSum['min_sum'], "\n";
}

/*
 * This method finds minimum cost
 * Arguments : Cost of all the routers
 * Displays : Broadcast router and cost
 */

function findMinimumSum($sumArray) {
    $ret = array();
    $min = INF;
    foreach ($sumArray as $index => $value) {
        if ($min >= $value) {
            $min = $value;
            $minIndex = $index;
        }
    }
    $ret['router'] = $minIndex;
    $ret['min_sum'] = $min;
    return $ret;
}

/*
 * This method displays the connection table
 */

function displayTable($table, $totalRouters) {

    print "\n";
    $mask = "%10s |  %-30.30s\n";
    printf($mask, 'Destination', 'Interface');

    for ($i = 1; $i <= $totalRouters; $i++) {
        $router = 'R' . $i;
        if (array_key_exists($router, $table)) {
            $interface = ($table[$router] == '') ? '-' : $table[$router];
        } else {
            $interface = '-';
        }
        printf($mask, $router, $interface);
    }
}
