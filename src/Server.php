<?php

namespace LightGun;

class Server
{

    /**
     * @var LumenApplicationWrapper
     */
    private $lumenApplication;

    /**
     * @var bool
     */
    private $debugging = false;

    /**
     * @var int
     */
    private $port = 1337;

    /**
     * @param LumenApplicationWrapper $lumenApplication
     *
     * @throws \React\Socket\ConnectionException
     */
    public function __construct(LumenApplicationWrapper $lumenApplication)
    {
        $this->lumenApplication = $lumenApplication;
    }

    /**
     *
     */
    public function enableDebugging()
    {
        $this->debugging = true;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @param $port
     *
     * @throws \React\Socket\ConnectionException
     */
    public function listen($port=false)
    {
        if ($port) {
            $this->port = $port;
        }

        /**
         * @param \React\Http\Request $request
         * @param \React\Http\Response $response
         */
        $requestHandler = function ($request, $response) {

            // If debugging is on, track the request start time
            $requestStartTime = ($this->debugging) ? microtime(true) : null;

            $symfonyFormattedRequest = $this->convertRequest($request);

            // Run the Lumen app and get a response
            /** @var \Illuminate\Http\Response $lumenResponse */
            $lumenResponse = $this->lumenApplication->dispatch($symfonyFormattedRequest);

            $lumenResponse->prepare($symfonyFormattedRequest);

            // Build a React response from the symfony response
            $response->writeHead($lumenResponse->getStatusCode(), $lumenResponse->headers->all());
            $response->end($lumenResponse->content());

            if ($this->debugging) {
                echo $this->getLogEntry($request, $lumenResponse, $requestStartTime);
            }

            $response->on('close', function() use ($lumenResponse) {
                $this->lumenApplication->runTerminableMiddleware($lumenResponse);
            });


        };

        $loop = \React\EventLoop\Factory::create();
        $socket = new \React\Socket\Server($loop);
        $http = new \React\Http\Server($socket, $loop);

        $http->on('request', $requestHandler);

        if ($this->debugging) {
            echo "Server running on port ".$this->port.PHP_EOL.PHP_EOL;
        }

        $socket->listen($this->port);
        $loop->run();

    }

    /**
     * @param \React\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function convertRequest($request)
    {
        // Convert the React Request to a Symfony Request
        $symReq = new \Symfony\Component\HttpFoundation\Request();

        // Set Method
        $symReq->setMethod($request->getMethod());

        // Add path
        $symReq->server->set('REQUEST_URI', $request->getPath());
        // TODO: consider appending http_build_query($request->getQuery())

        // Add GET variables
        $symReq->query->add($request->getQuery());

        // Add POST variables
        $symReq->request->add($request->getPost());

        // Add headers
        $symReq->headers->add($request->getHeaders());

        return $symReq;
    }

    /**
     * @param \React\Http\Request       $request
     * @param \Illuminate\Http\Response $lumenResponse
     *
     * @param                           $requestStartTime
     *
     * @return string
     */
    private function getLogEntry($request, $lumenResponse, $requestStartTime)
    {
        $out = ' '.$lumenResponse->getStatusCode().'  | ';

        $totalTime = number_format(((microtime(true) - $requestStartTime)*1000),1).' ms';

        $out .= str_pad($totalTime, 12, ' ', STR_PAD_LEFT);

        $out .= '  |  '.$request->getMethod().'  |  '.$request->getPath().PHP_EOL;

        return $out;
    }

}