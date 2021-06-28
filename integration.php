<?php

require_once(dirname(__FILE__) . "/mixpanel/Mixpanel.php");
require_once(dirname(__FILE__) . "/google-api-php-client/autoload.php");

//https://github.com/mixpanel/mixpanel-php
$mp = new Mixpanel("", array(
	"debug" => false,
	"use_ssl" => false,
	"fork" => false,
	"consumer" => "curl"
));

define('APPLICATION_NAME', 'Google Sheets API PHP Quickstart');
define('CREDENTIALS_PATH', 'credentials/sheets.googleapis.com-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');

define('SCOPES', implode(
	' ',
	array(
		Google_Service_Sheets::SPREADSHEETS_READONLY
	)
));

function getClient()
{
	$client = new Google_Client();
	$client->setApplicationName(APPLICATION_NAME);
	$client->setScopes(SCOPES);
	$client->setAuthConfigFile(CLIENT_SECRET_PATH);
	$client->setAccessType('offline');

	// Load previously authorized credentials from a file.
	$credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
	if (file_exists($credentialsPath)) {
		$accessToken = file_get_contents($credentialsPath);
	} else {
		// Request authorization from the user.
		$authUrl = $client->createAuthUrl();
		printf("Open the following link in your browser:\n%s\n", $authUrl);
		print 'Enter verification code: ';
		$authCode = trim(fgets(STDIN));

		// Exchange authorization code for an access token.
		$accessToken = $client->authenticate($authCode);

		// Store the credentials to disk.
		if (!file_exists(dirname($credentialsPath))) {
			mkdir(dirname($credentialsPath), 0700, true);
		}
		file_put_contents($credentialsPath, $accessToken);
		printf("Credentials saved to %s\n", $credentialsPath);
	}
	$client->setAccessToken($accessToken);

	// Refresh the token if it's expired.
	if ($client->isAccessTokenExpired()) {
		$client->refreshToken($client->getRefreshToken());
		file_put_contents($credentialsPath, $client->getAccessToken());
	}
	return $client;
}

function expandHomeDirectory($path)
{
	$homeDirectory = getenv('HOME');
	if (empty($homeDirectory)) {
		$homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
	}
	return str_replace('~', realpath($homeDirectory), $path);
}

$client = getClient();
$service = new Google_Service_Sheets($client);

$spreadsheetId = "";
$range = "";
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

$rowNum = 0;

if (count($values) == 0) {
	echo "No data found.\n";
} else {
	foreach ($values as $row) {
		if ($rowNum != 0) {
			$data = array(
				'$first_created' => $row[0],
				'$first_name' => $row[1],
				'$last_name' => $row[2],
				'$phone' => $row[3],
				'$email' => $row[4],
				'$lead_source' => $row[5],
				'$service_type' => $row[6],
				'$company_name' => $row[7],
				'$event_date' => $row[8],
				'$event_duration' => $row[9],
				'$event_time' => $row[10],
				'$boat_name' => $row[11],
				'$lead_price' => $row[12],
				'$payment_type' => $row[13],
				'$lead_status' => $row[14],
				'$billing_status' => $row[15]
			);

			$res = $mp->people->set($row[3], $data);
		}
		$rowNum++;
	}
}
echo "cron finished";
