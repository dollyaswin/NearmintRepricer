<?php

namespace Application;

abstract class ApiConnection
{
    protected $authorizePostVariables;
    protected $config;

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

        // $output contains the output string
        $output = curl_exec($ch);
        if ($output === false) {
            echo 'Curl error: ' . curl_error($ch) . PHP_EOL;
        }

        // close curl resource to free up system resources
        curl_close($ch);
        return $output;
    }

    protected function transmit($url, $postVariables = false)
    {
        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($postVariables) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->authorizePostVariables);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true );
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_REFERER, $this->config['referer']);

        // $output contains the output string
        $output = curl_exec($ch);
        if ($output === false) {
            echo 'Curl error: ' . curl_error($ch) . PHP_EOL;
        }

        // close curl resource to free up system resources
        curl_close($ch);
        return $output;
    }

}