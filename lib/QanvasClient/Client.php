<?php
namespace QanvasClient;

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

    public function __construct($url, $apiKey, $cacheDir)
    {
        $this->url = $url;
        $this->apiKey = $apiKey;
        $this->cacheDir = $cacheDir;
        $this->validator = Validation::createValidator();
    }

    public function enqueueHighChart(HighChart $chart, $format = 'svg')
    {
        $handle = $this->getCurlHandle($this->url . '/highchart/enqueue');

        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, array(
            'data' => $chart->toJson(),
            'format' => $format,
        ));

        $output = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        // status is expected to be 200 and output is expected to be a full url
        $violations = $this->validator->validateValue($output, new Url());
        if ($status == 200 && $violations->count() == 0) {
            return $output;
        }

        throw new \RuntimeException('HighChart could not be enqueued!');
    }

    public function isProcessedHighChart($url)
    {
        $handle = $this->getCurlHandle($url, true, false);

        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

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
        $handle = $this->getCurlHandle($this->url . '/highchart/clear-queue', true, false);

        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function clearHighChartQueue()
    {
        $handle = $this->getCurlHandle($this->url . '/highchart/clear-queue', true, false);

        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function clearOpenDocumentQueue()
    {
        $handle = $this->getCurlHandle($this->url . '/open-document/clear-queue', true, false);

        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function clearProcessedDocument($url)
    {
        $handle = $this->getCurlHandle($this->url . '/document/clear', true, false);

        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function clearDocumentQueue()
    {
        $handle = $this->getCurlHandle($this->url . '/document/clear-queue', true, false);

        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status == 200) {
            return true;
        }

        return false;
    }

    public function isProcessedOpenDocument($url)
    {
        $handle = $this->getCurlHandle($url, true, false);

        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

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

        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

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
        while (!$this->isProcessedHighChart($url)) {
            sleep(1);
        }
    }

    public function waitForProcessedOpenDocument($url)
    {
        while (!$this->isProcessedOpenDocument($url)) {
            sleep(1);
        }
    }

    public function downloadDocument($url)
    {
        $handle = $this->getCurlHandle($url, false, true);

        $output = curl_exec($handle);

        return $output;
    }

    public function downloadHighChart($url)
    {
        $handle = $this->getCurlHandle($url, false, true);

        $output = curl_exec($handle);

        return $output;
    }

    public function downloadOpenDocument($url)
    {
        $handle = $this->getCurlHandle($url, false, true);

        $output = curl_exec($handle);

        return $output;
    }

    public function waitForProcessedDocument($url)
    {
        while (!$this->isProcessedDocument($url)) {
            sleep(1);
        }
    }

    public function generateOpenDocument($template, $data)
    {
        $data = array(
            'template' => class_exists('CURLFile') ? new \CURLFile($template) : "@$template",
            'data' => json_encode($data),
        );

        $handle = $this->getCurlHandle($this->url . '/open-document/generate');

        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

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

        $handle = $this->getCurlHandle($this->url . '/open-document/enqueue');

        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        // status is expected to be 200 and output is expected to be a full url
        $violations = $this->validator->validateValue($output, new Url());
        if ($status == 200 && $violations->count() == 0) {
            return $output;
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

        $handle = $this->getCurlHandle($this->url . '/document/enqueue.' . $format);

        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        // status is expected to be 200 and output is expected to be a full url
        $violations = $this->validator->validateValue($output, new Url());
        if ($status == 200 && $violations->count() == 0) {
            return $output;
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

        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        $content = curl_exec($handle);
        curl_close($handle);

        return $content;
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

    private function getCurlHandle($url, $includeHeader = true, $includeBody = true)
    {
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
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
}
