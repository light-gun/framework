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

    /**
     * Get the HTML from the Light Gun welcome screen.
     *
     * @return string
     */
    public function welcome()
    {
        return "<!DOCTYPE html>
            <html>
            <head>
                <title>Light Gun</title>

                <link href=\"//fonts.googleapis.com/css?family=Lato:100\" rel=\"stylesheet\" type=\"text/css\">

                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        width: 100%;
                        height: 100%;
                        color: #B0BEC5;
                        display: table;
                        font-weight: 100;
                        font-family: 'Lato', 'HelveticaNeue-Light', 'Helvetica Neue Light', 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    }

                    .container {
                        text-align: center;
                        display: table-cell;
                        vertical-align: middle;
                    }

                    .content {
                        text-align: center;
                        display: inline-block;
                    }

                    img.logo {
                        margin-top: 40px;
                        width: 130px;
                        height: 130px;
                    }

                    .title {
                        margin-bottom: 40px;
                        font-family: 'Lato', Arial, sans-serif;
                        font-size: 64px;
                        letter-spacing: 15px;
                        color: #A9A9A9;
                    }

                    .quote {
                        font-size: 24px;
                    }
                </style>
            </head>
            <body>
                <div class=\"container\">
                    <div class=\"content\">
                        <img class=\"logo\" src=\"data:image/png;base64,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\"/>
                        <div class=\"title\">LIGHT GUN</div>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

}