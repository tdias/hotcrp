<?php
// sorthelper.php -- HotCRP helper class for sorting, particularly scores
// HotCRP is Copyright (c) 2006-2013 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

class SortHelper {

    public static $score_sort_algorithms = array("C" => "Counts",
		    "A" => "Average",
		    "E" => "Median",
		    "V" => "Variance",
		    "D" => "Max &minus; min",
		    "Y" => "Your score");

    static function score_reset($row, $algorithm) {
        // $row will compare less than all papers with analyzed scores
        if ($algorithm == "C" || $algorithm == "Y" || $algorithm == "M")
            $row->_sort_info = "//////////////";
        else
            $row->_sort_info = -1;
        $row->_sort_average = 0;
    }

    static function score_analyze($row, $scorename, $scoremax, $algorithm) {
	if ($algorithm == "Y"
            && substr($scorename, -6) === "Scores"
	    && ($v = defval($row, substr($scorename, 0, -6))) > 0)
	    $row->_sort_info = ":" . $v;
	else if ($algorithm == "C" || $algorithm == "Y" || $algorithm == "M") {
	    $x = array();
	    foreach (preg_split('/[\s,]+/', $row->$scorename) as $i)
		if (($i = cvtint($i)) > 0)
		    $x[] = chr($i + 48);
	    rsort($x);
	    $x = (count($x) == 0 ? "0" : implode($x));
	    $x = str_pad($x, 14, chr(ord($x[strlen($x) - 1]) - 1));
	    $row->_sort_info = $x;
	} else if ($algorithm == "E") {
	    $x = array();
	    $sum = 0;
	    foreach (preg_split('/[\s,]+/', $row->$scorename) as $i)
		if (($i = cvtint($i)) > 0) {
		    $x[] = $i;
		    $sum += $i;
		}
	    sort($x);
	    $n = count($x);
	    if ($n % 2 == 1)
		$v = $x[($n-1)/2];
	    else if ($n > 0)
		$v = ($x[$n/2 - 1] + $x[$n/2]) / 2.0;
	    $row->_sort_info = $n ? $v : 0;
	    $row->_sort_average = $n ? $sum / $n : 0;
	} else {
	    $sum = $sum2 = $n = $max = 0;
	    $min = $scoremax;
	    foreach (preg_split('/[\s,]+/', $row->$scorename) as $i)
		if (($i = cvtint($i)) > 0) {
		    $sum += $i;
		    $sum2 += $i * $i;
		    $min = min($min, $i);
		    $max = max($max, $i);
		    $n++;
		}
	    if ($n == 0)
		$row->_sort_info = 0;
	    else if ($algorithm == "A")
		$row->_sort_info = $sum / $n;
	    else if ($algorithm == "V") {
		if ($n == 1)
		    $row->_sort_info = 0;
		else
		    $row->_sort_info = ($sum2 / ($n - 1)) - ($sum * $sum / (($n - 1) * $n));
	    } else
		$row->_sort_info = $max - $min;
	    $row->_sort_average = ($n ? $sum / $n : 0);
	}
    }

    static function score_compare($a, $b, $algorithm) {
        if (is_string($a->_sort_info))
            return strcmp($b->_sort_info, $a->_sort_info);
        else {
            $x = $b->_sort_info - $a->_sort_info;
            $x = $x ? $x : $b->_sort_average - $a->_sort_average;
            return $x < 0 ? -1 : ($x == 0 ? 0 : 1);
        }
    }

    static function default_score_sort($nosession = false) {
        global $Conf, $Opt;
        if (isset($_SESSION["scoresort"]) && !$nosession)
            return $_SESSION["scoresort"];
        else if ($Conf && ($s = $Conf->settingText("scoresort_default")))
            return $s;
        else
            return defval($Opt, "defaultScoreSort", "C");
    }

    static function parse_sorter($text) {
        $sort = (object) array("type" => null,
                               "reverse" => false,
                               "score" => self::default_score_sort(),
                               "empty" => $text == "");
        if (!preg_match('/\A(\d+)([a-z]*)\z/i', $text, $m)
            && !preg_match('/\A([^,+]+)(?:[,+]([a-z]*))?\z/i', $text, $m))
            $m = array();
        if (isset($m[1]))
            $sort->type = $m[1];
        if (isset($m[2]) && $m[2] != "")
            for ($i = 0; $i < strlen($m[2]); ++$i) {
                $x = strtoupper($m[2][$i]);
                if ($x == "R")
                    $sort->reverse = true;
                else if ($x == "N")
                    $sort->reverse = false;
                else if ($x == "M")
                    $sort->score = "C";
                else if (isset(self::$score_sort_algorithms[$x]))
                    $sort->score = $x;
            }
        return $sort;
    }

}
