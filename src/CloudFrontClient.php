<?php


class CloudFrontClient
{
    const PROTOCOL = 'https';
    const ENDPOINT = 'cloudfront.amazonaws.com';
    const API_VERSION = '2013-05-12';
    const SERVICE_NAME = 'cloudfront';
    const REGION = 'us-east-1';
    const HASH_ALGORITHM = 'sha256';
    const HASH_ALGORITHM_AWS_NAME = "AWS4-HMAC-SHA256";

    const DEFAULT_PAYLOAD = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    protected $accessKeyId;
    protected $secretKeyId;

    public function __construct($accessKeyId, $secretKeyId)
    {
        $this->accessKeyId = $accessKeyId;
        $this->secretKeyId = $secretKeyId;
    }

    public function getDistributions()
    {
        return $this->performRequest('distribution');
    }

    public function getInvalidations($distributionId){
        $location = 'distribution/' . $distributionId . '/invalidation';
        return $this->performRequest($location);
    }

    public function getInvalidation($distributionId, $invalidationId){
        $location = 'distribution/' . $distributionId . '/invalidation/' . $invalidationId;
        return $this->performRequest($location);
    }

    public function sendInvalidations($distributionId, $resourcesToInvalidate){
        $location = 'distribution/' . $distributionId . '/invalidation';

        $batch = new SimpleXMLElement('<InvalidationBatch />');
        $batch->addAttribute('xmlns', 'http://cloudfront.amazonaws.com/doc/2013-05-12/');
        $paths = $batch->addChild('Paths');
        $batch->addChild('CallerReference', time());
        $paths->addChild('Quantity', count($resourcesToInvalidate));
        $items = $paths->addChild('Items');

        foreach($resourcesToInvalidate as $resourcePath){
            $items->addChild('Path', $resourcePath);
        }

//        echo $batch->asXML();

        return $this->performRequest($location, 'POST', $batch->asXML());
    }

    protected function performRequest($location, $method='GET', $body='')
    {
        $date = gmdate('Ymd\THis\Z');

        $headers = array(
            'Host' => self::ENDPOINT,
            'x-amz-date' => $date,
        );

        if(strlen($body) > 0){
            $headers['Content-Type'] = 'text/xml';
            $headers['Content-Length'] = strlen($body);
        }

        $headers['Authorization'] = $this->getAuthorizationHeader($location, $method, $body, $headers, $date);

        $headers['Expect'] = '';

        $url = self::PROTOCOL.'://'.self::ENDPOINT . '/' . self::API_VERSION . '/' . $location;
        return $this->sendRequest($url, $method, $headers, $body);
    }

    protected function sendRequest($url, $method, $headers, $body = '')
    {
        $ch = curl_init();


        curl_setopt($ch, CURL_HTTP_VERSION_1_1, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);

        if($method == 'POST'){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->createCurlHeaders($headers));

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    protected function getAuthorizationHeader($location, $method, $body, $headers, $date){
        $shortDate = $this->makeShortDate($date);

        $canonicalRequest = $this->createCanonicalRequest($method, '/'.self::API_VERSION.'/'.$location, '', $headers, $body);
        $signedHeaders = $canonicalRequest['signed_headers'];
        $canonicalRequest = $canonicalRequest['canonical_request'];


        $credentialScope = $shortDate . '/'.self::REGION.'/'.self::SERVICE_NAME.'/aws4_request';
        $stringToSign = self::HASH_ALGORITHM_AWS_NAME."\n" . "$date\n" . "$credentialScope\n" . hash(self::HASH_ALGORITHM, $canonicalRequest);
        $signKey = $this->getSigningKey($shortDate, self::REGION, self::SERVICE_NAME, $this->secretKeyId);
        $signature = hash_hmac(self::HASH_ALGORITHM, $stringToSign, $signKey);

        return self::HASH_ALGORITHM_AWS_NAME . ' Credential=' . $this->accessKeyId . '/' . $credentialScope . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;

    }

    protected function getSigningKey($shortDate, $region, $service, $secretKey)
    {
        $dateKey = hash_hmac(self::HASH_ALGORITHM, $shortDate, 'AWS4' . $secretKey, true);
        $regionKey = hash_hmac(self::HASH_ALGORITHM, $region, $dateKey, true);
        $serviceKey = hash_hmac(self::HASH_ALGORITHM, $service, $regionKey, true);
        return hash_hmac(self::HASH_ALGORITHM, 'aws4_request', $serviceKey, true);
    }

    protected function createCanonicalRequest($method, $path, $query, $rawHeaders, $body)
    {
        $canon = $method . "\n"
            . '/' . ltrim($path, '/') . "\n"
            . $query . "\n";

        // Create the canonical headers
        $headers = array();
        foreach ($rawHeaders as $key => $value) {
            if ($key != 'User-Agent') {
                $key = strtolower($key);
                if (!isset($headers[$key])) {
                    $headers[$key] = array();
                }
                $headers[$key][] = preg_replace('/\s+/', ' ', trim($value));
            }
        }

        // The headers must be sorted
        ksort($headers);

        // Continue to build the canonical request by adding headers
        foreach ($headers as $key => $values) {
            // Combine multi-value headers into a sorted comma separated list
            if (count($values) > 1) {
                sort($values);
            }
            $canon .= $key . ':' . implode(',', $values) . "\n";
        }

        // Create the signed headers
        $signedHeaders = implode(';', array_keys($headers));
        $canon .= "\n{$signedHeaders}\n";

        // Create the payload if this request has an entity body
        if (array_key_exists('x-amz-content-sha256', $rawHeaders)) {
            // Handle streaming operations (e.g. Glacier.UploadArchive)
            $canon .= $rawHeaders['x-amz-content-sha256'];
        } elseif (strlen($body) > 0) {
            $canon .= hash(
                self::HASH_ALGORITHM,
                (string) $body
            );
        }  else {
            $canon .= self::DEFAULT_PAYLOAD;
        }

        return array(
            'canonical_request' => $canon,
            'signed_headers' => $signedHeaders
        );
    }

    protected function makeShortDate($longDate){
        $shortDate = str_replace(array('T', 'Z'), '', $longDate);
        $shortDate = substr($shortDate, 0, 8);

        return $shortDate;
    }

    private function createCurlHeaders($headers)
    {
        $curlHeaders = array();

        foreach($headers as $header => $value){
            $curlHeaders[] = $header . ': ' . $value;
        }

        return $curlHeaders;
    }
}

if(!isSet($_ENV['ACCESS_KEY_ID'])){
  echo "ACCESS_KEY_ID enviroment variable not set. Make sure you have set variables_order to 'EGPCS' in php.ini.";
  exit();
}

if(!isSet($_ENV['SECRET_KEY_ID'])){
  echo "SECRET_KEY_ID enviroment variable not set. Make sure you have set variables_order to 'EGPCS' in php.ini.";
  exit();
}

$client = new CloudFrontClient($_ENV['ACCESS_KEY_ID'], $_ENV['SECRET_KEY_ID']);


/**
 * Example use of CloudFrontClient
 */
echo $client->getDistributions();
//echo $client->getInvalidations('ERO7LN7O2OCD1');
//echo $client->getInvalidation('ERO7LN7O2OCD1', 'IU6GBV19OP328');
//echo $client->sendInvalidations('ERO7LN7O2OCD1', array('/chroma.min.js'));
