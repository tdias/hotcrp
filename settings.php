<?php
// settings.php -- HotCRP chair-only conference settings management page
// HotCRP is Copyright (c) 2006-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

require_once("src/initweb.php");
if ($Me->is_empty() || !$Me->privChair)
    $Me->escape();

if (!isset($_REQUEST["group"]) && isset($_SERVER["PATH_INFO"])
    && preg_match(',\A/(\w+)\z,i', $_SERVER["PATH_INFO"]))
    $_REQUEST["group"] = substr($_SERVER["PATH_INFO"], 1);

$Highlight = defval($_SESSION, "settings_highlight", array());
unset($_SESSION["settings_highlight"]);
$Error = array();
$Values = array();
$DateExplanation = "Date examples: “now”, “10 Dec 2006 11:59:59pm PST” <a href='http://www.gnu.org/software/tar/manual/html_section/Date-input-formats.html'>(more examples)</a>";
$TagStyles = "red|orange|yellow|green|blue|purple|gray|bold|italic|big|small";

$SettingList = array("acct_addr" => "checkbox",
                     "au_seerev" => 2,
                     "banal" => "xspecial",
                     "clickthrough_submit" => "htmlstring",
                     "cmt_always" => "checkbox",
                     "decisions" => "xspecial",
                     "extrev_chairreq" => "checkbox",
                     "extrev_hard" => "date",
                     "extrev_soft" => "date",
                     "extrev_view" => 2,
                     "final_done" => "date",
                     "final_grace" => "grace",
                     "final_open" => "checkbox",
                     "final_soft" => "date",
                     "mailbody_requestreview" => "string",
                     "msg.conflictdef" => "htmlstring",
                     "msg.home" => "htmlstring",
                     "msg.responseinstructions" => "htmlstring",
                     "msg.revprefdescription" => "htmlstring",
                     "opt.contactEmail" => "emailstring",
                     "opt.contactName" => "simplestring",
                     "opt.longName" => "simplestring",
                     "opt.shortName" => "simplestring",
                     "options" => "xspecial",
                     "pc_seeall" => "checkbox",
                     "pc_seeallrev" => 4,
                     "pc_seeblindrev" => 1,
                     "pcrev_any" => "checkbox",
                     "pcrev_editdelegate" => "checkbox",
                     "pcrev_hard" => "date",
                     "pcrev_soft" => "date",
                     "resp_done" => "date",
                     "resp_grace" => "grace",
                     "resp_open" => "checkbox",
                     "resp_words" => "zint",
                     "rev_blind" => 2,
                     "rev_notifychair" => "checkbox",
                     "rev_open" => "cdate",
                     "rev_ratings" => 2,
                     "rev_roundtag" => "special",
                     "reviewform" => "xspecial",
                     "seedec" => 3,
                     "sub_blind" => 3,
                     "sub_collab" => "checkbox",
                     "sub_freeze" => 1,
                     "sub_grace" => "grace",
                     "sub_open" => "cdate",
                     "sub_pcconf" => "checkbox",
                     "sub_pcconfsel" => "checkbox",
                     "sub_reg" => "date",
                     "sub_sub" => "date",
                     "tag_chair" => "special",
                     "tag_color" => "special",
                     "tag_rank" => "special",
                     "tag_seeall" => "checkbox",
                     "tag_vote" => "special",
                     "topics" => "xspecial",
                     "tracks" => "special");

$GroupMapping = array("rev" => "reviews", "rfo" => "reviewform");

$Group = defval($_REQUEST, "group");
if ($Group === "reviews" || $Group === "review")
    $Group = "rev";
if ($Group === "reviewform")
    $Group = "rfo";
if (array_search($Group, array("acc", "msg", "sub", "opt", "rev", "rfo", "dec")) === false) {
    if ($Conf->timeAuthorViewReviews())
	$Group = "dec";
    else if ($Conf->deadlinesAfter("sub_sub") || $Conf->timeReviewOpen())
	$Group = "rev";
    else
	$Group = "sub";
}
if ($Group == "rfo")
    require_once("src/reviewsetform.php");
if ($Group == "acc")
    require_once("src/contactlist.php");


$SettingText = array(
	"sub_open" => "Submissions open setting",
	"sub_reg" => "Paper registration deadline",
	"sub_sub" => "Paper submission deadline",
	"rev_open" => "Reviews open setting",
	"cmt_always" => "Comments open setting",
	"pcrev_soft" => "PC soft review deadline",
	"pcrev_hard" => "PC hard review deadline",
	"extrev_soft" => "External reviewer soft review deadline",
	"extrev_hard" => "External reviewer hard review deadline",
	"sub_grace" => "Submissions grace period",
	"sub_blind" => "Blind submission setting",
	"rev_blind" => "Blind review setting",
	"sub_pcconf" => "Collect PC conflicts setting",
	"sub_pcconfsel" => "Collect conflict types setting",
	"sub_collab" => "Collect collaborators setting",
	"acct_addr" => "Collect addresses setting",
	"sub_freeze" => "Submitters can update until the deadline setting",
	"rev_notifychair" => "Notify chairs about reviews setting",
	"pc_seeall" => "PC can see all papers setting",
	"pcrev_any" => "PC can review any paper setting",
	"extrev_chairreq" => "PC chair must approve proposed external reviewers setting",
	"pcrev_editdelegate" => "PC members can edit delegated reviews setting",
	"pc_seeallrev" => "PC can see all reviews setting",
	"pc_seeblindrev" => "PC can see blind reviewer identities setting",
	"extrev_view" => "External reviewers can view reviews setting",
	"tag_chair" => "Chair tags",
	"tag_vote" => "Voting tags",
	"tag_rank" => "Rank tag",
	"tag_color" => "Tag colors",
	"tag_seeall" => "PC can see tags for conflicted papers",
	"rev_ratings" => "Review ratings setting",
	"au_seerev" => "Authors can see reviews setting",
	"seedec" => "Decision visibility",
	"final_open" => "Collect final versions setting",
	"final_soft" => "Final version upload deadline",
	"final_done" => "Final version upload hard deadline",
	"msg.home" => "Home page message",
	"msg.conflictdef" => "Definition of conflict of interest",
        "msg.responseinstructions" => "Authors’ response instructions",
        "msg.revprefdescription" => "Review preference instructions",
        "clickthrough_submit" => "Clickthrough submission terms",
	"mailbody_requestreview" => "Mail template for external review requests",
        "opt.contactEmail" => "Primary site administrator email"
	);

function parseGrace($v) {
    $t = 0;
    $v = trim($v);
    if ($v == "" || strtoupper($v) == "N/A" || strtoupper($v) == "NONE" || $v == "0")
	return -1;
    if (ctype_digit($v))
	return $v * 60;
    if (preg_match('/^\s*([\d]+):([\d.]+)\s*$/', $v, $m))
	return $m[1] * 60 + $m[2];
    if (preg_match('/^\s*([\d.]+)\s*d(ays?)?(?![a-z])/i', $v, $m)) {
	$t += $m[1] * 3600 * 24;
	$v = substr($v, strlen($m[0]));
    }
    if (preg_match('/^\s*([\d.]+)\s*h(rs?|ours?)?(?![a-z])/i', $v, $m)) {
	$t += $m[1] * 3600;
	$v = substr($v, strlen($m[0]));
    }
    if (preg_match('/^\s*([\d.]+)\s*m(in(ute)?s?)?(?![a-z])/i', $v, $m)) {
	$t += $m[1] * 60;
	$v = substr($v, strlen($m[0]));
    }
    if (preg_match('/^\s*([\d.]+)\s*s(ec(ond)?s?)?(?![a-z])/i', $v, $m)) {
	$t += $m[1];
	$v = substr($v, strlen($m[0]));
    }
    if (trim($v) == "")
	return $t;
    else
	return null;
}

function unparseGrace($v) {
    if ($v === null || $v <= 0 || !is_numeric($v))
	return "none";
    if ($v % 3600 == 0)
	return ($v / 3600) . " hr";
    if ($v % 60 == 0)
	return ($v / 60) . " min";
    return sprintf("%d:%02d", intval($v / 60), $v % 60);
}

function expandMailTemplate($name, $default) {
    global $nullMailer;
    if (!isset($nullMailer)) {
	$nullMailer = new Mailer(null, null);
	$nullMailer->width = 10000000;
    }
    return $nullMailer->expandTemplate($name, $default);
}

function parseValue($name, $type) {
    global $Conf, $SettingText, $Error, $Highlight, $Now;

    if (!isset($_REQUEST[$name]))
	return null;
    $v = trim($_REQUEST[$name]);

    if ($type === "checkbox")
	return $v != "";
    else if ($type === "cdate" && $v == "1")
	return 1;
    else if ($type === "date" || $type === "cdate") {
	if ($v == "" || strtoupper($v) == "N/A" || $v == "0")
	    return -1;
	else if (($v = $Conf->parse_time($v)) !== false)
	    return $v;
	else
	    $err = $SettingText[$name] . ": invalid date.";
    } else if ($type === "grace") {
	if (($v = parseGrace($v)) !== null)
	    return intval($v);
	else
	    $err = $SettingText[$name] . ": invalid grace period.";
    } else if ($type === "int" || $type === "zint") {
        if (preg_match("/\\A[-+]?[0-9]+\\z/", $v))
            return intval($v);
	else
	    $err = $SettingText[$name] . ": should be a number.";
    } else if ($type === "string") {
	// Avoid storing the default message in the database
	if (substr($name, 0, 9) == "mailbody_") {
	    $t = expandMailTemplate(substr($name, 9), true);
	    $v = cleannl($v);
	    if ($t["body"] == $v)
		return 0;
	}
	return ($v == "" ? 0 : array(0, $v));
    } else if ($type === "simplestring") {
	$v = simplify_whitespace($v);
	return ($v == "" ? 0 : array(0, $v));
    } else if ($type === "emailstring") {
        $v = trim($v);
        if (validateEmail($v))
            return ($v == "" ? 0 : array(0, $v));
        else
            $err = $SettingText[$name] . ": invalid email.";
    } else if ($type === "htmlstring") {
	if (($v = CleanHTML::clean($v, $err)) === false)
	    $err = $SettingText[$name] . ": $err";
        else if (str_starts_with($name, "msg.")
                 && $v === $Conf->message_default_html($name))
            return 0;
	else if ($v === $Conf->setting_data($name))
            return null;
        else
	    return ($v == "" ? 0 : array($Now, $v));
    } else if (is_int($type)) {
	if (ctype_digit($v) && $v >= 0 && $v <= $type)
	    return intval($v);
	else
	    $err = $SettingText[$name] . ": parse error on “" . htmlspecialchars($v) . "”.";
    } else
	return $v;

    $Highlight[$name] = true;
    $Error[] = $err;
    return null;
}

function doTags($set, $what) {
    global $Conf, $Values, $Error, $Highlight, $TagStyles;
    $tagger = new Tagger;

    if (!$set && $what == "tag_chair" && isset($_REQUEST["tag_chair"])) {
	$vs = array();
	foreach (preg_split('/\s+/', $_REQUEST["tag_chair"]) as $t)
	    if ($t !== "" && $tagger->check($t, Tagger::NOPRIVATE | Tagger::NOCHAIR | Tagger::NOVALUE))
		$vs[$t] = true;
	    else if ($t !== "") {
		$Error[] = "Chair-only tag “" . htmlspecialchars($t) . "” contains odd characters.";
		$Highlight["tag_chair"] = true;
	    }
	$v = array(count($vs), join(" ", array_keys($vs)));
	if (!isset($Highlight["tag_chair"])
	    && ($Conf->setting("tag_chair") !== $v[0]
		|| $Conf->setting_data("tag_chair") !== $v[1]))
	    $Values["tag_chair"] = $v;
    }

    if (!$set && $what == "tag_vote" && isset($_REQUEST["tag_vote"])) {
	$vs = array();
	foreach (preg_split('/\s+/', $_REQUEST["tag_vote"]) as $t)
	    if ($t !== "" && $tagger->check($t, Tagger::NOPRIVATE | Tagger::NOCHAIR)) {
		if (preg_match('/\A([^#]+)(|#|#0+|#-\d*)\z/', $t, $m))
		    $t = $m[1] . "#1";
		$vs[] = $t;
	    } else if ($t !== "") {
		$Error[] = "Voting tag “" . htmlspecialchars($t) . "” contains odd characters.";
		$Highlight["tag_vote"] = true;
	    }
	$v = array(count($vs), join(" ", $vs));
	if (!isset($Highlight["tag_vote"])
	    && ($Conf->setting("tag_vote") != $v[0]
		|| $Conf->setting_data("tag_vote") !== $v[1]))
	    $Values["tag_vote"] = $v;
    }

    if ($set && $what == "tag_vote" && isset($Values["tag_vote"])) {
	// check allotments
	$pcm = pcMembers();
	foreach (preg_split('/\s+/', $Values["tag_vote"][1]) as $t) {
	    if ($t === "")
		continue;
	    $base = substr($t, 0, strpos($t, "#"));
	    $allotment = substr($t, strlen($base) + 1);

	    $result = $Conf->q("select paperId, tag, tagIndex from PaperTag where tag like '%~" . sqlq_for_like($base) . "'");
	    $pvals = array();
	    $cvals = array();
	    $negative = false;
	    while (($row = edb_row($result))) {
		$who = substr($row[1], 0, strpos($row[1], "~"));
		if ($row[2] < 0) {
		    $Error[] = "Removed " . Text::user_html($pcm[$who]) . "'s negative &ldquo;$base&rdquo; vote for paper #$row[0].";
		    $negative = true;
		} else {
		    $pvals[$row[0]] = defval($pvals, $row[0], 0) + $row[2];
		    $cvals[$who] = defval($cvals, $who, 0) + $row[2];
		}
	    }

	    foreach ($cvals as $who => $what)
		if ($what > $allotment) {
		    $Error[] = Text::user_html($pcm[$who]) . " already has more than $allotment votes for tag &ldquo;$base&rdquo;.";
		    $Highlight["tag_vote"] = true;
		}

	    $q = ($negative ? " or (tag like '%~" . sqlq_for_like($base) . "' and tagIndex<0)" : "");
	    $Conf->qe("delete from PaperTag where tag='" . sqlq($base) . "'$q", "while counting votes");

	    $q = array();
	    foreach ($pvals as $pid => $what)
		$q[] = "($pid, '" . sqlq($base) . "', $what)";
	    if (count($q) > 0)
		$Conf->qe("insert into PaperTag values " . join(", ", $q), "while counting votes");
	}
    }

    if (!$set && $what == "tag_rank" && isset($_REQUEST["tag_rank"])) {
	$vs = array();
	foreach (preg_split('/\s+/', $_REQUEST["tag_rank"]) as $t)
	    if ($t !== "" && $tagger->check($t, Tagger::NOPRIVATE | Tagger::NOCHAIR | Tagger::NOVALUE))
		$vs[] = $t;
	    else if ($t !== "") {
		$Error[] = "Rank tag “" . htmlspecialchars($t) . "” contains odd characters.";
		$Highlight["tag_rank"] = true;
	    }
	if (count($vs) > 1) {
	    $Error[] = "At most one rank tag is currently supported.";
	    $Highlight["tag_rank"] = true;
	}
	$v = array(count($vs), join(" ", $vs));
	if (!isset($Highlight["tag_rank"])
	    && ($Conf->setting("tag_rank") !== $v[0]
		|| $Conf->setting_data("tag_rank") !== $v[1]))
	    $Values["tag_rank"] = $v;
    }

    if (!$set && $what == "tag_color") {
	$vs = array();
	$any_set = false;
	foreach (explode("|", $TagStyles) as $k)
	    if (isset($_REQUEST["tag_color_" . $k])) {
		$any_set = true;
		foreach (preg_split('/,*\s+/', $_REQUEST["tag_color_" . $k]) as $t)
		    if ($t !== "" && $tagger->check($t, Tagger::NOPRIVATE | Tagger::NOCHAIR | Tagger::NOVALUE))
			$vs[] = $t . "=" . $k;
		    else if ($t !== "") {
			$Error[] = ucfirst($k) . " color tag “" . htmlspecialchars($t) . "” contains odd characters.";
			$Highlight["tag_color_" . $k] = true;
		    }
	    }
	$v = array(1, join(" ", $vs));
	if ($any_set && $Conf->setting_data("tag_color") !== $v[1])
	    $Values["tag_color"] = $v;
    }

    if ($set)
        Tagger::invalidate_defined_tags();
}

function doTopics($set) {
    global $Conf, $Values;
    if (!$set) {
	$Values["topics"] = true;
	return;
    }
    $while = "while updating topics";

    $numnew = defval($_REQUEST, "newtopcount", 50);
    $tmap = $Conf->topic_map();
    foreach ($_REQUEST as $k => $v) {
	if (!(strlen($k) > 3 && $k[0] == "t" && $k[1] == "o" && $k[2] == "p"))
	    continue;
	$v = simplify_whitespace($v);
	if ($k[3] == "n" && $v != "" && !ctype_digit($v) && cvtint(substr($k, 4), 100) <= $numnew)
	    $Conf->qe("insert into TopicArea (topicName) values ('" . sqlq($v) . "')", $while);
	else if (($k = cvtint(substr($k, 3), -1)) >= 0) {
	    if ($v == "") {
		$Conf->qe("delete from TopicArea where topicId=$k", $while);
		$Conf->qe("delete from PaperTopic where topicId=$k", $while);
                $Conf->qe("delete from TopicInterest where topicId=$k", $while);
	    } else if (isset($tmap[$k]) && $v != $tmap[$k] && !ctype_digit($v))
		$Conf->qe("update TopicArea set topicName='" . sqlq($v) . "' where topicId=$k", $while);
	}
    }
}


function option_request_to_json(&$new_opts, $id, $current_opts) {
    global $Conf;

    $name = simplify_whitespace(defval($_REQUEST, "optn$id", ""));
    if (!isset($_REQUEST["optn$id"]) && $id[0] != "n") {
        if (@$current_opts[$id])
            $new_opts[$id] = $current_opts[$id];
        return;
    } else if ($name == ""
               || @$_REQUEST["optfp$id"] == "delete"
               || ($id[0] == "n" && ($name == "New option" || $name == "(Insira uma nova opção)")))
        return;

    $oarg = array("name" => $name, "id" => (int) $id, "req_id" => $id);
    if ($id[0] == "n") {
        $nextid = max($Conf->setting("next_optionid", 1), 1);
        foreach ($new_opts as $id => $o)
            $nextid = max($nextid, $id + 1);
        foreach ($current_opts as $id => $o)
            $nextid = max($nextid, $id + 1);
        $oarg["id"] = $nextid;
        $oarg["is_new"] = true;
    }

    if (@$_REQUEST["optd$id"] && trim($_REQUEST["optd$id"]) != "") {
	$t = CleanHTML::clean($_REQUEST["optd$id"], $err);
	if ($t === false) {
	    $Error[] = $err;
	    $Highlight["optd$id"] = true;
	} else
	    $oarg["description"] = $t;
    }

    if (($optvt = @$_REQUEST["optvt$id"])) {
        if (($pos = strpos($optvt, ":")) !== false) {
            $oarg["type"] = substr($optvt, 0, $pos);
            if (preg_match('/:final/', $optvt))
                $oarg["final"] = true;
            if (preg_match('/:ds_(\d+)/', $optvt, $m))
                $oarg["display_space"] = (int) $m[1];
        } else
            $oarg["type"] = $optvt;
    } else
        $oarg["type"] = "checkbox";

    if (PaperOption::type_has_selector($oarg["type"])) {
	$oarg["selector"] = array();
        $seltext = trim(cleannl(defval($_REQUEST, "optv$id", "")));
        if ($seltext == "") {
	    $Error[] = "Enter selectors one per line.";
	    $Highlight["optv$id"] = true;
        } else
            foreach (explode("\n", $seltext) as $t)
                $oarg["selector"][] = $t;
    }

    $oarg["view_type"] = defval($_REQUEST, "optp$id", "pc");
    if (@$oarg["final"])
        $oarg["view_type"] = "pc";

    $oarg["position"] = (int) defval($_REQUEST, "optfp$id", 1);

    if (@$_REQUEST["optdt$id"] == "near_submission"
        || ($oarg["type"] == "pdf" && @$oarg["final"]))
        $oarg["near_submission"] = true;
    else if (@$_REQUEST["optdt$id"] == "highlight")
        $oarg["highlight"] = true;

    $new_opts[$oarg["id"]] = new PaperOption($oarg);
}

function option_clean_form_positions($new_opts, $current_opts) {
    foreach ($new_opts as $id => $o) {
        $current_o = @$current_opts[$id];
        $o->old_position = ($current_o ? $current_o->position : $o->position);
    }
    for ($i = 0; $i < count($new_opts); ++$i) {
	$best = null;
	foreach ($new_opts as $id => $o)
            if (!@$o->position_set
                && (!$best
                    || (@$o->near_submission
                        && !@$best->near_submission)
		    || $o->position < $best->position
		    || ($o->position == $best->position
                        && $o->position != $o->old_position
                        && $best->position == $best->old_position)
		    || ($o->position == $best->position
			&& strcasecmp($o->name, $best->name) < 0)
		    || ($o->position == $best->position
			&& strcasecmp($o->name, $best->name) == 0
			&& strcmp($o->name, $best->name) < 0)))
                $best = $o;
        $best->position = $i + 1;
        $best->position_set = true;
    }
}

function doOptions($set) {
    global $Conf, $Values, $Error, $Highlight;

    if (!$set) {
        $current_opts = PaperOption::option_list();

        // convert request to JSON
        $new_opts = array();
        foreach ($current_opts as $id => $o)
            option_request_to_json($new_opts, $id, $current_opts);
        foreach ($_REQUEST as $k => $v)
            if (substr($k, 0, 4) == "optn"
                && !@$current_opts[substr($k, 4)])
                option_request_to_json($new_opts, substr($k, 4), $current_opts);

        // check abbreviations
        $optabbrs = array();
        foreach ($new_opts as $id => $o)
            if (preg_match('/\Aopt\d+\z/', $o->abbr)) {
                $Error[] = "Option name “" . htmlspecialchars($o->name) . "” is reserved. Please pick another option name.";
                $Highlight["optn$o->req_id"] = true;
            } else if (@$optabbrs[$o->abbr]) {
                $Error[] = "Multiple options abbreviate to “{$o->abbr}”. Please pick option names that abbreviate uniquely.";
                $Highlight["optn$o->req_id"] = $Highlight[$optabbrs[$o->abbr]->req_id] = true;
            } else
                $optabbrs[$o->abbr] = $o;

	if (count($Error) == 0)
	    $Values["options"] = $new_opts;
	return;
    }

    $while = "while updating options";

    $new_opts = $Values["options"];
    $current_opts = PaperOption::option_list();
    option_clean_form_positions($new_opts, $current_opts);

    $newj = (object) array();
    uasort($new_opts, array("PaperOption", "compare"));
    $nextid = $Conf->setting("next_optionid", 1);
    foreach ($new_opts as $id => $o) {
        $newj->$id = $o->unparse();
        $nextid = max($nextid, $id + 1);
    }
    $Conf->save_setting("next_optionid", $nextid);
    $Conf->save_setting("options", 1, count($newj) ? $newj : null);

    $deleted_ids = array();
    foreach ($current_opts as $id => $o)
        if (!@$new_opts[$id])
            $deleted_ids[] = $id;
    if (count($deleted_ids))
        $Conf->qe("delete from PaperOption where optionId in (" . join(",", $deleted_ids) . ")");

    // invalidate cached option list
    PaperOption::option_list(true);
}

function doDecisions($set) {
    global $Conf, $Values, $Error, $Highlight;
    if (!$set) {
	if (defval($_REQUEST, "decn", "") != ""
	    && !defval($_REQUEST, "decn_confirm")) {
	    $delta = (defval($_REQUEST, "dtypn", 1) > 0 ? 1 : -1);
	    $match_accept = (stripos($_REQUEST["decn"], "accept") !== false);
	    $match_reject = (stripos($_REQUEST["decn"], "reject") !== false);
	    if ($delta > 0 && $match_reject) {
		$Error[] = "You are trying to add an Accept-class decision that has “reject” in its name, which is usually a mistake.  To add the decision anyway, check the “Confirm” box and try again.";
		$Highlight["decn"] = true;
		return;
	    } else if ($delta < 0 && $match_accept) {
		$Error[] = "You are trying to add a Reject-class decision that has “accept” in its name, which is usually a mistake.  To add the decision anyway, check the “Confirm” box and try again.";
		$Highlight["decn"] = true;
		return;
	    }
	}

	$Values["decisions"] = true;
	return;
    }

    // mark all used decisions
    $while = "while updating decisions";
    $dec = $Conf->outcome_map();
    $update = false;
    foreach ($_REQUEST as $k => $v)
	if (str_starts_with($k, "dec")
            && ($k = cvtint(substr($k, 3), 0)) != 0) {
	    if ($v == "") {
		$Conf->qe("update Paper set outcome=0 where outcome=$k", $while);
                unset($dec[$k]);
                $update = true;
	    } else if ($v != $dec[$k]) {
                $dec[$k] = $v;
                $update = true;
            }
	}

    if (defval($_REQUEST, "decn", "") != "") {
	$delta = (defval($_REQUEST, "dtypn", 1) > 0 ? 1 : -1);
	for ($k = $delta; isset($dec[$k]); $k += $delta)
            /* skip */;
        $dec[$k] = $_REQUEST["decn"];
        $update = true;
    }

    if ($update)
        $Conf->save_setting("outcome_map", 1, $dec);
}

function doBanal($set) {
    global $Conf, $Values, $Highlight, $Error, $ConfSitePATH;
    if ($set)
	return true;
    if (!isset($_REQUEST["sub_banal"])) {
	if (($t = $Conf->setting_data("sub_banal", "")) != "")
	    $Values["sub_banal"] = array(0, $t);
	else
	    $Values["sub_banal"] = null;
	return true;
    }

    // check banal subsettings
    $old_error_count = count($Error);
    $bs = array_fill(0, 6, "");
    if (($s = trim(defval($_REQUEST, "sub_banal_papersize", ""))) != ""
	&& strcasecmp($s, "any") != 0 && strcasecmp($s, "N/A") != 0) {
	$ses = preg_split('/\s*,\s*|\s+OR\s+/i', $s);
	$sout = array();
	foreach ($ses as $ss)
	    if ($ss != "" && CheckFormat::parse_dimen($ss, 2))
		$sout[] = $ss;
	    else if ($ss != "") {
		$Highlight["sub_banal_papersize"] = true;
		$Error[] = "Invalid paper size.";
		$sout = null;
		break;
	    }
	if ($sout && count($sout))
	    $bs[0] = join(" OR ", $sout);
    }

    if (($s = trim(defval($_REQUEST, "sub_banal_pagelimit", ""))) != ""
	&& strcasecmp($s, "N/A") != 0) {
	if (($sx = cvtint($s, -1)) > 0)
	    $bs[1] = $sx;
	else if (preg_match('/\A(\d+)\s*-\s*(\d+)\z/', $s, $m)
		 && $m[1] > 0 && $m[2] > 0 && $m[1] <= $m[2])
	    $bs[1] = +$m[1] . "-" . +$m[2];
	else {
	    $Highlight["sub_banal_pagelimit"] = true;
	    $Error[] = "Page limit must be a whole number bigger than 0, or a page range such as <code>2-4</code>.";
	}
    }

    if (($s = trim(defval($_REQUEST, "sub_banal_columns", ""))) != ""
	&& strcasecmp($s, "any") != 0 && strcasecmp($s, "N/A") != 0) {
	if (($sx = cvtint($s, -1)) >= 0)
	    $bs[2] = ($sx > 0 ? $sx : $bs[2]);
	else {
	    $Highlight["sub_banal_columns"] = true;
	    $Error[] = "Columns must be a whole number.";
	}
    }

    if (($s = trim(defval($_REQUEST, "sub_banal_textblock", ""))) != ""
	&& strcasecmp($s, "any") != 0 && strcasecmp($s, "N/A") != 0) {
	// change margin specifications into text block measurements
	if (preg_match('/^(.*\S)\s+mar(gins?)?/i', $s, $m)) {
	    $s = $m[1];
	    if (!($ps = CheckFormat::parse_dimen($bs[0]))) {
		$Highlight["sub_banal_pagesize"] = true;
		$Highlight["sub_banal_textblock"] = true;
		$Error[] = "You must specify a page size as well as margins.";
	    } else if (strpos($s, "x") !== false) {
		if (!($m = CheckFormat::parse_dimen($s)) || !is_array($m) || count($m) > 4) {
		    $Highlight["sub_banal_textblock"] = true;
		    $Error[] = "Invalid margin definition.";
		    $s = "";
		} else if (count($m) == 2)
		    $s = array($ps[0] - 2 * $m[0], $ps[1] - 2 * $m[1]);
		else if (count($m) == 3)
		    $s = array($ps[0] - 2 * $m[0], $ps[1] - $m[1] - $m[2]);
		else
		    $s = array($ps[0] - $m[0] - $m[2], $ps[1] - $m[1] - $m[3]);
	    } else {
		$s = preg_replace('/\s+/', 'x', $s);
		if (!($m = CheckFormat::parse_dimen($s)) || (is_array($m) && count($m) > 4)) {
		    $Highlight["sub_banal_textblock"] = true;
		    $Error[] = "Invalid margin definition.";
		} else if (!is_array($m))
		    $s = array($ps[0] - 2 * $m, $ps[1] - 2 * $m);
		else if (count($m) == 2)
		    $s = array($ps[0] - 2 * $m[1], $ps[1] - 2 * $m[0]);
		else if (count($m) == 3)
		    $s = array($ps[0] - 2 * $m[1], $ps[1] - $m[0] - $m[2]);
		else
		    $s = array($ps[0] - $m[1] - $m[3], $ps[1] - $m[0] - $m[2]);
	    }
	    $s = (is_array($s) ? CheckFormat::unparse_dimen($s) : "");
	}
	// check text block measurements
	if ($s && !CheckFormat::parse_dimen($s, 2)) {
	    $Highlight["sub_banal_textblock"] = true;
	    $Error[] = "Invalid text block definition.";
	} else if ($s)
	    $bs[3] = $s;
    }

    if (($s = trim(defval($_REQUEST, "sub_banal_bodyfontsize", ""))) != ""
	&& strcasecmp($s, "any") != 0 && strcasecmp($s, "N/A") != 0) {
	if (!is_numeric($s) || $s <= 0) {
	    $Highlight["sub_banal_bodyfontsize"] = true;
	    $Error[] = "Minimum body font size must be a number bigger than 0.";
	} else
	    $bs[4] = $s;
    }

    if (($s = trim(defval($_REQUEST, "sub_banal_bodyleading", ""))) != ""
	&& strcasecmp($s, "any") != 0 && strcasecmp($s, "N/A") != 0) {
	if (!is_numeric($s) || $s <= 0) {
	    $Highlight["sub_banal_bodyleading"] = true;
	    $Error[] = "Minimum body leading must be a number bigger than 0.";
	} else
	    $bs[5] = $s;
    }

    while (count($bs) > 0 && $bs[count($bs) - 1] == "")
	array_pop($bs);

    // actually create setting
    if (count($Error) == $old_error_count) {
	$Values["sub_banal"] = array(1, join(";", $bs));
	$zoomarg = "";

	// Perhaps we have an old pdftohtml with a bad -zoom.
	for ($tries = 0; $tries < 2; ++$tries) {
	    $cf = new CheckFormat();
	    $s1 = $cf->analyzeFile("$ConfSitePATH/src/sample.pdf", "letter;2;;6.5inx9in;12;14" . $zoomarg);
	    $e1 = $cf->errors;
	    if ($s1 == 1 && ($e1 & CheckFormat::ERR_PAPERSIZE) && $tries == 0)
		$zoomarg = ">-zoom=1";
	    else if ($s1 != 2 && $tries == 1)
		$zoomarg = "";
	}

	$Values["sub_banal"][1] .= $zoomarg;
	$e1 = $cf->errors;
	$s2 = $cf->analyzeFile("$ConfSitePATH/src/sample.pdf", "a4;1;;3inx3in;14;15" . $zoomarg);
	$e2 = $cf->errors;
	$want_e2 = CheckFormat::ERR_PAPERSIZE | CheckFormat::ERR_PAGELIMIT
	    | CheckFormat::ERR_TEXTBLOCK | CheckFormat::ERR_BODYFONTSIZE
	    | CheckFormat::ERR_BODYLEADING;
	if ($s1 != 2 || $e1 != 0 || $s2 != 1 || ($e2 & $want_e2) != $want_e2)
	    $Conf->warnMsg("Running the automated paper checker on a sample PDF file produced unexpected results.  Check that your <code>pdftohtml</code> package is up to date.  You may want to disable the automated checker for now. (Internal error information: $s1 $e1 $s2 $e2)");
    }
}

function do_save_tracks($set) {
    global $Values, $Error, $Highlight;
    if ($set)
        return true;
    $tagger = new Tagger;
    $tracks = (object) array();
    $missing_tags = false;
    for ($i = 1; isset($_REQUEST["name_track$i"]); ++$i) {
        $trackname = trim($_REQUEST["name_track$i"]);
        if ($trackname === "" || $trackname === "(tag)")
            continue;
        else if (!$tagger->check($trackname, Tagger::NOPRIVATE | Tagger::NOCHAIR | Tagger::NOVALUE)
                 || ($trackname === "_" && $i != 1)) {
            $Error[] = "Track name “" . htmlspecialchars($trackname) . "” contains odd characters.";
            $Highlight["name_track$i"] = $Highlight["tracks"] = true;
            continue;
        }
        $t = (object) array();
        foreach (array("view", "viewrev", "assrev", "unassrev") as $type)
            if (($ttype = defval($_REQUEST, "${type}_track$i", "")) == "+"
                || $ttype == "-") {
                $ttag = trim(defval($_REQUEST, "${type}tag_track$i", ""));
                if ($ttag === "" || $ttag === "(tag)") {
                    $Error[] = "Tag missing for track setting.";
                    $Highlight["${type}_track$i"] = $Highlight["tracks"] = true;
                } else if ($tagger->check($ttag, Tagger::NOPRIVATE | Tagger::NOCHAIR | Tagger::NOVALUE))
                    $t->$type = $ttype . $ttag;
                else {
                    $Error[] = "Tag “" . htmlspecialchars($ttag) . "” contains odd characters.";
                    $Highlight["${type}_track$i"] = $Highlight["tracks"] = true;
                }
            }
        if (count((array) $t) || @$tracks->_)
            $tracks->$trackname = $t;
    }
    if (count((array) $tracks))
        $Values["tracks"] = array(1, json_encode($tracks));
    else
        $Values["tracks"] = null;
}

function doSpecial($name, $set) {
    global $Values, $Error, $Highlight;
    if ($name == "tag_chair" || $name == "tag_vote"
	|| $name == "tag_rank" || $name == "tag_color")
	doTags($set, $name);
    else if ($name == "topics")
	doTopics($set);
    else if ($name == "options")
	doOptions($set);
    else if ($name == "decisions")
	doDecisions($set);
    else if ($name == "reviewform") {
	if (!$set)
	    $Values[$name] = true;
	else
	    rf_update();
    } else if ($name == "banal")
	doBanal($set);
    else if ($name == "rev_roundtag") {
	if (!$set && !isset($_REQUEST["rev_roundtag"]))
	    $Values["rev_roundtag"] = null;
	else if (!$set) {
	    $t = trim($_REQUEST["rev_roundtag"]);
	    if ($t == "" || $t == "(None)")
		$Values["rev_roundtag"] = null;
	    else if (preg_match('/^[a-zA-Z0-9]+$/', $t))
		$Values["rev_roundtag"] = array(1, $t);
	    else {
		$Error[] = "The review round must contain only letters and numbers.";
		$Highlight["rev_roundtag"] = true;
	    }
	}
    } else if ($name == "tracks")
        do_save_tracks($set);
}

function truthy($x) {
    return !($x === null || $x === 0 || $x === false
             || $x === "" || $x === "0" || $x === "false");
}

function accountValue($name, $type) {
    global $Values;
    $xname = $name;
    if (($dot = strpos($name, ".")) !== false) {
        $xname = str_replace(".", "_", $name);
        if (isset($_REQUEST[$xname]))
            $_REQUEST[$name] = $_REQUEST[$xname];
    }

    if ($type === "special" || $type === "xspecial") {
        if (truthy(@$_REQUEST["has_$xname"]))
            doSpecial($name, false);
    } else if (isset($_REQUEST[$xname])
               || (($type === "cdate" || $type === "checkbox")
                   && truthy(@$_REQUEST["has_$xname"]))) {
	$v = parseValue($name, $type);
	if ($v === null) {
	    if ($type !== "cdate" && $type !== "checkbox")
		return;
	    $v = 0;
	}
	if (!is_array($v) && $v <= 0 && !is_int($type) && $type !== "zint")
	    $Values[$name] = null;
	else
	    $Values[$name] = $v;
    }
}

function value($name, $default = null) {
    global $Conf, $Values;
    if (array_key_exists($name, $Values))
        return $Values[$name];
    else
        return $default;
}

function value_or_setting($name) {
    global $Conf, $Values;
    if (array_key_exists($name, $Values))
        return $Values[$name];
    else
        return $Conf->setting($name);
}

if (isset($_REQUEST["update"]) && check_post()) {
    // parse settings
    foreach ($SettingList as $name => $type)
        accountValue($name, $type);

    // check date relationships
    foreach (array("sub_reg" => "sub_sub", "pcrev_soft" => "pcrev_hard",
		   "extrev_soft" => "extrev_hard", "final_soft" => "final_done")
	     as $first => $second)
	if (!isset($Values[$first]) && isset($Values[$second]))
	    $Values[$first] = $Values[$second];
	else if (isset($Values[$first]) && isset($Values[$second])) {
	    if ($Values[$second] && !$Values[$first])
		$Values[$first] = $Values[$second];
	    else if ($Values[$second] && $Values[$first] > $Values[$second]) {
		$Error[] = $SettingText[$first] . " must come before " . $SettingText[$second] . ".";
		$Highlight[$first] = true;
		$Highlight[$second] = true;
	    }
	}
    if (array_key_exists("sub_sub", $Values))
	$Values["sub_update"] = $Values["sub_sub"];
    // need to set 'resp_open' to a timestamp,
    // so we can join on later review changes
    if (value("resp_open") > 0 && $Conf->setting("resp_open") <= 0)
	$Values["resp_open"] = $Now;
    if (@($Values["opt.longName"][1] === "(same as abbreviation)"))
        $Values["opt.longName"][1] = "";

    // update 'papersub'
    if (isset($_REQUEST["pc_seeall"])) {
	// see also conference.php
	$result = $Conf->q("select ifnull(min(paperId),0) from Paper where " . (defval($Values, "pc_seeall", 0) <= 0 ? "timeSubmitted>0" : "timeWithdrawn<=0"));
	if (($row = edb_row($result)) && $row[0] != $Conf->setting("papersub"))
	    $Values["papersub"] = $row[0];
    }

    // warn on other relationships
    if (value("resp_open") > 0
        && value("au_seerev", -1) <= 0
        && value("resp_done", $Now + 1) > $Now)
	$Conf->warnMsg("Authors are allowed to respond to the reviews, but authors can’t see the reviews.  This seems odd.");
    if (value("sub_freeze", -1) == 0
        && value("sub_open") > 0
        && value("sub_sub") <= 0)
	$Conf->warnMsg("You have not set a paper submission deadline, but authors can update their submissions until the deadline.  This seems odd.  You probably should (1) specify a paper submission deadline; (2) select “Authors must freeze the final version of each submission”; or (3) manually turn off “Open site for submissions” when submissions complete.");
    if (value("sub_open", 1) <= 0
        && $Conf->setting("sub_open") > 0
        && value_or_setting("sub_sub") <= 0)
        $Values["sub_close"] = $Now;
    foreach (array("pcrev_soft", "pcrev_hard", "extrev_soft", "extrev_hard")
	     as $deadline)
	if (value($deadline) > $Now
	    && value($deadline) != $Conf->setting($deadline)
	    && value_or_setting("rev_open") <= 0) {
	    $Conf->warnMsg("Review deadline set.  You may also want to open the site for reviewing.");
	    $Highlight["rev_open"] = true;
	    break;
	}
    if (value_or_setting("au_seerev") != AU_SEEREV_NO
	&& $Conf->setting("pcrev_soft") > 0
	&& $Now < $Conf->setting("pcrev_soft")
	&& count($Error) == 0)
	$Conf->warnMsg("Authors can now see reviews and comments although it is before the review deadline.  This is sometimes unintentional.");
    if (value("final_open")
        && (!value("final_done") || value("final_done") > $Now)
	&& value_or_setting("seedec") != Conference::SEEDEC_ALL)
	$Conf->warnMsg("The system is set to collect final versions, but authors cannot submit final versions until they know their papers have been accepted.  You should change the “Who can see paper decisions” setting to “<strong>Authors</strong>, etc.”");
    if (value("seedec") == Conference::SEEDEC_ALL
        && value_or_setting("au_seerev") == AU_SEEREV_NO)
        $Conf->warnMsg("Authors can see decisions, but not reviews. This is sometimes unintentional.");

    // make settings
    if (count($Error) == 0 && count($Values) > 0) {
	$while = "updating settings";
	$tables = "Settings write, TopicArea write, PaperTopic write, TopicInterest write, PaperOption write";
	if (array_key_exists("decisions", $Values)
            || array_key_exists("tag_vote", $Values))
	    $tables .= ", Paper write";
	if (array_key_exists("tag_vote", $Values))
	    $tables .= ", PaperTag write";
	if (array_key_exists("reviewform", $Values))
	    $tables .= ", PaperReview write";
	$Conf->qe("lock tables $tables", $while);

	// apply settings
	foreach ($Values as $n => $v)
	    if (@$SettingList[$n] == "special" || @$SettingList[$n] == "xspecial")
		doSpecial($n, true);

	$dq = $aq = "";
	foreach ($Values as $n => $v)
            if (@$SettingList[$n] != "xspecial") {
                $dq .= " or name='$n'";
                if (is_array($v))
                    $aq .= ", ('$n', '" . sqlq($v[0]) . "', '" . sqlq($v[1]) . "')";
                else if ($v !== null)
                    $aq .= ", ('$n', '" . sqlq($v) . "', null)";
                if (substr($n, 0, 4) === "opt.")
                    $Opt[substr($n, 4)] = (is_array($v) ? $v[1] : $v);
            }
        if (strlen($dq))
            $Conf->qe("delete from Settings where " . substr($dq, 4), $while);
	if (strlen($aq))
	    $Conf->qe("insert into Settings (name, value, data) values " . substr($aq, 2), $while);

	$Conf->qe("unlock tables", $while);
	$Conf->log("Updated settings group '$Group'", $Me);
	$Conf->load_settings();
    }

    // report errors
    if (count($Error) > 0) {
	$filter_error = array();
	foreach ($Error as $e)
	    if ($e !== true && $e !== 1)
		$filter_error[] = $e;
	if (count($filter_error))
	    $Conf->errorMsg(join("<br />\n", $filter_error));
    }

    // update the review form in case it's changed
    reviewForm(true);
    $_SESSION["settings_highlight"] = $Highlight;
    if (count($Error) == 0)
        redirectSelf();
    unset($_SESSION["settings_highlight"]);
} else if ($Group == "rfo")
    rf_update();
if (isset($_REQUEST["cancel"]) && check_post())
    redirectSelf();


// header and script
$Conf->header("Settings", "settings", actionBar());


function setting_label($name, $text, $islabel = false) {
    global $Highlight;
    if (isset($Highlight[$name]))
	$text = "<span class=\"error\">$text</span>";
    if ($islabel)
	$text = Ht::label($text, $islabel);
    return $text;
}

function setting($name, $defval = null) {
    global $Error, $Conf;
    if (count($Error) > 0)
	return defval($_REQUEST, $name, $defval);
    else
	return defval($Conf->settings, $name, $defval);
}

function setting_data($name, $defval = null) {
    global $Error, $Conf;
    if (count($Error) > 0)
	return defval($_REQUEST, $name, $defval);
    else
	return defval($Conf->settingTexts, $name, $defval);
}

function opt_data($name, $defval = "", $killval = "") {
    global $Error, $Opt;
    if (count($Error) > 0)
        $val = defval($_REQUEST, "opt.$name", $defval);
    else
        $val = defval($Opt, $name, $defval);
    if ($val == $killval)
        $val = "";
    return $val;
}

function doCheckbox($name, $text, $tr = false, $js = "hiliter(this)") {
    $x = setting($name);
    echo ($tr ? "<tr><td class='nowrap'>" : ""),
        Ht::hidden("has_$name", 1),
	Ht::checkbox($name, 1, $x !== null && $x > 0, array("onchange" => $js, "id" => "cb$name")),
	"&nbsp;", ($tr ? "</td><td>" : ""),
	setting_label($name, $text, true),
	($tr ? "</td></tr>\n" : "<br />\n");
}

function doRadio($name, $varr) {
    $x = setting($name);
    if ($x === null || !isset($varr[$x]))
	$x = 0;
    echo "<table>\n";
    foreach ($varr as $k => $text) {
	echo "<tr><td class='nowrap'>", Ht::radio_h($name, $k, $k == $x),
	    "&nbsp;</td><td>";
	if (is_array($text))
	    echo setting_label($name, $text[0], true), "<br /><small>", $text[1], "</small>";
	else
	    echo setting_label($name, $text, true);
	echo "</td></tr>\n";
    }
    echo "</table>\n";
}

function doSelect($name, $nametext, $varr, $tr = false) {
    echo ($tr ? "<tr><td class='nowrap lcaption'>" : ""),
	setting_label($name, $nametext),
	($tr ? "</td><td class='lentry'>" : ": &nbsp;"),
	Ht::select($name, $varr, setting($name),
		    array("onchange" => "hiliter(this)")),
	($tr ? "</td></tr>\n" : "<br />\n");
}

function doTextRow($name, $text, $v, $size = 30,
                   $capclass = "lcaption", $tempText = "") {
    global $Conf;
    $settingname = (is_array($text) ? $text[0] : $text);
    $js = array("class" => "textlite", "size" => $size,
                "onchange" => "hiliter(this)", "hottemptext" => $tempText);
    echo "<tr><td class='$capclass nowrap'>", setting_label($name, $settingname), "</td><td class='lentry'>", Ht::entry($name, $v, $js);
    if (is_array($text) && isset($text[2]))
	echo $text[2];
    if (is_array($text) && $text[1])
	echo "<br /><span class='hint'>", $text[1], "</span>";
    echo "</td></tr>\n";
}

function doDateRow($name, $text, $othername = null, $capclass = "lcaption") {
    global $Conf, $Error, $DateExplanation;
    $x = setting($name);
    if ($x === null || (count($Error) == 0 && $x <= 0)
	|| (count($Error) == 0 && $othername && setting($othername) == $x))
	$v = "N/A";
    else if (count($Error) == 0)
	$v = $Conf->parseableTime($x, true);
    else
	$v = $x;
    if ($DateExplanation) {
	if (is_array($text))
	    $text[1] = $DateExplanation . "<br />" . $text[1];
	else
	    $text = array($text, $DateExplanation);
	$DateExplanation = null;
    }
    doTextRow($name, $text, $v, 30, $capclass, "N/A");
}

function doGraceRow($name, $text, $capclass = "lcaption") {
    global $GraceExplanation;
    if (!isset($GraceExplanation)) {
	$text = array($text, "Example: “15 min”");
	$GraceExplanation = true;
    }
    doTextRow($name, $text, unparseGrace(setting($name)), 15, $capclass, "none");
}

function doActionArea($top) {
    echo "<div class='aa'", ($top ? " style='margin-top:0'" : ""), ">
  <input type='submit' class='bb' name='update' value='Salvar Alterações' />
  &nbsp;<input type='submit' name='cancel' value='Cancelar' />
</div>";
}



// Accounts
function doAccGroup() {
    global $Conf, $Me, $belowHr;

    doCheckbox("acct_addr", "Agrupar endereços e telefones de usuários");

    echo "<h3 class=\"settings g\">Organizadores e Administradores do Sistema</h3>";

    echo "<p><a href='", hoturl("profile", "u=new"), "' class='button'>Criar Conta</a> &nbsp;|&nbsp; ",
	"Selecione um nome de usuário para editar o perfil.</p>\n";
    $pl = new ContactList($Me, false);
    echo $pl->text("pcadminx", hoturl("users", "t=pcadmin"));
}

// Messages
function do_message($name, $description, $rows = 10, $hint = "") {
    global $Conf;
    $default = $Conf->message_default_html($name);
    $current = setting_data($name, $default);
    echo '<div class="fold', ($current == $default ? "c" : "o"),
        '" hotcrpfold="yes">',
        '<div class="f-c childfold" onclick="return foldup(this,event)">',
        '<a class="q fn" href="#" onclick="return foldup(this,event)">',
        expander(true), setting_label($name, $description),
        '</a><a class="q fx" href="#" onclick="return foldup(this,event)">',
        expander(false), setting_label($name, $description),
        '</a> <span class="f-cx fx">(HTML allowed)</span></div>',
        $hint,
        '<textarea class="textlite fx" name="', $name, '" cols="80"',
        ' rows="', $rows, '" onchange="hiliter(this)">',
        htmlspecialchars($current),
        '</textarea></div><div class="g"></div>', "\n";
}

function doMsgGroup() {
    global $Conf, $Opt;

    echo "<div class='f-c'>", setting_label("opt.shortName", "Conference abbreviation"), "</div>\n",
        Ht::entry("opt.shortName", opt_data("shortName"), array("class" => "textlite", "size" => 20, "onchange" => "hiliter(this)")),
        "<div class='g'></div>\n";

    $long = opt_data("longName");
    if ($long == opt_data("shortName"))
        $long = "";
    echo "<div class='f-c'>", setting_label("opt.longName", "Full conference name"), "</div>\n",
        Ht::entry("opt.longName", $long, array("class" => "textlite", "size" => 70, "onchange" => "hiliter(this)", "hottemptext" => "(same as abbreviation)")),
        "<div class='lg'></div>\n";

    echo "<div class='f-c'>", setting_label("opt.contactName", "Name of primary site administrator"), "</div>\n",
        Ht::entry("opt.contactName", opt_data("contactName", null, "Your Name"), array("class" => "textlite", "size" => 50, "onchange" => "hiliter(this)")),
        "<div class='g'></div>\n";

    echo "<div class='f-c'>", setting_label("opt.contactEmail", "Email do Administrador Principal"), "</div>\n",
        Ht::entry("opt.contactEmail", opt_data("contactEmail", null, "you@example.com"), array("class" => "textlite", "size" => 40, "onchange" => "hiliter(this)")),
        "<div class='ug'></div>\n",
        "<div class='hint'>O administrador do site é listado como contato nos emails do sistema.</div>",
        "<div class='lg'></div>\n";

    do_message("msg.home", "Home page message");
    do_message("clickthrough_submit", "Concorde com os termos de submissão", 10,
               "<div class=\"hint fx\">Usuários devem concordar com estes termos para editar ou submeter um artigo. Use HTML e considere incluir um cabeçalho ;Termos de Submissão</div>");
 
    do_message("msg.conflictdef", "Definição de conflitos de interesse", 5);
    do_message("msg.revprefdescription", "Review preference instructions", 20);
    do_message("msg.responseinstructions", "Authors’ response instructions");
}

// Submissions
function doSubGroup() {
    global $Conf;

    doCheckbox('sub_open', '<b>Abrir site para submissões</b>');

    echo "<div class='g'></div>\n";
    echo "<strong>Submissão oculta:</strong> Os autores podem ocultar nomes dos revisores?<br />\n";
    doRadio("sub_blind", array(Conference::BLIND_ALWAYS => "Sim—submissões são anônimas",
                               Conference::BLIND_NEVER => "Nenhuma nome de autor é visível para revisores",
                               Conference::BLIND_UNTILREVIEW => "Oculto até o nome do autor ou revisor tornar-se visível após a revisão de submissão",
                               Conference::BLIND_OPTIONAL => "Depende do autor decidir se expõe seu nome"));

    echo "<div class='g'></div>\n<table>\n";
    doDateRow("sub_reg", "Prazo para registro de Artigo", "sub_sub");
    doDateRow("sub_sub", "Prazo para submissão de Artigo");
    doGraceRow("sub_grace", 'Prazo de Carência');
    echo "</table>\n";

    echo "<div class='g'></div>\n<table id='foldpcconf' class='fold",
	($Conf->setting("sub_pcconf") ? "o" : "c"), "'>\n";
    doCheckbox("sub_pcconf", "Agrupar conflitos de autores com membros da comissão científica", true,
	       "hiliter(this);void fold('pcconf',!this.checked)");
    echo "<tr class='fx'><td></td><td>";
    doCheckbox("sub_pcconfsel", "Agrupar tipos de conflitos da comissão científica (“Acessor/estudante,” “Colaborador Recente,” etc.)");
    echo "</td></tr>\n";
    doCheckbox("sub_collab", "Agrupar outras colaborações do autor como texto", true);
    echo "</table>\n";

    if (is_executable("src/banal")) {
	echo "<div class='g'></div>",
            Ht::hidden("has_banal", 1),
            "<table id='foldbanal' class='", ($Conf->setting("sub_banal") ? "foldo" : "foldc"), "'>";
	doCheckbox("sub_banal", "<strong>Checagem de formato automatizada<span class='fx'>:</span></strong>", true, "hiliter(this);void fold('banal',!this.checked)");
	echo "<tr class='fx'><td></td><td class='top'><table>";
	$bsetting = explode(";", preg_replace("/>.*/", "", $Conf->setting_data("sub_banal", "")));
	for ($i = 0; $i < 6; $i++)
	    if (defval($bsetting, $i, "") == "")
		$bsetting[$i] = "N/A";
	doTextRow("sub_banal_papersize", array("Tamanho do Artigo", "Exemplo: “Carta”, “A4”, “8.5in&nbsp;x&nbsp;14in”,<br />“Carta ou A4”"), setting("sub_banal_papersize", $bsetting[0]), 18, "lxcaption", "N/A");
	doTextRow("sub_banal_pagelimit", "Limite de Páginas", setting("sub_banal_pagelimit", $bsetting[1]), 4, "lxcaption", "N/A");
	doTextRow("sub_banal_textblock", array("Text block", "Examples: “6.5in&nbsp;x&nbsp;9in”, “1in&nbsp;margins”"), setting("sub_banal_textblock", $bsetting[3]), 18, "lxcaption", "N/A");
	echo "</table></td><td><span class='sep'></span></td><td class='top'><table>";
	doTextRow("sub_banal_bodyfontsize", array("Tamanho mínimo de fonte", null, "&nbsp;pt"), setting("sub_banal_bodyfontsize", $bsetting[4]), 4, "lxcaption", "N/A");
	doTextRow("sub_banal_bodyleading", array("Minimum leading", null, "&nbsp;pt"), setting("sub_banal_bodyleading", $bsetting[5]), 4, "lxcaption", "N/A");
	doTextRow("sub_banal_columns", array("Columns", null), setting("sub_banal_columns", $bsetting[2]), 4, "lxcaption", "N/A");
	echo "</table></td></tr></table>";
    }

    echo "<hr class='hr' />\n";
    doRadio("sub_freeze", array(0 => "<strong>Autores podem atualizar suas submissões até o prazo</strong>", 1 => array("Autores devem preservar a versão final de cada submissão", "“Autores podem atualizar submissões até a prazo” é geralmente a melhor escolha.  Preservar submissões pode ser util quando não há data limite.")));

    echo "<div class='g'></div><table>\n";
    // compensate for pc_seeall magic
    if ($Conf->setting("pc_seeall") < 0)
	$Conf->settings["pc_seeall"] = 1;
    doCheckbox('pc_seeall', "Membros da comissão científica podem visualizar <i>todos artigos registrados</i> até o prazo de submissão<br /><small>Marque está opção se deseja agrupar preferências de revisão<em> antes</em> da maioria dos artigos serem submetidos. Depos do prazo de submissão, membros da comissão científica podem somente visualizar artigos submetidos.</small>", true);
    echo "</table>";
}

// Submission options
function checkOptionNameUnique($oname) {
    if ($oname == "" || $oname == "none" || $oname == "any")
	return false;
    $m = 0;
    foreach (PaperOption::option_list() as $id => $o)
	if (strstr(strtolower($o->name), $oname) !== false)
	    $m++;
    return $m == 1;
}

function doOptGroupOption($o) {
    global $Conf, $Error;

    if (is_string($o))
        $o = new PaperOption(array("id" => $o,
                "name" => "(Insira uma nova opção)",
                "description" => "",
                "type" => "checkbox",
                "position" => count(PaperOption::option_list()) + 1));
    $id = $o->id;

    if (count($Error) > 0 && isset($_REQUEST["optn$id"])) {
	$o = new PaperOption(array("id" => $id,
		"name" => $_REQUEST["optn$id"],
		"description" => defval($_REQUEST, "optd$id", ""),
		"type" => defval($_REQUEST, "optvt$id", "checkbox"),
		"view_type" => defval($_REQUEST, "optp$id", ""),
                "position" => defval($_REQUEST, "optfp$id", 1),
                "highlight" => @($_REQUEST["optdt$id"] == "highlight"),
                "near_submission" => @($_REQUEST["optdt$id"] == "near_submission")));
        if ($o->has_selector())
            $o->selector = explode("\n", rtrim(defval($_REQUEST, "optv$id", "")));
    }

    echo "<tr><td><div class='f-contain'>\n",
	"  <div class='f-i'>",
	"<div class='f-c'>",
	setting_label("optn$id", ($id === "n" ? "Nome da nova opção" : "Nome da opção")),
	"</div>",
	"<div class='f-e'>",
        Ht::entry("optn$id", $o->name, array("class" => "textlite", "hottemptext" => "(Insira uma nova opção)", "size" => 50, "onchange" => "hiliter(this)")),
	"</div>\n",
	"  <div class='f-i'>",
	"<div class='f-c'>",
	setting_label("optd$id", "Descrição"),
	"</div>",
	"<div class='f-e'><textarea class='textlite' name='optd$id' rows='2' cols='50' onchange='hiliter(this)'>", htmlspecialchars($o->description), "</textarea></div>",
	"</div></td>";

    if ($id !== "n") {
	echo "<td style='padding-left: 1em'><div class='f-i'>",
	    "<div class='f-c'>Exemplo de Busca</div>",
	    "<div class='f-e'>";
	$oabbrev = simplify_whitespace($o->name);
	foreach (preg_split('/\s+/', preg_replace('/[^a-z\s]/', '', strtolower($o->name))) as $oword)
	    if (checkOptionNameUnique($oword)) {
		$oabbrev = $oword;
		break;
	    }
	if ($o->has_selector() && count($o->selector) > 1
            && $o->selector[1] !== "")
            $oabbrev .= "#" . strtolower(simplify_whitespace($o->selector[1]));
	if (strstr($oabbrev, " ") !== false)
	    $oabbrev = "\"$oabbrev\"";
	echo "“<a href=\"", hoturl("search", "q=opt:" . urlencode($oabbrev)), "\">",
	    "opt:", htmlspecialchars($oabbrev), "</a>”",
	    "</div></div></td>";
    }

    echo "</tr>\n  <tr><td colspan='2'><table id='foldoptvis$id' class='fold2c fold3o'><tr>";

    echo "<td class='pad'><div class='f-i'><div class='f-c'>",
	setting_label("optvt$id", "Tipo"), "</div><div class='f-e'>";

    $optvt = $o->type;
    if ($optvt == "text" && @$o->display_space > 3)
        $optvt .= ":ds_" . $o->display_space;
    if (@$o->final)
        $optvt .= ":final";

    $show_final = $Conf->collectFinalPapers();
    foreach (PaperOption::option_list() as $ox)
        $show_final = $show_final || @$ox->final;

    $otypes = array();
    if ($show_final)
	$otypes["xxx1"] = array("optgroup", "Opções para submissão");
    $otypes["checkbox"] = "Checkbox";
    $otypes["selector"] = "Seletor";
    $otypes["radio"] = "Botão rádio";
    $otypes["numeric"] = "Numerico";
    $otypes["text"] = "Texto";
    if ($o->type == "text" && @$o->display_space > 3 && $o->display_space != 5)
        $otypes[$optvt] = "Texto Multilinha";
    else
        $otypes["text:ds_5"] = "Texto Multilinha";
    $otypes["pdf"] = "PDF";
    $otypes["slides"] = "Slides";
    $otypes["video"] = "Video";
    $otypes["attachments"] = "Anexos";
    if ($show_final) {
	$otypes["xxx2"] = array("optgroup", "Opções para artigos aceitos");
	$otypes["pdf:final"] = "Versão final alternativa";
	$otypes["slides:final"] = "Slides Final";
	$otypes["video:final"] = "Video Final";
    }
    echo Ht::select("optvt$id", $otypes, $optvt, array("onchange" => "do_option_type(this)", "id" => "optvt$id")),
	"</div></div></td>";
    $Conf->footerScript("do_option_type(\$\$('optvt$id'),true)");

    echo "<td class='fn2 pad'><div class='f-i'><div class='f-c'>",
	setting_label("optp$id", "Visibilidade"), "</div><div class='f-e'>",
	Ht::select("optp$id", array("admin" => "Administradores Somente", "pc" => "Visível para revisores e membros da comissão científica", "nonblind" => "Visível  se autores estão visíveis"), $o->view_type, array("onchange" => "hiliter(this)")),
	"</div></div></td>";

    echo "<td class='pad'><div class='f-i'><div class='f-c'>",
        setting_label("optfp$id", "Ordem do formulário"), "</div><div class='f-e'>";
    $x = array();
    // can't use "foreach (PaperOption::option_list())" because caller
    // uses cursor
    for ($n = 0; $n < count(PaperOption::option_list()); ++$n)
        $x[$n + 1] = ordinal($n + 1);
    if ($id === "n")
        $x[$n + 1] = ordinal($n + 1);
    else
        $x["delete"] = "Remover Opção";
    echo Ht::select("optfp$id", $x, $o->position, array("onchange" => "hiliter(this)")),
        "</div></div></td>";

    echo "<td class='pad fn3'><div class='f-i'><div class='f-c'>",
        setting_label("optdt$id", "Exibir"), "</div><div class='f-e'>";
    echo Ht::select("optdt$id", array("normal" => "Normal",
                                      "highlight" => "Destacado",
                                      "near_submission" => "Próximo da submissao"),
                    $o->display_type(), array("onchange" => "hiliter(this)")),
        "</div></div></td>";

    if (isset($otypes["pdf:final"]))
	echo "<td class='pad fx2'><div class='f-i'><div class='f-c'>&nbsp;</div><div class='f-e hint' style='margin-top:0.7ex'>(Set by accepted authors during final version submission period)</div></div></td>";

    echo "</tr></table>";

    $rows = 3;
    if (PaperOption::type_has_selector($optvt) && count($o->selector)) {
        $value = join("\n", $o->selector) . "\n";
	$rows = max(count($o->selector), 3);
    } else
	$value = "";
    echo "<div id='foldoptv$id' class='", (PaperOption::type_has_selector($optvt) ? "foldo" : "foldc"),
	"'><div class='fx'>",
	"<div class='hint' style='margin-top:1ex'>Entre uma opção por linha.  A primeira opção será padrão.</div>",
	"<textarea class='textlite' name='optv$id' rows='", $rows, "' cols='50' onchange='hiliter(this)'>", htmlspecialchars($value), "</textarea>",
	"</div></div>";

    echo "</div></td></tr>\n";
}

function doOptGroup() {
    global $Conf;

    echo "<h3 class=\"settings\">Opções de Submissão</h3>\n";
    echo "Opções são selecionadas pelos autores no momento de submissão. Exemplos incluem “Artigos da comissão cientifica,” “Considere este artigo para um premio de melhor estudante,” e  “Permitir membro que membros ocultos da CC visualize este artigo.”  O nome da opção deve ser resumido. Uma descrição melhorada pode ser feita depois e utilizar XHTML.  ";
    echo "Adicione uma opção por vez.\n";
    echo "<div class='g'></div>\n",
        Ht::hidden("has_options", 1),
        "<table>";
    $sep = "";
    $all_options = array_merge(PaperOption::option_list()); // get our own iterator
    foreach ($all_options as $o) {
	echo $sep;
	doOptGroupOption($o);
	$sep = "<tr><td colspan='2'><hr class='hr' /></td></tr>\n";
    }

    echo $sep;

    doOptGroupOption("n");

    echo "</table>\n";


    // Topics
    // load topic interests
    $result = $Conf->q("select topicId, interest, count(*) from TopicInterest group by topicId, interest");
    $interests = array();
    $ninterests = 0;
    while (($row = edb_row($result))) {
	if (!isset($interests[$row[0]]))
	    $interests[$row[0]] = array();
	$interests[$row[0]][$row[1]] = $row[2];
	$ninterests += ($row[2] ? 1 : 0);
    }

    echo "<h3 class=\"settings g\">Tópicos</h3>\n";
    echo "Insira um tópico por linha. Autores selecionam os tópicos que são aplicáveis aos seus artigos; Membros da Comissão podem usar esta informação para procurar artigos que desejam revisar. Para deletar um tópico, delete seu nome.\n";
    echo "<div class='g'></div>",
        Ht::hidden("has_topics", 1),
        "<table id='newtoptable' class='", ($ninterests ? "foldo" : "foldc"), "'>";
    echo "<tr><th colspan='2'></th><th class='fx'><small>Low</small></th><th class='fx'><small>High</small></th></tr>";
    $td1 = "<td class='lcaption'>Atual</td>";
    foreach ($Conf->topic_map() as $tid => $tname) {
	echo "<tr>$td1<td class='lentry'><input type='text' class='textlite' name='top$tid' value=\"", htmlspecialchars($tname), "\" size='40' onchange='hiliter(this)' /></td>";

	$tinterests = defval($interests, $tid, array());
	echo "<td class='fx rpentry'>", (defval($tinterests, 0) ? "<span class='topic0'>" . $tinterests[0] . "</span>" : ""), "</td>",
	    "<td class='fx rpentry'>", (defval($tinterests, 2) ? "<span class='topic2'>" . $tinterests[2] . "</span>" : ""), "</td>";

	if ($td1 !== "<td></td>") {
	    // example search
	    echo "<td class='llentry' style='vertical-align: top' rowspan='40'><div class='f-i'>",
		"<div class='f-c'>Example search</div>";
	    $oabbrev = strtolower($tname);
	    if (strstr($oabbrev, " ") !== false)
		$oabbrev = "\"$oabbrev\"";
	    echo "“<a href=\"", hoturl("search", "q=topic:" . urlencode($oabbrev)), "\">",
		"topic:", htmlspecialchars($oabbrev), "</a>”",
		"<div class='hint'>Topic abbreviations are also allowed.</div>";
	    if ($ninterests)
		echo "<a class='hint fn' href=\"#\" onclick=\"return fold('newtoptable')\">Show PC interest counts</a>",
		    "<a class='hint fx' href=\"#\" onclick=\"return fold('newtoptable')\">Hide PC interest counts</a>";
	    echo "</div></td>";
	}
	echo "</tr>\n";
	$td1 = "<td></td>";
    }
    $td1 = "<td class='lcaption' rowspan='40'>Novo<br /><small><a href='#' onclick='return authorfold(\"newtop\",1,1)'>Mais</a> | <a href='#' onclick='return authorfold(\"newtop\",1,-1)'>Anteriores</a></small></td>";
    for ($i = 1; $i <= 40; $i++) {
	echo "<tr id='newtop$i' class='auedito'>$td1<td class='lentry'><input type='text' class='textlite' name='topn$i' value=\"\" size='40' onchange='hiliter(this)' /></td></tr>\n";
	$td1 = "";
    }
    echo "</table>",
	"<input id='newtopcount' type='hidden' name='newtopcount' value='40' />";
    $Conf->echoScript("authorfold(\"newtop\",0,3)");
}

// Reviews
function do_track_permission($type, $question, $tnum, $thistrack) {
    global $Conf, $Error;
    if (count($Error) > 0) {
        $tclass = defval($_REQUEST, "${type}_track$tnum", "");
        $ttag = defval($_REQUEST, "${type}tag_track$tnum", "");
    } else if ($thistrack && @$thistrack->$type) {
        $tclass = substr($thistrack->$type, 0, 1);
        $ttag = substr($thistrack->$type, 1);
    } else
        $tclass = $ttag = "";

    echo "<tr hotcrpfold=\"1\" class=\"fold", ($tclass == "" ? "c" : "o"), "\">",
        "<td class=\"lxcaption\">",
        setting_label("${type}_track$tnum", $question, "${type}_track$tnum"),
        "</td>",
        "<td>",
        Ht::select("${type}_track$tnum", array("" => "Whole PC", "+" => "PC members with tag:", "-" => "PC members without tag:"), $tclass,
                   array("onchange" => "foldup(this,event,{f:this.selectedIndex==0});hiliter(this)")),
        " &nbsp;",
        Ht::entry("${type}tag_track$tnum", $ttag,
                  array("class" => "fx textlite",
                        "id" => "${type}tag_track$tnum",
                        "onchange" => "hiliter(this)",
                        "hottemptext" => "(tag)")),
        "</td></tr>";
}

function do_track($trackname, $tnum) {
    global $Conf;
    echo "<div id=\"trackgroup$tnum\"",
        ($tnum ? "" : " style=\"display:none\""),
        "><div class=\"trackname\" style=\"margin-bottom:3px\">";
    if ($trackname === "_")
        echo "For papers not on other tracks:", Ht::hidden("name_track$tnum", "_");
    else
        echo "For papers with tag &nbsp;",
            Ht::entry("name_track$tnum", $trackname, array("class" => "textlite", "id" => "name_track$tnum", "hottemptext" => "(tag)")), ":";
    echo "</div>\n";

    $t = $Conf->setting_json("tracks");
    $t = $t && $trackname !== "" ? @$t->$trackname : null;
    echo "<table style=\"margin-left:1.5em;margin-bottom:0.5em\">";
    do_track_permission("view", "Who can view these papers?", $tnum, $t);
    do_track_permission("viewrev", "Who can view reviews?", $tnum, $t);
    do_track_permission("assrev", "Who can be assigned a review?", $tnum, $t);
    do_track_permission("unassrev", "Who can review without an assignment?", $tnum, $t);
    echo "</table></div>";
}

function doRevGroup() {
    global $Conf, $Error, $Highlight, $DateExplanation, $TagStyles;

    doCheckbox("rev_open", "<b>Abrir site para revisões</b>");
    doCheckbox("cmt_always", "Permitir comentários mesmo se revisões estiverem fechadas");

    echo "<div class='g'></div>\n";
    echo "<strong>Privacidade da Revisão:</strong> Os nomes de revisores estão ocultos de seus autores?<br />\n";
    doRadio("rev_blind", array(Conference::BLIND_ALWAYS => "Sim, revisores são anônimos",
                               Conference::BLIND_NEVER => "Não, nenhum nome de revisor é visível para os autores",
                               Conference::BLIND_OPTIONAL => "Depende dos revisores decidir de irão expor seus nomes"));

    echo "<div class='g'></div>\n";
    doCheckbox('rev_notifychair', 'Membros da comissão científica são notificados sobre novas revisões por email');


    // Review visibility
    echo "<h3 class=\"settings g\">Visibilidade de Revisão</h3>\n";

    echo "Os membros da comissão podem <strong>visualizar todas as revisões</strong> exceto as que possuem conflitos?<br />\n";
    doRadio("pc_seeallrev", array(Conference::PCSEEREV_YES => "SIM",
				  Conference::PCSEEREV_UNLESSINCOMPLETE => "Sim, a não ser que não tenham completado uma revissão atribuída ao mesmo artigo",
				  Conference::PCSEEREV_UNLESSANYINCOMPLETE => "Sim, após completar todas suas revisões atribuidas",
				  Conference::PCSEEREV_IFCOMPLETE => "Somente após completar uma revisão do mesmo artigo"));

    echo "<div class='g'></div>\n";
    echo "Somente membros da comissão podem<strong> visualizar nomes  </strong> exceto os que possuem conflitos?<br />\n";
    doRadio("pc_seeblindrev", array(0 => "Yes",
				    1 => "Somente depois de completar uma revisão para o mesmo artigo<br /><span class='hint'>Esta configuração também oculta comentários de revisores  da comissão que não tenha completado uma revisão deste artigo.</span>"));

    echo "<div class='g'></div>";
    echo "Revisores externos podem visualizar outras revisões para artigos atríbuidos, uma vez que tenham submetido seus próprios artigos ?<br />\n";
    doRadio("extrev_view", array(2 => "Yes", 1 => "Sim, mas podem visualizar quem escreveu revisões ocultas", 0 => "No"));


    // PC reviews
    echo "<h3 class=\"settings g\">Revisões da Comissão Científica</h3>\n";

    echo "<table>\n";
    $date_text = $DateExplanation;
    $DateExplanation = null;
    doDateRow("pcrev_soft", array("Prazo", "Revisões são definidas pelo prazo."), "pcrev_hard");
    doDateRow("pcrev_hard", array("Prazo Rígido", "Revisões <em>não podem ser inseridas ou alteradas</em> após o prazo rígido.  Se selecionado, devem geralmente ocorrer após o encntro da comissão.<br />$date_text"));
    if (!($rev_roundtag = setting_data("rev_roundtag")))
	$rev_roundtag = "(None)";
    doTextRow("rev_roundtag", array("Review round", "This will mark new PC review assignments by default.  Examples: “R1”, “R2” &nbsp;<span class='barsep'>|</span>&nbsp; <a href='" . hoturl("help", "t=revround") . "'>What is this?</a>"), $rev_roundtag, 15, "lxcaption", "(None)");
    echo "</table>\n",
        Ht::hidden("has_rev_roundtag", 1);

    echo "<div class='g'></div>\n";
    doCheckbox('pcrev_any', "Membros da comissão científica podem revisar <strong>qualquer</strong> artigo enviado");


    // External reviews
    echo "<h3 class=\"settings g\">Revisões Externas</h3>\n";

    doCheckbox("extrev_chairreq", "Membros da comissão científica devem aprovar os revisores externos propostos");
    doCheckbox("pcrev_editdelegate", "Membros da Comissão podem editar revisões externas que requisitaram");
    echo "<div class='g'></div>";

    echo "<table>\n";
    doDateRow("extrev_soft", "Prazo", "extrev_hard");
    doDateRow("extrev_hard", "Prazo Rígido");
    echo "</table>\n";

    echo "<div class='g'></div>\n";
    $t = expandMailTemplate("requestreview", false);
    echo "<table id='foldmailbody_requestreview' class='",
        ($t == expandMailTemplate("requestreview", true) ? "foldc" : "foldo"),
        "'><tr><td>", foldbutton("mailbody_requestreview", ""), "</td>",
	"<td><a href='#' onclick='return fold(\"mailbody_requestreview\")' class='q'><strong>Exemplo de Email para requisição de revisões externas</strong></a>",
	" <span class='fx'>(<a href='", hoturl("mail"), "'>palavras chave</a> permitidas; deixe vazio por padrão)<br /></span>
<textarea class='tt fx' name='mailbody_requestreview' cols='80' rows='20' onchange='hiliter(this)'>", htmlspecialchars($t["body"]), "</textarea>",
	"</td></tr></table>\n";


    // Tags
    $tagger = new Tagger;
    echo "<h3 class=\"settings g\">Tags</h3>\n";

    echo "<table><tr><td class='lcaption'>", setting_label("tag_chair", "Chair-only tags"), "</td>";
    if (count($Error) > 0)
	$v = defval($_REQUEST, "tag_chair", "");
    else
        $v = join(" ", array_keys($tagger->chair_tags()));
    echo "<td>",
        Ht::hidden("has_tag_chair", 1),
        "<input type='text' class='textlite' name='tag_chair' value=\"", htmlspecialchars($v), "\" size='40' onchange='hiliter(this)' /><br /><div class='hint'>Only PC chairs can change these tags.  (PC members can still <i>view</i> the tags.)</div></td></tr>";

    echo "<tr><td class='lcaption'>", setting_label("tag_vote", "Voting tags"), "</td>";
    if (count($Error) > 0)
	$v = defval($_REQUEST, "tag_vote", "");
    else {
	$x = "";
	foreach ($tagger->vote_tags() as $n => $v)
	    $x .= "$n#$v ";
	$v = trim($x);
    }
    echo "<td>",
        Ht::hidden("has_tag_vote", 1),
        Ht::entry_h("tag_vote", $v, array("class" => "textlite", "size" => 40)),
        "<br /><div class='hint'>“vote#10” declares a voting tag named “vote” with an allotment of 10 votes per PC member. &nbsp;<span class='barsep'>|</span>&nbsp; <a href='", hoturl("help", "t=votetags"), "'>What is this?</a></div></td></tr>";

    echo "<tr><td class='lcaption'>", setting_label("tag_rank", "Ranking tag"), "</td>";
    if (count($Error) > 0)
	$v = defval($_REQUEST, "tag_rank", "");
    else
	$v = $Conf->setting_data("tag_rank", "");
    echo "<td>",
        Ht::hidden("has_tag_rank", 1),
        Ht::entry_h("tag_rank", $v, array("class" => "textlite", "size" => 40)),
        "<br /><div class='hint'>The <a href='", hoturl("offline"), "'>offline reviewing page</a> will expose support for uploading rankings by this tag. &nbsp;<span class='barsep'>|</span>&nbsp; <a href='", hoturl("help", "t=ranking"), "'>What is this?</a></div></td></tr>";
    echo "</table>";

    echo "<div class='g'></div>\n";
    doCheckbox('tag_seeall', "PC can see tags for conflicted papers");

    echo "<div class='g'></div>\n";
    echo "<table id='foldtag_color' class='",
	(defval($_REQUEST, "tagcolor") ? "foldo" : "foldc"), "'><tr>",
	"<td>", foldbutton("tag_color", ""), Ht::hidden("has_tag_color", 1), "</td>",
	"<td><a href='#' onclick='return fold(\"tag_color\")' name='tagcolor' class='q'><strong>Styles and colors</strong></a><br />\n",
	"<div class='hint fx'>Papers tagged with a style name, or with one of the associated tags (if any), will appear in that style in paper lists.</div>",
	"<div class='smg fx'></div>",
	"<table class='fx'><tr><th colspan='2'>Style name</th><th>Tags</th></tr>";
    $tag_colors = array();
    preg_match_all('_(\S+)=(\S+)_', $Conf->setting_data("tag_color", ""), $m,
                   PREG_SET_ORDER);
    foreach ($m as $x)
        $tag_colors[Tagger::canonical_color($x[2])][] = $x[1];
    foreach (explode("|", $TagStyles) as $k) {
	if (count($Error) > 0)
	    $v = defval($_REQUEST, "tag_color_$k", "");
	else if (isset($tag_colors[$k]))
            $v = join(" ", $tag_colors[$k]);
        else
            $v = "";
	echo "<tr class='k0 ${k}tag'><td class='lxcaption'></td><td class='lxcaption'>$k</td><td class='lentry' style='font-size: 10.5pt'><input type='text' class='textlite' name='tag_color_$k' value=\"", htmlspecialchars($v), "\" size='40' onchange='hiliter(this)' /></td></tr>"; /* MAINSIZE */
    }
    echo "</table></td></tr></table>\n";

    echo "<div class='g'></div>\n";
    echo "<table id='foldtracks' class='",
	(defval($_REQUEST, "tracks") || $Conf->has_tracks() || @$Highlight["tracks"] ? "foldo" : "foldc"), "'><tr>",
	"<td>", foldbutton("tracks", ""), Ht::hidden("has_tracks", 1), "</td>",
	"<td><a href='#' onclick='return fold(\"tracks\")' name='tracks' class='q'><strong>Tracks</strong></a><br />\n",
	"<div class='hint fx'>Tracks control whether specific PC members can view or review specific papers. &nbsp;|&nbsp; <a href=\"" . hoturl("help", "t=tracks") . "\">What is this?</a></div>",
	"<div class='smg fx'></div>",
        "<div class='fx'>";
    do_track("", 0);
    do_track("_", 1);
    $tracknum = 2;
    if (($trackj = $Conf->setting_json("tracks")))
        foreach ($trackj as $trackname => $x)
            if ($trackname !== "_") {
                do_track($trackname, $tracknum);
                ++$tracknum;
            }
    echo Ht::button("Add track", array("onclick" => "settings_add_track()"));
    echo "</div></td></tr></table>\n";

    // Review ratings
    echo "<h3 class=\"settings g\">Classificações de Revisão</h3>\n";

    echo "O HotCRP deve agrupar classificações de revisões? &nbsp; <a class='hint' href='", hoturl("help", "t=revrate"), "'>(Aprenda mais)</a><br />\n";
    doRadio("rev_ratings", array(REV_RATINGS_PC => "Sim, Membros da comissão podem classificar revisões", REV_RATINGS_PC_EXTERNAL => "Sim, Membros da comissão científica e revisores externos podem classificar revisões", REV_RATINGS_NONE => "Não"));
}

// Review form
function doRfoGroup() {
    require_once("src/reviewsetform.php");
    rf_show();
}

// Responses and decisions
function doDecGroup() {
    global $Conf, $Highlight, $Error;

    // doCheckbox('au_seerev', '<b>Authors can see reviews</b>');
    echo "<b>Autores podem visualizar revisões e comentários</b> para seus artigos?<br />";
    doRadio("au_seerev", array(AU_SEEREV_NO => "No", AU_SEEREV_ALWAYS => "Yes", AU_SEEREV_YES => "Sim, uma vez que tenham completado qualquer revisão requisitada"));

    echo "<div class='g'></div>\n<table id='foldauresp' class='foldo'>";
    doCheckbox('resp_open', "<b>Reunir autores&rsquo; e respostas aos revisores<span class='fx'>:</span></b>", true, "void fold('auresp',!this.checked)");
    echo "<tr class='fx'><td></td><td><table>";
    doDateRow('resp_done', 'Prazo máximo', null, "lxcaption");
    doGraceRow('resp_grace', 'Período de Carência', "lxcaption");
    doTextRow("resp_words", array("Limíte de Palavras", "Este limite é flexível: autores podem enviar respostas grandes. 0 significa sem limite."), setting("resp_words", 500), 5, "lxcaption", "none");
    echo "</table></td></tr></table>";
    $Conf->footerScript("fold('auresp',!\$\$('cbresp_open').checked)");

    echo "<div class='g'></div>\n<hr class='hr' />\n",
	"Quem pode visualizar o artigo <b>decisões</b> (aceitar/rejeitar)?<br />\n";
    doRadio("seedec", array(Conference::SEEDEC_ADMIN => "Somente administradores",
                            Conference::SEEDEC_NCREV => "Revisores e membros da comissão científica não conflitados",
                            Conference::SEEDEC_REV => "Revisores  <em>e todos</em> os membros da Comissão Científica",
                            Conference::SEEDEC_ALL => "<b>Autores</b>, revisores, e todos membros da comissão científica (e revisores podem visualizar artigos aceitos’ e lista de autores)"));

    echo "<div class='g'></div>\n";
    echo "<table>\n";
    $decs = $Conf->outcome_map();
    krsort($decs);

    // count papers per decision
    $decs_pcount = array();
    $result = $Conf->qe("select outcome, count(*) from Paper where timeSubmitted>0 group by outcome");
    while (($row = edb_row($result)))
	$decs_pcount[$row[0]] = $row[1];

    // real decisions
    $n_real_decs = 0;
    foreach ($decs as $k => $v)
	$n_real_decs += ($k ? 1 : 0);
    $caption = "<td class='lcaption' rowspan='$n_real_decs'>Tipos atuais de decisão</td>";
    foreach ($decs as $k => $v)
	if ($k) {
	    if (count($Error) > 0)
		$v = defval($_REQUEST, "dec$k", $v);
	    echo "<tr>$caption<td class='lentry nowrap'>",
		"<input type='text' class='textlite' name='dec$k' value=\"", htmlspecialchars($v), "\" size='35' onchange='hiliter(this)' />",
		" &nbsp; ", ($k > 0 ? "Accept class" : "Reject class"), "</td>";
	    if (isset($decs_pcount[$k]) && $decs_pcount[$k])
		echo "<td class='lentry nowrap'>", plural($decs_pcount[$k], "paper"), "</td>";
	    echo "</tr>\n";
	    $caption = "";
	}

    // new decision
    $v = "";
    $vclass = 1;
    if (count($Error) > 0) {
	$v = defval($_REQUEST, "decn", $v);
	$vclass = defval($_REQUEST, "dtypn", $vclass);
    }
    echo "<tr><td class='lcaption'>",
	setting_label("decn", "Novo tipo de decisão"),
	"<br /></td>",
	"<td class='lentry nowrap'>",
        Ht::hidden("has_decisions", 1),
        "<input type='text' class='textlite' name='decn' value=\"", htmlspecialchars($v), "\" size='35' onchange='hiliter(this)' /> &nbsp; ",
	Ht::select("dtypn", array(1 => "Categoria aceita", -1 => "Categoria rejeitada"),
		    $vclass, array("onchange" => "hiliter(this)")),
	"<br /><small>Exemplos: “Aceito como artigo curto”, “Inicialmente Rejeitado”</small>",
	"</td>";
    if (defval($Highlight, "decn"))
	echo "<td class='lentry nowrap'>",
	    Ht::checkbox_h("decn_confirm", 1, false),
	    "&nbsp;<span class='error'>", Ht::label("Confirmar"), "</span></td>";
    echo "</tr>\n</table>\n";

    // Final versions
    echo "<h3 class=\"settings g\">Versões Finais</h3>\n";
    echo "<table id='foldfinal' class='foldo'>";
    doCheckbox('final_open', "<b>Reunir versões finalizadas de artigos aceitados<span class='fx'>:</span></b>", true, "void fold('final',!this.checked)");
    echo "<tr class='fx'><td></td><td><table>";
    doDateRow("final_soft", "Data Limite", "final_done", "lxcaption");
    doDateRow("final_done", "Data Limite Rígida", null, "lxcaption");
    doGraceRow("final_grace", "Prazo de Carencia", "lxcaption");
    echo "</table><div class='gs'></div>",
	"<small>Para reunir <em>multiple</em> versões finalizadas,como as que estão em 9pte 11pt, adicione a opção “Versão final alternativa” através de <a href='", hoturl("settings", "group=opt"), "'>Configurações &gt; Opções de Submissão</a>.</small>",
	"</div></td></tr></table>\n\n";
    $Conf->footerScript("fold('final',!\$\$('cbfinal_open').checked)");
}

$belowHr = true;

echo "<form method='post' action='", hoturl_post("settings"), "' enctype='multipart/form-data' accept-charset='UTF-8'><div><input type='hidden' name='group' value='$Group' />\n";

echo "<table class='settings'><tr><td class='caption initial final'>";
echo "<table class='lhsel'>";
foreach (array("acc" => "Contas",
	       "msg" => "Mensagens",
	       "sub" => "Submissões",
	       "opt" => "Opções de Submissão",
	       "rev" => "Revisões",
	       "rfo" => "Formulário de Revisões",
	       "dec" => "Decisões") as $k => $v) {
    $kk = defval($GroupMapping, $k, $k);
    echo "<tr><td>";
    if ($Group == $k)
	echo "<div class='lhl1'><a class='q' href='", hoturl("settings", "group=$kk"), "'>$v</a></div>";
    else
	echo "<div class='lhl0'><a href='", hoturl("settings", "group=$kk"), "'>$v</a></div>";
    echo "</td></tr>";
}
echo "</table></td><td class='top'><div class='lht'>";

// Good to warn multiple times about GD
if (!function_exists("imagecreate"))
    $Conf->warnMsg("Your PHP installation appears to lack GD support, which is required for drawing graphs.  You may want to fix this problem and restart Apache.", true);

echo "<div class='aahc'>";
doActionArea(true);

if ($Group == "acc")
    doAccGroup();
else if ($Group == "msg")
    doMsgGroup();
else if ($Group == "sub")
    doSubGroup();
else if ($Group == "opt")
    doOptGroup();
else if ($Group == "rev")
    doRevGroup();
else if ($Group == "rfo")
    doRfoGroup();
else
    doDecGroup();

doActionArea(false);
echo "</div></div></td></tr>
</table></div></form>\n";

$Conf->footer();
