<?php
$email_1_subject = "Your Flexible Working account setup has been completed";
$email_1_content = "Dear %%whoto%%

Your user account setup has now been completed.
You are now able to add times to your online timesheet, request annual and flexi leave which will automatically send an email to your manager for their approval, and you will receive an email notification when the leave has been approved.

Here is the link for the flexible working system https://internal.ncl.ac.uk/crfflexi please add this to you favorites.

For help and assistance check out the help information on the intranet http://www.ncl.ac.uk/crf/internal or contact IT/Mandy Jarvis.

Kind regards,

CRP Flexible Working System";
$email_2_subject = "Your flexible working account has been updated";
$email_2_content = "Dear %%whoto%%

Your flexi pot account has been updated.
Unfortunately you had accumulated more than the maximum flexi you are allowed to carry over. Therefore you have lost %%INSERT%% time.

Please contact your Manager to discuss this.

Kind regards,

CRP Flexible Working System";
$email_3_subject = "Your flexible working account has been updated";
$email_3_content = "Dear %%whoto%%

Your flexi pot account has been updated.
Unfortunately you have a balance in your flexipot which is less than the acceptable amount.

Please contact your Manager to discuss this immediately.

Kind regards,

CRP Flexitime System";
$email_4_subject = "Password Change Request";
$email_4_content = "Dear %%whoto%%

A request has been made to change your password within the CRP Flexitime system. If you didn't make this request then please ignore it, if you have forgotton your password please click on the link below to reset it.

https://internal.ncl.ac.uk/crfflexi/index.php?func=resetPassword&passcode=%%link%%

You will then be asked to type your email address and the new password, this will reset your password and allow you back in to the flexitime system.

For help and assistance check out the help information on the intranet http://www.ncl.ac.uk/crf/internal or contact IT/Mandy Jarvis.

Kind regards,

CRP Flexible Working System";
$email_5_subject = "Your user account has been created";
$email_5_content = "Dear %%whoto%%
	
Your user account has been created within the CRP Flexitime system. To login to your account you will need to follow the link below.
	
https://internal.ncl.ac.uk/crfflexi/index.php?func=confirmAcc&passcode=%%link%%

Once you setup your password you will be able to login to the flexitime system and manage your timesheet, request annual and flexi leave and check your reamaining leave entitlement. The system also automates the leave request function allowing your manager to see your leave and flexitime requests whilst receiving an email when the requests are made.

Here is the link for the flexitime system https://internal.ncl.ac.uk/crfflexi or look on the flexitime pages on the intranet.

For help and assistance check out the help information on the intranet http://www.ncl.ac.uk/crf/internal or contact IT/Mandy Jarvis.

Kind regards,

CRP Flexible Working System";
$email_6_subject = "Your authorisation is required";
$email_6_content = "Your authorisation is required as a request has been made by %%user%% for %%event%%.

%%MULTIDATES%%Please login to the flexitime system and approve the request, you can login here https://internal.ncl.ac.uk/crfflexi then check the `Outstanding Requests` section at the left hand side and select the link to view the request. Here you will be able to approve or inform the requester why the request cannot be approved.

For help and assistance check out the help information on the intranet http://www.ncl.ac.uk/crf/internal or contact IT/Mandy Jarvis.

Kind regards,

CRP Flexible Working System";
$email_6_multidates = "The request is for multiple dates as follows:

Start Date : %%STARTDATE%%
End Date : %%ENDDATE%%
No. of Days : %%DATECOUNT%%

"; 	
$email_7_subject = "Leave deletion confirmed.";
$email_7_subject1 = "None Leave/Work deletion attempted";
$email_7_content = "Dear %%whoto%%

%%user%% has %%delete%% an event which may already have been authorised. This email is to notify all parties so that they are aware of the removal of the event.

The Event Details are described below:

Event Owner : %%eventowner%% 
Event Type : %%eventtype%%
Event Date : %%EVENTDATE%%
Event Start Time : %%START%% 
Event End Time : %%END%%

The user %%HAS/HASNOT%% been allowed to delete this event, please keep this email for your records.

Kind regards,

CRP Flexible Working System.";
$email_8_subject = "Your leave request has been approved";
$email_8_content = "Dear %%whoto%%

Your request for leave has been approved within the CRF Flexible Working system for the following date:

%%date%%

Kind regards,

%%approver%%";
$email_9_subject = "Your leave request has been refused";
$email_9_content = "Dear %%whoto%%

Your request for leave on %%date%% has been refused for the following reason:

%%MESSAGE%%

Kind regards,

%%approver%%";				
?>