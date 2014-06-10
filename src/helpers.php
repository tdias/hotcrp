<?php
// helpers.php -- HotCRP non-class helper functions
// HotCRP is Copyright (c) 2006-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

function defappend(&$var, $str) {
    if (!isset($var))
	$var = "";
    $var .= $str;
}

function arrayappend(&$var, $value) {
    if (isset($var))
	$var[] = $value;
    else
	$var = array($value);
}

function &array_ensure(&$arr, $key, $val) {
    if (!isset($arr[$key]))
        $arr[$key] = $val;
    return $arr[$key];
}

function set_error_html($x, $error_html = null) {
    if (!$error_html) {
        $error_html = $x;
        $x = (object) array();
    }
    $x->error = true;
    $x->error_html = $error_html;
    return $x;
}


// database helpers

// number of rows returned by a select query, or 'false' if result is an error
function edb_nrows($result) {
    return ($result ? $result->num_rows : false);
}

// number of rows affected by an update/insert query, or 'false' if result is
// an error
function edb_nrows_affected($result) {
    global $Conf;
    return ($result ? $Conf->dblink->affected_rows : false);
}

// next row as an array, or 'false' if no more rows or result is an error
function edb_row($result) {
    return ($result ? $result->fetch_row() : false);
}

// next row as an object, or 'false' if no more rows or result is an error
function edb_orow($result) {
    return ($result ? $result->fetch_object() : false);
}

// array of all rows as objects
function edb_orows($result) {
    $x = array();
    while ($result && ($row = $result->fetch_object()))
        $x[] = $row;
    return $x;
}

// quoting for SQL
function sqlq($value) {
    global $Conf;
    return $Conf->dblink->escape_string($value);
}

function sqlq_for_like($value) {
    return preg_replace("/(?=[%_\\\\'\"\\x00\\n\\r\\x1a])/", "\\", $value);
}

function sqlqtrim($value) {
    global $Conf;
    return $Conf->dblink->escape_string(trim($value));
}

function sql_in_numeric_set($set, $negated = false) {
    if (count($set) == 0)
        return $negated ? "!=-1" : "=-1";
    else if (count($set) == 1)
        return ($negated ? "!=" : "=") . $set[0];
    else
        return ($negated ? " not" : "") . " in (" . join(",", $set) . ")";
}


// string helpers

function cvtint($value, $default = -1) {
    $v = trim($value);
    if (is_numeric($v)) {
	$ival = intval($v);
	if ($ival == floatval($v))
	    return $ival;
    }
    return $default;
}

function cvtnum($value, $default = -1) {
    $v = trim($value);
    if (is_numeric($v))
	return floatval($v);
    return $default;
}

function rcvtint(&$value, $default = -1) {
    return (isset($value) ? cvtint($value, $default) : $default);
}

function mkarray($value) {
    if (is_array($value))
	return $value;
    else
	return array($value);
}

if (function_exists("mb_check_encoding")) {
    function is_valid_utf8($str) {
	return @mb_check_encoding($str, "UTF-8");
    }
} else if (function_exists("iconv")) {
    // Aren't these hoops delicious?
    function _is_valid_utf8_error_handler($errno, $errstr) {
	global $_is_valid_utf8_result;
	$_is_valid_utf8_result = false;
	return false;
    }
    function is_valid_utf8($str) {
	global $_is_valid_utf8_result;
	$_is_valid_utf8_result = true;
	set_error_handler("_is_valid_utf8_error_handler");
	@iconv("UTF-8", "UTF-8", $str); // possible E_NOTICE captured above
	restore_error_handler();
	return $_is_valid_utf8_result;
	// While it might also work to compare iconv's return value to the
	// original string, who knows whether iconv canonicalizes composed
	// Unicode character sequences or something?  Safer to check for
	// errors.
    }
} else {
    function is_valid_utf8($str) {
	return true;		// give up
    }
}

if (function_exists("iconv")) {
    function windows_1252_to_utf8($str) {
	return iconv("Windows-1252", "UTF-8//IGNORE", $str);
    }
    function mac_os_roman_to_utf8($str) {
        return iconv("Mac", "UTF-8//IGNORE", $str);
    }
} else {
    function windows_1252_to_utf8($str) {
	return $str;		// give up
    }
    function mac_os_roman_to_utf8($str) {
        return $str;		// give up
    }
}

function convert_to_utf8($str) {
    if (substr_count(substr($str, 0, 5000), "\r")
        > 1.5 * substr_count(substr($str, 0, 5000), "\n"))
        return mac_os_roman_to_utf8($str);
    else
        return windows_1252_to_utf8($str);
}

if (function_exists("iconv")) {
    function utf8_substr($str, $off, $len) {
	return iconv_substr($str, $off, $len, "UTF-8");
    }
} else if (function_exists("mb_substr")) {
    function utf8_substr($str, $off, $len) {
	return mb_substr($str, $off, $len, "UTF-8");
    }
} else {
    function utf8_substr($str, $off, $len) {
	$x = substr($str, $off, $len);
	$poff = 0;
	while (($n = preg_match_all("/[\200-\277]/", $x, $m, PREG_PATTERN_ORDER, $poff))) {
	    $poff = strlen($x);
	    $x .= substr($str, $poff, $n);
	}
	if (preg_match("/\\A([\200-\277]+)/", substr($str, strlen($x)), $m))
	    $x .= $m[1];
	return $x;
    }
}


// web helpers

function hoturl($page, $options = null) {
    global $ConfSiteBase, $ConfSiteSuffix, $Opt, $Me, $paperTable, $CurrentList;
    $t = $ConfSiteBase . $page . $ConfSiteSuffix;
    // see also redirectSelf
    if ($options && is_array($options)) {
        $x = "";
        foreach ($options as $k => $v)
            if ($v !== null && $k !== "anchor")
                $x .= ($x === "" ? "" : "&amp;") . $k . "=" . urlencode($v);
        if (isset($options["anchor"]))
            $x .= "#" . urlencode($options[$anchor]);
        $options = $x;
    }
    // anchor
    $anchor = "";
    if (preg_match('/\A(.*?)(#.*)\z/', $options, $m))
        list($options, $anchor) = array($m[1], $m[2]);
    // append forceShow to links to same paper if appropriate
    $are = '/\A(|.*?(?:&|&amp;))';
    $zre = '(?:&(?:amp;)?|\z)(.*)\z/';
    $is_paper_page = preg_match('/\A(?:paper|review|comment|assign)\z/', $page);
    if (@$paperTable && $paperTable->prow && $is_paper_page
        && preg_match($are . 'p=' . $paperTable->prow->paperId . $zre, $options)
        && $Me->canAdminister($paperTable->prow)
        && $paperTable->prow->has_conflict($Me)
        && !preg_match($are . 'forceShow=/', $options))
        $options .= "&amp;forceShow=1";
    if (@$paperTable && $paperTable->prow && $is_paper_page
        && @$CurrentList && $CurrentList > 0
        && !preg_match($are . 'ls=/', $options))
        $options .= "&amp;ls=$CurrentList";
    // create slash-based URLs if appropriate
    if ($options && !defval($Opt, "disableSlashURLs")) {
        if ($page == "review"
            && preg_match($are . 'r=(\d+[A-Z]+)' . $zre, $options, $m)) {
            $t .= "/" . $m[2];
            $options = $m[1] . $m[3];
            if (preg_match($are . 'p=\d+' . $zre, $options, $m))
                $options = $m[1] . $m[2];
        } else if (($is_paper_page
                    && preg_match($are . 'p=(\d+|%\w+%)' . $zre, $options, $m))
                   || ($page == "profile"
                       && preg_match($are . 'u=([^&]+)' . $zre, $options, $m))
                   || ($page == "help"
                       && preg_match($are . 't=(\w+)' . $zre, $options, $m))
                   || ($page == "settings"
                       && preg_match($are . 'group=(\w+)' . $zre, $options, $m))
                   || preg_match($are . '__PATH__=([^&]+)' . $zre, $options, $m)) {
            $t .= "/" . $m[2];
            $options = $m[1] . $m[3];
        }
    }
    if ($options && preg_match('/\A\&(?:amp;)?(.*)\z/', $options, $m))
	$options = $m[1];
    if ($options)
	return $t . "?" . $options . $anchor;
    else
	return $t . $anchor;
}

function hoturl_post($page, $options = null) {
    if (is_array($options))
        $options["post"] = post_value();
    else if ($options)
        $options .= "&amp;post=" . post_value();
    else
        $options = "post=" . post_value();
    return hoturl($page, $options);
}

function hoturl_absolute($page, $options = null) {
    global $Opt, $ConfSiteBase;
    return $Opt["paperSite"] . "/" . substr(hoturl($page, $options), strlen($ConfSiteBase));
}

function hoturl_image($page) {
    global $ConfSiteBase;
    return $ConfSiteBase . $page;
}


function fileUploaded(&$var) {
    global $Conf;
    if (!isset($var) || ($var['error'] != UPLOAD_ERR_OK && !$Conf))
	return false;
    switch ($var['error']) {
    case UPLOAD_ERR_OK:
	return is_uploaded_file($var['tmp_name']);
    case UPLOAD_ERR_NO_FILE:
	return false;
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
	$Conf->errorMsg("Você tentou enviar um arquivo que é grande demais para ser aceito pelo nosso sistema. O tamanho máximo é " . ini_get("upload_max_filesize") . "B.");
	return false;
    case UPLOAD_ERR_PARTIAL:
	$Conf->errorMsg("Aparentemente você interrompeu o processo de upload; O arquivo não está sendo salvo.");
	return false;
    default:
	$Conf->errorMsg("Erro de Envio Intero " . $var['error'] . "!");
	return false;
    }
}

function selfHref($extra = array(), $htmlspecialchars = true) {
    global $CurrentList, $ConfSiteSuffix, $Opt;
    // clean parameters from pathinfo URLs
    foreach (array("paperId" => "p", "pap" => "p", "reviewId" => "r", "commentId" => "c") as $k => $v)
	if (isset($_REQUEST[$k]) && !isset($_REQUEST[$v]))
	    $_REQUEST[$v] = $_REQUEST[$k];

    $param = "";
    foreach (array("p", "r", "c", "m", "u", "mode", "forceShow", "validator", "ls", "list", "t", "q", "qa", "qo", "qx", "qt", "tab", "atab", "group", "sort", "monreq", "noedit", "contact", "reviewer") as $what)
	if (isset($_REQUEST[$what]) && !array_key_exists($what, $extra)
            && !is_array($_REQUEST[$what]))
	    $param .= "&$what=" . urlencode($_REQUEST[$what]);
    foreach ($extra as $key => $value)
	if ($key != "anchor" && $value !== null)
	    $param .= "&$key=" . urlencode($value);
    if (isset($CurrentList) && $CurrentList > 0
        && !isset($_REQUEST["ls"]) && !array_key_exists("ls", $extra))
	$param .= "&ls=" . $CurrentList;

    $base = request_script_base();
    $uri = hoturl($base ? $base : "index", $param ? substr($param, 1) : "");
    if (isset($extra["anchor"]))
	$uri .= "#" . $extra["anchor"];
    return $htmlspecialchars ? htmlspecialchars($uri) : $uri;
}

function redirectSelf($extra = array()) {
    go(selfHref($extra, false));
}

function validateEmail($email) {
    // validate email address
    // Allow @_.com email addresses.  Simpler than RFC822 validation.
    if (!preg_match(':\A[-!#$%&\'*+./0-9=?A-Z^_`a-z{|}~]+@(.+)\z:', $email, $m))
	return false;
    if ($m[1][0] == "_")
	return preg_match(':\A_\.[0-9A-Za-z]+\z:', $m[1]);
    else
	return preg_match(':\A([-0-9A-Za-z]+\.)+[0-9A-Za-z]+\z:', $m[1]);
}

function foldsessionpixel($name, $var, $sub = false) {
    $val = "&amp;val=";
    if ($sub === false)
	$val .= defval($_SESSION, $var, 1);
    else if ($sub === null)
	$val .= "&amp;sub=";
    else if ($sub !== null) {
	if (!isset($_SESSION[$var])
	    || array_search($sub, explode(" ", $_SESSION[$var])) === false)
	    $val .= "1";
	else
	    $val .= "0";
	$val = "&amp;sub=" . $sub . $val;
    }

    return "<img id='foldsession." . $name . "' alt='' src='" . hoturl("sessionvar", "var=" . $var . $val . "&amp;cache=1") . "' width='1' height='1' />";
}

function foldbutton($foldtype, $title, $foldnum = 0) {
    $showtitle = ($title ? " title='" . htmlspecialchars("Show $title") . "'" : "");
    $hidetitle = ($title ? " title='" . htmlspecialchars("Hide $title") . "'" : "");
    $foldclass = ($foldnum ? $foldnum : "");
    $foldnum = ($foldnum ? ",$foldnum" : "");
    return '<a href="#" class="q fn' . $foldclass
        . '" onclick="return fold(\'' . $foldtype . '\',0' . $foldnum . ')"'
        . $showtitle . '>' . expander(true) . '</a>'
        . '<a href="#" class="q fx' . $foldclass
        . '" onclick="return fold(\'' . $foldtype . '\',1' . $foldnum . ')"'
        . $hidetitle . '>' . expander(false) . '</a>';
}

function expander($open) {
    return '<span class="expander' . ($open ? 1 : 0) . '">'
        . '<span class="in">' . ($open ? '&#x25B6;' : '&#x25BC;')
        . '</span></span>';
}

function reviewType($paperId, $row, $long = 0) {
    if ($row->reviewType == REVIEW_PRIMARY)
	return "<span class='rtype rtype_pri'>Primário</span>";
    else if ($row->reviewType == REVIEW_SECONDARY)
	return "<span class='rtype rtype_sec'>Secundário</span>";
    else if ($row->reviewType == REVIEW_EXTERNAL)
	return "<span class='rtype rtype_req'>Externo</span>";
    else if ($row->conflictType >= CONFLICT_AUTHOR)
	return "<span class='author'>Autor</span>";
    else if ($row->conflictType > 0)
	return "<span class='conflict'>Conflito</span>";
    else if (!($row->reviewId === null) || $long)
	return "<span class='rtype rtype_pc'>Comissão Científica</span>";
    else
	return "";
}

function documentDownload($doc, $dlimg_class = "dlimg", $text = null) {
    global $Conf;
    $p = HotCRPDocument::url($doc);
    $finalsuffix = ($doc->documentType == DTYPE_FINAL ? "f" : "");
    $sp = "&nbsp;";
    $imgsize = ($dlimg_class[0] == "s" ? "" : "24");
    if ($doc->mimetype == "application/postscript")
	$x = "<a href=\"$p\" class='q nowrap'>" . $Conf->cacheableImage("postscript${finalsuffix}${imgsize}.png", "[PS]", null, $dlimg_class);
    else if ($doc->mimetype == "application/pdf")
	$x = "<a href=\"$p\" class='q nowrap'>" . $Conf->cacheableImage("pdf${finalsuffix}${imgsize}.png", "[PDF]", null, $dlimg_class);
    else
	$x = "<a href=\"$p\" class='q nowrap'>" . $Conf->cacheableImage("generic${finalsuffix}${imgsize}.png", "[Download]", null, $dlimg_class);
    if ($text)
	$x .= $sp . $text;
    if (isset($doc->size) && $doc->size > 0) {
	$x .= "&nbsp;<span class='dlsize'>" . ($text ? "(" : "");
        if ($doc->size > 921)
            $x .= round($doc->size / 1024);
        else
            $x .= max(round($doc->size / 102.4), 1) / 10;
        $x .= "kB" . ($text ? ")" : "") . "</span>";
    }
    return $x . "</a>";
}

function paperDocumentData($prow, $documentType = DTYPE_SUBMISSION, $paperStorageId = 0) {
    global $Conf, $Opt;
    assert($paperStorageId || $documentType == DTYPE_SUBMISSION || $documentType == DTYPE_FINAL);
    if ($documentType == DTYPE_FINAL && $prow->finalPaperStorageId <= 0)
	$documentType = DTYPE_SUBMISSION;
    if ($paperStorageId == 0 && $documentType == DTYPE_FINAL)
	$paperStorageId = $prow->finalPaperStorageId;
    else if ($paperStorageId == 0)
	$paperStorageId = $prow->paperStorageId;
    if ($paperStorageId <= 1)
	return null;

    // pre-load document object from paper
    $doc = (object) array("paperId" => $prow->paperId,
			  "mimetype" => defval($prow, "mimetype", ""),
			  "size" => defval($prow, "size", 0),
			  "timestamp" => defval($prow, "timestamp", 0),
			  "sha1" => defval($prow, "sha1", ""));
    if ($prow->finalPaperStorageId > 0) {
	$doc->paperStorageId = $prow->finalPaperStorageId;
	$doc->documentType = DTYPE_FINAL;
    } else {
	$doc->paperStorageId = $prow->paperStorageId;
	$doc->documentType = DTYPE_SUBMISSION;
    }

    // load document object from database if pre-loaded version doesn't work
    if ($paperStorageId > 0
	&& ($doc->documentType != $documentType
	    || $paperStorageId != $doc->paperStorageId)) {
	$result = $Conf->qe("select paperStorageId, paperId, length(paper) as size, mimetype, timestamp, sha1, filename, documentType from PaperStorage where paperStorageId=$paperStorageId", "while reading documents");
	$doc = edb_orow($result);
    }

    return $doc;
}

function paperDownload($prow, $final = false) {
    global $Conf, $Me;
    // don't let PC download papers in progress
    if ($prow->timeSubmitted <= 0 && !$Me->canDownloadPaper($prow))
	return "";
    $doc = paperDocumentData($prow, $final ? DTYPE_FINAL : DTYPE_SUBMISSION);
    return $doc ? documentDownload($doc) : "";
}

function requestDocumentType($req, $default = DTYPE_SUBMISSION) {
    if (is_string($req))
	$req = array("dt" => $req);
    if (($dt = defval($req, "dt"))) {
	if (preg_match('/\A-?\d+\z/', $dt))
	    return (int) $dt;
	$dt = strtolower($dt);
	if ($dt == "paper" || $dt == "submission")
	    return DTYPE_SUBMISSION;
	if ($dt == "final")
	    return DTYPE_FINAL;
	if (substr($dt, 0, 4) == "opt-")
	    $dt = substr($dt, 4);
	foreach (PaperOption::option_list() as $o)
	    if ($dt == $o->abbr)
		return $o->id;
    }
    if (defval($req, "final", 0) != 0)
	return DTYPE_FINAL;
    return $default;
}

function topicTable($prow, $active = 0) {
    global $Conf;
    $rf = reviewForm();
    $paperId = ($prow ? $prow->paperId : -1);

    // read from paper row if appropriate
    if ($paperId > 0 && $active < 0 && isset($prow->topicIds)) {
	$top = $rf->webTopicArray($prow->topicIds, defval($prow, "topicInterest"));
	return join(" <span class='sep'>&nbsp;</span> ", $top);
    }

    // get current topics
    $paperTopic = array();
    $tmap = $Conf->topic_map();
    if ($paperId > 0) {
	$result = $Conf->q("select topicId from PaperTopic where paperId=$paperId");
	while ($row = edb_row($result))
	    $paperTopic[$row[0]] = $tmap[$row[0]];
    }
    $allTopics = ($active < 0 ? $paperTopic : $tmap);
    if (count($allTopics) == 0)
	return "";

    $out = "<table><tr><td class='pad'>";
    $colheight = (int) ((count($allTopics) + 1) / 2);
    $i = 0;
    foreach ($tmap as $tid => $tname) {
	if (!isset($allTopics[$tid]))
	    continue;
	if ($i > 0 && ($i % $colheight) == 0)
	    $out .= "</td><td>";
	$tname = htmlspecialchars($tname);
	if ($paperId <= 0 || $active >= 0) {
	    $out .= Ht::checkbox_h("top$tid", 1, ($active > 0 ? isset($_REQUEST["top$tid"]) : isset($paperTopic[$tid])),
				    array("disabled" => $active < 0))
		. "&nbsp;" . Ht::label($tname) . "<br />\n";
	} else
	    $out .= $tname . "<br />\n";
	$i++;
    }
    return $out . "</td></tr></table>";
}

function viewas_link($cid, $contact = null) {
    global $Conf;
    $contact = !$contact && is_object($cid) ? $cid : $contact;
    $cid = is_object($contact) ? $cid->email : $cid;
    return '<a href="' . selfHref(array("actas" => $cid))
        . '">' . $Conf->cacheableImage("viewas.png", "[Act as]", "Act as " . Text::name_html($contact)) . '</a>';
}

function authorTable($aus, $viewAs = null) {
    global $Conf;
    $out = "";
    if (!is_array($aus))
	$aus = explode("\n", $aus);
    foreach ($aus as $aux) {
	$au = trim(is_array($aux) ? Text::user_html($aux) : $aux);
	if ($au != '') {
	    if (strlen($au) > 30)
		$out .= "<span class='autblentry_long'>";
	    else
		$out .= "<span class='autblentry'>";
	    $out .= $au;
	    if ($viewAs !== null && is_array($aux) && count($aux) >= 2 && $viewAs->email != $aux[2] && $viewAs->privChair)
                $out .= " " . viewas_link($aux[2], $aux);
	    $out .= "</span> ";
	}
    }
    return $out;
}

function highlightMatch($match, $text, &$n = null) {
    if ($match == "") {
	$n = 0;
	return $text;
    }
    if ($match[0] != "{")
	$match = "{(" . $match . ")}i";
    return preg_replace($match, "<span class='match'>\$1</span>", $text, -1, $n);
}

function decorateNumber($n) {
    if ($n < 0)
	return "&#8722;" . (-$n);
    else if ($n > 0)
	return $n;
    else
	return 0;
}


class SessionList {
    static function lookup($idx) {
        if (!isset($_SESSION["l"]))
            $_SESSION["l"] = array();
        $x = @($_SESSION["l"][$idx]);
        return $x ? (object) $x : null;
    }
    static function change($idx, $delta) {
        $l = self::lookup($idx);
        $l = $l ? $l : (object) array();
        foreach ($delta as $k => $v)
            $l->$k = $v;
        $_SESSION["l"][$idx] = $l;
    }
    static function allocate($listid) {
        $oldest = $empty = 0;
        for ($i = 1; $i <= 8; ++$i)
            if (($l = self::lookup($i))) {
                if ($listid && @($l->listid == $listid))
                    return $i;
                else if (!$oldest || @($_SESSION["l"][$oldest]->timestamp < $l->timestamp))
                    $oldest = $i;
            } else if (@$_REQUEST["ls"] == $i)
                return $i;
            else if (!$empty)
                $empty = $i;
        return $empty ? $empty : $oldest;
    }
    static function create($listid, $ids, $description, $url) {
        global $Me, $ConfSiteBase, $Now;
        if ($url && $ConfSiteBase && str_starts_with($url, $ConfSiteBase))
            $url = substr($url, strlen($ConfSiteBase));
        return (object) array("listid" => $listid, "ids" => $ids,
                              "description" => $description,
                              "url" => $url, "timestamp" => $Now,
                              "cid" => $Me ? $Me->contactId : 0);
    }
}

function _tryNewList($opt, $listtype, $sort = null) {
    global $Conf, $ConfSiteSuffix, $ConfSitePATH, $Me;
    if ($listtype == "u" && $Me->privChair) {
	$searchtype = (defval($opt, "t") === "all" ? "all" : "pc");
	$q = "select email from ContactInfo";
	if ($searchtype == "pc")
	    $q .= " join PCMember using (contactId)";
	$result = $Conf->qx("$q order by lastName, firstName, email");
	$a = array();
	while (($row = edb_row($result)))
	    $a[] = $row[0];
        return SessionList::create("u/" . $searchtype, $a,
                                   ($searchtype == "pc" ? "Program committee" : "Users"),
                                   "users$ConfSiteSuffix?t=$searchtype");
    } else {
	$search = new PaperSearch($Me, $opt);
	$x = $search->session_list_object($sort);
        if ($sort) {
            $pl = new PaperList($search, array("sort" => $sort));
            $x->ids = $pl->text("s", array("idarray" => true));
        }
        return $x;
    }
}

function _one_quicklink($id, $baseUrl, $urlrest, $listtype, $isprev) {
    global $Conf;
    if ($listtype == "u") {
	$result = $Conf->qx("select email from ContactInfo where email='" . sqlq($id) . "'");
	$row = edb_row($result);
	$paperText = htmlspecialchars($row ? $row[0] : $id);
	$urlrest = "u=" . urlencode($id) . $urlrest;
    } else {
	$paperText = "#$id";
	$urlrest = "p=" . $id . $urlrest;
    }
    return "<a id=\"quicklink_" . ($isprev ? "prev" : "next")
	. "\" href=\"" . hoturl($baseUrl, $urlrest)
	. "\" onclick=\"return !Miniajax.isoutstanding('revprevform', make_link_callback(this))\">"
	. ($isprev ? $Conf->cacheableImage("_.gif", "&lt;-", null, "prev") : "")
	. $paperText
	. ($isprev ? "" : $Conf->cacheableImage("_.gif", "-&gt;", null, "next"))
	. "</a>";
}

function quicklinks($id, $baseUrl, $args, $listtype) {
    global $Me, $Conf, $ConfSiteBase, $CurrentList, $Now;

    $list = false;
    $CurrentList = 0;
    if (isset($_REQUEST["ls"])
	&& ($listno = rcvtint($_REQUEST["ls"])) > 0
        && ($xlist = SessionList::lookup($listno))
        && str_starts_with($xlist->listid, $listtype)
        && (!@$xlist->cid || $xlist->cid == ($Me ? $Me->contactId : 0))) {
	$list = $xlist;
	$CurrentList = $listno;
    } else if (isset($_REQUEST["ls"]) && $listtype == "p") {
	$l = $_REQUEST["ls"];
        if (preg_match('_\Ap/([^/]*)/([^/]*)/?(.*)\z_', $l, $m))
            $list = _tryNewList(array("t" => $m[1],
                                      "q" => urldecode($m[2])),
                                $listtype, $m[3]);
	if (!$list && preg_match('/\A[a-z]+\z/', $l))
	    $list = _tryNewList(array("t" => $l), $listtype);
        if (!$list && preg_match('/\A(all|s):(.*)\z/s', $l, $m))
	    $list = _tryNewList(array("t" => $m[1], "q" => $m[2]), $listtype);
	if (!$list)
	    $list = _tryNewList(array("q" => $l), $listtype);
    }

    $k = false;
    if ($list)
	$k = array_search($id, $list->ids);

    if ($k === false && !isset($_REQUEST["list"])) {
	$CurrentList = 0;
	$list = _tryNewList(array(), $listtype);
	$k = array_search($id, $list->ids);
	if ($k === false && $Me->privChair) {
	    $list = _tryNewList(array("t" => "all"), $listtype);
	    $k = array_search($id, $list->ids);
	}
	if ($k === false)
	    $list = false;
    }

    if (!$list)
	return "";

    if ($CurrentList == 0) {
	$CurrentList = SessionList::allocate($list->listid);
        SessionList::change($CurrentList, $list);
    }
    SessionList::change($CurrentList, array("timestamp" => $Now));

    $urlrest = "&amp;ls=" . $CurrentList;
    foreach ($args as $what => $val)
	$urlrest .= "&amp;" . urlencode($what) . "=" . urlencode($val);

    $x = "";
    if ($k > 0)
	$x .= _one_quicklink($list->ids[$k - 1], $baseUrl, $urlrest, $listtype, true);
    if (@$list->description) {
	$x .= ($k > 0 ? "&nbsp;&nbsp;" : "");
	if (@$list->url)
	    $x .= '<a id="quicklink_list" href="' . $ConfSiteBase . htmlspecialchars($list->url) . "\">" . $list->description . "</a>";
	else
	    $x .= '<span id="quicklink_list">' . $list->description . '</span>';
    }
    if (isset($list->ids[$k + 1])) {
	$x .= ($k > 0 || @$list->description ? "&nbsp;&nbsp;" : "");
	$x .= _one_quicklink($list->ids[$k + 1], $baseUrl, $urlrest, $listtype, false);
    }
    return $x;
}

function goPaperForm($baseUrl = null, $args = array()) {
    global $Conf, $Me, $CurrentList;
    if ($Me->is_empty())
	return "";
    if ($baseUrl === null)
	$baseUrl = ($Me->isPC && $Conf->setting("rev_open") ? "review" : "paper");
    $x = "<form class='gopaper' action='" . hoturl($baseUrl) . "' method='get' accept-charset='UTF-8'><div class='inform'>";
    $x .= "<input id='quicksearchq' class='textlite temptext' type='text' size='10' name='p' value='(All)' title='Insira número do artigo ou termos para busca' />";
    $Conf->footerScript("mktemptext('quicksearchq','(All)')");
    foreach ($args as $what => $val)
	$x .= "<input type='hidden' name=\"" . htmlspecialchars($what) . "\" value=\"" . htmlspecialchars($val) . "\" />";
    if (isset($CurrentList) && $CurrentList > 0)
	$x .= "<input type='hidden' name='ls' value='$CurrentList' />";
    $x .= "&nbsp; <input type='submit' value='Search' /></div></form>";
    return $x;
}

function clean_tempdirs() {
    $dir = null;
    if (function_exists("sys_get_temp_dir"))
	$dir = sys_get_temp_dir();
    if (!$dir)
	$dir = "/tmp";
    while (substr($dir, -1) == "/")
	$dir = substr($dir, 0, -1);
    $dirh = opendir($dir);
    $now = time();
    while (($fname = readdir($dirh)) !== false)
	if (preg_match('/\Ahotcrptmp\d+\z/', $fname)
	    && is_dir("$dir/$fname")
	    && ($mtime = @filemtime("$dir/$fname")) !== false
	    && $mtime < $now - 1800) {
	    $xdirh = @opendir("$dir/$fname");
	    while (($xfname = readdir($xdirh)) !== false)
		@unlink("$dir/$fname/$xfname");
	    @closedir("$dir/$fname");
	    @rmdir("$dir/$fname");
	}
    closedir($dirh);
}

function tempdir($mode = 0700) {
    $dir = null;
    if (function_exists("sys_get_temp_dir"))
	$dir = sys_get_temp_dir();
    if (!$dir)
	$dir = "/tmp";
    while (substr($dir, -1) == "/")
	$dir = substr($dir, 0, -1);
    for ($i = 0; $i < 100; $i++) {
	$path = $dir . "/hotcrptmp" . mt_rand(0, 9999999);
	if (mkdir($path, $mode))
	    return $path;
    }
    return false;
}


function setCommentType($crow) {
    if ($crow && !isset($crow->commentType)) {
        if ($crow->forAuthors == 2)
            $crow->commentType = COMMENTTYPE_RESPONSE | COMMENTTYPE_AUTHOR
                | ($crow->forReviewers ? 0 : COMMENTTYPE_DRAFT);
        else if ($crow->forAuthors == 1)
            $crow->commentType = COMMENTTYPE_AUTHOR;
        else if ($crow->forReviewers == 2)
            $crow->commentType = COMMENTTYPE_ADMINONLY;
        else if ($crow->forReviewers)
            $crow->commentType = COMMENTTYPE_REVIEWER;
        else
            $crow->commentType = COMMENTTYPE_PCONLY;
        if (isset($crow->commentBlind) ? $crow->commentBlind : $crow->blind)
            $crow->commentType |= COMMENTTYPE_BLIND;
    }
}

// watch functions
function saveWatchPreference($paperId, $contactId, $watchtype, $on) {
    global $Conf, $OK;
    $explicit = ($watchtype << WATCHSHIFT_EXPLICIT);
    $selected = ($watchtype << WATCHSHIFT_NORMAL);
    $onvalue = $explicit | ($on ? $selected : 0);
    $Conf->qe("insert into PaperWatch (paperId, contactId, watch)
		values ($paperId, $contactId, $onvalue)
		on duplicate key update watch = (watch & ~" . ($explicit | $selected) . ") | $onvalue",
	      "while saving email notification preference");
    return $OK;
}

function genericWatch($prow, $watchtype, $callback, $contact) {
    global $Conf;

    $q = "select ContactInfo.contactId, firstName, lastName, email,
		password, roles, defaultWatch,
		PaperReview.reviewType myReviewType,
		PaperReview.reviewSubmitted myReviewSubmitted,
		PaperReview.reviewNeedsSubmit myReviewNeedsSubmit,
		conflictType, watch, preferredEmail";
    if ($Conf->sversion >= 47)
        $q .= ", disabled";
    $q .= "\nfrom ContactInfo
	left join PaperConflict on (PaperConflict.paperId=$prow->paperId and PaperConflict.contactId=ContactInfo.contactId)
	left join PaperWatch on (PaperWatch.paperId=$prow->paperId and PaperWatch.contactId=ContactInfo.contactId)
	left join PaperReview on (PaperReview.paperId=$prow->paperId and PaperReview.contactId=ContactInfo.contactId)
	left join PaperComment on (PaperComment.paperId=$prow->paperId and PaperComment.contactId=ContactInfo.contactId)
	where watch is not null
	or conflictType>=" . CONFLICT_AUTHOR . "
	or reviewType is not null or commentId is not null
	or (defaultWatch & " . ($watchtype << WATCHSHIFT_ALL) . ")!=0";

    $result = $Conf->qe($q, "while processing email notifications");
    $watchers = array();
    $lastContactId = 0;
    while (($row = edb_orow($result))) {
	if ($row->contactId == $lastContactId
	    || ($contact && $row->contactId == $contact->contactId)
	    || preg_match('/\Aanonymous\d*\z/', $row->email))
	    continue;
	$lastContactId = $row->contactId;

	if ($row->watch
	    && ($row->watch & ($watchtype << WATCHSHIFT_EXPLICIT))) {
	    if (!($row->watch & ($watchtype << WATCHSHIFT_NORMAL)))
		continue;
	} else {
	    if (!($row->defaultWatch & (($watchtype << WATCHSHIFT_NORMAL) | ($watchtype << WATCHSHIFT_ALL))))
		continue;
	}

	$watchers[$row->contactId] = $row;
    }

    // Need to check for outstanding reviews if the settings might prevent a
    // person with outstanding reviews from seeing a comment.
    if (count($watchers)
	&& (($Conf->timePCViewAllReviews(false, false) && !$Conf->timePCViewAllReviews(false, true))
	    || ($Conf->timeAuthorViewReviews(false) && !$Conf->timeAuthorViewReviews(true)))) {
	$result = $Conf->qe("select ContactInfo.contactId, PaperReview.contactId, max(reviewNeedsSubmit) from ContactInfo
 		left join PaperReview on (PaperReview.contactId=ContactInfo.contactId)
		where ContactInfo.contactId in (" . join(",", array_keys($watchers)) . ")
		group by ContactInfo.contactId", "while processing email notifications");
	while (($row = edb_row($result))) {
	    $watchers[$row[0]]->has_review = $row[1] > 0;
	    $watchers[$row[0]]->has_outstanding_review = $row[2] > 0;
	}
    }

    foreach ($watchers as $row) {
	$minic = Contact::make($row);
        $prow->assign_contact_info($row, $row->contactId);
        call_user_func($callback, $prow, $minic);
    }
}


// text helpers
function commajoin($what, $joinword = "and") {
    $what = array_values($what);
    $c = count($what);
    if ($c == 0)
	return "";
    else if ($c == 1)
	return $what[0];
    else if ($c == 2)
	return $what[0] . " " . $joinword . " " . $what[1];
    else
	return join(", ", array_slice($what, 0, -1)) . ", " . $joinword . " " . $what[count($what) - 1];
}

function numrangejoin($range) {
    $i = 0;
    $a = array();
    while ($i < count($range)) {
	for ($j = $i + 1;
             $j < count($range) && $range[$j-1] == $range[$j] - 1;
             $j++)
	    /* nada */;
	if ($j == $i + 1)
	    $a[] = $range[$i];
	else
	    $a[] = $range[$i] . "&ndash;" . $range[$j - 1];
	$i = $j;
    }
    return commajoin($a);
}

function pluralx($n, $what) {
    if (is_array($n))
	$n = count($n);
    if ($n == 1)
	return $what;
    if ($what == "this")
	return "these";
    if (preg_match('/\A.*?(?:s|sh|ch|[bcdfgjklmnpqrstvxz][oy])\z/', $what)) {
	if (substr($what, -1) == "y")
	    return substr($what, 0, -1) . "ies";
	else
	    return $what . "es";
    } else
	return $what . "s";
}

function plural($n, $what) {
    return (is_array($n) ? count($n) : $n) . ' ' . pluralx($n, $what);
}

function ordinal($n) {
    if ($n >= 1 && $n <= 3)
	return $n . ($n == 1 ? "st" : ($n == 2 ? "nd" : "rd"));
    else
	return $n . "th";
}

function tabLength($text, $all) {
    $len = 0;
    for ($i = 0; $i < strlen($text); $i++)
	if ($text[$i] == ' ')
	    $len++;
	else if ($text[$i] == '\t')
	    $len += 8 - ($len % 8);
	else if (!$all)
	    break;
	else
	    $len++;
    return $len;
}

function wordWrapIndent($text, $info, $indent = 18, $totWidth = 75, $rjinfo = true) {
    if (is_int($indent)) {
	$indentlen = $indent;
	$indent = str_pad("", $indent);
    } else
	$indentlen = strlen($indent);

    $out = "";
    while ($text != "" && ctype_space($text[0])) {
	$out .= $text[0];
	$text = substr($text, 1);
    }

    $out .= preg_replace("/^(?!\\Z)/m", $indent, wordwrap($text, $totWidth - $indentlen));
    if (strlen($info) <= $indentlen) {
	$info = str_pad($info, $indentlen, " ", ($rjinfo ? STR_PAD_LEFT : STR_PAD_RIGHT));
	return $info . substr($out, $indentlen);
    } else
	return $info . "\n" . $out;
}

function htmlWrapText($text) {
    $lines = explode("\n", $text);
    while (count($lines) && $lines[count($lines) - 1] == "")
	array_pop($lines);
    $text = "";
    for ($i = 0; $i < count($lines); $i++) {
	$l = $lines[$i];
	while (($pos = strpos($l, "\t")) !== false)
	    $l = substr($l, 0, $pos) . substr('        ', 0, 8 - ($pos % 8)) . substr($l, $pos + 1);
	if (preg_match("/\\A  +.*[^\s.?!-'\")]   +/", $l))
	    $l = str_replace(" ", "\xC2\xA0", $l);
	else if (strlen($l) && $l[0] == " ") {
	    for ($x = 0; $x < strlen($l) && $l[$x] == " "; $x++)
		/* nada */;
	    $l = str_repeat("\xC2\xA0", $x) . substr($l, $x);
	}
	$l = preg_replace('@((?:https?|ftp)://\S+[^\s").,:;])([").,:;]*(?:\s|\z))@',
			  '<a href="$1" rel="noreferrer">$1</a>$2', $l);
	$lines[$i] = $l . "<br />\n";
    }
    return join("", $lines);

    // $lines = explode("\n", $text);
    // Rules: Indented line that starts with "-", "*", or "#[.]" starts
    //   indented text.
    //      Other indented text is preformatted.
    //
    // States: -1 initial, 0 normal text, 1 preformatted text, 2 indented text
    // $state = -1;
    // $savedPar = "";
    // $savedParLines = 0;
    // $indent = 0;
    // $out = "";
    // for ($i = 0; $i < count($lines); $i++) {
    //    $line = $lines[$i];
    //    if (preg_match("/^\\s*\$/", $line)) {
    //		$savedPar .= $line . "\n";
    //		$savedParLines++;
    //    } else if ($state == 1 && ctype_isspace($line[0]))
    //		$out .= $line . "\n";
    //    else if (preg_match("/^(\\s+)(-+|\\*+|\\d+\\.?)\\s/", $line, $matches)) {
    //		$x = tabLength($line, false);
    //    }
    // }
}

function htmlFold($text, $maxWords) {
    global $foldId;

    if (strlen($text) < $maxWords * 7)
	return $text;
    $words = preg_split('/\\s+/', $text);
    if (count($words) < $maxWords)
	return $text;

    $x = join(" ", array_slice($words, 0, $maxWords));

    $fid = (isset($foldId) ? $foldId : 1);
    $foldId = $fid + 1;

    $x .= "<span id='fold$fid' class='foldc'><span class='fn'> ... </span><a class='fn' href='javascript:void fold($fid, 0)'>[More]</a><span class='fx'> " . join(" ", array_slice($words, $maxWords)) . " </span><a class='fx' href='javascript:void fold($fid, 1)'>[Less]</a></span>";

    return $x;
}

function ini_get_bytes($varname) {
    // from PHP manual
    $val = trim(ini_get($varname));
    $last = strtolower($val[strlen($val)-1]);
    switch ($last) {
    case 'g':
	$val *= 1024; // fallthru
    case 'm':
	$val *= 1024; // fallthru
    case 'k':
	$val *= 1024;
    }
    return $val;
}

function whyNotText($whyNot, $action) {
    global $Conf;
    if (!is_array($whyNot))
	$whyNot = array($whyNot => 1);
    $paperId = (isset($whyNot['paperId']) ? $whyNot['paperId'] : -1);
    $reviewId = (isset($whyNot['reviewId']) ? $whyNot['reviewId'] : -1);
    $thisPaper = ($paperId < 0 ? "this paper" : "paper #$paperId");
    $text = '';
    if (isset($whyNot['invalidId'])) {
	$x = $whyNot['invalidId'] . "Id";
	$xid = (isset($whyNot[$x]) ? " \"" . $whyNot[$x] . "\"" : "");
	$text .= "Invalid " . $whyNot['invalidId'] . " number" . htmlspecialchars($xid) . ". ";
    }
    if (isset($whyNot['noPaper']))
	$text .= "Nenhum artigr" . ($paperId < 0 ? "" : " #$paperId") . ". ";
    if (isset($whyNot['noReview']))
	$text .= "Nenhuma revisão" . ($reviewId < 0 ? "" : " #$reviewId") . ". ";
    if (isset($whyNot['dbError']))
	$text .= $whyNot['dbError'] . " ";
    if (isset($whyNot['permission']))
	$text .= "Você não tem permissão para $action $thisPaper. ";
    if (isset($whyNot['withdrawn']))
	$text .= ucfirst($thisPaper) . " foi removido. ";
    if (isset($whyNot['notWithdrawn']))
	$text .= ucfirst($thisPaper) . " não foi removido. ";
    if (isset($whyNot['notSubmitted']))
	$text .= ucfirst($thisPaper) . " nunca foi oficialmente submetido. ";
    if (isset($whyNot['notAccepted']))
	$text .= ucfirst($thisPaper) . " não foi aceito para publicação. ";
    if (isset($whyNot["decided"]))
        $text .= "O processo de revisão para $thisPaper foi completado. ";
    if (isset($whyNot['updateSubmitted']))
	$text .= ucfirst($thisPaper) . " já foi submetido e não pode ser atualizado. ";
    if (isset($whyNot['notUploaded']))
	$text .= ucfirst($thisPaper) . " não pode ser submetido porque você ainda não enviou o artigo. Envie o artigo e tente novamente. ";
    if (isset($whyNot['reviewNotSubmitted']))
	$text .= "Esta revisão não esta pronta para ser visualiada por outros. ";
    if (isset($whyNot['reviewNotComplete']))
	$text .= "Sua própria revisão para $thisPaper não está completa, então você não pode visualizar a revisão de outras pessoas. ";
    if (isset($whyNot['responseNotReady']))
	$text .= "A resposta do autor&rsquo; para $thisPaper ainda não está pronta para ser visualizada pelos revisores. ";
    if (isset($whyNot['reviewsOutstanding']))
	$text .= "Você terá acesso as revisões desde que complete <a href=\"" . hoturl("search", "q=&amp;t=r") . "\"> suas revisões atribuídas para outros artigos</a>.  Se não puder completar suas revisões, por favor, comunique os organizadores da conferência pelo link 'Recusar revisão'. ";
    if (isset($whyNot['reviewNotAssigned']))
	$text .= "Vocẽ não está atribuído para a revisão $thisPaper. ";
    if (isset($whyNot['deadline'])) {
	$dname = $whyNot['deadline'];
	if ($dname[0] == "s")
	    $start = $Conf->setting("sub_open", -1);
	else if ($dname[0] == "p" || $dname[0] == "e")
	    $start = $Conf->setting("rev_open", -1);
	else
	    $start = 1;
	$end = $Conf->setting($dname, -1);
	$now = time();
	if ($start <= 0)
	    $text .= "Você não pode $action $thisPaper ainda. ";
	else if ($start > 0 && $now < $start)
	    $text .= "Você não pode $action $thisPaper até " . $Conf->printableTime($start, "span") . ". ";
	else if ($end > 0 && $now > $end) {
	    if ($dname == "sub_reg")
		$text .= "O prazo de registro expirou. ";
	    else if ($dname == "sub_update")
		$text .= "O prazo para atualização de artigos expirou. ";
	    else if ($dname == "sub_sub")
		$text .= "O prazo para submissão de artigo expirou. ";
	    else if ($dname == "extrev_hard")
		$text .= "The external review deadline has passed. ";
	    else if ($dname == "pcrev_hard")
		$text .= "O prazo de revisão da comissão científica expirou. ";
	    else
		$text .= "O prazo para $action $thisPaper expirou. ";
	    $text .= "It was " . $Conf->printableTime($end, "span") . ". ";
	} else if ($dname == "au_seerev") {
	    if ($Conf->setting("au_seerev") == AU_SEEREV_YES)
		$text .= "Autores que também são revisores não podem ver revisões para artigos os quais ainda não tenham  <a href='" . hoturl("search", "t=rout&amp;q=") . "'>completado a revisão </a> of their own. ";
	    else
		$text .= "Autores não podem visualizar revisões no momento. ";
	} else
	    $text .= "Você não pode $action $thisPaper no momento. ";
	$text .= "(<a class='nowrap' href='" . hoturl("deadlines") . "'>Visualizar prazos</a>) ";
    }
    if (isset($whyNot['override']) && $whyNot['override'])
        $text .= "“Ignorar prazos” pode anular esta restrição. ";
    if (isset($whyNot['blindSubmission']))
	$text .= "Submissão para esta conferencia está oculta. ";
    if (isset($whyNot['author']))
	$text .= "Você não é um contato para $thisPaper. ";
    if (isset($whyNot['conflict']))
	$text .= "Você tem um conflito com $thisPaper. ";
    if (isset($whyNot['externalReviewer']))
	$text .= "Revisores externos não podem visualizar outras revisões para artigos que revisaram. ";
    if (isset($whyNot['differentReviewer']))
	$text .= "Você não pode escrever esta revisão, então não pode altera-la. ";
    if (isset($whyNot['reviewToken']))
	$text .= "If you know a valid review token, enter it above to edit that review. ";
    // finish it off
    if (isset($whyNot['chairMode']))
	$text .= "(<a class='nowrap' href=\"" . selfHref(array("forceShow" => 1)) . "\">" . ucfirst($action) . " the paper anyway</a>) ";
    if (isset($whyNot['forceShow']))
	$text .= "(<a class='nowrap' href=\"". selfHref(array("forceShow" => 1)) . "\">Ignorar conflito</a>) ";
    if ($text && $action == "view")
	$text .= "Insira um número de artigo abaixo, ou <a href='" . hoturl("search", "q=") . "'>listar os artigos que você pode visualizar</a>. ";
    return rtrim($text);
}

function actionTab($text, $url, $default) {
    if ($default)
	return "    <td><div class='vbtab1'><div class='vbtab1x'><div class='vbtab1y'><a href='$url'>$text</a></div></div></div></td>\n";
    else
	return "    <td><div class='vbtab'><a href='$url'>$text</a></div></td>\n";
}

function actionBar($mode = "", $prow = null) {
    global $Me, $Conf, $CurrentList;
    $forceShow = ($Me->is_admin_force() ? "&amp;forceShow=1" : "");

    $goBase = "paper";
    $paperArg = "p=*";
    $xmode = array();
    $listtype = "p";

    if ($mode == "assign")
	$goBase = "assign";
    else if ($mode == "r" || $mode == "re" || $mode == "review")
	$goBase = "review";
    else if ($mode == "c" || $mode == "comment")
	$goBase = "comment";
    else if ($mode == "account") {
	$listtype = "u";
	if ($Me->privChair)
	    $goBase = "profile";
	else
	    $prow = null;
    } else if ($mode == "" && $Me->isPC && $Conf->setting("rev_open"))
	$goBase = "review";
    else if (($wantmode = defval($_REQUEST, "m", defval($_REQUEST, "mode"))))
	$xmode["m"] = $wantmode;

    $listarg = $forceShow;
    $quicklinks_txt = "";
    if ($prow) {
	$id = ($listtype === "u" ? $prow->email : $prow->paperId);
	$quicklinks_txt = quicklinks($id, $goBase, $xmode, $listtype);
	if (isset($CurrentList) && $CurrentList > 0)
	    $listarg .= "&amp;ls=$CurrentList";
    }

    // collect actions
    $x = "<div class='nvbar'><table class='vbar'><tr><td class='spanner'></td>\n";

    if ($quicklinks_txt)
	$x .= "  <td class='quicklinks nowrap'>" . $quicklinks_txt . "</td>\n";
    if ($quicklinks_txt && $Me->privChair && $listtype == "p")
        $x .= "  <td id=\"trackerconnect\" class=\"nowrap\"><a id=\"trackerconnectbtn\" href=\"#\" onclick=\"return hotcrp_deadlines.tracker(1)\" class=\"btn btn-default\" title=\"Start meeting tracker\">&#9759;</a><td>\n";

    $x .= "  <td class='gopaper nowrap'>" . goPaperForm($goBase, $xmode) . "</td>\n";

    return $x . "</tr></table></div>";
}

function parseReviewOrdinal($text) {
    $text = strtoupper($text);
    if (preg_match('/^[A-Z]$/', $text))
	return ord($text) - 64;
    else if (preg_match('/^([A-Z])([A-Z])$/', $text, $m))
	return (ord($m[0]) - 64) * 26 + ord($m[1]) - 64;
    else
	return -1;
}

function unparseReviewOrdinal($ord) {
    if ($ord === null)
	return "x";
    else if (is_object($ord)) {
	if ($ord->reviewOrdinal)
	    return $ord->paperId . unparseReviewOrdinal($ord->reviewOrdinal);
	else
	    return $ord->reviewId;
    } else if ($ord <= 26)
	return chr($ord + 64);
    else
	return chr(intval(($ord - 1) / 26) + 65) . chr(($ord % 26) + 64);
}

function titleWords($title, $chars = 40) {
    // assume that title whitespace has been simplified
    if (strlen($title) <= $chars)
	return $title;
    // don't over-shorten due to UTF-8
    $xtitle = utf8_substr($title, 0, $chars);
    if (($pos = strrpos($xtitle, " ")) > 0
	&& substr($title, strlen($xtitle), 1) != " ")
	$xtitle = substr($xtitle, 0, $pos);
    return $xtitle . "...";
}

function downloadCSV($info, $header, $filename, $opt = array()) {
    global $Opt;
    $iscsv = defval($opt, "type", "csv") == "csv" && !isset($Opt["disableCSV"]);
    $csvg = new CsvGenerator($iscsv ? CsvGenerator::TYPE_COMMA : CsvGenerator::TYPE_TAB);
    if ($header)
        $csvg->set_header($header, true);
    $csvg->add($info);
    $csvg->download_headers($Opt["downloadPrefix"] . $filename . $csvg->extension(), !defval($opt, "inline"));
    $csvg->download();
}

function downloadText($text, $filename, $inline = false) {
    global $Opt;
    $csvg = new CsvGenerator(CsvGenerator::TYPE_TAB);
    $csvg->download_headers($Opt["downloadPrefix"] . $filename, !$inline);
    if ($text !== false) {
        $csvg->add($text);
        $csvg->download();
    }
}

function parse_preference($n) {
    if (preg_match(',\A\s*(-+|\++|[-+]?\d+(?:\.\d*)?|)\s*([xyz]|)\s*\z,i', $n, $m)) {
        if ($m[1] === "")
            $p = 0;
        else if (is_numeric($m[1])) {
            if ($m[1] <= 1000000)
                $p = round($m[1]);
            else
                return null;
        } else if ($m[1][0] === "-")
            $p = -strlen($m[1]);
        else
            $p = strlen($m[1]);
        if ($m[2] === "")
            $e = null;
        else
            $e = (ord($m[2]) & 15) - 9;
        return array($p, $e);
    } else if (strpos($n, "\xE2") !== false)
	// Translate UTF-8 for minus sign into a real minus sign ;)
	return parse_preference(str_replace("\xE2\x88\x92", '-', $n));
    else
	return null;
}

function unparse_expertise($expertise) {
    if ($expertise === null)
        return "";
    else
        return $expertise < 0 ? "X" : ($expertise == 0 ? "Y" : "Z");
}

function unparse_preference($preference, $expertise = null) {
    if (is_object($preference))
        list($preference, $expertise) = array(@$preference->reviewerPreference,
                                              @$preference->reviewerExpertise);
    else if (is_array($preference))
        list($preference, $expertise) = $preference;
    if ($preference === null || $preference === false)
        $preference = "0";
    return $preference . unparse_expertise($expertise);
}

function unparse_preference_span($preference, $topicInterestScore = 0) {
    if (is_object($preference))
        $preference = array(@$preference->reviewerPreference,
                            @$preference->reviewerExpertise);
    if (@$preference[2] !== null)
	$topicInterestScore = $preference[2];
    if ($preference[0] != 0)
	$type = ($preference[0] > 0 ? 1 : -1);
    else
	$type = ($topicInterestScore > 0 ? 1 : -1);
    $t = "";
    if ($preference[0] || $preference[1])
	$t .= "P" . decorateNumber($preference[0]) . unparse_expertise($preference[1]);
    if ($t !== "" && $topicInterestScore)
	$t .= " ";
    if ($topicInterestScore)
	$t .= "T" . decorateNumber($topicInterestScore);
    return " <span class='asspref$type'>$t</span>";
}

function decisionSelector($curOutcome = 0, $id = null, $extra = "") {
    global $Conf;
    $text = "<select" . ($id === null ? "" : " id='$id'") . " name='decision'$extra>\n";
    $outcomeMap = $Conf->outcome_map();
    if (!isset($outcomeMap[$curOutcome]))
	$curOutcome = null;
    $outcomes = array_keys($outcomeMap);
    sort($outcomes);
    $outcomes = array_unique(array_merge(array(0), $outcomes));
    if ($curOutcome === null)
	$text .= "    <option value='' selected='selected'><b>Set decision...</b></option>\n";
    foreach ($outcomes as $key)
	$text .= "    <option value='$key'" . ($curOutcome == $key && $curOutcome !== null ? " selected='selected'" : "") . ">" . htmlspecialchars($outcomeMap[$key]) . "</option>\n";
    return $text . "  </select>";
}

function pcMembers() {
    global $Conf, $Opt;
    $version = 2;
    if (!isset($_SESSION["pcmembers"]) || !is_array($_SESSION["pcmembers"])
	|| count($_SESSION["pcmembers"]) < 4
	|| $Conf->setting("pc") <= 0
	|| $_SESSION["pcmembers"][0] < $Conf->setting("pc")
        || $_SESSION["pcmembers"][1] != $version
	|| count($_SESSION["pcmembers"][2]) == 0
        || $_SESSION["pcmembers"][3] != @$Opt["sortByLastName"]) {
	$pc = array();
	$qa = ($Conf->sversion >= 35 ? ", contactTags" : "") . ($Conf->sversion >= 47 ? ", disabled" : "");
	$result = $Conf->q("select firstName, lastName, affiliation, email, ContactInfo.contactId contactId, roles$qa from ContactInfo join PCMember using (contactId)");
	$by_name_text = array();
	while (($row = edb_orow($result))) {
	    $pc[$row->contactId] = $row = Contact::make($row);
	    if ($row->firstName || $row->lastName) {
		$name_text = Text::name_text($row);
		if (isset($by_name_text[$name_text]))
		    $row->nameAmbiguous = $by_name_text[$name_text]->nameAmbiguous = true;
		$by_name_text[$name_text] = $row;
	    }
	}
	uasort($pc, "Contact::compare");
	$_SESSION["pcmembers"] = array($Conf->setting("pc"), $version, $pc,
                                       @$Opt["sortByLastName"]);
    }
    return $_SESSION["pcmembers"][2];
}

function pcTags() {
    $pcm = pcMembers();
    $tags = array("pc" => "pc");
    foreach ($pcm as $pc)
	if (isset($pc->contactTags) && $pc->contactTags) {
	    foreach (explode(" ", $pc->contactTags) as $t)
		if ($t !== "")
		    $tags[strtolower($t)] = $t;
	}
    ksort($tags);
    return $tags;
}

function pcByEmail($email) {
    $pc = pcMembers();
    foreach ($pc as $id => $row)
	if (strcasecmp($row->email, $email) == 0)
	    return $row;
    return null;
}

function review_type_icon($revtype, $unfinished = null, $title = null) {
    static $revtypemap = array(-3 => array("&minus;", "Refused"),
                               -2 => array("A", "Author"),
                               -1 => array("X", "Conflict"),
                               1 => array("R", "External review"),
                               2 => array("R", "PC review"),
                               3 => array("2", "Secondary review"),
                               4 => array("1", "Primary review"));
    if (!$revtype)
        return '<span class="rt0"></span>';
    $x = $revtypemap[$revtype];
    return '<span class="rt' . $revtype
        . ($revtype > 0 && $unfinished ? "n" : "")
        . '" title="' . ($title ? $title : $revtypemap[$revtype][1])
        . '"><span class="rti">' . $revtypemap[$revtype][0] . '</span></span>';
}

function matchContact($pcm, $firstName, $lastName, $email) {
    $lastmax = $firstmax = false;
    if (!$lastName) {
	$lastName = $email;
	$lastmax = true;
    }
    if (!$firstName) {
	$firstName = $lastName;
	$firstmax = true;
    }
    assert(is_string($email) && is_string($firstName) && is_string($lastName));

    $cid = -2;
    $matchprio = 0;
    foreach ($pcm as $pcid => $pc) {
	// Match full email => definite match.
	// Otherwise, sum priorities as follows:
	//   Entire front of email, or entire first or last name => +10 each
	//   Part of word in email, first, or last name          => +1 each
	// If a string is used for more than one of email, first, and last,
	// don't count a match more than once.  Pick closest match.

	$emailprio = $firstprio = $lastprio = 0;
	if ($email !== "") {
	    if ($pc->email === $email)
		return $pcid;
	    if (($pos = stripos($pc->email, $email)) !== false) {
		if ($pos === 0 && $pc->email[strlen($email)] == "@")
		    $emailprio = 10;
		else if ($pos === 0 || !ctype_alnum($pc->email[$pos - 1]))
		    $emailprio = 1;
	    }
	}
	if ($firstName != "") {
	    if (($pos = stripos($pc->firstName, $firstName)) !== false) {
		if ($pos === 0 && strlen($pc->firstName) == strlen($firstName))
		    $firstprio = 10;
		else if ($pos === 0 || !ctype_alnum($pc->firstName[$pos - 1]))
		    $firstprio = 1;
	    }
	}
	if ($lastName != "") {
	    if (($pos = stripos($pc->lastName, $lastName)) !== false) {
		if ($pos === 0 && strlen($pc->lastName) == strlen($lastName))
		    $lastprio = 10;
		else if ($pos === 0 || !ctype_alnum($pc->firstName[$pos - 1]))
		    $lastprio = 1;
	    }
	}
	if ($lastmax && $firstmax)
	    $thisprio = max($emailprio, $firstprio, $lastprio);
	else if ($lastmax)
	    $thisprio = max($emailprio, $lastprio) + $firstprio;
	else if ($firstmax)
	    $thisprio = $emailprio + max($firstprio, $lastprio);
	else
	    $thisprio = $emailprio + $firstprio + $lastprio;

	if ($thisprio && $matchprio <= $thisprio) {
	    $cid = ($matchprio < $thisprio ? $pcid : -1);
	    $matchprio = $thisprio;
	}
    }
    return $cid;
}

function matchValue($a, $word, $allowKey = false) {
    $outa = array();
    $outb = array();
    $outc = array();
    foreach ($a as $k => $v)
	if (strcmp($word, $v) == 0
	    || ($allowKey && strcmp($word, $k) == 0))
	    $outa[] = $k;
	else if (strcasecmp($word, $v) == 0)
	    $outb[] = $k;
	else if (stripos($v, $word) !== false)
	    $outc[] = $k;
    if (count($outa) > 0)
	return $outa;
    else if (count($outb) > 0)
	return $outb;
    else
	return $outc;
}

function scoreCounts($text, $max = null) {
    $merit = ($max ? array_fill(1, $max, 0) : array());
    $n = $sum = $sumsq = 0;
    foreach (preg_split('/[\s,]+/', $text) as $i)
	if (($i = cvtint($i)) > 0) {
	    while ($i > count($merit))
		$merit[count($merit) + 1] = 0;
	    $merit[$i]++;
	    $sum += $i;
	    $sumsq += $i * $i;
	    $n++;
	}
    $avg = ($n > 0 ? $sum / $n : 0);
    $dev = ($n > 1 ? sqrt(($sumsq - $sum*$sum/$n) / ($n - 1)) : 0);
    return (object) array("v" => $merit, "max" => count($merit),
			  "n" => $n, "avg" => $avg, "stddev" => $dev);
}

function displayOptionsSet($sessionvar, $var = null, $val = null) {
    global $Conf;
    if (isset($_SESSION[$sessionvar]))
	$x = $_SESSION[$sessionvar];
    else if ($sessionvar == "pldisplay")
	$x = $Conf->setting_data("pldisplay_default", "");
    else if ($sessionvar == "ppldisplay")
	$x = $Conf->setting_data("ppldisplay_default", "");
    else
	$x = "";
    if ($x == null || strpos($x, " ") === false) {
        if ($sessionvar == "pldisplay")
            $x = " overAllMerit ";
        else if ($sessionvar == "ppldisplay")
            $x = " tags ";
        else
            $x = " ";
    }

    // set $var to $val in list
    if ($var) {
	$x = str_replace(" $var ", " ", $x);
	if ($val)
	    $x .= "$var ";
    }

    // store list in $_SESSION
    return ($_SESSION[$sessionvar] = $x);
}


function cleanAuthor($row) {
    if (!$row || isset($row->authorTable))
	return;
    $row->authorTable = array();
    if (strpos($row->authorInformation, "\t") === false) {
	foreach (explode("\n", $row->authorInformation) as $line)
	    if ($line != "") {
		$email = $aff = "";
		if (($p1 = strpos($line, '<')) !== false) {
		    $p2 = strpos($line, '>', $p1);
		    if ($p2 === false)
			$p2 = strlen($line);
		    $email = substr($line, $p1 + 1, $p2 - ($p1 + 1));
		    $line = substr($line, 0, $p1) . substr($line, $p2 + 1);
		}
		if (($p1 = strpos($line, '(')) !== false) {
		    $p2 = strpos($line, ')', $p1);
		    if ($p2 === false)
			$p2 = strlen($line);
		    $aff = substr($line, $p1 + 1, $p2 - ($p1 + 1));
		    $line = substr($line, 0, $p1) . substr($line, $p2 + 1);
		    if (!$email && strpos($aff, '@') !== false
			&& preg_match('_^\S+@\S+\.\S+$_', $aff)) {
			$email = $aff;
			$aff = '';
		    }
		}
		$a = Text::split_name($line);
		$a[2] = $email;
		$a[3] = $aff;
		$row->authorTable[] = $a;
	    }
    } else {
	$info = "";
	foreach (explode("\n", $row->authorInformation) as $line)
	    if ($line != "") {
		$row->authorTable[] = $a = explode("\t", $line);
		if ($a[0] && $a[1])
		    $info .= "$a[0] $a[1]";
		else if ($a[0] || $a[1])
		    $info .= $a[0] . $a[1];
                else
                    $info .= $a[2];
		if ($a[3])
		    $info .= " (" . $a[3] . ")";
		else if ($a[2] && ($a[0] || $a[1]))
		    $info .= " <" . $a[2] . ">";
		$info .= "\n";
	    }
	$row->authorInformation = $info;
    }
}

function reviewForm($force = false) {
    global $Conf, $ReviewFormCache;
    if (!$ReviewFormCache || $force)
	$ReviewFormCache = new ReviewForm;
    return $ReviewFormCache;
}


function hotcrp_random_bytes($length = 16, $secure_only = false) {
    $key = false;
    if (function_exists("openssl_random_pseudo_bytes")) {
	$key = openssl_random_pseudo_bytes($length, $strong);
	$key = ($strong ? $key : false);
    }
    if ($key === false || $key === "")
	$key = @file_get_contents("/dev/urandom", false, null, 0, $length);
    if (($key === false || $key === "") && !$secure_only) {
	$key = "";
	while (strlen($key) < $length)
	    $key .= pack("V", mt_rand());
	$key = substr($key, 0, $length);
    }
    if ($key === false || $key === "")
        return false;
    else
        return $key;
}


function encode_token($x, $format = "") {
    $s = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $t = "";
    if (is_int($x))
        $format = "V";
    if ($format)
	$x = pack($format, $x);
    $i = 0;
    $have = 0;
    $n = 0;
    while ($have > 0 || $i < strlen($x)) {
	if ($have < 5 && $i < strlen($x)) {
	    $n += ord($x[$i]) << $have;
	    $have += 8;
	    ++$i;
	}
	$t .= $s[$n & 31];
	$n >>= 5;
	$have -= 5;
    }
    if ($format == "V")
        return preg_replace('/(\AA|[^A])A*\z/', '$1', $t);
    else
        return $t;
}

function decode_token($x, $format = "") {
    $map = "//HIJKLMNO///////01234567/89:;</=>?@ABCDEFG";
    $t = "";
    $n = $have = 0;
    $x = trim(strtoupper($x));
    for ($i = 0; $i < strlen($x); ++$i) {
	$o = ord($x[$i]);
        if ($o >= 48 && $o <= 90 && ($out = ord($map[$o - 48])) >= 48)
            $o = $out - 48;
	else if ($o == 46 /*.*/ || $o == 34 /*"*/)
	    continue;
	else
	    return false;
	$n += $o << $have;
	$have += 5;
	while ($have >= 8 || ($n && $i == strlen($x) - 1)) {
	    $t .= chr($n & 255);
	    $n >>= 8;
	    $have -= 8;
	}
    }
    if ($format == "V") {
        $x = unpack("Vx", $t . "\x00\x00\x00\x00\x00\x00\x00");
        return $x["x"];
    } else if ($format)
        return unpack($format, $t);
    else
        return $t;
}
