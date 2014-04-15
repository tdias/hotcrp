<?php
// meetingtracker.php -- HotCRP meeting tracker support
// HotCRP is Copyright (c) 2006-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

class MeetingTracker {

    static function lookup() {
        global $Conf;
        return $Conf->setting_json("tracker");
    }

    static function update($list, $trackerid, $position) {
        global $Conf, $Me, $Now;
        assert($list && str_starts_with($list->listid, "p/"));
        ensure_session();
        $tracker = (object) array("trackerid" => $trackerid,
                                  "listid" => $list->listid,
                                  "ids" => $list->ids,
                                  "url" => $list->url,
                                  "description" => $list->description,
                                  "start_at" => $Now,
                                  "owner" => $Me->contactId,
                                  "sessionid" => session_id(),
                                  "position" => $position);
        $old_tracker = $Conf->setting_json("tracker");
        if ($old_tracker && $old_tracker->trackerid == $tracker->trackerid)
            $tracker->start_at = $old_tracker->start_at;
        self::save($tracker);
        return $tracker;
    }

    static function save($mn) {
        global $Conf;
        $Conf->save_setting("tracker", 1, $mn);
    }

    static function status($acct) {
        global $Conf;
        $tracker = $Conf->setting_json("tracker");
        if (!$tracker || !$acct->isPC)
            return false;
        if (($status = @$_SESSION["tracker"])
            && $status->trackerid == $tracker->trackerid
            && $status->position == $tracker->position)
            return $status;
        $status = (object) array("trackerid" => $tracker->trackerid,
                                 "listid" => $tracker->listid,
                                 "position" => $tracker->position,
                                 "url" => $tracker->url);
        if ($status->position !== false) {
            $pids = array_slice($tracker->ids, $tracker->position, 3);
            $result = $Conf->qe("select p.paperId, p.title, p.leadContactId, p.managerContactId, r.reviewType, conf.conflictType
		from Paper p
		left join PaperReview r on (r.paperId=p.paperId and r.contactId=$acct->contactId)
		left join PaperConflict conf on (conf.paperId=p.paperId and conf.contactId=$acct->contactId)
		where p.paperId in (" . join(",", $pids) . ")");
            $papers = array();
            while (($row = edb_orow($result))) {
                $papers[$row->paperId] = $p = (object)
                    array("pid" => (int) $row->paperId,
                          "title" => $row->title);
                if ($row->managerContactId == $acct->contactId)
                    $p->is_manager = true;
                if ($row->reviewType)
                    $p->is_reviewer = true;
                if ($row->conflictType)
                    $p->is_conflict = true;
                if ($row->leadContactId)
                    $p->is_lead = true;
            }
            $status->papers = array();
            foreach ($pids as $pid)
                $status->papers[] = $papers[$pid];
        }
        $_SESSION["tracker"] = $status;
        return $status;
    }

}
