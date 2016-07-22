<?php

require(APPPATH . 'vendor/autoload.php');
require(APPPATH . 'third_party/JWT/autoload.php');


/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Auth extends CI_Controller {

    private $ci;
    private $route;
    private $request_type;
    public $JWToken;

    public function __construct() {
        parent::__construct();
        $this->ci = & get_instance();
        $this->route = $this->router->fetch_class() . "/" . $this->router->fetch_method();
        $this->request_type = $this->input->method(TRUE);
        $this->JWToken = new JWToken();
    }

    public function authenticate() {

        $JWToken = new JWToken();
        if ($this->request_type == 'GET') {
            switch ($this->route) {
                case 'user/you_view' :
                    
                    $token = $this->JWToken->issueToken(array('name'=>"fayaz","email"=>"famomca@yahoo.com"));
                    echo $token; exit;
                    break;
                default :
                    echo "hello";
            }
        }
    }

}
