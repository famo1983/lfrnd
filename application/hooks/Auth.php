<?php
require(APPPATH.'vendor/autoload.php');
require(APPPATH.'third_party/JWT/autoload.php');


/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Auth extends CI_Controller
{
    private $ci;
    public function __construct() {
        parent::__construct();
         $this->ci =& get_instance();
    }
    
    public function authenticate()
    {
        echo "hello authenticate";
        echo $this->input->method(TRUE);// Outputs: post
        $JWToken = new JWToken();
        echo $this->ci->router->method."::";
    }
}
