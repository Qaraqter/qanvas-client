<?php
namespace QanvasClient;

use PhpHighCharts\HighChart;

class Client
{
    /**
     * URL of the Qanvas instance.
     *
     * @var string
     */
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function enqueueHighChart(HighChart $chart, $format = 'svg')
    {
        $handle = curl_init($this->url . '/highcharts/enqueue');

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, array(
            'data' => $chart->toJson(),
            'format' => $format,
        ));

        $output = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status == 200) {
            return $output;
        }

        return false;
    }

    public function isProcessedHighChart($url)
    {
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        switch ($status) {
            case 200:
                return true;
            case 204:
                return false;
            default:
                throw new \RuntimeException('An error occurred during generating a HighChart.');
        }
    }

    public function isProcessedOpenDocument($url)
    {
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        switch ($status) {
            case 200:
                return true;
            case 204:
                return false;
            default:
                throw new \RuntimeException('An error occurred during generating an Open Document.');
        }
    }

    public function isProcessedDocument($url)
    {
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        switch ($status) {
            case 200:
                return true;
            case 204:
                return false;
            default:
                throw new \RuntimeException('An error occurred during generating a document.');
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

    public function waitForProcessedDocument($url)
    {
        while (!$this->isProcessedDocument($url)) {
            sleep(1);
        }
    }

    public function generateOpenDocument($template, $data)
    {
        $data = array(
            'template' => new \CURLFile($template),
            'data' => json_encode($data),
        );

        $handle = curl_init($this->url . '/open-document/generate');

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

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
            'template' => new \CURLFile($template),
            'data' => json_encode($data),
        );

        $handle = curl_init($this->url . '/open-document/enqueue');

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status == 200) {
            return $output;
        }

        throw new \RuntimeException('An error occurred during enqueueing an Open Document.');
    }

    public function enqueueDocument($template, $data, $format = 'pdf')
    {
        $data = array(
            'template' => new \CURLFile($template),
            'data' => json_encode($data),
        );

        $handle = curl_init($this->url . '/document/enqueue.' . $format);

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status == 200) {
            return $output;
        }

        throw new \RuntimeException('An error occurred during enqueueing a document.');
    }

    public function generateDocument($template, $data, $format = 'pdf')
    {
        $data = array(
            'template' => new \CURLFile($template),
            'data' => json_encode($data),
        );

        $handle = curl_init($this->url . '/document/generate.' . $format);

        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        $content = curl_exec($handle);
        curl_close($handle);

        return $content;
    }
}
