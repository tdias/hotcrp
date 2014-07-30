<?php
// mailtemplate.php -- HotCRP mail templates
// HotCRP is Copyright (c) 2006-2014 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

global $mailTemplates;
$mailTemplates = array
    ("createaccount" =>
     array("subject" => "[%CONFSHORTNAME%] Cadastro",
	   "body" => "Prezado(a),

Uma conta foi criada para voce no sistema de submissao da %CONFNAME%
Segue as informacoes para acesso:

        Site: %URL%/
       Email: %EMAIL%
    Password: %OPT(PASSWORD)%

Use o link abaixo para acessar o sistema

%LOGINURL%

Se voce ja posssui uma conta com um email diferente, você pode mesclar esta nova conta com a antiga. Va para a pagina do perfil e selecione \"Juntar com outra conta\"

Entre em contato com o administrador do sistema, %ADMIN% para quaisquer outras dúvidas

- %CONFSHORTNAME% Sistema de Submissao\n"),

     "accountinfo" =>
     array("subject" => "[%CONFSHORTNAME%] Informações da Conta",
	   "body" => "Dear %NAME%,

Seguem as informações da sua conta para o sistema de submissão do evento %CONFNAME%

        Site: %URL%/
       Email: %EMAIL%
    Password: %OPT(PASSWORD)%

Use o link abaixo para entrar automaticamente.

%LOGINURL%

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions\n"),

     "resetpassword" =>
     array("subject" => "[%CONFSHORTNAME%] Password reset request",
	   "body" => "Dear %NAME%,

We have received a request to reset the password for your account on the %CONFNAME% submissions site. If you made this request, please use the following link to create a new password. The link is only valid for 3 days from the time this email was sent.

%URL%/resetpassword%PHP%/%CAPABILITY%

If you did not make this request, please ignore this email.

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions\n"),

     "changeemail" =>
     array("subject" => "[%CONFSHORTNAME%] Email change request",
	   "body" => "Dear %NAME%,

We have received a request to change the email address for your account on the %CONFNAME% submissions site. If you made this request, please use the following link to update your account to use %EMAIL%. The link is only valid for 3 days from the time this email was sent.

%URL%/profile%PHP%?changeemail=%CAPABILITY%

If you did not make this request, please ignore this email.

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions\n"),

     "mergeaccount" =>
     array("subject" => "[%CONFSHORTNAME%] Merged account",
	   "body" => "Dear %NAME%,

Your account at the %CONFSHORTNAME% submissions site has been merged with the account of %OTHERCONTACT%. From now on, you should log in using the %OTHEREMAIL% account.

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions\n"),

     "requestreview" =>
     array("subject" => "[%CONFSHORTNAME%] Review request for paper #%NUMBER%",
	   "body" => "Dear %NAME%,

On behalf of the %CONFNAME% program committee, %OTHERCONTACT% would like to solicit your help with the review of %CONFNAME% paper #%NUMBER%.%IF(REASON)% They supplied this note: %REASON%%ENDIF%

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(paper, p=%NUMBER%)%

If you are willing to review this paper, you may enter your review on the conference site or complete a review form offline and upload it.%IF(DEADLINE(extrev_soft))% Your review is requested by %DEADLINE(extrev_soft)%.%ENDIF%

Once you've decided, please take a moment to accept or decline this review request by using one of these links. You may also contact %OTHERNAME% directly or decline the review using the conference site.

      Accept: %URL(review, p=%NUMBER%&accept=1&%LOGINURLPARTS%)%
     Decline: %URL(review, p=%NUMBER%&decline=1&%LOGINURLPARTS%)%

For reference, your account information is as follows.

        Site: %URL%/
       Email: %EMAIL%
    Password: %OPT(PASSWORD)%

Or use the link below to sign in.

%LOGINURL%

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

Thanks for your help -- we appreciate that reviewing is hard work!
- %CONFSHORTNAME% Submissions\n"),

     "retractrequest" =>
     array("subject" => "[%CONFSHORTNAME%] Retracting review request for paper #%NUMBER%",
	   "body" => "Dear %NAME%,

%OTHERNAME% has retracted a previous request that you review %CONFNAME% paper #%NUMBER%. There's no need to complete your review.

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%

Contact the site administrator, %ADMIN%, with any questions or concerns.

Thank you,
- %CONFSHORTNAME% Submissions\n"),

     "proposereview" =>
     array("subject" => "[%CONFSHORTNAME%] Proposed reviewer for paper #%NUMBER%",
	   "body" => "Greetings,

%OTHERCONTACT% would like %CONTACT3% to review %CONFNAME% paper #%NUMBER%.%IF(REASON)% They supplied this note: %REASON%%ENDIF%

Visit the assignment page to approve or deny the request.

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(assign, p=%NUMBER%)%

- %CONFSHORTNAME% Submissions\n"),

     "denyreviewrequest" =>
     array("subject" => "[%CONFSHORTNAME%] Proposed reviewer for paper #%NUMBER% denied",
	   "body" => "Dear %NAME%,

Your proposal that %OTHERCONTACT% review %CONFNAME% paper #%NUMBER% has been denied by an administrator. You may want to propose someone else.

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(paper, p=%NUMBER%)%

Contact the site administrator, %ADMIN%, with any questions or concerns.

Thank you,
- %CONFSHORTNAME% Submissions\n"),

     "refusereviewrequest" =>
     array("subject" => "[%CONFSHORTNAME%] Review request for paper #%NUMBER% declined",
	   "body" => "Dear %NAME%,

%OTHERCONTACT% cannot complete the review of %CONFNAME% paper #%NUMBER% that you requested. %IF(REASON)%They gave the following reason: %REASON% %ENDIF%You may want to find an alternate reviewer.

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(paper, p=%NUMBER%)%

- %CONFSHORTNAME% Submissions\n"),

     "authorwithdraw" =>
     array("subject" => "[%CONFSHORTNAME%] Withdrawn paper #%NUMBER% %TITLEHINT%",
	   "body" => "Dear %NAME%,

An author of %CONFNAME% paper #%NUMBER% has withdrawn the paper from consideration. The paper will not be reviewed.%IF(REASON)% They gave the following reason: %REASON%%ENDIF%

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(paper, p=%NUMBER%)%

Contact the site administrator, %ADMIN%, with any questions or concerns.

Thank you,
- %CONFSHORTNAME% Submissions\n"),

     "adminwithdraw" =>
     array("subject" => "[%CONFSHORTNAME%] Withdrawn paper #%NUMBER% %TITLEHINT%",
	   "body" => "Dear %NAME%,

%CONFNAME% paper #%NUMBER% has been withdrawn from consideration and will not be reviewed.

%IF(REASON)%The paper was withdrawn by an administrator, who provided the following reason: %REASON%%ELSE%The paper was withdrawn by an administrator.%ENDIF%

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(paper, p=%NUMBER%)%

Contact the site administrator, %ADMIN%, with any questions or concerns.

Thank you,
- %CONFSHORTNAME% Submissions\n"),

     "withdrawreviewer" =>
     array("subject" => "[%CONFSHORTNAME%] Withdrawn paper #%NUMBER% %TITLEHINT%",
	   "body" => "Dear %NAME%,

%CONFSHORTNAME% paper #%NUMBER%, which you reviewed or have been assigned to review, has been withdrawn from consideration for the conference.

Authors and administrators can withdraw submissions during the review process.%IF(REASON)% The following reason was provided: %REASON%%ENDIF%

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(paper, p=%NUMBER%)%

You are not expected to complete your review (and the system will not allow it unless the paper is revived).

Contact the site administrator, %ADMIN%, with any questions or concerns.

- %CONFSHORTNAME% Submissions\n"),

     "deletepaper" =>
     array("subject" => "[%CONFSHORTNAME%] Deleted paper #%NUMBER% %TITLEHINT%",
	   "body" => "Caro %NAME%,

Your %CONFNAME% paper #%NUMBER% has been removed from the submission database by an administrator. This can be done to remove duplicate papers. %IF(REASON)%The following reason was provided for deleting the paper: %REASON%%ENDIF%

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%

Contact the site administrator, %ADMIN%, with any questions or concerns.

- %CONFSHORTNAME% Submissions\n"),

     "reviewsubmit" =>
     array("subject" => "[%CONFSHORTNAME%] Revisão submetida #%REVIEWNUMBER% %TITLEHINT%",
	   "body" => "Prezado(a) %NAME%,

A revisão #%REVIEWNUMBER% referente ao trabalho #%NUMBER% a ser apresentado em %CONFNAME% foi submetido. A revisão está disponível no site do trabalho.

  Site do trabalho: %URL(paper, p=%NUMBER%)%
            Título: %TITLE%
           Autores: %OPT(AUTHORS)%
      Revisado por: %OPT(REVIEWAUTHOR)%

Visite o site do trabalho para verificar as revisões e comentários mais atuais, ou para remover a notificação por email.

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Sistema de submissões\n"),

     "reviewupdate" =>
     array("subject" => "[%CONFSHORTNAME%] Revisão atualizada #%REVIEWNUMBER% %TITLEHINT%",
	   "body" => "Prezado(a) %NAME%,

A revisão #%REVIEWNUMBER% referente ao trabalho #%NUMBER% a ser apresentado em %CONFNAME% foi submetido. A revisão foi atualizada.

  Site do trabalho: %URL(paper, p=%NUMBER%)%
            Título: %TITLE%
           Autores: %OPT(AUTHORS)%
      Revisado por: %OPT(REVIEWAUTHOR)%Acesse o site de submissão para obter as informações mais atualizados referente ao seu trabalho.

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions\n"),

     "acceptnotify" =>
     array("mailtool_name" => "Accept notification",
	   "mailtool_priority" => 10,
	   "mailtool_recipients" => "dec:yes",
	   "subject" => "[%CONFSHORTNAME%] Trabalho aceito #%NUMBER% %TITLEHINT%",
	   "body" => "Prezado(a) %NAME%,

A comissão científica do evento %CONFNAME% tem o prazer de informar que o seu trabalho #%NUMBER%  foi aceito para apresentação no evento.

       Título: %TITLE%
      Autores: %OPT(AUTHORS)%
  Site do trabalho: %URL(paper, p=%NUMBER%&%AUTHORVIEWCAPABILITY%)%

Seu trabalho foi um no total de %NUMACCEPTED% aceitos até o momento. Parabéns!

Revisões e comentários para o seu paper estão descritos no fim deste email. Estes comentários estão dispnoíveis no site de submissão assim como outras informações referentes a sua revisão.%LOGINNOTICE%

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions

%REVIEWS%
%COMMENTS%\n"),

     "rejectnotify" =>
     array("mailtool_name" => "Reject notification",
	   "mailtool_priority" => 11,
	   "mailtool_recipients" => "dec:no",
	   "subject" => "[%CONFSHORTNAME%] Trabalho desclassificado #%NUMBER% %TITLEHINT%",
	   "body" => "Prezado %NAME%,

The %CONFNAME% program committee is sorry to inform you that your paper #%NUMBER% was rejected, and will not appear in the conference.

         Título: %TITLE%
        Autores: %OPT(AUTHORS)%
  Site do paper: %URL(paper, p=%NUMBER%&%AUTHORVIEWCAPABILITY%)%

%NUMACCEPTED% foi aceito do total de %NUMSUBMITTED% submissões.

Revisões e comentários para o seu paper estão descritos no fim deste email. Estes comentários estão dispnoíveis no site de submissão assim como outras informações referentes a sua revisão.%LOGINNOTICE%

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissões 

%REVIEWS%
%COMMENTS%\n"),

     "commentnotify" =>
     array("subject" => "[%CONFSHORTNAME%] Comentário para #%NUMBER% %TITLEHINT%",
	   "body" => "Um comentário foi cadastrado para o trabalho #%NUMBER% do evento %CONFNAME%. Para acessar os comentários mais atualizados, ou para desativar a notificação por email acesse o site do seu trabalho.

  Site do trabalho: %URL(paper, p=%NUMBER%)%

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- Submissões %CONFSHORTNAME% 

%COMMENTS%\n"),

     "responsenotify" =>
     array("subject" => "[%CONFSHORTNAME%] Response for #%NUMBER% %TITLEHINT%",
	   "body" => "The authors' response for %CONFNAME% paper #%NUMBER% is available as shown below. The authors may still update their response; for the most up-to-date version, or to turn off notification emails, see the paper site.

  Paper site: %URL(paper, p=%NUMBER%)%

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions

%COMMENTS%\n"),

     "finalsubmitnotify" =>
     array("subject" => "[%CONFSHORTNAME%] Updated final paper #%NUMBER% %TITLEHINT%",
	   "body" => "A versão final do seu trabalho #%NUMBER% submetido para o evento  %CONFNAME% foi atualizado. Os autores ainda podem realizar atualizações; para acessar a versão mais atual ou para desativar as notificações por email acesse o site do trabalho. 

  Site do trabalho: %URL(paper, p=%NUMBER%)%

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.


- %CONFSHORTNAME% Submissions\n"),

     "genericmailtool" =>
     array("mailtool_name" => "Generic",
	   "mailtool_pc" => true,
	   "mailtool_priority" => 0,
	   "mailtool_recipients" => "s",
	   "subject" => "[%CONFSHORTNAME%] Paper # %NUMBER% %TITLEHINT%",
	   "body" => "Prezado %NAME%,

Your message here.

       Título: %TITLE%
  Site do trabalho: %URL(paper, p=%NUMBER%)%

Utilize o link abaixo para acessar o sistema de submissão.

%LOGINURL%

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions\n"),

     "reviewremind" =>
     array("mailtool_name" => "Review reminder",
	   "mailtool_pc" => true,
	   "mailtool_priority" => 20,
	   "mailtool_recipients" => "uncrev",
	   "subject" => "[%CONFSHORTNAME%] Review reminder for paper #%NUMBER% %TITLEHINT%",
	   "body" => "Dear %NAME%,

This is a reminder to finish your review for %CONFNAME% paper #%NUMBER%. %IF(REVIEWDEADLINE)% Reviews are requested by %REVIEWDEADLINE%. %ENDIF% If you are unable to complete the review, please decline the review using the site or contact the person who requested the review directly.

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(paper, p=%NUMBER%)%

Use the link below to sign in to the submissions site.

%LOGINURL%

Thank you for your help -- we appreciate that reviewing is hard work.

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions\n"),

     "myreviewremind" =>
     array("mailtool_name" => "Personalized review reminder",
	   "mailtool_pc" => true,
	   "mailtool_priority" => 21,
	   "mailtool_recipients" => "uncmyextrev",
	   "mailtool_search_type" => "t",
	   "subject" => "[%CONFSHORTNAME%] Review reminder for paper #%NUMBER% %TITLEHINT%",
	   "body" => "Dear %NAME%,

This is a reminder from %OTHERCONTACT% to finish your review for %CONFNAME% paper #%NUMBER%. %IF(REVIEWDEADLINE)% Reviews are requested by %REVIEWDEADLINE%. %ENDIF% If you are unable to complete the review, please decline the review using the site or contact %OTHERNAME% directly.

       Title: %TITLE%
     Authors: %OPT(AUTHORS)%
  Paper site: %URL(paper, p=%NUMBER%)%

Use the link below to sign in to the submissions site.

%LOGINURL%

Thank you for your help -- we appreciate that reviewing is hard work.

Entre em contato com o administrador do site, %ADMIN%, para quaisquer questões ou preocupações.

- %CONFSHORTNAME% Submissions\n"),

     "newpcrev" =>
     array("mailtool_name" => "Review assignment notification",
	   "mailtool_recipients" => "newpcrev",
	   "subject" => "[%CONFSHORTNAME%] New review assignments",
	   "body" => "Dear %NAME%,

You have been assigned new reviews for %CONFNAME%. %IF(REVIEWDEADLINE)% Reviews are requested by %REVIEWDEADLINE%.%ENDIF%

             Site: %URL%/
     Your reviews: %URL(search, q=re:me)%
  New assignments: %NEWASSIGNMENTS%

Thank you for your help -- we appreciate that reviewing is hard work.

Contact the site administrator, %ADMIN%, with any questions or concerns.

- %CONFSHORTNAME% Submissions\n"),

     "registerpaper" =>
     array("subject" => "[%CONFSHORTNAME%] Trabalho registrado #%NUMBER% %TITLEHINT%",
	   "body" => "O trabalho #%PAPER% foi registrado no sistema de submissão da %CONFNAME%.

            Título: %TITLE%
           Autores: %OPT(AUTHORS)%
  Site do trabalho: %URL(paper, p=%NUMBER%&%AUTHORVIEWCAPABILITY%)%

%NOTES%%IF(REASON)%An administrator provided the following reason for this registration: %REASON%

%ELSEIF(ADMINUPDATE)%An administrator performed this registration.

%ENDIF%Entre em contato com o administrador do sistema, %ADMIN%, para quaisquer outras dúvidas.

- %CONFSHORTNAME% Submissões\n"),

     "updatepaper" =>
     array("subject" => "[%CONFSHORTNAME%] Trabalho atualizado #%NUMBER% %TITLEHINT%",
	   "body" => "O trabalho #%PAPER% foi atualizado no sistema de submissão da %CONFNAME%.

            Título: %TITLE%
           Autores: %OPT(AUTHORS)%
  Site do trabalho: %URL(paper, p=%NUMBER%&%AUTHORVIEWCAPABILITY%)%

%NOTES%%IF(REASON)%An administrator provided the following reason for this update: %REASON%

%ELSEIF(ADMINUPDATE)%An administrator performed this update.

%ENDIF%Entre em contato com o administrador do sistema, %ADMIN%, para quaisquer outras dúvidas.

- %CONFSHORTNAME% Submissions\n"),

     "submitpaper" =>
     array("subject" => "[%CONFSHORTNAME%] Trabalho submetido #%NUMBER% %TITLEHINT%",
	   "body" => "O trabalho #%PAPER% foi submetido para o sistema de submissão %CONFNAME%.

            Título: %TITLE%
           Autores: %OPT(AUTHORS)%
  Site do trabalho: %URL(paper, p=%NUMBER%&%AUTHORVIEWCAPABILITY%)%

%NOTES%%IF(REASON)%An administrator provided the following reason for this update: %REASON%

%ELSEIF(ADMINUPDATE)%An administrator performed this update.

%ENDIF%Entre em contato com o administrador do sistema, %ADMIN%, para quaisquer outras dúvidas.

- %CONFSHORTNAME% Submissões\n"),

     "submitfinalpaper" =>
     array("subject" => "[%CONFSHORTNAME%] Versão final do trabalho atualizado #%NUMBER% %TITLEHINT%",
	   "body" => "A versão final do trabalho #%PAPER% foi atualizado no sistema de submissão da %CONFNAME%.

            Título: %TITLE%
           Autores: %OPT(AUTHORS)%
  Site do trabalho: %URL(paper, p=%NUMBER%&%AUTHORVIEWCAPABILITY%)%

%NOTES%%IF(REASON)%An administrator provided the following reason for this update: %REASON%

%ELSEIF(ADMINUPDATE)%An administrator performed this update.

%ENDIF%Entre em contato com o administrador do sistema, %ADMIN%, para quaisquer outras dúvidas.

- %CONFSHORTNAME% Submissions\n")

);
