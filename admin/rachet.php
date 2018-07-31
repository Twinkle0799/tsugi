<?php

/** To run

   php rachet.php

   In client:

   // Then some JavaScript in the browser:
   var conn = new WebSocket('ws://localhost:2021/echo');
   conn.onmessage = function(e) { console.log(e.data); };
   conn.onopen = function(e) { conn.send('Hello Me!'); };

   https://github.com/ratchetphp/Ratchet
   https://github.com/cboden/Ratchet-examples
   http://socketo.me/docs/install

*/

require_once "../config.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Tsugi\Util\U;

/**
 * chat.php
 * Send any incoming messages to all connected clients (except sender)
 */
class MyNotify implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // https://github.com/ratchetphp/Ratchet/issues/604
        // https://stackoverflow.com/questions/22761900/access-extra-parameters-in-ratchet-web-socket-requests
        $querystring = $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring,$queryarray);
        if ( U::get($queryarray,'token') != 'xyzzy' ) {
            error_log('Not authorized\r\n');
            return;
        }
        $room = U::get($queryarray,'room');
        if (! is_numeric($room) ) $room = null;
        $conn->room = $room;
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
echo("FR=".$from->room." CL=".$client->room."\r\n");
            if ($from != $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

if ( isset($CFG->websocket_port) ) {
    // Run the server application through the WebSocket protocol on port 2021
    $app = new Ratchet\App('localhost', $CFG->websocket_port);
    $app->route('/notify', new MyNotify);
    echo("Websocket server started on port $CFG->websocket_port\r\n");
    $app->run();
} else {
    echo("Error: CFG->websocket_port is not set, websocket server not started \r\n");
}
