<?php
/*
 * Copyright 2012 - NodePing LLC
 * PHP API library for NodePing server monitoring service.
 * http://nodeping.com
 * Overview of the NodePing API can be found at https://nodeping.com/API_Documentation
 * Full reference is at https://nodeping.com/API_Reference
 * Support questions can be sent to support@nodeping.com
 *
 * MIT License
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class NodePingClient {
    // Config
    public $config = array(
        'token'     =>false,
        'customerid'=>false,
        'apiurl'    =>"https://api.nodeping.com/api",
        'apiversion'=>'1'
    );

    public function __construct($setuparray = array()){
        if (!in_array('curl', get_loaded_extensions())) {
            trigger_error("It looks like you do not have curl installed.\n".
                "Curl is required to make HTTP requests using the NodePingClient\n" .
                "library. For install instructions, visit the following page:\n" .
                "http://php.net/manual/en/curl.installation.php",
                E_USER_WARNING
            );
        }
        if($setuparray['token']){
            $this->config['token'] = $setuparray['token'];
        }
        if($setuparray['customerid']){
            $this->config['customerid'] = $setuparray['customerid'];
        }elseif($setuparray['id']){
            $this->config['customerid'] = $setuparray['id'];
        }elseif($setuparray['_id']){
            $this->config['customerid'] = $setuparray['_id'];
        }
        if($setuparray['url']){
            $this->config['apiurl'] = $setuparray['url'];
        }
        if($setuparray['version']){
            $this->config['apiversion'] = $setuparray['version'];
        }
        $this->account = new NodePingResource($this->config, 'accounts');
        $this->contact = new NodePingResource($this->config, 'contacts');
        $this->contactgroup = new NodePingResource($this->config, 'contactgroups');
        $this->schedule = new NodePingResource($this->config, 'schedules');
        $this->check = new NodePingResource($this->config, 'checks');
        $this->result = new NodePingResource($this->config, 'results');
        $this->notification = new NodePingResource($this->config, 'notifications');
    }
}

class NodePingResource {
    private $config;
    private $resource;
    public function __construct($config, $resource){
        $this->config = $config;
        $this->resource = $resource;
    }
    public function get($dataarray=false){
        $httpclient = new NodePingRequest($this->config, $this->resource);
        return $httpclient->call('GET',$dataarray);
    }

    public function put($dataarray=false){
        $httpclient = new NodePingRequest($this->config, $this->resource);
        return $httpclient->call('PUT',$dataarray);
    }

    public function post($dataarray=false){
        $httpclient = new NodePingRequest($this->config, $this->resource);
        return $httpclient->call('POST',$dataarray);
    }

    public function delete($dataarray=false){
        $httpclient = new NodePingRequest($this->config, $this->resource);
        return $httpclient->call('DELETE',$dataarray);
    }

    public function resetpassword($dataarray=false){
        if($this->resource == 'contacts'){
            $httpclient = new NodePingRequest($this->config, $this->resource);
            $dataarray['action'] = 'RESETPASSWORD';
            return $httpclient->call('GET',$dataarray);
        }
        return array('error', 'resetpassword only works on contacts');
    }

    public function current(){
        if($this->resource == 'results'){
            $httpclient = new NodePingRequest($this->config, $this->resource);
            $dataarray['action'] = 'CURRENT';
            return $httpclient->call('GET',$dataarray);
        }
        return array('error', 'current only works on results');
    }
}

class NodePingRequest{
    public $config;
    public $resource;

    public function __construct($config, $resource){
        $this->config = $config;
        $this->resource =  $resource;
    }

    public function call($action='GET',$dataarray=false){
        $query = '';
        if($dataarray && is_array($dataarray)){
            $jsondata = json_encode($dataarray);
            if($this->config['customerid']){
                $queryarray = array('json'=>$jsondata, 'customerid'=>$this->config['customerid']);
            }else{
                $queryarray = array('json'=>$jsondata);
            }
            //$dataarray = array_map('NodePingRequest::jsonize', $dataarray);
            $query = '?'.http_build_query($queryarray);
            //error_log('Query is: '.print_r($query, true));
        }elseif($this->config['customerid']){
            $queryarray = array('customerid'=>$this->config['customerid']);
            $query = '?'.http_build_query($queryarray);
        }
        $url = $this->config['apiurl'].'/'.$this->config['apiversion'].'/'.$this->resource.$query;
        //error_log('URL is: '.print_r($url, true));
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $action);
        curl_setopt($ch, CURLOPT_USERAGENT, 'nodeping-php/0.1');
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['token'] . ":");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Accept-Charset: utf-8'));

        $result = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        //error_log('info: '.print_r($info,true));
        if(!empty($error)){
            curl_close($ch);
            throw new Exception($error.' : '.print_r($info,true));
        }
        curl_close($ch);
        //error_log('result: '.print_r($result,true));
        return json_decode($result, true);
    }
}
