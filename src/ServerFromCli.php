<?php

namespace LightGun;

class ServerFromCli
{
    /**
     * @var array
     */
    private static $args = [
        'port' => [
            'prefix'       => 'p',
            'longPrefix'   => 'port',
            'description'  => 'Port to serve on',
            'defaultValue' => 1337,
        ],
        'debug' => [
            'prefix'      => 'd',
            'longPrefix'  => 'debug',
            'description' => 'Output information for debugging',
            'noValue'     => true,
        ],
        'help' => [
            'longPrefix'  => 'help',
            'description' => 'Prints a usage statement',
            'noValue'     => true,
        ],
    ];

    /**
     * @param LumenApplicationWrapper $lumenApp
     *
     * @return Server
     * @throws \Exception
     */
    public static function create(LumenApplicationWrapper $lumenApp)
    {
        $climate = new \League\CLImate\CLImate;

        // Register and parse the arguments
        $climate->arguments->add(self::$args);
        $climate->arguments->parse();

        // If the help command is passed, output usage documentation and die
        if ($climate->arguments->defined('help')) {
            $climate->usage();
            die();
        }

        // Create the server
        $server = new Server($lumenApp);

        // Enable debugging if passed as an argument
        if ($climate->arguments->defined('debug')) {
            $server->enableDebugging();
        }

        // Set the port
        $server->setPort($climate->arguments->get('port'));

        return $server;
    }

}