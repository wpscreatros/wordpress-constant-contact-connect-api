<?php

define('redirecrurl', 'Redirect URL');
define('apiKey', 'API KEY');
define('secretcode', 'SECRET KEY');

function getAuthorizationURL($redirectURI, $clientId)
{
    // Create authorization URL
    $baseURL = "https://api.cc.email/v3/idfed";
    $authURL = $baseURL . "?client_id=" . $clientId . "&scope=contact_data&response_type=code" . "&redirect_uri=" . $redirectURI;
    echo $authURL;
}


function getAccessToken($redirectURI, $clientId, $clientSecret, $code)
{
    // Use cURL to get access token and refresh token
    $ch = curl_init();

    // Define base URL
    $base = 'https://idfed.constantcontact.com/as/token.oauth2';

    // Create full request URL
    $url = $base . '?code=' . $code . '&redirect_uri=' . $redirectURI . '&grant_type=authorization_code&scope=contact_data';
    curl_setopt($ch, CURLOPT_URL, $url);

    // Set authorization header
    // Make string of "API_KEY:SECRET"
    $auth = $clientId . ':' . $clientSecret;
    // Base64 encode it
    $credentials = base64_encode($auth);
    // Create and set the Authorization header to use the encoded credentials
    $authorization = 'Authorization: Basic ' . $credentials;
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization));

    // Set method and to expect response
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Make the call
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function refreshToken($refreshToken, $clientId, $clientSecret)
{
    // Use cURL to get a new access token and refresh token
    $ch = curl_init();

    // Define base URL
    $base = 'https://idfed.constantcontact.com/as/token.oauth2';

    // Create full request URL
    $url = $base . '?refresh_token=' . $refreshToken . '&grant_type=refresh_token';
    curl_setopt($ch, CURLOPT_URL, $url);

    // Set authorization header
    // Make string of "API_KEY:SECRET"
    $auth = $clientId . ':' . $clientSecret;
    // Base64 encode it
    $credentials = base64_encode($auth);
    // Create and set the Authorization header to use the encoded credentials
    $authorization = 'Authorization: Basic ' . $credentials;
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization));

    // Set method and to expect response
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Make the call
    $result = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($result, true);
    $access_token = $json['access_token'];
    $refresh_token = $json['refresh_token'];
    enterTokenInDatabase($access_token, $refresh_token);
}

function enterTokenInDatabase($access_token, $refreshtoken)
{
    $conn = mysqli_connect('YOURHOST', 'YOUR USERNAME', 'YOUR PASSWORD');
    if (!$conn) {
        die('Something went wrong while connecting to MSSQL');
    } else {
        mysqli_select_db($conn, 'DBNAME');
    }
    if (!$conn) {
        die('Could not connect: ' . mysqli_error());
    }

    $sql = "INSERT INTO access_token_log (access_token, refreshtoken, timestamp_created)VALUES ('" . $access_token . "', '" . $refreshtoken . "', current_timestamp)";
    mysqli_query($conn, $sql);

    mysqli_close($conn);
}

function getTokenfromDatabase()
{
    $conn = mysqli_connect('YOURHOST', 'YOUR USERNAME', 'YOUR PASSWORD');
    if (!$conn) {
        die('Something went wrong while connecting to MSSQL');
    } else {
        mysqli_select_db($conn, 'DBNAME');
    }
    if (!$conn) {
        die('Could not connect: ' . mysqli_error());
    }

    $sql = "SELECT * FROM access_token_log ORDER BY id DESC LIMIT 1;";
    $result = mysqli_query($conn, $sql);
    mysqli_close($conn);
    return $result;
}

getAuthorizationURL(redirecrurl, apiKey);

if (isset($_GET['code'])) {
    $responsecode = $_GET['code'];
    $json_response = getAccessToken(redirecrurl, apiKey, secretcode, $responsecode);
    //echo (json_decode($json_response, true));
    $json = json_decode($json_response, true);
    $access_token = $json['access_token'];
    $refresh_token = $json['refresh_token'];
    enterTokenInDatabase($access_token, $refresh_token);
}

function generateNewToken()
{
    $lastrefreshtoken = getTokenfromDatabase();
    $result = mysqli_fetch_assoc($lastrefreshtoken);
    refreshToken($result["refreshtoken"], apiKey, secretcode);
}
generateNewToken();
