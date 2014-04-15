# HotCRP TODO #

## Capabilities ##

- [ ] Use capabilities for accept/decline review links
- [ ] Right now “Decline review” requires confirmation; it would be better to make it HAPPEN when you click the link and then be undoable
- [ ] Contact::valid() rechecks capabilities too often

## Comments ##

- [ ] Allow attachments on comments and reviews
- [ ] Show response word count overages to viewers, not just editors
- [ ] Comment notification emails should include opt-out links

## Paper options ##

- [ ] Add multiple-checkboxes option type
- [ ] Auto-convert numeric <=> text when setting changes
- [ ] Box for chair notes (maybe a comment?)
- [ ] Add options set by administrators?
- [ ] Use a separate Delete button rather than form position

## Paper editing ##

- [ ] Add a “cancel” button on paper submission
- [ ] Submission history

## Conflicts ##

- [ ] A meeting mode where PC members can see conflicts

## Schema ##

- [ ] Review round should be Review tags
- [ ] InnoDB rather than MyISAM
- [ ] Make sure .htaccess files work with Require as well as Deny

## Visual appearance ##

- [ ] Top considered ugly.
- [ ] New logo: Paper with flaming review bubble?
- [ ] Top strip in dark red
- [ ] Links to home and (for chairs) settings

### Paper strip ###

- [ ] "Follow" rather than "Email notification"
- [ ] "Follow" is a button?
- [ ] Is there a good "Edit" image rather than button?
- [ ] "Edit" button for paper strip rather than folds
- [ ] "Your rank": kill "(context)" link, maybe kill "(all)" link
- [ ] "(shown only to PC reviewers)" etc. visibility should be bar-separated, not parenthesized.
- [ ] Comment edit links shouldn't reload the page
- [ ] "All reviews" links should say "Main"
- [ ] "(?)" score info links should maybe say "1-3 scale" or at least not be blue
- [ ] Why do the paper areas ("Abstract", etc.) have a background color?
- [ ] Maybe a separate little area with links for "main", "edit", "review", "assign", on left-hand side
- [ ] Review ratings appear in review region, above review itself (don't overload the review region)
- [ ] "None" for Discussion lead/Shepherd/Paper manager fold to nothing

### Paper list ###

- [ ] Rows gain "#p45" anchors
- [ ] If anchor is selected, highlight that row
- [ ] Edit pads have arrows on them, not dots

### Tags ###

- [ ] Tag completion is clickable, gains keyboard shortcuts
- [ ] Remove "?" superscript help link in paper list

## Behavior ##

- [ ] Inform users about [j]/[k]
- [ ] [t] to edit tags

## Old TODO ##

- If paper managers, chair can infer who reviewed what using review
  counts on autoassign screen

- Re-assign the review round in bulk

- Some sort of display that is linked with whatever paper the chair's
  currently on, to maybe display in the hallway for conflicts or
  something [Garth Gibson]

- When reviewers update their reviews, all we get is an email that the
  review was updated. But there's no indication how it changed: did the
  reviewer update the text? Did they raise/lower any score? Ideally, we'd
  like to see what has changed, but HotCRP doesn't have versioning.
  Versioning would be a lot of work, so perhaps a quicker/simpler solution
  would be this: upon a review change, perhaps you can format the old and
  new review (plus scores) as plain text, and include a diff(1) output of
  the old vs. new reviews along with the email that notifies the chairs of
  the review change? [Erez Zadok/Dan Tsafrir]

- We attempted to load balance the reviews among the PC members, such that
each had 14 papers to review. Suppose now that a PC member submitted to the
conference paper #1. Then, they could recognize from the statistics page
the reviewers of his paper as those having reviewed only 13 papers. We feel
this could be corrected by adding an option to disable the statistics page
view for users without chair rights. [Giuliano Casale, Martin Arlitt]

- During the PC meeting, we were highlighting the current paper for the
benefit of the members (i.e., so they could more easily follow along as we
discussed papers). Basically, this amounted to adding a red tag and
deleting it from the previously considered paper. It seems a quick action
to do, but while listening to the PC members, marking the decisions (in
HotCRP as well as on paper), discussing the merits of a paper with the PC
members, etc., we struggled to keep the appropriate paper tagged as
“current”. We’re not sure if this would be a feasible extension, but
perhaps having a button to color/uncolor the current selection would have
made our life easier. [Giuliano Casale, Martin Arlitt]

- http://harvesthq.github.com/chosen/ [Tom Limoncelli]

- (1) It would be nice to be able to tag papers with their session
  number and session chair.  However,
       tag:session#1 tag:sessionchairbalachanderkrishnamurthy
  somehow does't do it for me, partly because these two could
  get out of sync, and partly because I would like to be able
  to use "send mail" in a way that gets mail to the right session
  chairs.

  What I think I would ideally like is to be able to assign a session
  number to an accepted paper (manually only) -- maybe "tag:session#1" is
  the right way to do that -- and then be able to assign a session chair
  (from the PC members and/or external reviewers, in case not enough PC
  members are willing) to all papers with a given tag:session value --
  something like assigning a shepherd.

  (2) Then I would like to be able to send an email to the session chair
  that includes the name and email address of the author(s) in her session,
  and also send the authors an email with the name and address of their
  session chair.  In "Send Mail" I think this requires a way to select To:
  == session chairs (analogous to To: Shepherds), and also new keywords
  %SESSIONCHAIRNAME% and %SESSIONCHAIREMAIL%.  Oh, and maybe
  %SESSIONNUMBER%.

  And %SESSIONTITLE%, for fun, but that would require a way to set up that
  mapping, and this probably means a new screen somewhere.

  (Extra credit if you adopt the suggestion that John made, a while back,
  to optionally send a single message with multiple recipients; for
  example, if we want to tell the 10 authors of one paper that "one of you
  should contact the session chair" and (a) not end up sending 10 emails to
  the chair, and (b) make sure that they are all CCed on whatever replies
  get sent.)

  (3) Then I would like to be able to download a spreadsheet showing
  papers, session numbers, session chairs (names and emails), titles, and
  authors (names and emails) -- I had to do this myself, somewhat by hand,
  and it was tedious.

  (4) Then I would like a way for the authors of an accepted paper to mark
  one of themselves as the speaker for the paper (sort of like being the
  contact author, except that probably it should allow no more than one).
  This turned out to be tedious, too -- it involved writing scripts to
  generate emails and then following up by updating a spreadsheet by hand.

  Extra credit: if I can send mail to "authors of papers with no designated
  speaker" so that we can automatically hound these miscreants.

  (5) And, I guess, the spreadsheet from #3 should have a type field that
  indicates which author is designated as the speaker.

  (6) Extra-extra credit: a way to assign a paper-within-session order, and
  then a way to format the entire program, with session titles, session
  chairs, papers in the right order, author names and affiliations, and the
  speaker's name in bold. [Jeff Mogul]

- We didn't use HotCRP for sending the acceptance letter.  The chairs have
  been passing down a bunch of shell scripts through the years that
  generate the accept/reject emails and it would be much nicer if hotcrp
  could do the process for us.  The hotcrp formletter system can't insert
  the day and time of the session the paper will be presented in.  In
  hindsight, we should have used HotCRP anyway: it would have been less
  work over-all even if we had to forgo the day/time info. [Tony Limoncelli]

- Two questions we got constantly were ... "how do I get back to the main
  menu?" (click the name of the conference in the upper left corner).  Both
  of these indicate areas of improvement for the GUI. [Tony Limoncelli]

- Add a "bulk update" that shows the papers in a spreadsheet-like grid with
  number, name, shepherd, accept status, submission type, and so on.  Let
  the user edit any of the data in the grid. [Tony Limoncelli]

- We have to collect information for the program such as "name as to
  appear in the program". It would be nice to have a facility to collect
  all that information AND be able to collect whether the author has
  approved/acknowledge it to be correct.  (If we could do this for ITs
  and Gurus, this could produce the entire program) [Tony Limoncelli]

- A way to easily see the list of papers assigned to a user and which reviews
  are complete would be really useful! [Doug Hughes]

- Feature 1: Multi-paper discussion.  A few times per conference, I want to
  write on a review "This paper is better/worse than paper X because ..."
  or similar. The problem is that such comments are hard to do without
  risking anonymity. When I enter a comment for a paper, it would be great
  to be able to, say, enter the number of another paper(s) and treat this
  comment as conflicting to people for whom those papers are
  conflicting. [Mike Dahlin]

- Feature 2: Cluster-based assignment.  Walfish and I were discussing how
  useful it is to make sure that there is good overlap of reviewers across
  papers on the same topic. The thought was that it might not be too
  difficult to make the automatic review assignment tools identify clusters
  of likely-related papers (based on co-citation, abstract text, keywords,
  body text, ...) and to help the chair get reviewer overlaps within
  clusters. [Mike Dahlin]

- In any case, it occurred to me that it would be very useful to have a
  semi-analogous feature that allows us to specify something like

   Assign at least [N] common reviewers to papers [X] and [Y]

  This seems to come up fairly often -- someone realizes that two papers
  cover very similar problems and/or approaches, but either they don't feel
  completely comfortable being the only reviewer who has read both papers,
  or they is us, and we want to make sure that some PC member has done a
  true review of both papers.

  I assume that this kind of request can overconstrain the assigner, but it
  seems like the kind of thing that might rank fairly high in the
  prioritization of assigning reviews (e.g., perhaps somewhat more
  important than minor differences in review preferences or workload.)

  In any case, I think we would want to do these overlapping assignments as
  early as possible in the process, as opposed to the seemingly usual
  practice of deciding during the PC meeting that "Eddie should read paper
  #97 during lunch".  So if we don't get automated support from HotCRP, we
  will try to do it by hand. [Jeff Mogul]

- HTTPS-only redirect: [Jeff Chase]

  RewriteEngine on
  RewriteCond %{HTTPS} !=on
  RewriteRule .* https://%{SERVER_NAME}%{REQUEST_URI} [R,L]

- Send Mail semantics: When sending an e-mail to contact authors X, Y, and
  Z of a paper P, the current HotCRP behavior is to send three separate
  emails to each respective author rather than one where all recipients are
  visible on the "To:" line.  The latter seems more natural to me and gives
  contact authors an explicit hint that only 3 of the 10 authors received
  this Accept/Reject notification, for example. [John Byers]

- Can we save the review forms from this offering of the WQE for use in the
  next offering of the WQE?  Since the WQE is not an actual conference, the
  standard conference-style review forms are less useful, so it would be
  useful to keep what we develop for future offerings. [Amit Sahai]

- Viewable history of review scores (and review texts?) [Yoshi Kohno]

- Cookie Stealing Vulnerability [Aditya K Sood]

- The download list is limited (what if I want authors AND scores at the
  same time).  Doesn't it seem cleaner to use the existing diplay options to
  select fields, then have download > "list as tabbed text" that gets
  whatever you've selected?  It would be also good to work this in to the
  chair help page for someone who reads the documentation. [John Heidemann]

- HotCRP Feature Request: PDF Anonymization [Matt Johnson]

- Online TPC meeting support [Richard Mortier]

- PC members can use mail tool to send mail to authors

- What we would like is the option of sending a single message to all
  authors of a paper at once. [Jeff Mogul]

- Code/createdb: Support creating a database that runs on a remote machine
  [Manolis Stamatogiannakis]

- Add "Full author info" checkbox to manual assignment page [Jeff Mogul]

- Do people who've submitted only abstracts get email in the rebuttal period?

- Show PC when authors can see reviews.

- It would have been useful to have a "mail reviewer" link on the
  reviews page for communicating with individual reviewers about
  specific papers. [Stephanie Weirich]

- For chair conflicts I used the review tokens (managed by a third
  party) to create anonymous reviews for my conflicts. This worked
  well, for the most part. Except:
    o I've learned to prepend all searches with "-conflict:weirich" to
    avoid information leakage from searching. For example, "ovemer:AB"
    returns a conflicted paper, carefully hiding its score, but I have
    a good idea what the score is now. Seems like the default should
    be to exclude conflicted papers from searches, unless I
    specifically override.
    o Also, cutting and pasting search results reveals information. I
    wanted to send a list of my conflicts to another PC member who was
    helping me out (b/c he couldn't search for them). So I selected the
    search result and sent him an email. But the extracted text had
    more information than was shown on the screen.
    o The default comment notification is PC+externals, but that is a
    little dangerous because of the tendency to compare papers in
    comments ("this paper is much better than paper 32!"), and the
    nontrivial likelihood that an external reviewers would be an
    author of paper 32. I'd like to be able to change this default to
    "PC only" (still leaving the option to include externals for each
    particular message) to be just a little more paranoid.
  [Stephanie Weirich]

- a way to see the distribution of topics for
  submitted/selected/accepted papers. I computed this information
  manually, but it would be nice if the system could automatically
  display the info. [Stephanie Weirich]

- a way to list the accepted papers w/ authors suitable for posting on
  the conference website. Did I miss how to do this? [Stephanie
  Weirich]

- HotCRP already has the very lovely "conflict" keyword, but I think
  it might be nice to have two more keywords:

  "pref": e.g., "pref:<0" to find papers where an assigned reviewer
  has expressed a preference <0

  "topicpref": likewise, but for an assigned reviewer's topic preferences
  (e.g., "topicpref:<0" for low, "topicprof:>0" for high) [Jeff Mogul]

- it would be really nice if email replies to comments became comments.
  yes, there is an auth/anti-spam issue.  but you know the emails of the
  reviewer set.

  all real noc ticketing systems do this.  it's a users' expectation that,
  if i receive an email and respond to it, the response will have the same
  level of distribution as the message to which one is responding. [Randy Bush]

-  1) discussion list during the PC meeting

  we created a separate page for the paper discussion list for the meeting,
  which looked something like:

  http://cseweb.ucsd.edu/~voelker/sigcomm10/example/

  we displayed the page on the room projection screen so it was visible to
  all PC members.  many people loaded it on their laptops to use the links
  to jump to papers.  during the meeting we moved papers among the various
  categories as we went through the papers.  the page auto-reloaded to keep
  it up-to-date.

  we thought having a similar feature in HotCRP would be useful, and I
  think it can almost be emulated in HotCRP as is.  a discussion page would
  be defined by a list of ordered tags, and the discussion list would be
  broken up into groups according to the tags.  moving a paper from one
  list to another would just require retagging.  two possible extensions
  are a feature for auto-reloading the page, and a visual separation
  between tag groups (perhaps with a sub-header based on the tag name) to
  keep the groups straight.  adding per-group colored tags might do the
  trick, although moving a paper from one category to another would require
  adjusting multiple tags and might be prone to error.

  there is also the question of whether conflicted papers should be shown
  or not.  when it gets to the discussion list at PC meeting time, we
  preferred to have everyone see the same list.  Jeff's idea of a
  discussion timer would naturally fold in with this.

  2) chair-specific tags: similar to personal tags, but visible and
  changeable across all PC chairs.  there were times when one of us wanted
  to define a group that only we could see and change; we wanted them to be
  used by more than one person, but not to the entire PC.

  3) more examples of complex searches (e.g., when narrowing papers down
  between an upper range and a lower range).  after experimentation we got
  the hang of it.

  4) partition papers into sub-groups and then sorting the sub-groups based
  on some criteria.  this comes from when we were formulating the
  discussion order by first separating papers into different groups, and
  then sorting the papers within the group.  we did this by first tagging
  papers into a group and then sorting within that group. [Geoff Voelker /
  KK Ramakrishnan]

- It would be nice if the To: choice in the Send Mail function allowed me
  to select "Reviewers with no preferences" (or perhaps "PC members with no
  preferences"). [Jeff Mogul]

- I see your point; possibly this means that the per-paper screen that the
  reviewer sees should have an "I have a conflict" button that they can
  push, but only the chair can un-push it. [Jeff Mogul]

- On the Conference Setting/Submissions Options page, it's a bit tedious to
  type or cut-n-paste all of the topics.  Seems like a way to bulk-upload
  this list would be useful to future PC chairs. [Jeff Mogul]

- At recent PC meetings that I have attended, it has been hard for the PC
  chairs to keep track of the amount of time spent discussing each paper
  (since they are juggling lots of other tasks).

  I realize that this could be a bit tricky to get right, but perrhaps
  HotCRP could have a "discussion timer" function, sort of a per-paper
  stopwatch.

  Probably there should be two values: the time spent in the current
  discussion, and the total time spent discussing the paper (since PC
  meetings generally have loops).

  The PC chairs would see a Start-/Stop- timer button.

  Possibly this timing info should be hidden from the conflicted reviewers.

  Additional possibilities would be to do fancy things such as estimating
  how much time is remaining to discuss the average paper, or how late the
  PC dinner will be if we keep spending so many minutes per paper,
  etc. [Jeff Mogul]

- I am using your software as part of the program committee of PETS2010. I
  really like it, easy to use and convenient. However, I am missing the
  option of being notified when new reviews (for my assigned papers, or
  others) are input in the system -- there is the option of being notified
  of the comments, but not the reviews. [Carmela Troncoso]

- generic (no names, passwords) emails to contact authors should be grouped
  by paper and sent To: all authors of a paper at once [Chris Frost]

- Chair searches do not display chair scores by default [Jane-Ellen Long]

- It would be great to be able to assign reviews manually, but then ask the
  system to look for swaps that would make both reviewers happier according
  to their preferences. I'd want to be able to confirm one swap at a
  time. [Andrew Myers]

- Visually or in search differentiate external review scores from PC review
  scores [Casey Henderson/Adam Moskowitz].

- string options

- When external reviewers submit their reviews, it might be a good idea to
  inform the requesting PC member that the review has been
  submitted. Perhaps this could be an option available in the settings
  panel ? [Moses Charikar]

- More than once, PC members asked if they could upload pdf files they had
  received as reviews. In one case, I converted the pdf to text, but in
  general, it would be useful to have a pdf upload option for reviews. I
  agree that it interferes with viewing everything on one screen, but in
  some cases (with a lot of math) pdf is very handy. If this is supported,
  it would also be good to send such pdf reviews as attachments (if they
  are meant to be seen by authors) when reviews are sent out, or be
  available to authors online when the reviews are made visible. [Moses
  Charikar]

- We didn't do a full fledged rebuttal phase, but I contacted authors on a
  case by case basis, typically forwarding snippets of reviews and
  requesting a response. I got back email responses which I posted as a
  comment. It would be convenient if the system had a mechanism to allow
  authors to respond on the conference server (without having all reviews
  visible to them) and have the response appear as a comment
  automatically. A couple more related things: a. It would be convenient to
  allow PC members to initiate such author communication (in an anonymous
  fashion). As PC chair, all communication to authors was channeled through
  me - such a feature would take some of the load off.  b. Authors
  sometimes responded with pdf files, so it would be convenient to allow
  pdf uploads in author responses. [Moses Charikar]

- As PC chair, I used the action log quite frequently. It occurred to me
  that it might have been convenient for PC members to have such a list too
  - restricted to papers that they were assigned to, or have commented on
  ... basically all papers that they would receive email notification
  about. This would allow them to scan through all the new things that have
  happened since the last time they logged in. [Moses Charikar]

- In several cases, we compared multiple papers. To facilitate this, I
  added a common tag to a group of papers and also created a dummy paper to
  hold the comments relevant to that group. It would be nice if the system
  supported such a discussion. One idea might be to allow comments to have
  tags, and then the comment shows up for all papers that have the matching
  tag. Alternately, a comment could be associated with multiple papers and
  this could be specified when the comment is entered (this will be hard to
  do if the group of relevant papers is large.) [Moses Charikar]

- One PC member said "The server lost the name of my external reviewer
  that I entered in the name field (on two occasions; see, e.g., #21), and
  rudely addressed the person by their email address.  :) Now it does not
  allow me to change "[no name]" in the name field." [Moses Charikar]

- I was going to put out the abstracts for accepted papers together with
  the accepted papers list. In the current system, is there a way for
  authors to update the text abstract they submitted ? I suppose not,
  because they could probably change their submission too if we opened up
  the system for edits. [Moses Charikar]

- Related to the previous point, I may have to collect tex files containing
  titles and abstracts from authors. I know the system allows collection of
  final versions (and archives original submissions). Would there be a way
  for the authors to submit additional files too ? [Moses Charikar]

- 2. It would be helpful to store information about reviews outstanding by
  reviewing round eg paper n has 1/3 for the first round and 4/5 for the
  second.  In fact it would also be great to be able to look at paper
  rankings by round to see how things changed.  I would really like stats
  to see the value of multiple reviewing rounds - how much first round
  reviews predict the eventual outcome etc. [Rebecca Isaacs]

- 4. Can you list both authors and institutions in the conflicts page?  And
  for that matter it would be helpful (to the chair at least) if the source
  of conflict was listed, eg requested by author (or PC) vs matching words
  in author list, etc. [Rebecca Isaacs]

- 5. It would be nice to have multiple review forms, in particular to have
  a different form for externals. [Rebecca Isaacs]

- 6. Have you considered functionality so the automatic review assignment
  can take into account nominated reviewers?  Eg so 1st round reviewers can
  suggest 2nd round people through the system and the chair doesn't have to
  do reviewing rearrangements manually. [Rebecca Isaacs]

- i like the fact that i can see - just based on simple averaging - how
  harsh or liberal a reviewer is. however in defense of harsh reviewers who
  may have obtained genuinely crummy papers and liberal ones who might have
  recd instant-accepts, what might be useful is once all or most of reviews
  are in, looking at the deviation of the 'harsh' reviewer from other
  reviews of the papers he/she reviewed, and likewise for 'liberal reviewer
  and recompute a balanced score. as long as this is available just before
  the PC meeting then during the PC meeting we would know who is a softie
  and who is trying to 'kill' papers. am not sure if this feature exists
  now. [Balachander Krishnamurthy]

- it would be great to infer area/sub-topic expert based on self-rating of
  reviewers in their reviews to see where reviewers are in the cheriton
  scale (1..100, where cheriton is 420). this would help assing conflict
  papers, poorly reviewed papers, last minute papers to such
  reviewers. [Balachander Krishnamurthy]

- comment threading

* From the "Assign reviews manually by PC member" page, you can get to
  "Assign reviews manually by paper," but not vice versa. [Benjamin Pierce]

* Again for tweaking the assignment, it would be really helpful to have a
  *single* global view of the whole assignment, showing all available
  information and allowing papers to be reassigned at will. [Benjamin
  Pierce]

* There should be a bulk *download* option -- this would allow you to save
  away the current assignment and restore it later if you messed things up.
  (This would require the bulk upload feature to be able to delete
  assignments as well as add them -- and/or to be able to say "these are
  exactly the reviewers for this paper".  This would be useful more
  generally.) [Benjamin Pierce]

* How about cc'ing the requesting PC member on the invitation request
  email? [Benjamin Pierce] -- won't cc, but maybe resend mail

- Check that review tokens and external reviewer requests mix

* When I'm sending mail to people via the website, I want to make sure that
  it's going to be CC'd to me.  It would be helpful if the mail template
  either offered me a field to control who gets messages or -- perhaps
  sufficient -- displayed an un-editable indication that this was going to
  happen automatically. [Benjamin Pierce]

* Similarly, when an external reviewer goes to the website to decline (or
  confirm!) a review, it should be more obvious who will see the
  message. [Benjamin Pierce]

* The Comments section could be a little more self-documenting -- e.g.,
  there could be a note someplace saying that anyone who has written a
  review for a given paper will be notified by default when there are new
  comments.  (Or do I mean: anyone who has written OR IS SIGNED UP TO BE
  WRITING a review for a given paper...?) [Benjamin Pierce]

* As a PC member, I might be interested in discussions even if I am not a
  reviewer who is emailed a comment by default.  It would be nice if we
  could search on "commented after 7/23" or display and sort on things like
  the number of comments and the last time a comment was entered. [Fred
  Douglis]

* It would be nice if the chair email generated when a review is updated
  included a diff.  (Otherwise is it usually close to impossible to figure
  out what changed.)  I would use this a lot, because I try to read all the
  reviews as they come in, to track how everything is going. [Benjamin
  Pierce]

* Is there a way for me to sign up to see comments that are entered on
  every paper whatsoever?  (Or does this already happen for the PC chair?)
  [Benjamin Pierce]

* But maybe instead of sending a literal CC, the PC member could be sent a
  redacted copy [of the external review request] with the password blanked
  out? [Benjamin Pierce]

* Mainly that the PC member can't see the wording of the request -- it
  feels like shooting blind.  (More concretely, they can't see, for
  example, what deadline the subreviewers are being given.)  Also, it means
  that they don't have a copy of the email in their files, so they can't do
  things like forward it with "PING" added to the subject line... :-)
  [Benjamin Pierce]

* Ability to search accepted papers for those that have / have not
  submitted final copies of their paper [Mark Gebhart, MICRO 41]

* Here are some policy decisions that we changed by editing the code:

  Ability to prevent reviewers from seeing other reviews of papers they
  reviewed until shortly before PC meeting [Mark Gebhart, MICRO 41]

  Ability to show reviews to authors without showing comments to
  authors.  We had some PC comments that were entered before the PC
  meeting that we didn't want to expose until after decisions had been
  made. [Mark Gebhart, MICRO 41]

  Not allow PC members to see anything about papers they didn't review
  until PC meeting [Mark Gebhart, MICRO 41]

  Allow PC members to view a paper's manuscript if they can view any
  information about a paper [Mark Gebhart, MICRO 41]

* At the hotel where the PC meeting was held it was not possible to provide
  internet access to PC members.  To allow PC members to view content
  during the meeting we used wget to spider the submission site a couple of
  days before the meeting as each member and burned a custom cd for each PC
  member.  We had to make quite a few code changes to create a version of
  the site to spider that would not generate an large number of pages.  We
  removed the ability to search and anything else that would depend on
  generating dynamic content.  The resulting spidered webpages were around
  100mb per PC member.  This seemed to work very well for the meeting and
  we didn't have any trouble with people accessing their files on their
  machines.  We found this to be quite useful and while it involved a fair
  amount of work the ability to turn off all of the dynamic content with a
  setting or flag in the code would be a great addition.  We realize this
  may not be easy to do. [Mark Gebhart, MICRO 41]

* It would be really useful to be able to turn off sending emails about
  comments to anyone outside of the PC.  During the PC meeting, they
  naturally get used for all sorts of tasks like communicating final
  suggestions to the authors that reveal too much (e.g., the current
  acceptance state) to the outside reviewers.  (I guess this can actually
  be achieved with settings already available -- right?  So maybe this is a
  documentation issue...) [Benjamin Pierce]

* it would be cute to have 1 config of hotcrp so that PC members get given
  as many scores drawn from a normal distribution as they have papers, and
  then can only use each score once. [Jon Crowcroft]

* Do not log updates to reviews made using chair power, if those reviews
  are explicitly anonymous. [John Wilkes]

4. It's somewhat of tedious to flip back and forth between per-paper and
  per-PC member review assignments.  Could some better navigation shortcuts
  be made available? [John Wilkes]

Multi-round assignment process.  Doing this showed up a couple of issues:

10. Conflict checking

    * The list of names/words to ignore could usefully be both longer
  (e.g., add "Labs" and "research" to the list), and settable by the PC
  chair.

    * Conflicts of the form "username (affiliation)" leads to entire
  "affiliation" getting marked as a conflict, which seems overkill.  Or was
  that the intent?  The help text seems to suggest not. [John Wilkes]

Format checker: This worked really nicely.  But ...

11. It would be really helpful to be able to state "x% overage is OK" for
  the format checker, rather than editing the individual settings by hand,
  which tended to confuse people about what the real rules were. [John
  Wilkes]

* it would be nice if there was an easy way to send email from one of a
  paper's pages to the author(s) or reviewers.  As it is, it seems I have
  to go back to the home page, select Send Mail, select "Choose individual
  papers" and type in the paper number.

  Instead, how about a "send email about this paper" link on the "paper"
  page/tab which took me to the "send email" page with those values filled
  in?  This is low priority in comparison to my previous request :-). [John
  Wilkes]

4. The "manual by PC member" page gives no indication of discussion
  leads. [John Wilkes]

- static HTML snapshot

- entry to identify presenting author
- Matthew Frank: five values for reviewer topic interest
- Matthew Frank: local hill climbing for auto assignment

Minor features
- Search: Assignments & conflicts: [External reviewer]
- START-style enter-paper-all-at-once (no email verification)?
- when email off: do not show authors to PC members, do not show reviewers
  to authors
- scrub author information from PDFs

## Done ##

- [X] Get rid of contactauthors.php
- [X] Add buttons on Edit screen to make a particular author a contact
- [X] Use capabilities when a user changes their email address
