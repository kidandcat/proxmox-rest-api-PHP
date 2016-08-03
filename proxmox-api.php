<?php

/**
 *  @version 1.0
 *
 *  @author Jairo Caro-Accino Viciana <kidandcat@gmail.com>
 */
 
namespace Netelip\vpsBundle\Utilities;

//DEBUG ONLY  DEBUG ONLY  DEBUG ONLY  DEBUG ONLY
//DEBUG ONLY  DEBUG ONLY  DEBUG ONLY  DEBUG ONLY
require 'vendor/autoload.php';
//DEBUG ONLY  DEBUG ONLY  DEBUG ONLY  DEBUG ONLY
//DEBUG ONLY  DEBUG ONLY  DEBUG ONLY  DEBUG ONLY

///////////////////////////////////////////////////////////////
//                       Composer                            //
//                                                           //
//    {                                                      //
//       "require": {                                        //
//           "guzzlehttp/guzzle": "~6.0"                     //
//       }                                                   //
//    }                                                      //
//                                                           //
///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////
//                       Use example                         //
//                                                           //
//   $api = new vpsApi();                                    //
//                                                           //
//   $result = $api->userInfo(['username' => 'jairo']);      //
//   echo print_r($result, 1)."\n";                          //
//                                                           //
///////////////////////////////////////////////////////////////

class vpsApi
{
    private $ERROR_MODE = 'return'; // return | exception
    private $url = 'http://127.0.0.1:8000/';
    private $client;

    public function __construct($url = null, $errorMode = null)
    {
        $this->client = new \GuzzleHttp\Client();
        if ($errorMode) {
            $this->ERROR_MODE = $errorMode;
        }
        if ($url) {
            $this->$url = $url;
        }

        return true;
    }

    ///////////////
    //  Cluster  //
    ///////////////
    public function clusterStatus()
    {
        $res = $this->client->request('REPORT', $this->url.'cluster');

        return $this->response($res);
    }

    public function destroyMachinesList()
    {
        $res = $this->client->request('GET', $this->url.'cluster');

        return $this->response($res);
    }

    public function nextFreeID()
    {
        $res = $this->client->request('GET', $this->url.'cluster/nextid');

        return $this->response($res);
    }

    public function destroyMachinesDESTROY()
    {
        $res = $this->client->request('DELETE', $this->url.'cluster');

        return $this->response($res);
    }

    ///////////////
    //  Nodes    //
    ///////////////
    public function nodeStatus()
    {
        $res = $this->client->request('REPORT', $this->url.'node');

        return $this->response($res);
    }

    /////////////////
    //  Templates  //
    /////////////////
    public function templateList()
    {
        $res = $this->client->request('GET', $this->url.'template');

        return $this->response($res);
    }

    ///////////////
    //  Users    //
    ///////////////
    public function userInfo($params)
    {
        $ok = $this->checkParams($params, ['username']);
        if ($ok != 'OK') {
            return $ok;
        }

        $res = $this->client->request('GET', $this->url.'user'.((!empty($params['username'])) ? '?username='.$params['username'] : ''));

        return $this->response($res);
    }

    public function userCreate($params)
    {
        $ok = $this->checkParams($params, ['username', 'password']);
        if ($ok != 'OK') {
            return $ok;
        }

        $res = $this->client->request('POST', $this->url.'user', [
          'json' => $params,
        ]);

        return $this->response($res);
    }

    public function userDelete($params)
    {
        $ok = $this->checkParams($params, ['username']);
        if ($ok != 'OK') {
            return $ok;
        }

        $res = $this->client->request('DELETE', $this->url.'user', [
          'json' => $params,
        ]);

        return $this->response($res);
    }

    public function userModify($params)
    {
        $ok = $this->checkParams($params, ['username', 'password']);
        if ($ok != 'OK') {
            return $ok;
        }

        $res = $this->client->request('PUT', $this->url.'user', [
          'json' => $params,
        ]);

        return $this->response($res);
    }

    /////////////////
    //  container  //
    /////////////////
    public function containerCreate($params)
    {
        $ok = $this->checkParams($params, ['template']);
        if ($ok != 'OK') {
            return $ok;
        }

        $res = $this->client->request('POST', $this->url.'container', [
          'json' => $params,
        ]);

        return $this->response($res);
    }

    public function containerDelete($params)
    {
        $ok = $this->checkParams($params, ['id']);
        if ($ok != 'OK') {
            return $ok;
        }

        $res = $this->client->request('DELETE', $this->url.'container', [
          'json' => $params,
        ]);

        return $this->response($res);
    }

    public function containerInfo($params)
    {
        $ok = $this->checkParams($params, []);
        if ($ok != 'OK') {
            return $ok;
        }

        $res = $this->client->request('GET', $this->url.'container', [
          'query' => $params,
        ]);

        return $this->response($res);
    }

    /////////////////
    //    utils    //
    /////////////////
    public function utilsBrowserTerminal($params)
    {
        $ok = $this->checkParams($params, ['id']);
        if ($ok != 'OK') {
            return $ok;
        }

        $auth = $this->authetincationTicket();

        $res = $this->client->request('GET', $this->url.'utils/vnc', [
          'query' => $params,
        ]);

        $response = $this->response($res);

        $response->header = ['CSRFPreventionToken' => $auth->CSRF];
        $response->cookie = ['PVEAuthCookie' => $auth->ticket];

        return $response;
    }

    public function utilsGraphicsData($params)
    {
        $ok = $this->checkParams($params, ['id']);
        if ($ok != 'OK') {
            return $ok;
        }

        $res = $this->client->request('GET', $this->url.'utils/graphic', [
          'query' => $params,
        ]);

        return $this->response($res);
    }

    ///////////////
    //  Private  //
    ///////////////
    private function authetincationTicket()
    {
        $res = $this->client->request('GET', $this->url.'utils/ticket');

        return $this->response($res);
    }

    private function response($res)
    {
        if ($res->getStatusCode() == 200) {
            return json_decode($res->getBody());
        } else {
            return $res->statusCode();
        }
    }

    private function checkParams($params, $expected)
    {
        $msg = '';
        $error = false;
        if (!is_array($params)) {
            $error = true;
            $msg = 'Parameters must be passed as an associative Array';
        } else {
            foreach ($expected as $par) {
                if (!array_key_exists($par, $params)) {
                    $error = true;
                    $msg = debug_backtrace()[1]['function'].": Expected parameter $par";
                }
            }
        }

        if ($error && $this->ERROR_MODE == 'return') {
            return $msg;
        }
        if ($error && $this->ERROR_MODE == 'exception') {
            throw new \Exception($msg);
        }

        return 'OK';
    }
}
