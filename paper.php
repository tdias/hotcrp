<?php
// paper.php -- HotCRP paper view and edit page
// HotCRP is Copyright (c) 2006-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

$Error = $Warning = array();
require_once("src/initweb.php");
require_once("src/papertable.php");
if ($Me->is_empty())
    $Me->escape();
$useRequest = false;
foreach (array("emailNote", "reason") as $x)
    if (isset($_REQUEST[$x]) && $_REQUEST[$x] == "Optional explanation")
	unset($_REQUEST[$x]);
if (defval($_REQUEST, "mode") == "edit")
    $_REQUEST["mode"] = "pe";
else if (defval($_REQUEST, "mode") == "view")
    $_REQUEST["mode"] = "p";
if (!isset($_REQUEST["p"]) && !isset($_REQUEST["paperId"])
    && isset($_SERVER["PATH_INFO"])
    && preg_match(',\A/(?:new|\d+)\z,i', $_SERVER["PATH_INFO"]))
    $_REQUEST["p"] = substr($_SERVER["PATH_INFO"], 1);


// header
function confHeader() {
    global $paperId, $newPaper, $prow, $paperTable, $Conf, $Error;
    if ($paperTable)
	$mode = $paperTable->mode;
    else
	$mode = "p";
    if ($paperId <= 0)
	$title = ($newPaper ? "Novo trabalho" : "Visualizar trabalho");
    /* else if ($mode == "pe")
	$title = "Edit Paper #$paperId";
    else if ($mode == "r")
	$title = "Paper #$paperId Reviews"; */
    else
	$title = "Trabalho #$paperId";

    $Conf->header($title, "paper_" . ($mode == "pe" ? "editar" : "visualizar"), actionBar($mode, $prow), false);
}

function errorMsgExit($msg) {
    global $Conf;
    confHeader();
    $Conf->footerScript("shortcut().add()");
    $Conf->errorMsgExit($msg);
}


// collect paper ID: either a number or "new"
$newPaper = (defval($_REQUEST, "p") == "new"
	     || defval($_REQUEST, "paperId") == "new");
$paperId = -1;


// general error messages
if (isset($_REQUEST["post"]) && $_REQUEST["post"] && !count($_POST))
    $Conf->errorMsg("Você está tentando enviar um arquivo muito grande. Qualquer modificação será perdida.");


// grab paper row
function loadRows() {
    global $prow, $Error;
    if (!($prow = PaperTable::paperRow($whyNot)))
	errorMsgExit(whyNotText($whyNot, "view"));
    if (isset($Error["paperId"]) && $Error["paperId"] != $prow->paperId)
	$Error = array();
}
$prow = null;
if (!$newPaper) {
    loadRows();
    $paperId = $prow->paperId;
}


// paper actions
if (isset($_REQUEST["clickthrough"]) && check_post()) {
    if (@$_REQUEST["clickthrough_accept"])
        $Me->save_data("clickthrough_" . $_REQUEST["clickthrough"], $Now);
    else if (@$_REQUEST["clickthrough_decline"])
        $Conf->errorMsg("Você não pode editar um trabalho antes de os termos de submissão.");
}
if (isset($_REQUEST["setrevpref"]) && $prow && check_post()) {
    PaperActions::setReviewPreference($prow);
    loadRows();
}
if (isset($_REQUEST["setrank"]) && $prow && check_post()) {
    PaperActions::setRank($prow);
    loadRows();
}
if (isset($_REQUEST["rankctx"]) && $prow && check_post()) {
    PaperActions::rankContext($prow);
    loadRows();
}


// check paper action
if (isset($_REQUEST["checkformat"]) && $prow && $Conf->setting("sub_banal")) {
    $ajax = defval($_REQUEST, "ajax", 0);
    $cf = new CheckFormat();
    $dt = requestDocumentType($_REQUEST);
    if ($Conf->setting("sub_banal$dt"))
	$format = $Conf->setting_data("sub_banal$dt", "");
    else
	$format = $Conf->setting_data("sub_banal", "");
    $status = $cf->analyzePaper($prow->paperId, $dt, $format);

    // chairs get a hint message about multiple checking
    if ($Me->privChair) {
	if (!isset($_SESSION["info"]))
	    $_SESSION["info"] = array();
	$_SESSION["info"]["nbanal"] = defval($_SESSION["info"], "nbanal", 0) + 1;
	if ($_SESSION["info"]["nbanal"] >= 3 && $_SESSION["info"]["nbanal"] <= 6)
	    $cf->msg("info", "To run the format checker for many papers, use Download &gt; Format check on the <a href='" . hoturl("search", "q=") . "'>search page</a>.");
    }

    $cf->reportMessages();
    if ($ajax)
	$Conf->ajaxExit(array("status" => $status), true);
}


// withdraw and revive actions
if (isset($_REQUEST["withdraw"]) && !$newPaper && check_post()) {
    if ($Me->canWithdrawPaper($prow, $whyNot)) {
	$q = "update Paper set timeWithdrawn=" . time()
	    . ", timeSubmitted=if(timeSubmitted>0,-100,0)";
	$reason = defval($_REQUEST, "reason", "");
	if ($reason == "" && $Me->privChair && defval($_REQUEST, "doemail") > 0)
	    $reason = defval($_REQUEST, "emailNote", "");
	if ($Conf->sversion >= 44 && $reason != "")
	    $q .= ", withdrawReason='" . sqlq($reason) . "'";
	$Conf->qe($q . " where paperId=$paperId", "while withdrawing paper");
	$result = $Conf->qe("update PaperReview set reviewNeedsSubmit=0 where paperId=$paperId", "while withdrawing paper");
	$numreviews = edb_nrows_affected($result);
	$Conf->updatePapersubSetting(false);
	loadRows();

	// email contact authors themselves
	if (!$Me->privChair || defval($_REQUEST, "doemail") > 0)
	    Mailer::sendContactAuthors(($prow->conflictType >= CONFLICT_AUTHOR ? "@authorwithdraw" : "@adminwithdraw"),
				       $prow, null, array("reason" => $reason, "infoNames" => 1));

	// email reviewers
	if (($numreviews > 0 && $Conf->timeReviewOpen())
	    || $prow->startedReviewCount > 0)
	    Mailer::sendReviewers("@withdrawreviewer", $prow, null, array("reason" => $reason));

	// remove voting tags so people don't have phantom votes
        $tagger = new Tagger;
	if ($tagger->has_vote()) {
	    $q = array();
	    foreach ($tagger->vote_tags() as $t => $v)
		$q[] = "tag='" . sqlq($t) . "' or tag like '%~" . sqlq_for_like($t) . "'";
	    $Conf->qe("delete from PaperTag where paperId=$prow->paperId and (" . join(" or ", $q) . ")", "while cleaning up voting tags");
	}

	$Conf->log("Withdrew", $Me, $paperId);
	redirectSelf();
    } else
	$Conf->errorMsg(whyNotText($whyNot, "withdraw"));
}
if (isset($_REQUEST["revive"]) && !$newPaper && check_post()) {
    if ($Me->canRevivePaper($prow, $whyNot)) {
	$q = "update Paper set timeWithdrawn=0, timeSubmitted=if(timeSubmitted=-100," . time() . ",0)";
	if ($Conf->sversion >= 44)
	    $q .= ", withdrawReason=null";
	$Conf->qe($q . " where paperId=$paperId", "while reviving paper");
	$Conf->qe("update PaperReview set reviewNeedsSubmit=1 where paperId=$paperId and reviewSubmitted is null", "while reviving paper");
	$Conf->qe("update PaperReview join PaperReview as Req on (Req.paperId=$paperId and Req.requestedBy=PaperReview.contactId and Req.reviewType=" . REVIEW_EXTERNAL . ") set PaperReview.reviewNeedsSubmit=-1 where PaperReview.paperId=$paperId and PaperReview.reviewSubmitted is null and PaperReview.reviewType=" . REVIEW_SECONDARY, "while reviving paper");
	$Conf->qe("update PaperReview join PaperReview as Req on (Req.paperId=$paperId and Req.requestedBy=PaperReview.contactId and Req.reviewType=" . REVIEW_EXTERNAL . " and Req.reviewSubmitted>0) set PaperReview.reviewNeedsSubmit=0 where PaperReview.paperId=$paperId and PaperReview.reviewSubmitted is null and PaperReview.reviewType=" . REVIEW_SECONDARY, "while reviving paper");
	$Conf->updatePapersubSetting(true);
	loadRows();
	$Conf->log("Revived", $Me, $paperId);
	redirectSelf();
    } else
	$Conf->errorMsg(whyNotText($whyNot, "revive"));
}


// set request authorTable from individual components
function set_request_author_table() {
    if (!isset($_REQUEST["aueditcount"]))
	$_REQUEST["aueditcount"] = 50;
    if ($_REQUEST["aueditcount"] < 1)
	$_REQUEST["aueditcount"] = 1;
    $_REQUEST["authorTable"] = array();
    $anyAuthors = false;
    for ($i = 1; $i <= $_REQUEST["aueditcount"]; $i++) {
	if (isset($_REQUEST["auname$i"]) || isset($_REQUEST["auemail$i"]) || isset($_REQUEST["auaff$i"]))
	    $anyAuthors = true;
	$a = simplify_whitespace(defval($_REQUEST, "auname$i", ""));
	$b = simplify_whitespace(defval($_REQUEST, "auemail$i", ""));
	$c = simplify_whitespace(defval($_REQUEST, "auaff$i", ""));
	if ($a != "" || $b != "" || $c != "") {
	    $a = Text::split_name($a);
	    $a[2] = $b;
	    $a[3] = $c;
	    $_REQUEST["authorTable"][] = $a;
	}
    }
    if (!count($_REQUEST["authorTable"]) && !$anyAuthors)
        $_REQUEST["authorTable"] = array();
}
if (isset($_REQUEST["auname1"]) || isset($_REQUEST["auemail1"])
    || isset($_REQUEST["aueditcount"]))
    set_request_author_table();
else
    unset($_REQUEST["authorTable"]);


// update paper action
function attachment_request_keys($o) {
    $x = array();
    $okey = "opt" . (is_object($o) ? $o->id : $o) . "_";
    foreach ($_FILES as $k => $v)
        if (str_starts_with($k, $okey))
            $x[substr($k, strlen($okey))] = $k;
    $okey = "remove_$okey";
    foreach ($_REQUEST as $k => $v)
        if (str_starts_with($k, $okey))
            $x[substr($k, strlen($okey))] = $k;
    ksort($x);
    return $x;
}

function clean_request($prow, $isfinal) {
    // basics
    foreach (array("title", "abstract", "collaborators") as $x)
        if (!isset($_REQUEST[$x]))
            $_REQUEST[$x] = $prow ? $prow->$x : "";
    $_REQUEST["title"] = simplify_whitespace($_REQUEST["title"]);
    $_REQUEST["abstract"] = trim($_REQUEST["abstract"]);
    $_REQUEST["collaborators"] = trim($_REQUEST["collaborators"]);
    if (!isset($_REQUEST["authorTable"]))
        $_REQUEST["authorTable"] = $prow ? $prow->authorTable : array();
    $_REQUEST["authorInformation"] = "";
    foreach ($_REQUEST["authorTable"] as $x)
        $_REQUEST["authorInformation"] .= "$x[0]\t$x[1]\t$x[2]\t$x[3]\n";

    // options
    foreach (PaperOption::option_list() as $o) {
        $oname = "opt$o->id";
        $v = trim(defval($_REQUEST, $oname, ""));
        if (@$o->final && !$isfinal)
            continue;
        else if ($o->type == "checkbox")
            $_REQUEST[$oname] = ($v == 0 || $v == "" ? 0 : 1);
        else if ($o->type == "selector" || $o->type == "radio")
            $_REQUEST[$oname] = cvtint($v, 0);
        else if ($o->type == "numeric") {
            if ($v == "" || ($v = cvtint($v, null)) !== null)
                $_REQUEST[$oname] = ($v == "" ? 0 : $v);
            else
                $_REQUEST[$oname] = false;
        } else if ($o->type == "text") {
            if (@$o->display_space <= 1)
                $_REQUEST[$oname] = simplify_whitespace($v);
            else
                $_REQUEST[$oname] = rtrim($v);
        } else
            unset($_REQUEST[$oname]);
    }
}

function request_differences($prow, $isfinal) {
    global $Conf, $Me;
    if (!$prow)
        return array("new" => true);
    $diffs = array();

    // direct entries
    foreach (array("title", "abstract", "collaborators") as $x)
	if ($_REQUEST[$x] != $prow->$x)
            $diffs[$x] = true;
    $ai = "";
    foreach ($prow->authorTable as $x)
        $ai .= "$x[0]\t$x[1]\t$x[2]\t$x[3]\n";
    if ($_REQUEST["authorInformation"] !== $ai)
        $diffs["authors"] = true;

    // paper upload
    if (fileUploaded($_FILES["paperUpload"]))
        $diffs["paper"] = true;
    if ($Conf->subBlindOptional()
        && !defval($_REQUEST, "blind") != !$prow->blind)
        $diffs["anonymity"] = true;

    // topics
    $result = $Conf->q("select TopicArea.topicId, PaperTopic.paperId from TopicArea left join PaperTopic on PaperTopic.paperId=$prow->paperId and PaperTopic.topicId=TopicArea.topicId");
    while (($row = edb_row($result)))
	if (($row[1] > 0) != (rcvtint($_REQUEST["top$row[0]"]) > 0))
            $diffs["topics"] = true;

    // options
    foreach (PaperOption::option_list() as $o) {
        $oname = "opt$o->id";
        $v = @$_REQUEST[$oname];
        $ox = @$prow->option($o->id);
        if (@$o->final && !$isfinal)
            continue;
        else if ($o->type == "checkbox"
                 || $o->type == "selector"
                 || $o->type == "radio"
                 || $o->type == "numeric") {
            if ($v !== ($ox ? $ox->value : 0))
                $diffs[$o->name] = true;
        } else if ($o->type == "text") {
            if ($v !== ($ox ? $ox->data : ""))
                $diffs[$o->name] = true;
        } else if ($o->type == "attachments") {
            if (count(attachment_request_keys($o)))
                $diffs[$o->name] = true;
        } else if ($o->is_document()) {
            if (fileUploaded($_FILES["opt$o->id"])
                || defval($_REQUEST, "remove_opt$o->id"))
                $diffs[$o->name] = true;
        }
    }

    // PC conflicts
    if ($Conf->setting("sub_pcconf") && (!$isfinal || $Me->privChair)) {
        $curconf = array();
        $result = $Conf->q("select contactId, conflictType from PaperConflict where paperId=$prow->paperId");
        while (($row = edb_row($result)))
            $curconf[$row[0]] = $row[1];

	$cmax = $Me->privChair ? CONFLICT_CHAIRMARK : CONFLICT_MAXAUTHORMARK;
        foreach (pcMembers() as $pcid => $pc) {
            $ctype = cvtint(defval($_REQUEST, "pcc$pcid", 0), 0);
            $otype = defval($curconf, $pcid, 0);
            if ($otype >= CONFLICT_AUTHOR
                || ($otype == CONFLICT_CHAIRMARK && !$Me->privChair))
                $ntype = $otype;
            else if ($ctype)
                $ntype = max(min($ctype, $cmax), CONFLICT_AUTHORMARK);
            else
                $ntype = 0;
            if ($ntype != $otype)
                $diffs["PC conflicts"] = true;
        }
    }

    return $diffs;
}

function upload_option($o, $oname, $prow) {
    global $Conf, $Me, $Error;
    $doc = $Conf->storeDocument($oname, $prow ? $prow->paperId : -1, $o->id);
    if (isset($doc->error_html)) {
	$Error["opt$o->id"] = $doc->error_html;
        return 0;
    } else
        return $doc->paperStorageId;
}

function check_contacts($prow) {
    global $Me, $Conf, $Error;
    $ch = array(array(), array());
    $errs = array();

    $cau = array();
    if ($prow) {
        $result = $Conf->qe("select email, ContactInfo.contactId from ContactInfo join PaperConflict using (contactId) where paperId=$prow->paperId and conflictType>=" . CONFLICT_AUTHOR);
        while (($row = edb_row($result)))
            $cau[$row[0]] = $row[1];
    }

    // Check marked contacts
    if (@$_REQUEST["setcontacts"]) {
        $ncau = array();
        foreach ($_REQUEST as $k => $v)
            if (str_starts_with($k, "contact_")) {
                $email = html_id_decode(substr($k, 8));
                if (@$cau[$email])
                    $ncau[$email] = $cau[$email];
                else if (validateEmail($email)) {
                    if (($c = Contact::find_by_email($email, array("name" => $v), true)))
                        $ncau[$email] = $c->contactId;
                    else
                        $errs[] = "Couldn’t create a contact account for author “" . Text::user_html_nolink(array("email" => $e, "name" => $v)) . "”.";
                }
            }
    } else
        $ncau = $cau;

    // Check new contact
    $new_name = @simplify_whitespace($_REQUEST["newcontact_name"]);
    $new_email = @simplify_whitespace($_REQUEST["newcontact_email"]);
    if ($new_name == "Name")
        $new_name = "";
    if ($new_email == "Email")
        $new_email = "";
    if ($new_email == "" && $new_name == "")
        /* no new contact */;
    else if (!validateEmail($new_email))
        $errs[] = "Entre com um email válido para o novo contato.";
    else {
        if (($new_contact = Contact::find_by_email($new_email, array("name" => $new_name), true)))
            $ncau[$new_contact->email] = $new_contact->contactId;
        else
            $errs[] = "Não foi possivel criar uma conta para o novo contato.";
    }

    // Check for zero contacts
    if (!$prow && (!count($ncau) || !$Me->privChair))
        $ncau[$Me->email] = $Me->contactId;
    if (!$Me->privChair && !count($ncau))
        $errs[] = "Todo trabalho deve ter ao menos um contato.";

    // Report delta
    if (!count($errs)) {
        foreach ($ncau as $email => $id)
            if (!@$cau[$email])
                $ch[0][] = $id;
        foreach ($cau as $email => $id)
            if (!@$ncau[$email])
                $ch[1][] = $id;
        return $ch;
    } else {
        $Error["contactAuthor"] = $errs;
        return false;
    }
}

function save_contacts($paperId, $contact_changes, $request_authors) {
    global $prow, $Conf;
    if (count($contact_changes[0])) {
        $q = array();
        foreach ($contact_changes[0] as $cid)
            $q[] = "($paperId,$cid," . CONFLICT_CONTACTAUTHOR . ")";
        if (!$Conf->qe("insert into PaperConflict (paperId,contactId,conflictType) values " . join(",", $q) . " on duplicate key update conflictType=greatest(conflictType,values(conflictType))", "while updating contacts"))
            return false;
    }
    if (count($contact_changes[1])) {
        if (!$Conf->qe("delete from PaperConflict where paperId=$paperId and conflictType>=" . CONFLICT_AUTHOR . " and contactId in (" . join(",", $contact_changes[1]) . ")", "while updating contacts"))
            return false;
    }

    $aunew = $auold = "";
    if ($prow)
	foreach ($prow->authorTable as $au)
	    if ($au[2] != "")
		$auold .= "'" . sqlq($au[2]) . "', ";
    if ($request_authors) {
        foreach ($_REQUEST["authorTable"] as $au)
            if ($au[2] != "")
                $aunew .= "'" . sqlq($au[2]) . "', ";
    } else
        $aunew = $auold;

    if ($auold != $aunew || count($contact_changes[1])) {
	if ($auold && !$Conf->qe("delete from PaperConflict where paperId=$paperId and conflictType=" . CONFLICT_AUTHOR, "while updating paper authors"))
	    return false;
	if ($aunew && !$Conf->qe("insert into PaperConflict (paperId, contactId, conflictType) select $paperId, contactId, " . CONFLICT_AUTHOR . " from ContactInfo where email in (" . substr($aunew, 0, strlen($aunew) - 2) . ") on duplicate key update conflictType=greatest(conflictType, " . CONFLICT_AUTHOR . ")", "while updating paper authors"))
	    return false;
    }

    return count($contact_changes[0]) || count($contact_changes[1]);
}

function report_update_paper_errors() {
    global $Conf, $Error, $Warning;
    $m = array();
    $Error = array_merge($Error, $Warning);
    foreach ($Error as $k => $v)
        if (is_string($v))
            $m[] = "<li>$v</li>";
        else if (is_array($v) && count($v)) {
            foreach ($v as $x)
                $m[] = "<li>$x</li>";
        }
    $Conf->errorMsg("Ocorreram erros ao salvar seu trabalho. Por favor, corrija-os e tente novamente." . (count($m) ? "<ul>" . join("", $m) . "</ul>" : ""));
}

// send watch messages
function final_submit_watch_callback($prow, $minic) {
    if ($minic->canViewPaper($prow))
	Mailer::send("@finalsubmitnotify", $prow, $minic);
}

function update_paper($Me, $isSubmit, $isSubmitFinal, $diffs) {
    global $paperId, $newPaper, $Error, $Warning, $Conf, $Opt, $prow, $OK;
    $contactId = $Me->contactId;
    $uploaded_documents = array();
    assert(!$newPaper == !!$prow);

    // XXX lock tables

    // clear 'isSubmit' if no paper has been uploaded
    if ((!fileUploaded($_FILES["paperUpload"])
         && ($newPaper || $prow->size == 0)
         && !defval($Opt, "noPapers"))
        || $isSubmitFinal)
	$isSubmit = false;

    // Paper table, uploaded documents: collect updates, check for errors
    $q = array();
    foreach (array("title", "abstract", "authorInformation", "collaborators")
             as $x) {
        if ($_REQUEST[$x] != "" || $x == "collaborators")
            $q[] = "$x='" . sqlq($_REQUEST[$x]) . "'";
        else if ($x == "authorInformation")
            $Error[$x] = "Cada trabalho deve ter ao menos um autor.";
        else if ($x == "title")
            $Error[$x] = "Cada trabalho deve ter um título.";
        else
            $Error[$x] = "Cada trabalho deve ter um resumo.";
    }

    // exit early on error for initial registration
    if (!$prow && count($Error)) {
        if (fileUploaded($_FILES["paperUpload"]))
            $Error["paper"] = "<strong>A submissão que você tentou fazer o upload foi ignorada.</strong>";
        return false;
    }

    // blindness
    if (!$isSubmitFinal) {
        if ($Conf->subBlindNever()
            || ($Conf->subBlindOptional() && !defval($_REQUEST, "blind")))
            $q[] = "blind=0";
        else
            $q[] = "blind=1";
    }

    // paper document
    $paperdoc = null;
    if (fileUploaded($_FILES["paperUpload"])) {
        $paperdoc = $Conf->storeDocument("paperUpload", $prow ? $prow->paperId : -1, $isSubmitFinal ? DTYPE_FINAL : DTYPE_SUBMISSION);
        if (!isset($paperdoc->error_html)) {
            if ($isSubmitFinal)
                $q[] = "finalPaperStorageId=$paperdoc->paperStorageId";
            else
                $q[] = "paperStorageId=$paperdoc->paperStorageId";
            $q[] = "size=$paperdoc->size";
            $q[] = "mimetype='" . sqlq($paperdoc->mimetype) . "'";
            $q[] = "timestamp=$paperdoc->timestamp";
            $q[] = "sha1='" . sqlq($paperdoc->sha1) . "'";
            $uploaded_documents[] = $paperdoc->paperStorageId;
        } else
            $Error["paper"] = $paperdoc->error_html;
    } else if ($newPaper)
        $q[] = "paperStorageId=1";

    // options
    $opt_data = array();
    $no_delete_options = array("true");
    foreach (PaperOption::option_list() as $o) {
	$oname = "opt$o->id";
	$v = trim(defval($_REQUEST, $oname, ""));
        if (@$o->final && !$isSubmitFinal)
            $no_delete_options[] = "optionId!=" . $o->id;
	else if (($o->type == "checkbox" && $_REQUEST[$oname])
                 || $o->type == "selector"
                 || $o->type == "radio"
                 || $o->type == "numeric") {
            if ($_REQUEST[$oname] !== false)
                $opt_data[] = "$o->id, " . $_REQUEST[$oname] . ", null";
            else
                $Error[$oname] = htmlspecialchars($o->name) . " deve ser um número inteiro.";
        } else if ($o->type == "text") {
            if ($_REQUEST[$oname] !== "")
                $opt_data[] = "$o->id, 1, '" . sqlq($_REQUEST[$oname]) . "'";
        } else if ($o->type == "attachments") {
            if (!$prow || !($ox = @$prow->option($o->id)))
                $ox = (object) array("values" => array(), "data" => array());
            if (($next_ordinal = count($ox->data)))
                $next_ordinal = max($next_ordinal, cvtint($ox->data[count($ox->values) - 1]));
            foreach (attachment_request_keys($o) as $k)
                if ($k[0] != "r" /* not "remove_$oname_" */
                    && fileUploaded($_FILES[$k])
                    && ($docid = upload_option($o, $k, $prow))) {
                    $opt_data[] = "$o->id, $docid, '$next_ordinal'";
                    $uploaded_documents[] = $docid;
                    ++$next_ordinal;
                }
            foreach ($ox->values as $docid)
                if (!defval($_REQUEST, "remove_${oname}_$docid"))
                    $no_delete_options[] = "(optionId!=" . $o->id . " or value!=" . $docid . ")";
        } else if ($o->is_document()) {
            if (fileUploaded($_FILES[$oname])
                && ($docid = upload_option($o, $oname, $prow))) {
                $opt_data[] = "$o->id, $docid, null";
                $uploaded_documents[] = $docid;
            } else if (!defval($_REQUEST, "remove_$oname"))
                $no_delete_options[] = "optionId!=" . $o->id;
	} else
            $no_delete_options[] = "optionId!=" . $o->id;
    }

    // contacts
    $contact_changes = check_contacts($prow);

    // special handling for collaborators (missing == warning)
    if ($_REQUEST["collaborators"] == "" && $Conf->setting("sub_collab")) {
        $field = ($Conf->setting("sub_pcconf") ? "Outros conflitos" : "Conflitos em potencial");
        $Warning["collaborators"] = "Por favor, entre com os potenciais conflitos dos autores no $field campo. Se nenhum dos autores possuir nenhum conflito em potencial, apenas escreva “Nenhum”.";
    }

    // Commit accumulated changes
    // update Paper table
    if (!$newPaper)
        $Conf->qe("update Paper set " . join(", ", $q) . " where paperId=$paperId and timeWithdrawn<=0", "while updating paper");
    else {
        if (!($result = $Conf->qe("insert into Paper set " . join(", ", $q), "while creating paper"))) {
            $Conf->errorMsg("Não foi possível criar o trabalho.");
            return false;
        }
        if (!($result = $Conf->lastInsertId("while creating paper")))
            return false;
        $paperId = $_REQUEST["p"] = $_REQUEST["paperId"] = $result;
    }

    // update PaperConflict table
    if ($contact_changes && save_contacts($paperId, $contact_changes, true))
        $diffs["contacts"] = true;

    // update PaperTopic table
    $Conf->qe("delete from PaperTopic where paperId=$paperId", "while updating paper topics");
    $tq = array();
    foreach ($_REQUEST as $key => $value)
        if ($value > 0 && str_starts_with($key, "top")
            && ($id = cvtint(substr($key, 3))) > 0)
            $tq[] = "($paperId, $id)";
    if (count($tq))
        $Conf->qe("insert into PaperTopic (paperId, topicId) values " . join(",", $tq), "while updating paper topics");

    // update PaperOption table
    if (count(PaperOption::option_list())) {
	if (!$Conf->qe("delete from PaperOption where paperId=$paperId and " . join(" and ", $no_delete_options), "while updating paper options"))
	    return false;
        foreach ($opt_data as &$x)
            $x = "($paperId, $x)";
        unset($x);
        $Conf->qe("delete from PaperOption where paperId=$paperId and " . join(" and ", $no_delete_options), "while updating paper options");
        if (count($opt_data))
            $Conf->qe("insert into PaperOption (paperId,optionId,value,data) values " . join(", ", $opt_data), "while updating paper options");
    }

    // update PaperStorage.paperId for newly registered papers
    if ($newPaper && count($uploaded_documents))
        $Conf->qe("update PaperStorage set paperId=$paperId where paperStorageId in (" . join(",", $uploaded_documents) . ")", "while linking PDFs with paper");

    // update PC conflicts if appropriate
    if ($Conf->setting("sub_pcconf") && (!$isSubmitFinal || $Me->privChair)) {
	$max_conflict = $Me->privChair ? CONFLICT_CHAIRMARK : CONFLICT_MAXAUTHORMARK;
	if (!$Conf->qe("delete from PaperConflict where paperId=$paperId and conflictType>=" . CONFLICT_AUTHORMARK . " and conflictType<=" . $max_conflict, "while updating conflicts"))
	    return false;
	$q = array();
	$pcm = pcMembers();
	foreach ($_REQUEST as $key => $value)
	    if (strlen($key) > 3
		&& $key[0] == 'p' && $key[1] == 'c' && $key[2] == 'c'
		&& ($id = cvtint(substr($key, 3))) > 0 && isset($pcm[$id])
		&& $value > 0) {
		$q[] = "($paperId, $id, " . max(min($value, $max_conflict), CONFLICT_AUTHORMARK) . ")";
	    }
	if (count($q))
            $Conf->qe("insert into PaperConflict (paperId,contactId,conflictType) values " . join(", ", $q) . " on duplicate key update conflictType=conflictType",
                      "while updating conflicts");
    }

    // submit paper if no error so far
    loadRows();
    $submitkey = $isSubmitFinal ? "timeFinalSubmitted" : "timeSubmitted";
    $storekey = $isSubmitFinal ? "finalPaperStorageId" : "paperStorageId";
    $wasSubmitted = !$newPaper && $prow->$submitkey > 0;
    $didSubmit = false;
    if ($OK && !count($Error) && ($isSubmitFinal || $isSubmit)) {
        if ($prow->$storekey > 1 || defval($Opt, "noPapers")) {
            $result = $Conf->qe("update Paper set " . ($isSubmitFinal ? "timeFinalSubmitted=" : "timeSubmitted=") . time() . " where paperId=$paperId", "while submitting paper");
            $didSubmit = !!$result;
            loadRows();
        } else if (!$isSubmitFinal) {
            $Error["paper"] = 1;
            $Conf->errorMsg(whyNotText("notUploaded", "submit", $paperId));
        }
    } else if ($OK && !count($Error) && !$isSubmitFinal && !$isSubmit) {
	$Conf->qe("update Paper set timeSubmitted=0 where paperId=$paperId", "while unsubmitting paper");
        loadRows();
    }
    if ($isSubmit || $Conf->setting("pc_seeall"))
	$Conf->updatePapersubSetting(true);
    if ($wasSubmitted != ($prow->$submitkey > 0))
        $diffs["submission"] = 1;

    // confirmation message
    if ($isSubmitFinal) {
	$actiontext = "Versão final atualizada de";
	$template = "@submitfinalpaper";
    } else if ($didSubmit && $isSubmit && !$wasSubmitted) {
	$actiontext = "Submetido";
	$template = "@submitpaper";
    } else if ($newPaper) {
	$actiontext = "Registrado novo";
	$template = "@registerpaper";
    } else {
	$actiontext = "Atualizado";
	$template = "@updatepaper";
    }

    // additional information
    $notes = array();
    if ($isSubmitFinal) {
        if ($prow->$submitkey === null || $prow->$submitkey <= 0)
            $notes[] = "A versão final ainda não foi submetida.";
	$deadline = $Conf->printableTimeSetting("final_soft", "span");
	if ($deadline != "N/A" && $Conf->deadlinesAfter("final_soft"))
	    $notes[] = "<strong>A data limite para submissão das versões finais era $deadline.</strong>";
	else if ($deadline != "N/A")
	    $notes[] = "Você tem até $deadline para fazer outras modificações.";
    } else {
	if ($isSubmit || $prow->timeSubmitted > 0)
	    $notes[] = "Você receberá um email assim que as revisões estiverem disponíveis.";
	else if ($prow->size == 0 && !defval($Opt, "noPapers"))
	    $notes[] = "O trabalho ainda não foi enviado.";
	else if ($Conf->setting("sub_freeze") > 0)
	    $notes[] = "O trabalho ainda não foi submetido.";
	else
	    $notes[] = "O trabalho está marcado como ainda não finalizado, para os revisores.";
	$deadline = $Conf->printableTimeSetting("sub_update", "span");
	if ($deadline != "N/A" && ($prow->timeSubmitted <= 0 || $Conf->setting("sub_freeze") <= 0))
	    $notes[] = "Demais modificações são permitidas até $deadline.";
	$deadline = $Conf->printableTimeSetting("sub_sub", "span");
	if ($deadline != "N/A" && $prow->timeSubmitted <= 0)
	    $notes[] = "<strong>Se o trabalho "
                . ($Conf->setting("sub_freeze") > 0 ? "não for submetido"
                   : "não estiver pronto para revisão")
                . " até $deadline, ele não será considerado.</strong>";
    }
    $notes = join(" ", $notes);

    $webnotes = "";
    if (count($Warning) && $OK && !count($Error))
        $webnotes = " <ul><li>" . join("</li><li>", array_values($Warning)) . "</li></ul>";

    if (!count($diffs)) {
        $Conf->warnMsg("Não há modificações para o trabalho #$paperId. " . $notes . $webnotes);
        return $OK && !count($Error);
    }

    // HTML confirmation
    if ($prow->$submitkey > 0)
	$Conf->confirmMsg($actiontext . " trabalho #$paperId. " . $notes . $webnotes);
    else
	$Conf->warnMsg($actiontext . " trabalho #$paperId. " . $notes . $webnotes);

    // mail confirmation to all contact authors
    if (!$Me->privChair || defval($_REQUEST, "doemail") > 0) {
	$options = array("infoNames" => 1);
	if ($Me->privChair && $prow->conflictType < CONFLICT_AUTHOR)
	    $options["adminupdate"] = true;
	if ($Me->privChair && isset($_REQUEST["emailNote"]))
	    $options["reason"] = $_REQUEST["emailNote"];
	if ($notes !== "")
	    $options["notes"] = preg_replace(",</?(?:span.*?|strong)>,", "", $notes) . "\n\n";
	Mailer::sendContactAuthors($template, $prow, null, $options);
    }

    // other mail confirmations
    if ($isSubmitFinal && $OK && !count($Error) && $Conf->sversion >= 36)
	genericWatch($prow, WATCHTYPE_FINAL_SUBMIT, "final_submit_watch_callback", $Me);

    $Conf->log($actiontext, $Me, $paperId);

    return $OK && !count($Error);
}

if ((isset($_REQUEST["update"]) || isset($_REQUEST["submitfinal"]))
    && check_post()) {
    // get missing parts of request
    clean_request($prow, isset($_REQUEST["submitfinal"]));
    $diffs = request_differences($prow, isset($_REQUEST["submitfinal"]));

    // check deadlines
    if ($newPaper)
	// we know that canStartPaper implies canFinalizePaper
	$ok = $Me->canStartPaper($whyNot);
    else if (isset($_REQUEST["submitfinal"]))
	$ok = $Me->canSubmitFinalPaper($prow, $whyNot);
    else {
	$ok = $Me->canUpdatePaper($prow, $whyNot);
	if (!$ok && isset($_REQUEST["submit"]) && !count($diffs))
	    $ok = $Me->canFinalizePaper($prow, $whyNot);
    }

    // actually update
    if ($ok) {
        if (update_paper($Me, isset($_REQUEST["submit"]),
                         isset($_REQUEST["submitfinal"]), $diffs))
            redirectSelf(array("p" => $paperId, "m" => "pe"));
        else
            report_update_paper_errors();
    } else {
	if (isset($_REQUEST["submitfinal"]))
	    $action = "submit final version for";
	else
	    $action = ($newPaper ? "register" : "update");
	$Conf->errorMsg(whyNotText($whyNot, $action));
    }

    // use request?
    $useRequest = ($ok || $Me->privChair);
}

if (isset($_REQUEST["updatecontacts"]) && check_post() && !$newPaper) {
    if (!$Me->canAdminister($prow) && !$Me->actAuthorView($prow)) {
        $Conf->errorMsg(whyNotText(array("permission" => 1), "update contacts for"));
    } else if (($contact_changes = check_contacts($prow))
               && save_contacts($prow->paperId, $contact_changes, false)) {
	redirectSelf(array("p" => $paperId, "m" => "pe"));
	// NB normally redirectSelf() does not return
    }

    // use request?
    $useRequest = true;
}


// delete action
if (isset($_REQUEST["delete"]) && check_post()) {
    if ($newPaper)
	$Conf->confirmMsg("Trabalho excluído.");
    else if (!$Me->privChair)
	$Conf->errorMsg("Apenas os diretores da comissão científica podem excluir trabalho permanentemente. Autores podem retirar trabalhos duplicados.");
    else {
	// mail first, before contact info goes away
	if (!$Me->privChair || defval($_REQUEST, "doemail") > 0)
	    Mailer::sendContactAuthors("@deletepaper", $prow, null, array("reason" => defval($_REQUEST, "emailNote", ""), "infoNames" => 1));
	// XXX email self?

	$error = false;
	$tables = array('Paper', 'PaperStorage', 'PaperComment', 'PaperConflict', 'PaperReview', 'PaperReviewArchive', 'PaperReviewPreference', 'PaperTopic', 'PaperTag', "PaperOption");
	foreach ($tables as $table) {
	    $result = $Conf->qe("delete from $table where paperId=$paperId", "while deleting paper");
	    $error |= ($result == false);
	}
	if (!$error) {
	    $Conf->confirmMsg("Trabalho #$paperId excluído.");
	    $Conf->updatePapersubSetting(false);
	    if ($prow->outcome > 0)
		$Conf->updatePaperaccSetting(false);
	    $Conf->log("Excluído", $Me, $paperId);
	}

	$prow = null;
	errorMsgExit("");
    }
}


// paper actions
if ((isset($_REQUEST["settags"]) || isset($_REQUEST["settingtags"])) && check_post()) {
    PaperActions::setTags($prow);
    loadRows();
}
if (isset($_REQUEST["tagreport"]) && check_post())
    PaperActions::tagReport($prow);


// correct modes
$paperTable = new PaperTable($prow);
$paperTable->resolveComments();
if ($paperTable->mode == "r" || $paperTable->mode == "re") {
    $paperTable->resolveReview();
    $paperTable->fixReviewMode();
}


// page header
confHeader();


// prepare paper table
if ($paperTable->mode == "pe") {
    $editable = $newPaper
	|| ($prow->timeWithdrawn <= 0
	    && ($Conf->timeUpdatePaper($prow) || $Me->allowAdminister($prow)));
    if ($prow && $prow->outcome > 0 && $Conf->collectFinalPapers()
	&& (($Conf->timeAuthorViewDecision() && $Conf->timeSubmitFinalPaper())
	    || $Me->allowAdminister($prow)))
	$editable = "f";
} else
    $editable = false;

$paperTable->initialize($editable, $editable && $useRequest);

// produce paper table
$paperTable->paptabBegin();

if ($paperTable->mode == "r" && !$paperTable->rrow)
    $paperTable->paptabEndWithReviews();
else if ($paperTable->mode == "re" || $paperTable->mode == "r")
    $paperTable->paptabEndWithEditableReview();
else
    $paperTable->paptabEndWithReviewMessage();

if ($paperTable->mode != "pe")
    $paperTable->paptabComments();

$Conf->footer();
