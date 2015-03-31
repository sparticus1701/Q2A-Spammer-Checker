<?php

/*
	Walter Williams

	File: qa-plugin/spammer-check-widget/spammer-check-event.php
	Version: 2.0
	Date: 2011-7-27
	Description: Event module class for spammer check plugin
	
	History
	2015-03-28, Anthony
	  - Fixed to use more efficient single queries to StopForumSpam and BotScout instead of two separate queries.
	  - Add Logging using EventLogger if it is enabled.
	  - Die if a spammer is detected with a terse message.   Need to improve.
*/


require_once QA_INCLUDE_DIR.'qa-db-selects.php';
require_once QA_INCLUDE_DIR.'qa-app-users.php';
require_once QA_INCLUDE_DIR.'qa-app-format.php';
require_once QA_INCLUDE_DIR.'qa-app-emails.php';
require_once QA_INCLUDE_DIR.'qa-app-posts.php';
require_once QA_BASE_DIR.'qa-config.php';


class spammer_check_event
{
	function process_event ($event, $userid, $handle, $cookieid, $params)
	{
		if ($event == 'u_register' && qa_opt('auto_delete_spammers_enabled'))
		{
			// Find the email, and IP of the registrant
			$email = $params['email'];
			$userinfo = qa_db_select_with_pending(qa_db_user_account_selectspec($userid, true));
			$loginip = $userinfo['loginip'];

			$isspammer = false;

			$logParams = $params;

			// Make only a single request to StopForumSpam for both email and ip, faster and allows more queries.  
			// See: http://www.stopforumspam.com/usage
			$xmlUrl = "http://www.stopforumspam.com/api?email=" . urlencode($email) . "&ip=" . $loginip . "&f=json";
			$result = $this->GetWebPage($xmlUrl);
			if ($result->succeeded)
			{
				$logParams['STOPFORUMSPAM_RESULT'] = $result->response;
				$jobj = json_decode($result->response);
				if ($jobj->success)
				{
					$isSpammerIP=($jobj->ip->appears == 1 ? true : false);
					$isSpammerEmail=($jobj->email->appears == 1 ? true : false);
					$isspammer |= $isSpammerIP || $isSpammerEmail;

					if($isSpammerIP)
						$logParams['STOPFORUMSPAM_SPAMMER_IP'] = true;
					if($isSpammerEmail)
						$logParams['STOPFORUMSPAM_SPAMMER_EMAIL'] = true;
				}
			}

			// Make only a single request to botscout for both email and ip, faster and allows more queries.  
			// See: http://botscout.com/api.htm
			$xmlUrl = "http://botscout.com/test/?multi&mail=" . urlencode($email) . "&ip=" . $loginip . "&key=XXX"; // replace with your own keys
			$result = $this->GetWebPage($xmlUrl);
			if ($result->succeeded)
			{
				$logParams['BOTSCOUT_RESULT'] = $result->response;
				$botdata = explode('|', $result->response);

				if ($botdata[0] == "!") {
					$logParams['BOTSCOUT_ERROR'] = true;
				} else {
					$isBotScoutSpammer = ($botdata[0] == "Y" ? true : false);
					$isspammer != $isBotScoutSpammer;
					if ($isBotScoutSpammer)
						$logParams['BOTSCOUT_SPAMMER'] = true;
				}
			}

			//  If the EventLogger is enabled then log the Spam Check results
			if (qa_opt('event_logger_to_database') || qa_opt('event_logger_to_database')) {
				$eventLogger = new qa_event_logger();
				$eventLogger->process_event ('spamcheck_'.$isspammer, $userid, $handle, $cookieid, $logParams);
			}

			if ($isspammer) // Automatically delete the spammer user
			{

				// Delete from users table
				qa_db_query_sub('DELETE IGNORE FROM ^users WHERE userid = ($)', $userid);

				// Delete from userpoints & userprofile tables
				qa_db_query_sub('DELETE IGNORE FROM ^userpoints WHERE userid = ($)', $userid);
				qa_db_query_sub('DELETE IGNORE FROM ^userprofile WHERE userid = ($)', $userid);

				qa_send_email(array(
					'fromemail' => qa_opt('from_email'),
					'fromname' => qa_opt('site_title'),
					'toemail' => $email,
					'toname' => $email,
					'subject' => "User registration rejected",
					'body' => "Your registration has been rejected because your IP address or email has been reported as used for sending spam.  If you feel this is in error, please contact your service provider.",
					'html' => false,
				));


				// At this point the user that QA is expecting no longer exists so it will blow up.
				// need to come up with something nicer but that is for later..
				echo "Your credentials appear to be associated with a spam user.  Good bye.";
				die();
			}
		}
	}

	function admin_form(&$qa_content)
	{
		$saved=false;

		if (qa_clicked('spammercheck_save_button')) {
			qa_opt('auto_delete_spammers_enabled', (int)qa_post_text('auto_delete_spammers_enabled_field'));
			$saved=true;
		}

		return array(
			'ok' => $saved ? 'Spammer Check settings saved' : null,

			'fields' => array(
				array(
					'label' => 'Automatically delete users who appear to be spammers (via stopforumspam.com and botscout.com)',
					'type' => 'checkbox',
					'value' => (int)qa_opt('auto_delete_spammers_enabled'),
					'tags' => 'NAME="auto_delete_spammers_enabled_field" ID="auto_delete_spammers_enabled_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'NAME="spammercheck_save_button"',
				),
			),
		);
	}

	function GetWebPage($strURL, $intConnectTimeOut = 10, $intTimeOut = 0)
	{
		try{
			$c = curl_init($strURL);
		}
		catch (Exception $e)
		{
			return (object) array("succeeded" => false, "response" => $e->getMessage());
		}

		curl_setopt($c,CURLOPT_URL,$strURL);
		curl_setopt($c,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($c,CURLOPT_VERBOSE, true);
		curl_setopt($c,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $intConnectTimeOut);
		if ($intTimeOut)
		{
		curl_setopt($c, CURLOPT_TIMEOUT, $intTimeOut);
		}
		$strResponse = curl_exec($c);

		$intInfo = curl_getinfo($c,CURLINFO_HTTP_CODE);
		curl_close($c);

		if ($intInfo != 200)
		{
				return (object) array("succeeded" => false, "response" => "Error: Could not get web page. Result: " . $intInfo);
		}
		else
		{
				return (object) array("succeeded" => true, "response" => $strResponse);
		}
	}
};


/*
	Omit PHP closing tag to help avoid accidental output
*/
