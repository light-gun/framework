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
     * @param $port
     *
     * @throws \React\Socket\ConnectionException
     */
    public function listen($port)
    {
        /**
         * @param \React\Http\Request $request
         * @param \React\Http\Response $response
         */
        $requestHandler = function ($request, $response) {

            $symfonyFormattedRequest = $this->convertRequest($request);

            // Run the Lumen app and get a response
            /** @var \Illuminate\Http\Response $lumenResponse */
            $lumenResponse = $this->lumenApplication->dispatch($symfonyFormattedRequest);

            $lumenResponse->prepare($symfonyFormattedRequest);

            // Build a React response from the symfony response
            $response->writeHead($lumenResponse->getStatusCode(), $lumenResponse->headers->all());
            $response->end($lumenResponse->content());

            if ($this->debugging) {
                $this->logRequest($request, $lumenResponse);
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
            echo "Server running at http://127.0.0.1:1337".PHP_EOL.PHP_EOL;
        }

        $socket->listen($port);
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
        $symReq->setMethod($request->getMethod());
        $symReq->server->set('REQUEST_URI', $request->getPath());
        $symReq->query->add($request->getQuery());
        $symReq->request->add($request->getPost());
        $symReq->headers->add($request->getHeaders());

        return $symReq;
    }

    /**
     * @param \React\Http\Request $request
     * @param \Illuminate\Http\Response $lumenResponse
     */
    private function logRequest($request, $lumenResponse)
    {
        echo "Request: ".$request->getMethod().' '.$request->getPath().' -> '.$lumenResponse->getStatusCode().PHP_EOL;
    }

}