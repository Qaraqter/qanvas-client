<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use QanvasClient\Client;
use QanvasClient\Exceptions\CurlTimedOutException;

class ClientTest extends TestCase
{
    public function testADelayedCallWillThrowACurlTimedOutException()
    {
        $this->expectException(CurlTimedOutException::class);

        $client = new Client('', '', '');
        $client->setSecondsLeftBeforeTimeout(1);
        $client->isProcessedHighChart('https://postman-echo.com/delay/2');
    }

    public function testMultipleDelayedCallsWillThrowACurlTimedOutException()
    {
        $this->expectException(CurlTimedOutException::class);

        $client = new Client('', '', '');
        $client->setSecondsLeftBeforeTimeout(3);

        $client->isProcessedHighChart('https://postman-echo.com/delay/2');
        $client->isProcessedHighChart('https://postman-echo.com/delay/3');
    }
}
