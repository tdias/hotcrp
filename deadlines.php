<?php
// deadlines.php -- HotCRP deadline reporting page
// HotCRP is Copyright (c) 2006-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

require_once("src/initweb.php");

// *** NB If you change this script, also change the logic in index.php ***
// *** that hides the link when there are no deadlines to show.         ***

if (@$_REQUEST["track"] && $Me->privChair && check_post()) {
    // arguments: IDENTIFIER LISTNUM [POSITION] -OR- stop
    if ($_REQUEST["track"] == "stop")
        $Conf->save_setting("tracker", null);
    else {
        $args = preg_split('/\s+/', $_REQUEST["track"]);
        if (count($args) >= 2
            && ($xlist = SessionList::lookup($args[1]))) {
            $position = null;
            if (count($args) >= 3 && ctype_digit($args[2]))
                $position = array_search((int) $args[2], $xlist->ids);
            MeetingTracker::update($xlist, $args[0], $position);
        }
    }
}

$dl = $Me->deadlines();

if (@$_REQUEST["ajax"]) {
    $dl["ok"] = true;
    $Conf->ajaxExit($dl);
}


// header and script
$Conf->header("Data limite", "deadlines", actionBar());

echo "<p>Estas datas limites determinam até quando as funcionalidades de submissão de artigo e revisão poderão ser acessadas.";

if ($Me->privChair)
    echo " Como membro da Comissão Científica, você também pode <a href='", hoturl("settings"), "'>alterar estas data limites</a>.";

echo "</p>

<dl>\n";


function printDeadline($dl, $name, $phrase, $description) {
    global $Conf;
    echo "<dt><strong>", $phrase, "</strong>: ", $Conf->printableTime($dl[$name], "span") , "</dt>\n",
	"<dd>", $description, ($description ? "<br />" : "");
    if ($dl[$name] > $dl["now"])
	echo "<strong>Tempo restante:</strong>: menos que " . $Conf->printableInterval($dl[$name] - $dl["now"]);
    echo "</dd>\n";
}

if (defval($dl, "sub_reg"))
    printDeadline($dl, "sub_reg", "Data limite para registro de trabalho",
		  "Você pode registrar novos trabalhos até esta data.");

if (defval($dl, "sub_update"))
    printDeadline($dl, "sub_update", "Data limite para atualização do trabalho",
		  "Você pode atualizar novas versões do seu trabalho e alterar outras informações até esta data.");

if (defval($dl, "sub_sub"))
    printDeadline($dl, "sub_sub", "Data limite para submissão de trabalho",
		  "Trabalhos devem ser submetidos até esta data para serem revisados.");

if ($dl["resp_open"] && $dl["resp_done"])
    printDeadline($dl, "resp_done", "Data limite de resposta",
		  "Esta data limite define até quando você pode submeter uma resposta para os revisores.");

if ($dl["rev_open"] && defval($dl, "pcrev_done") && !defval($dl, "pcrev_ishard"))
    printDeadline($dl, "pcrev_done", "PC review deadline",
		  "Reviews are requested by this deadline.");
else if ($dl["rev_open"] && defval($dl, "pcrev_done"))
    printDeadline($dl, "pcrev_done", "PC review hard deadline",
		  "This deadline controls when you can submit or change your reviews.");

if ($dl["rev_open"] && defval($dl, "extrev_done") && !defval($dl, "extrev_ishard"))
    printDeadline($dl, "extrev_done", "External review deadline",
		  "Reviews are requested by this deadline.");
else if ($dl["rev_open"] && defval($dl, "extrev_done"))
    printDeadline($dl, "extrev_done", "External review hard deadline",
		  "This deadline controls when you can submit or change your reviews.");

echo "</table>\n";

$Conf->footer();
