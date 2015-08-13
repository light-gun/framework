<?php

namespace LightGun;

class Server
{

    /**
     * @var Application
     */
    private $lumenApplication;

    /**
     * @param LumenApplicationWrapper $lumenApplication
     *
     * @throws \React\Socket\ConnectionException
     */
    public function __construct(LumenApplicationWrapper $lumenApplication)
    {
        $this->lumenApplication = $lumenApplication;
    }

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

            // TODO: Find a way to run this asynchronously
            $this->lumenApplication->runTerminableMiddleware($lumenResponse);

        };

        $loop = \React\EventLoop\Factory::create();
        $socket = new \React\Socket\Server($loop);
        $http = new \React\Http\Server($socket, $loop);

        $http->on('request', $requestHandler);

        // TODO: depending on "debug level", output running notice
        echo "Server running at http://127.0.0.1:1337\n";

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
        $symReq->query->add($request->getQuery());
        $symReq->request->add($request->getPost());
        $symReq->headers->add($request->getHeaders());

        return $symReq;
    }

}