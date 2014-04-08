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
            'data' => $chart->toJson(),
            'format' => $format,
        ));
        $output = curl_exec($handle);
        curl_close($handle);

        return $output;
    }
}
