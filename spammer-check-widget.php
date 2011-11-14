<?php

/*
	Walter Williams

	File: qa-plugin/spammer-check-widget/spammer-check-widget.php
	Version: 1.0
	Date: 2011-10-21
	Description: Widget module class for spammer check
*/



require_once QA_INCLUDE_DIR.'qa-app-users.php';



class spammer_check_widget
{
	function allow_template($template)
	{
		return ($template == 'ip' || $template == 'user');
	}

	function allow_region($region)
	{
		return ($region=='main');
	}

	function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		if (qa_get_logged_in_level() < QA_USER_LEVEL_ADMIN)
			return;

		if ($template == "ip")
		{
			if (isset($qa_content["title"]))
			{
				$ipaddr = $qa_content["title"];
				$ipaddr = str_replace("IP address ", "", $ipaddr);

				$xmlUrl = "http://www.stopforumspam.com/api?ip=" . $ipaddr . "&f=json";
				$result = $this->GetWebPage($xmlUrl);
				if ($result->succeeded)
				{
					$jobj = json_decode($result->response);

					$themeobject->output('<DIV STYLE="font-size:14px; border:1px solid #aaaaaa; padding: 4px;">Spammer check via stopforumspam.com:');
					if ($jobj->success)
					{
						$appears = "<br>Is a known spammer: " . ($jobj->ip->appears == 1 ? "yes" : "no");
						$frequency = "<br>Frequency: " . $jobj->ip->frequency;

						$themeobject->output($appears . $frequency);
					}
					else
					{
						$themeobject->output("No record found");
					}
					$themeobject->output("</div>");
				}


				$xmlUrl = "http://botscout.com/test/?ip=" . $ipaddr . "&key=xxx"; // replace with your own keys
				$result = $this->GetWebPage($xmlUrl);
				if ($result->succeeded)
				{
					$botdata = explode('|', $result->response);

					$themeobject->output('<DIV STYLE="font-size:14px; border:1px solid #aaaaaa; padding: 4px;">Spammer check via botscout.com:');
					if ($botdata[0] != "!")
					{
						$appears = "<br>Is a known spammer: " . ($botdata[0] == "Y" ? "yes" : "no");
						$frequency = "<br>Frequency: " . $botdata[2];

						$themeobject->output($appears . $frequency);
					}
					else
					{
						$themeobject->output($result->response);
					}
					$themeobject->output("</div>");
				}
			}
		}
		else if ($template == "user")
		{
			$email = $qa_content["form_profile"]["fields"]["email"]["value"];

			$xmlUrl = "http://www.stopforumspam.com/api?email=" . urlencode($email) . "&f=json";
			$result = $this->GetWebPage($xmlUrl);
			if ($result->succeeded)
			{
				$jobj = json_decode($result->response);

				$themeobject->output('<DIV STYLE="font-size:14px; border:1px solid #aaaaaa; padding: 4px;">Spammer check via stopforumspam.com:');
				if ($jobj->success)
				{
					$appears = "<br>Is a known spammer: " . ($jobj->email->appears == 1 ? "yes" : "no");
					$frequency = "<br>Frequency: " . $jobj->email->frequency;

					$themeobject->output($appears . $frequency);
				}
				else
				{
					$themeobject->output("No record found");
				}
				$themeobject->output("</div>");
			}

			$xmlUrl = "http://botscout.com/test/?mail=" . urlencode($email) . "&key=xxx"; // replace with your own keys
			$result = $this->GetWebPage($xmlUrl);
			if ($result->succeeded)
			{
				$botdata = explode('|', $result->response);

				$themeobject->output('<DIV STYLE="font-size:14px; border:1px solid #aaaaaa; padding: 4px;">Spammer check via botscout.com:');
				if ($botdata[0] != "!")
				{
					$appears = "<br>Is a known spammer: " . ($botdata[0] == "Y" ? "yes" : "no");
					$frequency = "<br>Frequency: " . $botdata[2];

					$themeobject->output($appears . $frequency);
				}
				else
				{
					$themeobject->output($result->response);
				}
				$themeobject->output("</div>");
			}
		}
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