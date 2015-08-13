<?php

namespace LightGun;

use Laravel\Lumen\Application;

class LumenApplicationWrapper extends Application
{

    /**
     * Abstracted terminable middleware for running separate from run() or dispatch()
     *
     * @param $response
     */
    public function runTerminableMiddleware($response)
    {
        if (count($this->middleware) > 0) {
            $this->callTerminableMiddleware($response);
        }
    }

}