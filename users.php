<?php
// users.php -- HotCRP people listing/editing page
// HotCRP is Copyright (c) 2006-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

require_once("src/initweb.php");
require_once("src/contactlist.php");
$getaction = "";
if (isset($_REQUEST["get"]))
    $getaction = $_REQUEST["get"];
else if (isset($_REQUEST["getgo"]) && isset($_REQUEST["getaction"]))
    $getaction = $_REQUEST["getaction"];


// list type
$tOpt = array();
$tOpt["pc"] = "Program committee";
if ($Me->isPC && count($pctags = pcTags())) {
    foreach ($pctags as $t)
        if ($t != "pc")
            $tOpt["pc:$t"] = "PC members tagged &ldquo;$t&rdquo;";
    if (!isset($_SESSION["foldppltags"]))
	$_SESSION["foldppltags"] = 0;
}
if ($Me->isPC)
    $tOpt["admin"] = "Administradores do Sistema";
if ($Me->privChair || ($Me->isPC && $Conf->timePCViewAllReviews())) {
    $tOpt["re"] = "Todos os revisores";
    $tOpt["ext"] = "Revisores externos";
    $tOpt["extsub"] = "Revisores Externos que completaram uma revisão";
}
if ($Me->isPC)
    $tOpt["req"] = "Revisores externos requisitados por você";
if ($Me->privChair || ($Me->isPC && $Conf->subBlindNever()))
    $tOpt["au"] = "Contate os autores de artigos submetidos";
if ($Me->privChair
    || ($Me->isPC && $Conf->timePCViewDecision(true)))
    $tOpt["auacc"] = "Conate autores de artigos aceitos";
if ($Me->privChair
    || ($Me->isPC && $Conf->subBlindNever() && $Conf->timePCViewDecision(true)))
    $tOpt["aurej"] = "Contate autores de artigos rejeitados";
if ($Me->privChair) {
    $tOpt["auuns"] = "Conate autores de artigos não submetidos";
    $tOpt["all"] = "Todos usuários";
}
if (isset($_REQUEST["t"]) && !isset($tOpt[$_REQUEST["t"]])) {
    $Conf->errorMsg("You aren’t allowed to list those users.");
    unset($_REQUEST["t"]);
}
if (!isset($_REQUEST["t"]))
    $_REQUEST["t"] = key($tOpt);


// paper selection and download actions
function paperselPredicate($papersel) {
    return "contactId" . sql_in_numeric_set($papersel);
}

if (isset($_REQUEST["pap"]) && is_string($_REQUEST["pap"]))
    $_REQUEST["pap"] = preg_split('/\s+/', $_REQUEST["pap"]);
if (isset($_REQUEST["pap"]) && is_array($_REQUEST["pap"])) {
    $allowed_papers = array();
    $pl = new ContactList($Me, true);
    // Ensure that we only select contacts we're allowed to see.
    if (($rows = $pl->rows($_REQUEST["t"]))) {
	foreach ($rows as $row)
	    $allowed_papers[$row->paperId] = true;
    }
    $papersel = array();
    foreach ($_REQUEST["pap"] as $p)
	if (($p = cvtint($p)) > 0 && isset($allowed_papers[$p]))
	    $papersel[] = $p;
    if (count($papersel) == 0)
	unset($papersel);
}

if ($getaction == "nameemail" && isset($papersel) && $Me->isPC) {
    $result = $Conf->qe("select firstName, lastName, email from ContactInfo where " . paperselPredicate($papersel) . " order by lastName, firstName, email", "while selecting users");
    $people = array();
    while ($row = edb_row($result))
	$people[] = array($row[0] && $row[1] ? "$row[1], $row[0]" : "$row[1]$row[0]", $row[2]);
    downloadCSV($people, array("name", "email"), "users");
    exit;
}

if ($getaction == "nameaffemail" && isset($papersel) && $Me->isPC) {
    $result = $Conf->qe("select firstName, lastName, email, affiliation from ContactInfo where " . paperselPredicate($papersel) . " order by lastName, firstName, email", "while selecting users");
    $people = array();
    while ($row = edb_row($result))
	$people[] = array($row[0] && $row[1] ? "$row[1], $row[0]" : "$row[1]$row[0]", $row[3], $row[2]);
    downloadCSV($people, array("name", "affiliation", "email"), "users");
    exit;
}

if ($getaction == "address" && isset($papersel) && $Me->isPC) {
    $result = $Conf->qe("select firstName, lastName, email, voicePhoneNumber, ContactAddress.* from ContactInfo left join ContactAddress using (contactId) where " . paperselPredicate($papersel) . " order by lastName, firstName, email", "while selecting users");
    $people = array();
    $phone = false;
    while (($row = edb_orow($result))) {
	$p = array(null, $row->email, $row->addressLine1, $row->addressLine2,
		   $row->city, $row->state, $row->zipCode, $row->country);
	if ($row->voicePhoneNumber) {
	    $phone = true;
	    $p[] = $row->voicePhoneNumber;
	}
	if ($row->firstName && $row->lastName)
	    $p[0] = "$row->lastName, $row->firstName";
	else
	    $p[0] = "$row->lastName$row->firstName";
	$people[] = $p;
    }
    $header = array("name", "email", "address1", "address2",
		    "city", "state", "postalcode", "country");
    if ($phone)
	$header[] = "phone";
    downloadCSV($people, $header, "addresses");
    exit;
}


// modifications
function modify_confirm($j, $ok_message, $ok_message_optional) {
    global $Conf;
    if (@$j->ok && @$j->warnings)
        $Conf->warnMsg("<div>" . join("</div><div style='margin-top:0.5em'>", $j->warnings) . "</div>");
    if (@$j->ok && $ok_message && (!$ok_message_optional || !@$j->warnings))
        $Conf->confirmMsg($ok_message);
}

if ($Me->privChair && @$_REQUEST["modifygo"] && check_post() && isset($papersel)) {
    if (@$_REQUEST["modifytype"] == "disableaccount")
        modify_confirm(UserActions::disable($papersel, $Me), "Contas desativas.", true);
    else if (@$_REQUEST["modifytype"] == "enableaccount")
        modify_confirm(UserActions::enable($papersel, $Me), "Contas ativadas.", true);
    else if (@$_REQUEST["modifytype"] == "resetpassword")
        modify_confirm(UserActions::reset_password($papersel, $Me), "Redefinição de senhas. <a href=\"" . hoturl_post("users", "t=" . $_REQUEST["t"] . "&amp;modifygo=1&amp;modifytype=sendaccount&amp;pap=" . join("+", $papersel)) . "\">Enviar informações de contas à estas contas</a>", false);
    else if (@$_REQUEST["modifytype"] == "sendaccount")
        modify_confirm(UserActions::send_account_info($papersel, $Me), "Envio de informação de contas.", false);
    redirectSelf(array("modifygo" => null, "modifytype" => null));
}


// set scores to view
if (isset($_REQUEST["redisplay"])) {
    $_SESSION["ppldisplay"] = "";
    displayOptionsSet("ppldisplay", "aff", defval($_REQUEST, "showaff", 0));
    displayOptionsSet("ppldisplay", "topics", defval($_REQUEST, "showtop", 0));
    displayOptionsSet("ppldisplay", "tags", defval($_REQUEST, "showtags", 0));
    $_SESSION["pplscores"] = 0;
}
if (isset($_REQUEST["score"]) && is_array($_REQUEST["score"])) {
    $_SESSION["pplscores"] = 0;
    foreach ($_REQUEST["score"] as $s)
	$_SESSION["pplscores"] |= (1 << $s);
}
if (isset($_REQUEST["scoresort"])
    && ($_REQUEST["scoresort"] == "A" || $_REQUEST["scoresort"] == "V"
	|| $_REQUEST["scoresort"] == "D"))
    $_SESSION["pplscoresort"] = $_REQUEST["scoresort"];


if ($_REQUEST["t"] == "pc")
    $title = "Comissão de Programação";
else if (substr($_REQUEST["t"], 0, 3) == "pc:")
    $title = "“" . substr($_REQUEST["t"], 3) . "” Comissão de Programação";
else
    $title = "Users";
$Conf->header($title, "accounts", actionBar());


$pl = new ContactList($Me, true);
$pl_text = $pl->text($_REQUEST["t"], hoturl("users", "t=" . $_REQUEST["t"]), $tOpt[$_REQUEST["t"]]);


// form
echo "<div class='g'></div>\n";
if (count($tOpt) > 1) {
    echo "<table id='contactsform' class='tablinks1'>
<tr><td><div class='tlx'><div class='tld1'>";

    echo Ht::form(hoturl("users", "t=" . $_REQUEST["t"]), array("method" => "get")), "<div class='inform'>";
    if (isset($_REQUEST["sort"]))
	echo "<input type='hidden' name='sort' value=\"", htmlspecialchars($_REQUEST["sort"]), "\" />";
    echo Ht::select("t", $tOpt, $_REQUEST["t"], array("id" => "contactsform1_d")),
	" &nbsp;<input type='submit' value='Go' /></div></form>";

    echo "</div><div class='tld2'>";

    // Display options
    echo "<form method='get' action='", hoturl("users"), "' accept-charset='UTF-8'><div>\n";
    foreach (array("t", "sort") as $x)
	if (isset($_REQUEST[$x]))
	    echo "<input type='hidden' name='$x' value=\"", htmlspecialchars($_REQUEST[$x]), "\" />\n";

    echo "<table><tr><td><strong>Exibir:</strong> &nbsp;</td>
  <td class='pad'>";
    if ($pl->haveAffrow !== null) {
	echo Ht::checkbox("showaff", 1, $pl->haveAffrow,
			   array("onchange" => "fold('ppl',!this.checked,2)")),
	    "&nbsp;", Ht::label("Affiliations"),
	    foldsessionpixel("ppl2", "ppldisplay", "aff"), "<br />\n";
    }
    if ($pl->haveTags !== null) {
	echo Ht::checkbox("showtags", 1, $pl->haveTags,
			   array("onchange" => "fold('ppl',!this.checked,3)")),
	    "&nbsp;", Ht::label("Tags"),
	    foldsessionpixel("ppl3", "ppldisplay", "tags"), "<br />\n";
    }
    if ($pl->haveTopics !== null) {
	echo Ht::checkbox("showtop", 1, $pl->haveTopics,
			   array("onchange" => "fold('ppl',!this.checked,1)")),
	    "&nbsp;", Ht::label("Topic interests"),
	    foldsessionpixel("ppl1", "ppldisplay", "topics"), "<br />\n";
    }
    echo "</td>";
    if (isset($pl->scoreMax)) {
	echo "<td class='pad'>";
	$rf = reviewForm();
	$theScores = defval($_SESSION, "pplscores", 1);
	$revViewScore = $Me->viewReviewFieldsScore(null, true);
	foreach ($rf->forder as $f)
	    if ($f->view_score > $revViewScore && $f->has_options) {
		$i = array_search($f->id, $reviewScoreNames);
		echo Ht::checkbox("score[]", $i, $theScores & (1 << $i)),
		    "&nbsp;", Ht::label($f->name_html), "<br />";
	    }
	echo "</td>";
    }
    echo "<td><input type='submit' name='redisplay' value='Redisplay' /></td></tr>\n";
    if (isset($pl->scoreMax)) {
	$ss = array();
	foreach (array("A", "V", "D") as $k) /* ghetto array_intersect_key */
	    if (isset($scoreSorts[$k]))
		$ss[$k] = $scoreSorts[$k];
	echo "<tr><td colspan='3'><div class='g'></div><b>Sort scores by:</b> &nbsp;",
	    Ht::select("scoresort", $ss, defval($_SESSION, "pplscoresort", "A")),
	    "</td></tr>";
    }
    echo "</table></div></form>";

    echo "</div></div></td></tr>\n";

    // Tab selectors
    echo "<tr><td class='tllx'><table><tr>
  <td><div class='tll1'><a class='tla' onclick='return crpfocus(\"contactsform\", 1)' href=''>Seleção de Usuário</a></div></td>
  <td><div class='tll2'><a class='tla' onclick='return crpfocus(\"contactsform\", 2)' href=''>Opções de Exibição</a></div></td>
</tr></table></td></tr>
</table>\n\n";
}


if ($Me->privChair && $_REQUEST["t"] == "pc")
    $Conf->infoMsg("<p><a href='" . hoturl("profile", "u=new&amp;pc=1") . "' class='button'>Add PC member</a></p><p>Select a PC member’s name to edit their profile or remove them from the PC.</p>");
else if ($Me->privChair && $_REQUEST["t"] == "all")
    $Conf->infoMsg("<p><a href='" . hoturl("profile", "u=new") . "' class='button'>Criar conta</a></p><p>Selecione um usuário para editar seu perfil. Selecione <img src='images/viewas.png' alt='[Act as]' /> para visualizar como o site seria visto.</p>");


if (isset($pl->any->sel)) {
    echo Ht::form(hoturl_post("users", "t=" . $_REQUEST["t"])), "<div>";
    foreach (array("t", "sort") as $x)
	if (isset($_REQUEST[$x]))
	    echo "<input type='hidden' name='$x' value=\"", htmlspecialchars($_REQUEST[$x]), "\" />\n";
}
echo $pl_text;
if (isset($pl->any->sel))
    echo "</div></form>";


$Conf->footer();
