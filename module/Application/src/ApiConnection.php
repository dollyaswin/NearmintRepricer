<?php

/***************************************
 * This class exists in order to house functions commonly used
 * by all API connections.
 *
 * API Connection classes are meant to extend this one.
 *
 *****************************************/

namespace Application;

abstract class ApiConnection
{
    protected $authorizePostVariables;
    protected $config;

    // @var string
    public $mostRecentCurlError;

    protected function authorize()
    {
        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $this->config['authorizeUrl']);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->authorizePostVariables);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true );
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_REFERER, $this->config['baseUrl']);

        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);


        // $output contains the output string
        $response = curl_exec($ch);

        if ($response === false) {
            echo 'Curl error: ' . curl_error($ch) . PHP_EOL;
        }

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $this->headers = $headers;

        // close curl resource to free up system resources
        curl_close($ch);
        return $body;
    }

    protected function downloadToFile($remoteUrl, $localFileLocation)
    {
        $ch = curl_init($remoteUrl);
        $fp = fopen($localFileLocation, "w");

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true );
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['cookieFile'] );
        curl_exec($ch);
        $returnCode = true;
        if (curl_error($ch)) {
            $returnCode = false;
        }
        curl_close($ch);
        fclose($fp);
        return $returnCode;
    }

    /**********************************
     * Make the Curl, handle setting the post variables, storing the cookies, and checking the curl error object.
     *
     * @param string $url
     * @param bool|array $postVariables optional array
     * @param bool|array $headers optional array
     * @param bool|string $refererOverride optional URL of referring page.
     *
     * @return bool|string Output from the cURL
     */
    protected function transmit($url, $postVariables = false, $headers = false, $refererOverride = false)
    {
        // create curl resource
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ]
        );

        if ($postVariables) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postVariables);
        }

        // cookies!!!
        curl_setopt($ch, CURLOPT_COOKIESESSION, true );
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['cookieFile'] );
        if ($refererOverride) {
            curl_setopt($ch, CURLOPT_REFERER, $refererOverride);
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        } else {
            curl_setopt($ch, CURLOPT_REFERER, $this->config['referer']);
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        // This would add the header to the returned page
        //curl_setopt($ch, CURLOPT_HEADER, true);

        // $output contains the output string
        $output = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            $this->mostRecentCurlError = 'Curl error: ' . curl_error($ch);
            return false;
        }
        // close curl resource to free up system resources
        curl_close($ch);
        return $output;
    }

}