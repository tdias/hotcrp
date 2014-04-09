<?php
// review.php -- HotCRP paper review display/edit page
// HotCRP is Copyright (c) 2006-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

$Error = $Warning = array();
require_once("src/initweb.php");
require_once("src/papertable.php");

// special case: if "accept" or "refuse" is set, and "email" and "password"
// are both set, vector through the signin page
if (isset($_REQUEST["email"]) && isset($_REQUEST["password"])
    && (isset($_REQUEST["accept"]) || isset($_REQUEST["refuse"])
	|| isset($_REQUEST["decline"]))) {
    PaperTable::cleanRequest();
    $after = "";
    foreach (array("paperId" => "p", "pap" => "p", "reviewId" => "r", "commentId" => "c") as $k => $v)
	if (isset($_REQUEST[$k]) && !isset($_REQUEST[$v]))
	    $_REQUEST[$v] = $_REQUEST[$k];
    foreach (array("p", "r", "c", "accept", "refuse", "decline") as $opt)
	if (isset($_REQUEST[$opt]))
	    $after .= ($after === "" ? "" : "&") . $opt . "=" . urlencode($_REQUEST[$opt]);
    $url = substr(hoturl("review", $after), strlen($ConfSiteBase));
    go(hoturl("index", "email=" . urlencode($_REQUEST["email"]) . "&password=" . urlencode($_REQUEST["password"]) . "&go=" . urlencode($url)));
}

if ($Me->is_empty())
    $Me->escape();
$rf = reviewForm();
$useRequest = isset($_REQUEST["afterLogin"]);
if (defval($_REQUEST, "mode") == "edit")
    $_REQUEST["mode"] = "re";
else if (defval($_REQUEST, "mode") == "view")
    $_REQUEST["mode"] = "r";


// header
function confHeader() {
    global $prow, $Conf;
    if ($prow)
	//$title = "Paper #$prow->paperId";
         $title = "Artigo #$prow->paperId";
    else
	//$title = "Paper Reviews";
	$title = "Revisão de Artigos";
    $Conf->header($title, "review", actionBar("r", $prow), false);
}

function errorMsgExit($msg) {
    global $Conf;
    confHeader();
    $Conf->footerScript("shortcut().add()");
    $Conf->errorMsgExit($msg);
}


// collect paper ID
function loadRows() {
    global $Conf, $Me, $prow, $paperTable, $editRrowLogname, $Error;
    if (!($prow = PaperTable::paperRow($whyNot)))
	errorMsgExit(whyNotText($whyNot, "view"));
    $paperTable = new PaperTable($prow);
    $paperTable->resolveReview();

    if ($paperTable->editrrow && $paperTable->editrrow->contactId == $Me->contactId)
	$editRrowLogname = "Revisão " . $paperTable->editrrow->reviewId;
    else if ($paperTable->editrrow)
	$editRrowLogname = "Revisão " . $paperTable->editrrow->reviewId . " por " . $paperTable->editrrow->email;
    if (isset($Error["paperId"]) && $Error["paperId"] != $prow->paperId)
	$Error = array();
}

loadRows();


// general error messages
if (isset($_REQUEST["post"]) && $_REQUEST["post"] && !count($_POST))
    $Conf->errorMsg("O arquivo enviado excedeu o tamanho máximo aceito. O arquivo foi ignorado!.");	
    //$Conf->errorMsg("It looks like you tried to upload a gigantic file, larger than I can accept.  The file was ignored.");
else if (isset($_REQUEST["post"]) && isset($_REQUEST["default"])) {
    if (fileUploaded($_FILES["uploadedFile"]))
	$_REQUEST["uploadForm"] = 1;
    else
	$_REQUEST["update"] = 1;
} else if (isset($_REQUEST["submit"]))
    $_REQUEST["update"] = $_REQUEST["ready"] = 1;
else if (isset($_REQUEST["savedraft"])) {
    $_REQUEST["update"] = 1;
    unset($_REQUEST["ready"]);
}


// upload review form action
if (isset($_REQUEST["uploadForm"])
    && fileUploaded($_FILES['uploadedFile'])
    && check_post()) {
    // parse form, store reviews
    $tf = $rf->beginTextForm($_FILES['uploadedFile']['tmp_name'], $_FILES['uploadedFile']['name']);

    if (!($req = $rf->parseTextForm($tf)))
	/* error already reported */;
    else if (isset($req['paperId']) && $req['paperId'] != $prow->paperId)
	//$rf->tfError($tf, true, "This review form is for paper #" . $req['paperId'] . ", not paper #$prow->paperId; did you mean to upload it here?  I have ignored the
//ignored the form.<br /><a class='button_small' href='" . hoturl("review", "p=" . $req['paperId']) . "'>Review paper #" . $req['paperId'] . "</a> <a class='button_small'
//class='button_small' href='" . hoturl("offline") . "'>General review upload site</a>");
	$rf->tfError($tf, true, "Esta é uma visualização do artigo #" . $req['paperId'] . ", not paper #$prow->paperId; está certo que deseja enviar o arquivo aqui ?  O formulário foi ignorado.<br /><a class='button_small' href='" . hoturl("review", "p=" . $req['paperId']) . "'>Revisar Artigo #" . $req['paperId'] . "</a> <a class='button_small' href='" . hoturl("offline") . "'>Página de Revisão Geral de Artigos</a>");
    else if (!$Me->canSubmitReview($prow, $paperTable->editrrow, $whyNot))
	$rf->tfError($tf, true, whyNotText($whyNot, "review"));
    else {
	$req['paperId'] = $prow->paperId;
	if ($rf->checkRequestFields($req, $paperTable->editrrow, $tf)) {
	    if ($rf->saveRequest($req, $paperTable->editrrow, $prow, $Me))
		//$tf['confirm'][] = "Uploaded review for paper #$prow->paperId.";
		$tf['confirm'][] = "Revisão enviada para o Artigo #$prow->paperId.";
	}
    }

    if (count($tf['err']) == 0 && $rf->parseTextForm($tf))
	//$rf->tfError($tf, false, "Only the first review form in the file was parsed.  <a href='" . hoturl("offline") . "'>Upload multiple-review files here.</a>");
	$rf->tfError($tf, false, "Somente a primeiro formulário de revisão foi analisado.  <a href='" . hoturl("offline") . "'>Envie múltiplos arquivos de revisão aqui.</a>");

    $rf->textFormMessages($tf);
    loadRows();
} else if (isset($_REQUEST["uploadForm"]))
    //$Conf->errorMsg("Select a review form to upload.");
    $Conf->errorMsg("Selecione um formulário de revisão para enviar.");	

// check review submit requirements
if (isset($_REQUEST["unsubmit"]) && $paperTable->editrrow
    && $paperTable->editrrow->reviewSubmitted && $Me->canAdminister($prow)
    && check_post()) {
    //$while = "while unsubmitting review";
    $while = "while unsubmitting review";	
    $Conf->qe("lock tables PaperReview write", $while);
    $needsSubmit = 1;
    if ($paperTable->editrrow->reviewType == REVIEW_SECONDARY) {
	$result = $Conf->qe("select count(reviewSubmitted), count(reviewId) from PaperReview where paperId=$prow->paperId and requestedBy=" . $paperTable->editrrow->contactId . " and reviewType=" . REVIEW_EXTERNAL, $while);
	if (($row = edb_row($result)) && $row[0])
	    $needsSubmit = 0;
	else if ($row && $row[1])
	    $needsSubmit = -1;
    }
    $result = $Conf->qe("update PaperReview set reviewSubmitted=null, reviewNeedsSubmit=$needsSubmit where reviewId=" . $paperTable->editrrow->reviewId, $while);
    $Conf->qe("unlock tables", $while);
    if ($result) {
	$Conf->log("$editRrowLogname unsubmitted", $Me, $prow->paperId);
	$Conf->confirmMsg("Revisão não submetida.");
	//$Conf->confirmMsg("Unsubmitted review.");
    }
    redirectSelf();		// normally does not return
    loadRows();
} else if (isset($_REQUEST["update"]) && $paperTable->editrrow
	   && $paperTable->editrrow->reviewSubmitted)
    $_REQUEST["ready"] = 1;


// review rating action
if (isset($_REQUEST["rating"]) && $paperTable->rrow && check_post()) {
    if (!$Me->canRateReview($prow, $paperTable->rrow)
	|| !$Me->canViewReview($prow, $paperTable->rrow, null))
	//$Conf->errorMsg("You can’t rate that review.");
	$Conf->errorMsg("Você não pode classificar esta revisão.");
    else if ($Me->contactId == $paperTable->rrow->contactId)
	//$Conf->errorMsg("You can’t rate your own review.");
	$Conf->errorMsg("Você não pode classificar sua própria revisão.");
    else if (!isset($ratingTypes[$_REQUEST["rating"]]))
	//$Conf->errorMsg("Invalid rating.");
	$Conf->errorMsg("Classificação Inválida.");
    else if ($_REQUEST["rating"] == "n")
	$Conf->qe("delete from ReviewRating where reviewId=" . $paperTable->rrow->reviewId . " and contactId=$Me->contactId", "while updating rating");
    else
	$Conf->qe("insert into ReviewRating (reviewId, contactId, rating) values (" . $paperTable->rrow->reviewId . ", $Me->contactId, " . $_REQUEST["rating"] . ") on duplicate key update rating=" . $_REQUEST["rating"], "while updating rating");
    if (defval($_REQUEST, "ajax", 0))
	if ($OK)
	    //$Conf->ajaxExit(array("ok" => 1, "result" => "Thanks! Your feedback has been recorded."));  	
	    $Conf->ajaxExit(array("ok" => 1, "result" => "Obrigado! Seu comentário foi armazenado."));
	else
	    //$Conf->ajaxExit(array("ok" => 0, "result" => "There was an error while recording your feedback."));
	   $Conf->ajaxExit(array("ok" => 0, "result" => "Houve um erro enquanto seu comentário era armazenado."));
    if (isset($_REQUEST["allr"])) {
	$_REQUEST["paperId"] = $paperTable->rrow->paperId;
	unset($_REQUEST["reviewId"]);
	unset($_REQUEST["r"]);
    }
    loadRows();
}


// update review action
if (isset($_REQUEST["update"]) && check_post()) {
    if (!$Me->canSubmitReview($prow, $paperTable->editrrow, $whyNot)) {
	$Conf->errorMsg(whyNotText($whyNot, "review"));
	$useRequest = true;
    } else if ($rf->checkRequestFields($_REQUEST, $paperTable->editrrow)) {
	if ($rf->saveRequest($_REQUEST, $paperTable->editrrow, $prow, $Me)) {
            if ((@$_REQUEST["ready"] && !@$_REQUEST["unready"])
                || ($paperTable->editrrow && $paperTable->editrrow->reviewSubmitted))
                //$Conf->confirmMsg("Review submitted.");
		$Conf->confirmMsg("Revisão Enviada.");
            else
                //$Conf->confirmMsg("Review saved.  However, this version is marked as not ready for others to see.  Please finish the review and submit again.");
		 $Conf->confirmMsg("Revisão Salva.  De qualquer forma, esta versão esta definida como 'não-finalizada' para outros leitores.  Por favor finalize a revisão e a submeta novamente.");
	    redirectSelf();		// normally does not return
	    loadRows();
	} else
	    $useRequest = true;
    } else
	$useRequest = true;
}


// delete review action
if (isset($_REQUEST["delete"]) && $Me->canAdminister($prow) && check_post())
    if (!$paperTable->editrrow)
	//$Conf->errorMsg("No review to delete.");
	$Conf->errorMsg("Nenhuma revisão para ser removida.");
    else {
	archiveReview($paperTable->editrrow);
	$while = "while deleting review";
	$result = $Conf->qe("delete from PaperReview where reviewId=" . $paperTable->editrrow->reviewId, $while);
	if ($result) {
	    $Conf->log("$editRrowLogname deleted", $Me, $prow->paperId);
	    //$Conf->confirmMsg("Deleted review.");
	    //Conf->confirmMsg("Revisão Deletada.")
	    if (defval($paperTable->editrrow, "reviewToken", 0) != 0)
		$Conf->updateRevTokensSetting(true);

	    // perhaps a delegatee needs to redelegate
	    if ($paperTable->editrrow->reviewType == REVIEW_EXTERNAL && $paperTable->editrrow->requestedBy > 0) {
		$result = $Conf->qe("select count(reviewSubmitted), count(reviewId) from PaperReview where paperId=" . $paperTable->editrrow->paperId . " and requestedBy=" . $paperTable->editrrow->requestedBy . " and reviewType=" . REVIEW_EXTERNAL, $while);
		if (!($row = edb_row($result)) || $row[0] == 0)
		    $Conf->qe("update PaperReview set reviewNeedsSubmit=" . ($row && $row[1] ? -1 : 1) . " where reviewType=" . REVIEW_SECONDARY . " and paperId=" . $paperTable->editrrow->paperId . " and contactId=" . $paperTable->editrrow->requestedBy . " and reviewSubmitted is null", $while);
	    }

	    unset($_REQUEST["reviewId"]);
	    unset($_REQUEST["r"]);
	    $_REQUEST["paperId"] = $paperTable->editrrow->paperId;
	}
	redirectSelf();		// normally does not return
	loadRows();
    }


// download review form action
function downloadView($prow, $rr, $editable) {
    global $rf, $Me, $Conf;
    if ($editable && $prow->reviewType > 0
	&& (!$rr || $rr->contactId == $Me->contactId))
	return $rf->textForm($prow, $rr, $Me, $_REQUEST, true) . "\n";
    else if ($editable)
	return $rf->textForm($prow, $rr, $Me, null, true) . "\n";
    else
	return $rf->prettyTextForm($prow, $rr, $Me, false) . "\n";
}

function downloadForm($editable) {
    global $rf, $Conf, $Me, $prow, $paperTable, $Opt;
    $explicit = true;
    if ($paperTable->rrow)
	$downrrows = array($paperTable->rrow);
    else if ($editable)
	$downrrows = array();
    else {
	$downrrows = $paperTable->rrows;
	$explicit = false;
    }
    $text = "";
    foreach ($downrrows as $rr)
	if ($rr->reviewSubmitted
	    && $Me->canViewReview($prow, $rr, null, $whyNot))
	    $text .= downloadView($prow, $rr, $editable);
    foreach ($downrrows as $rr)
	if (!$rr->reviewSubmitted
	    && $Me->canViewReview($prow, $rr, null, $whyNot)
	    && ($explicit || $rr->reviewModified))
	    $text .= downloadView($prow, $rr, $editable);
    if (count($downrrows) == 0)
	$text .= downloadView($prow, null, $editable);
    if (!$explicit) {
	$paperTable->resolveComments();
	foreach ($paperTable->crows as $cr)
	    if ($Me->canViewComment($prow, $cr, false))
		$text .= $rf->prettyTextComment($prow, $cr, $Me) . "\n";
    }
    if (!$text)
	return $Conf->errorMsg(whyNotText($whyNot, "review"));
    if ($editable)
	$text = $rf->textFormHeader(count($downrrows) > 1, $Me->viewReviewFieldsScore($prow, null)) . $text;
    downloadText($text, "review-" . $prow->paperId, !$editable);
    exit;
}
if (isset($_REQUEST["downloadForm"]))
    downloadForm(true);
else if (isset($_REQUEST["text"]))
    downloadForm(false);


// refuse review action
function archiveReview($rrow) {
    global $Conf;
    $rf = reviewForm();
    $fields = $rf->reviewArchiveFields();
    $Conf->qe("insert into PaperReviewArchive ($fields) select $fields from PaperReview where reviewId=$rrow->reviewId", "while archiving review");
}

function refuseReview() {
    global $Conf, $Opt, $Me, $prow, $paperTable;

    $while = "while refusing review";
    $Conf->qe("lock tables PaperReview write, PaperReviewRefused write, PaperReviewArchive write", $while);

    $rrow = $paperTable->editrrow;
    if ($rrow->reviewModified > 0)
	archiveReview($rrow);
    $hadToken = defval($rrow, "reviewToken", 0) != 0;

    $result = $Conf->qe("delete from PaperReview where reviewId=$rrow->reviewId", $while);
    if (!$result)
	return;
    $reason = defval($_REQUEST, "reason", "");
    if ($reason == "Optional explanation")
	$reason = "";
    $result = $Conf->qe("insert into PaperReviewRefused (paperId, contactId, requestedBy, reason) values ($rrow->paperId, $rrow->contactId, $rrow->requestedBy, '" . sqlqtrim($reason) . "')", $while);
    if (!$result)
	return;

    // now the requester must potentially complete their review
    if ($rrow->reviewType == REVIEW_EXTERNAL && $rrow->requestedBy > 0) {
	$result = $Conf->qe("select count(reviewSubmitted), count(reviewId) from PaperReview where paperId=$rrow->paperId and requestedBy=$rrow->requestedBy and reviewType=" . REVIEW_EXTERNAL, $while);
	if (!($row = edb_row($result)) || $row[0] == 0)
	    $Conf->qe("update PaperReview set reviewNeedsSubmit=" . ($row && $row[1] ? -1 : 1) . " where reviewType=" . REVIEW_SECONDARY . " and paperId=$rrow->paperId and contactId=$rrow->requestedBy and reviewSubmitted is null", $while);
    }

    $Conf->qe("unlock tables");

    // send confirmation email
    $Requester = Contact::find_by_id($rrow->reqContactId);
    $reqprow = $Conf->paperRow($prow->paperId, $rrow->reqContactId);
    Mailer::send("@refusereviewrequest", $reqprow, $Requester, $rrow, array("reason" => $reason));

    // confirmation message
    //$Conf->confirmMsg("The request that you review paper #$prow->paperId has been removed.  Mail was sent to the person who originally requested the review.");
    $Conf->confirmMsg("A requisão para que você revise o artigo #$prow->paperId foi removida.  Um e-email foi enviado para a pessoa que originalmente requereu esta revisão.");
    if ($hadToken)
	$Conf->updateRevTokensSetting(true);

    $prow = null;
    confHeader();
    $Conf->footer();
    exit;
}

if (isset($_REQUEST["refuse"]) || isset($_REQUEST["decline"])) {
    if (!$paperTable->editrrow
	|| (!$Me->ownReview($paperTable->editrrow) && !$Me->canAdminister($prow)))
 	//$Conf->errorMsg("Esta revisão não atribuída a você, então você não pode rejeita-la.");
	$Conf->errorMsg("This review was not assigned to you, so you can’t decline it.");
    else if ($paperTable->editrrow->reviewType >= REVIEW_SECONDARY)
	//$Conf->errorMsg("PC members can’t decline their primary or secondary reviews.  Contact the PC chairs directly if you really cannot finish this review.");
	$Conf->errorMsg("Membros da comissão científica não podem negar suas revisões primárias ou secundárias. Entre em contato diretamente com a diretoria caso realmente não consiga finalizar esta revisão.");
    else if ($paperTable->editrrow->reviewSubmitted)
	//$Conf->errorMsg("This review has already been submitted; you can’t decline it now.");
	$Conf->errorMsg("Esta revisão já foi enviada; você não pode rejeita-la.");
    else if (defval($_REQUEST, "refuse") == "1"
	     || defval($_REQUEST, "decline") == "1") {
	//$Conf->confirmMsg("<p>Select “Decline review” to decline this review (you may enter a brief explanation, if you’d like). Thank you for telling us that you cannot
//you cannot complete your review.</p><div class='g'></div><form method='post' action=\"" . hoturl_post("review", "p=" . $paperTable->prow->paperId . "&amp;r=" . $paperTable
	$Conf->confirmMsg("<p>Selecione “Rejeitar Revisão” para rejeitar esta revisão(você pode inserir uma breve justificativa caso prefira). Obigado por nos comunicar que você não deseja completar sua revisão.</p><div class='g'></div><form method='post' action=\"" . hoturl_post("review", "p=" . $paperTable->prow->paperId . "&amp;r=" . 
//<input type='submit' value='Decline review' />
$paperTable->editrrow->reviewId) . "\" enctype='multipart/form-data' accept-charset='UTF-8'><div class='aahc'>
  <input type='hidden' name='refuse' value='refuse' />
  <textarea name='reason' rows='3' cols='40'></textarea>
  <span class='sep'></span>
  <input type='submit' value='Rejeitar Revisão' />
  </div></form>");
    } else {
	refuseReview();
	$Conf->qe("unlock tables");
	loadRows();
    }
}

if (isset($_REQUEST["accept"])) {
    if (!$paperTable->editrrow
	|| (!$Me->ownReview($paperTable->editrrow) && !$Me->canAdminister($prow)))
	//$Conf->errorMsg("This review was not assigned to you, so you cannot confirm your intention to write it.");
	$Conf->errorMsg("Esta revisão não foi associada à você, então você não pode confirmar sua intenção em escreve-lo.");
    else {
	if ($paperTable->editrrow->reviewModified <= 0)
	    $Conf->qe("update PaperReview set reviewModified=1 where reviewId=" . $paperTable->editrrow->reviewId . " and coalesce(reviewModified,0)<=0", "while confirming review");
	//$Conf->confirmMsg("Thank you for confirming your intention to finish this review.  You can download the paper and review form below.");
	$Conf->confirmMsg("Obrigado pela confirmação da sua intenção de finalizar esta revisão.  Voce pode baixar o artigo e o formulário de revisão logo abaixo.");
	loadRows();
    }
}


// paper actions
if (isset($_REQUEST["setdecision"]) && check_post()) {
    PaperActions::setDecision($prow);
    loadRows();
}
if (isset($_REQUEST["setrevpref"]) && check_post()) {
    PaperActions::setReviewPreference($prow);
    loadRows();
}
if (isset($_REQUEST["setrank"]) && check_post()) {
    PaperActions::setRank($prow);
    loadRows();
}
if (isset($_REQUEST["setlead"]) && check_post()) {
    PaperActions::setLeadOrShepherd($prow, "lead");
    loadRows();
}
if (isset($_REQUEST["setshepherd"]) && check_post()) {
    PaperActions::setLeadOrShepherd($prow, "shepherd");
    loadRows();
}
if (isset($_REQUEST["setmanager"]) && check_post()) {
    PaperActions::setLeadOrShepherd($prow, "manager");
    loadRows();
}
if ((isset($_REQUEST["settags"]) || isset($_REQUEST["settingtags"])) && check_post()) {
    PaperActions::setTags($prow);
    loadRows();
}


// can we view/edit reviews?
$viewAny = $Me->canViewReview($prow, null, null, $whyNotView);
$editAny = $Me->canReview($prow, null, $whyNotEdit);


// can we see any reviews?
if (!$viewAny && !$editAny) {
    if (!$Me->canViewPaper($prow, $whyNotPaper))
	errorMsgExit(whyNotText($whyNotPaper, "view"));
    if (!isset($_REQUEST["reviewId"]) && !isset($_REQUEST["ls"])) {
	//$Conf->errorMsg("You can’t see the reviews for this paper.  " . whyNotText($whyNotView, "review"));
	$Conf->errorMsg("Você não pode visualizar as revisões para este artigo.  " . whyNotText($whyNotView, "review"));
	go(hoturl("paper", "p=$prow->paperId"));
    }
}


// mode
if ($paperTable->mode == "r" || $paperTable->mode == "re")
    $paperTable->fixReviewMode();
if ($paperTable->mode == "pe")
    go(hoturl("paper", "p=$prow->paperId"));


// page header
confHeader();


// paper table
$paperTable->initialize(false, false);
$paperTable->paptabBegin();
$paperTable->resolveComments();

if (!$viewAny && !$editAny
    && (!$paperTable->rrow
	|| !$Me->canViewReview($prow, $paperTable->rrow, null)))
    $paperTable->paptabEndWithReviewMessage();
else if ($paperTable->mode == "r" && !$paperTable->rrow)
    $paperTable->paptabEndWithReviews();
else
    $paperTable->paptabEndWithEditableReview();

if ($paperTable->mode != "pe")
    $paperTable->paptabComments();

$Conf->footer();
