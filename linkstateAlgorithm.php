<?php

/*
 * @Author : Devika Beniwal
 * Desc : A class which implements Linkstate protocol using Dijkstras algorithm
 */

class linkstateAlgorithm {
    /*
     * Constructor initialization
     */

    function __construct() {
        
    }

    /*
     * This method is used to find the minimum distance value from the "key set"
     * Arguments : key , mset array
     * Return : Index with minimum distance value.
     */

    function findMinimumKey($key, $mset) {
        $min = INF;
        foreach ($key as $index => $value) {
            if ($mset[$index] == 'false' && $min >= $value) {
                $min = $value;
                $minIndex = $index;
            }
        }
        return $minIndex;
    }

    /*
     * Dijkstras algorithm finds the shortest path from source to all other routers.
     * Arguments : Graph, key, mset , source, parent, case (Based on the requirements) , destination (optional)
     * Returns : Return an array based on case statement
     */

    function dijkstrasAlgorithm($graph, $mset, $key, $source, $parent, $case, $destination = NULL) {
        for ($i = 0; $i < count($graph); $i++) {
            $row = $this->findMinimumKey($key, $mset);
            $mset[$row] = 'true'; // Visited Node
            foreach ($graph[$row] as $col => $data) {
                if ($graph[$row][$col] && $mset[$col] == 'false' && $key[$row] != INF && $key[$col] > $graph[$row][$col] + $key[$row]) {
                    $key[$col] = $graph[$row][$col] + $key[$row];
                    $parent[$col] = $row;
                }
            }
        }

        $return = array();

        /*
         * Handles different cases like connection_table, summation , shortest_path
         */
        switch ($case) {
            case 'connection_table': // get a connection table
                $connectionTable = array();
                foreach ($graph as $row => $data) {
                    $table = array();
                    $this->backTrace($parent, $row, $source, $table);
                    if ($destination != NULL && $destination == $row) {
                        $return['path'] = !empty($table) ? $table : array();
                        $return['cost'] = $key[$destination];
                    }
                    if (!empty($table)) {
                        $count = count($table) - 2;
                        $forward = array_key_exists($count, $table) ? $table[$count] : NULL;
                        $connectionTable[$row] = $forward;
                    } else {
                        $connectionTable[$row] = '-';
                    }
                }
                $return['connection_table'] = $connectionTable;
                break;
            case 'summation': // calaculates the total cost from source to all other routers
                $summation = $this->calculateSum($key);
                $return['sum'] = $summation;
                break;
            case 'shortest_path': // Calculates the cost and the path from source to destination
                $return = array();
                $resultant = array();
                $this->backTrace($parent, $destination, $source, $resultant); // Back trace the path from child to parent in the tree.
                $return['path'] = $resultant;
                $return['cost'] = $key[$destination];
                break;
            default :
                break;
        }
        return $return;
    }

    /*
     * This function calculates the sum from one router to all other routers. 
     */

    function calculateSum($key) {
        $sum = 0;
        foreach ($key as $row) {
            $sum = $sum + $row;
        }
        return $sum;
    }

    /*
     * Ths method back trace the path from source to destination using recursive approach 
     * Arguments : Parentchild array , destination, source , resultant array (passed by reference)
     */

    function backTrace($previous, $destination, $source, &$resultant) {

        if ($previous[$destination] == '') {
            return 0;
        }

        $resultant[] = $destination;
        $destination = $previous[$destination];
        if ($source == $destination) {
            $resultant[] = $source;
            $resultant = array_unique($resultant);
            return 0;
        }
        $this->backTrace($previous, $destination, $source, $resultant); // Recursive approach
    }

}
