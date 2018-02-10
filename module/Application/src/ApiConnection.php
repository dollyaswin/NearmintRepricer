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
        curl_setopt($ch, CURLOPT_HEADER, true);

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
    protected function submitFormWithFile($url, $postVariables = false, $files = false, $headers = false)
    {
        // create curl resource
        $ch = curl_init();

        curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ]
        );

        $this->curlCustomPostfields($ch, $postVariables, $files, $headers);

        // cookies!!!
        curl_setopt($ch, CURLOPT_COOKIESESSION, true );
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->config['cookieFile'] );
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);

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


    /**
     * For safe multipart POST request for PHP5.3 ~ PHP 5.4.
     *
     * @param resource $ch cURL resource
     * @param array $assoc "name => value"
     * @param array $files "name => path"
     * @param bool|array $headers
     * @return bool
     */
    private function curlCustomPostfields($ch, array $assoc = [], array $files = [], $headers = []) {

        // invalid characters for "name" and "filename"
        static $disallow = ["\0", "\"", "\r", "\n"];

        // build normal parameters
        foreach ($assoc as $key => $value) {
            $key = str_replace($disallow, "_", $key);
            $body[] = implode("\r\n", [
                "Content-Disposition: form-data; name=\"{$key}\"",
                "",
                $value,
            ]);
        }

        // build file parameters
        foreach ($files as $key => $value) {
            switch (true) {
                case false === $value = realpath(filter_var($value)):
                case !is_file($value):
                case !is_readable($value):
                    continue; // or return false, throw new InvalidArgumentException
            }
            $data = file_get_contents($value);
            $value = basename($value);
            $key = str_replace($disallow, "_", $key);
            $value = str_replace($disallow, "_", $value);
            $body[] = implode("\r\n", array(
                "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$value}\"",
                "Content-Type: text/csv",
                "",
                $data,
            ));
        }

        // generate safe boundary
        do {
            //$boundary = "---------------------" . md5(mt_rand() . microtime());
            $boundary = "--WebKitFormBoundaryFLfENGUAWvTqvoJ2";
        } while (preg_grep("/{$boundary}/", $body));

        // add boundary for each parameters
        array_walk($body, function (&$part) use ($boundary) {
            $part = "--{$boundary}\r\n{$part}";
        });

        // add final boundary
        $body[] = "--{$boundary}--";
        $body[] = "";


        $headers[] = "Expect: 100-continue";
        $headers[] = "Content-Type: multipart/form-data; boundary={$boundary}";

        curl_setopt_array($ch, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => implode("\r\n", $body),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        return true;
    }

    /*********************************
     * Read the CSV file downloaded from the source and turn it into a PHP Array
     * This is not scalable to over 1 million rows, but works for any normal number of products.
     *
     * @return array of arrays with keys of the header row
     ********************************/
    public function createArrayFromFile($fileName = '')
    {
        if (empty($fileName)) {
            $fileName = $this->config['localFileLocation'];
        }
        $headerArray = [];
        $resultArray = [];
        $fp = fopen($fileName, 'r');
        while ($line = fgetcsv($fp)) {
            if (count($headerArray) == 0) {
                $headerArray = $line;
            } else {
                $resultArray[] = array_combine($headerArray, $line);
            }
        }
        fclose($fp);
        return $resultArray;

    }

}