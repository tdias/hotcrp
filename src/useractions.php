<?php
// useractions.php -- HotCRP helpers for user actions
// HotCRP is Copyright (c) 2008-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

class UserActions {

    static private function modify_password_mail($where, $dopassword, $sendtype, $ids) {
        global $Conf;
        $j = (object) array("ok" => true);
        $result = $Conf->qe("select * from ContactInfo where $where and contactId" . sql_in_numeric_set($ids));
        while (($row = edb_orow($result))) {
            $Acct = Contact::make($row);
            if ($dopassword) {
                $Acct->password = Contact::random_password();
                $Acct->password_type = 0;
                $Acct->password_plaintext = $Acct->password;
                $Conf->qe("update ContactInfo set password='" . sqlq($Acct->password) . "' where contactId=" . $Acct->cid);
            }
            if ($sendtype && $Acct->password != "" && !$Acct->disabled)
                $Acct->sendAccountInfo($sendtype, false);
            else if ($sendtype)
                $j->warnings[] = "E-mails não serão enviados para contas desativadas " . htmlspecialchars($Acct->email) . ".";
        }
        return $j;
    }

    static function disable($ids, $contact) {
        global $Conf;
	$result = $Conf->qe("update ContactInfo set disabled=1 where contactId" . sql_in_numeric_set($ids) . " and contactId!=" . $contact->cid);
	if ($result && edb_nrows_affected($result))
            return (object) array("ok" => true);
	else if ($result)
            return (object) array("ok" => true, "warnings" => array("Estas contas já foram desativadas."));
        else
            return (object) array("error" => true);
    }

    static function enable($ids, $contact) {
        global $Conf;
	$result = $Conf->qe("update ContactInfo set disabled=1 where contactId" . sql_in_numeric_set($ids) . " and password='' and contactId!=" . $contact->cid);
	$result = $Conf->qe("update ContactInfo set disabled=0 where contactId" . sql_in_numeric_set($ids) . " and contactId!=" . $contact->cid);
	if ($result && edb_nrows_affected($result))
            return self::modify_password_mail("password='' and contactId!=" . $contact->cid, true, "create", $ids);
        else if ($result)
            return (object) array("ok" => true, "warnings" => array("Estas contas já foram ativadas."));
        else
            return (object) array("error" => true);
    }

    static function reset_password($ids, $contact) {
        global $Conf;
        return self::modify_password_mail("contactId!=" . $contact->cid, true, false, $ids);
	$Conf->confirmMsg("Redefinição de senha. Para enviar um email com uma nova senha, <a href='" . hoturl_post("users", "modifygo=1&amp;modifytype=sendaccount&amp;pap[]=" . (is_array($ids) ? join("+", $ids) : $ids)) . "'>clique aqui</a>.");
    }

    static function send_account_info($ids, $contact) {
        global $Conf;
        return self::modify_password_mail("true", false, "send", $ids);
	$Conf->confirmMsg("Informações de conta enviadas.");
    }

}
