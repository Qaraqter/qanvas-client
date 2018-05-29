<?php

namespace QanvasClient;

use QanvasClient\Exceptions\CurlTimedOutException;
use PhpHighCharts\HighChart;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\LegacyValidator;

class Client
{
    /**
     * URL of the Qanvas instance.
     *
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * Cache directory.
     *
     * @var string
     */
    private $cacheDir;

    /**
     * Validator.
     *
     * @var LegacyValidator
     */
    private $validator;

    /**
     * Seconds left for request.
     *
     * @var int
     */
    protected $secondsLeftBeforeTimeout = 55;

    public function __construct($url, $apiKey, $cacheDir)
    {
        $this->url = $url;
        $this->apiKey = $apiKey;
        $this->cacheDir = $cacheDir;
        $this->validator = Validation::createValidator();
    }

    public function enqueueHighChart(HighChart $chart, $format = 'svg', $width = 600)
    {
        $handle = $this->getCurlHandle($this->url . '/highchart/enqueue', false, true);

        curl_setopt($handle, CURLOPT_POSTFIELDS, array(
            'data' => $chart->toJson(),
            'format' => $format,
            'width' => $width,
        ));

        list($output, $status) = $this->executeCurl($handle);

        // status is expected to be 200 and output is expected to be a full url
        $violations = $this->validator->validateValue($output, new Url());
        if ($status == 200 && $violations->count() == 0) {
            return $output . '?api_key=' . $this->apiKey;
        }

        throw new \RuntimeException('HighChart could not be enqueued!');
    }

    public function isProcessedHighChart($url)
    {
        $handle = $this->getCurlHandle($url, true, false);

        list($output, $status) = $this->executeCurl($handle);

        switch ($status) {
            case 200:
                return true;
            case 204:
                return false;
            default:
                throw new \RuntimeException(sprintf(
                    'HTTP status code %d was returned from %s while waiting for HighChart to process.',
                    $status,
                    $url
                ));
        }
    }

    public function clearProcessedHighChart($url)
    {
        $filename = basename($url);
        $url = $this->url . '/highchart/delete-processed/' . $filename;

        $handle = $this->getCurlHandle($url, false, true);

        list($output, $status) = $this->executeCurl($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function clearHighChartQueue()
    {
        $handle = $this->getCurlHandle($this->url . '/highchart/clear-queue', true, false);

        list($output, $status) = $this->executeCurl($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function clearOpenDocumentQueue()
    {
        $handle = $this->getCurlHandle($this->url . '/open-document/clear-queue', true, false);

        list($output, $status) = $this->executeCurl($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function clearProcessedDocument($url)
    {
        $filename = basename($url);
        $url = $this->url . '/document/delete-processed/' . $filename;

        $handle = $this->getCurlHandle($url, false, true);

        list($output, $status) = $this->executeCurl($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function clearDocumentQueue()
    {
        $handle = $this->getCurlHandle($this->url . '/document/clear-queue', true, false);

        list($output, $status) = $this->executeCurl($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function isProcessedOpenDocument($url)
    {
        $handle = $this->getCurlHandle($url, true, false);

        list($output, $status) = $this->executeCurl($handle);

        switch ($status) {
            case 200:
                return true;
            case 204:
                return false;
            default:
                throw new \RuntimeException(sprintf(
                    'HTTP status code %d was returned from %s while waiting for OpenDocument to process.',
                    $status,
                    $url
                ));
        }
    }

    public function isProcessedDocument($url)
    {
        $handle = $this->getCurlHandle($url, true, false);

        list($output, $status) = $this->executeCurl($handle);

        switch ($status) {
            case 200:
                return true;
            case 204:
                return false;
            default:
                throw new \RuntimeException(sprintf(
                    'HTTP status code %d was returned from %s while waiting for Document to process.',
                    $status,
                    $url
                ));
        }
    }

    public function waitForProcessedHighChart($url)
    {
        $start = time();
        while (!$this->isProcessedHighChart($url)) {
            $this->handleWaitingForDocument($start);
        }
    }

    public function waitForProcessedOpenDocument($url)
    {
        $start = time();
        while (!$this->isProcessedOpenDocument($url)) {
            $this->handleWaitingForDocument($start);
        }
    }

    public function waitForProcessedDocument($url)
    {
        $start = time();
        while (!$this->isProcessedDocument($url)) {
            $this->handleWaitingForDocument($start);
        }
    }

    public function downloadDocument($url)
    {
        $handle = $this->getCurlHandle($url, false, true, false);

        list($output, $status) = $this->executeCurl($handle);

        return $output;
    }

    public function downloadHighChart($url)
    {
        $handle = $this->getCurlHandle($url, false, true, false);

        list($output, $status) = $this->executeCurl($handle);

        return $output;
    }

    public function downloadOpenDocument($url)
    {
        $handle = $this->getCurlHandle($url, false, true, false);

        list($output, $status) = $this->executeCurl($handle);

        return $output;
    }

    public function generateOpenDocument($template, $data)
    {
        $data = array(
            'template' => class_exists('CURLFile') ? new \CURLFile($template) : "@$template",
            'data' => json_encode($data),
        );

        $handle = $this->getCurlHandle($this->url . '/open-document/generate');

        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        list($output, $status) = $this->executeCurl($handle);

        if ($status == 200) {
            return $output;
        }

        throw new \RuntimeException('An error occurred during generating an Open Document.');
    }

    public function enqueueOpenDocument($template, $data)
    {
        $data = array(
            'template' => class_exists('CURLFile')
                ? new \CURLFile($template)
                : "@$template",
            'data' => json_encode($data),
        );

        $handle = $this->getCurlHandle($this->url . '/open-document/enqueue', false, true);

        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        list($output, $status) = $this->executeCurl($handle);

        // status is expected to be 200 and output is expected to be a full url
        $violations = $this->validator->validateValue($output, new Url());
        if ($status == 200 && $violations->count() == 0) {
            return $output . '?api_key=' . $this->apiKey;
        }

        throw new \RuntimeException('OpenDocument could not be enqueued!');
    }

    public function enqueueDocument($template, $data, $format = 'pdf')
    {
        $data = array(
            'template' => class_exists('CURLFile')
                ? new \CURLFile($template)
                : "@$template",
            'data' => json_encode($data),
        );

        $handle = $this->getCurlHandle($this->url . '/document/enqueue.' . $format, false, true);

        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        list($output, $status) = $this->executeCurl($handle);

        // status is expected to be 200 and output is expected to be a full url
        $violations = $this->validator->validateValue($output, new Url());
        if ($status == 200 && $violations->count() == 0) {
            return $output . '?api_key=' . $this->apiKey;
        }

        throw new \RuntimeException('Document could not be enqueued!');
    }

    public function generateDocument($template, $data, $format = 'pdf')
    {
        $data = array(
            'template' => class_exists('CURLFile') ? new \CURLFile($template) : "@$template",
            'data' => json_encode($data),
        );

        $handle = $this->getCurlHandle($this->url . '/document/generate.' . $format);

        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        list($output, $status) = $this->executeCurl($handle);

        return $output;
    }

    public function getMimeType($url)
    {
        if ($this->isProcessedDocument($url)) {
            // create tmp file
            $file = tempnam($this->cacheDir, 'qanvas');
            copy($url, $file);

            // guess mime type
            $guesser = MimeTypeGuesser::getInstance();
            $mimeType = $guesser->guess($file);

            // remove tmp file
            unlink($file);

            return $mimeType;
        }

        return false;
    }

    private function getCurlHandle($url, $includeHeader = true, $includeBody = true, $includePost = true)
    {
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_TIMEOUT, $this->getSecondsLeftBeforeTimeout());
        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        if ($includePost) {
            curl_setopt($handle, CURLOPT_POST, true);
        }

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'ApiKey: ' . $this->apiKey,
        ));

        if ($includeHeader) {
            curl_setopt($handle, CURLOPT_HEADER, true);
        }

        if (!$includeBody) {
            curl_setopt($handle, CURLOPT_NOBODY, true);
        }

        return $handle;
    }

    /**
     * Execute the curl handle and return the output. If there is any timeout we throw an exception.
     *
     * @param $handle
     * @return mixed
     * @throws CurlTimedOutException
     */
    private function executeCurl($handle)
    {
        $output = curl_exec($handle);

        if (curl_errno($handle) === CURLE_OPERATION_TIMEDOUT) {
            throw new CurlTimedOutException;
        }

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        return [
            $output,
            $status
        ];
    }

    /**
     * @param float $seconds
     */
    public function setSecondsLeftBeforeTimeout($seconds)
    {
        $this->secondsLeftBeforeTimeout = $seconds;
    }

    /**
     * @return float
     */
    public function getSecondsLeftBeforeTimeout()
    {
        return $this->secondsLeftBeforeTimeout;
    }

    /**
     * @param $start
     * @throws CurlTimedOutException
     */
    protected function handleWaitingForDocument($start)
    {
        sleep(1);
        if (time() >= $start + $this->getSecondsLeftBeforeTimeout()) {
            throw new CurlTimedOutException;
        }
    }

}
