<?php

class TokenAuth extends CI_Controller {

    private $secretKey = 'PMT!@#$';
    public $JWToken;

    public function __construct() {
        parent::__construct();
        $this->JWToken = new JWToken();
    }

    public function deny_access() {


        $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode(array('message' => 'Invalid Token')));
    }

    public function loginRequired() {


        $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode(array('message' => 'Login required.')));
    }

    public function call() {
        echo "call"; exit;
//        $app = $this->app;
//        $currentRoute = $app->request()->getPathInfo();
//
//        if ($currentRoute == '/user/auth/login/' || $currentRoute == '/getToken/' || $app->request->isGet()) {
//            if (strpos($currentRoute, '/user/auth/devices/') !== false) {
//                if (array_key_exists("loggedIN", $_SESSION) && $_SESSION["loggedIN"] == TRUE) {
//                    $this->next->call();
//                } else {
//                    //$this->loginRequired();
//                    $token = explode('/', $currentRoute);
//                    if (!empty($token['5'])) {
//                        $userData = json_decode(base64_decode($token['5']), true);
//                        if (in_array("email", $userData) || !empty($userData["email"])) {
//                            $app->render('user/login.html', array('email' => $userData['email'], 'field' => 'disabled="disabled"'));
//                        } else {
//                            $this->deny_access();
//                        }
//                    } else {
//                        $this->deny_access();
//                    }
//                }
//            } else {
//                $this->next->call();
//            }
//        } else {
//            $tokenAuth = $app->request->headers->get('Authorization');
//            //print_r($this->authenticate($tokenAuth));
//            if (!empty($tokenAuth) && $this->JWToken->authenticate($tokenAuth)) {
//                $this->next->call();
//            } else {
//                //if($currentRoute == '/user/auth/confirm/' || $currentRoute == '/user/auth/resendConfirmation/'){
//                if ($currentRoute == '/user/auth/resendConfirmation/') {
//                    $this->next->call();
//                } else {
//                    $this->deny_access();
//                }
//            }
//        }
    }

}
