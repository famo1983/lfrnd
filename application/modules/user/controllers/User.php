<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class User extends CI_Controller{
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index()
    {
        echo "this is user controller";
    }
    
    public function you_view()
    {
        $this->load->view('user/index');
        
    }
}
