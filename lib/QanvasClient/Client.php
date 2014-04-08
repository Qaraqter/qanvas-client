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

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, array(
            'data' => (string) $chart,
            'format' => $format,
        ));

        $output = curl_exec($handle);
        curl_close($handle);

        return $output;
    }

    public function isProcessedHighChart($url)
    {
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_exec($handle);

        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return $status != 204;
    }

    public function waitForProcessedHighChart($url)
    {
        while (!$this->isProcessedHighChart($url)) {
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

        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        $content = curl_exec($handle);
        curl_close($handle);

        return $content;
    }

    public function generateDocument($template, $data, $format = 'pdf')
    {
        $data = array(
            'template' => new \CURLFile($template),
            'data' => json_encode($data),
        );

        $handle = curl_init($this->url . '/document/generate.' . $format);

        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        $content = curl_exec($handle);
        curl_close($handle);

        return $content;
    }
}
