<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;

class main extends BaseController
{
    public function index()
    {
        if(!checkSession()){
            $this->loginForm();
            return;
        }


        $data['user'] = $_SESSION['user'];

        //loadView
        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('homepage', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    //================
    //LOGIN
    //===============
    public function loginForm()
    {
        // Já existe uma sessão -> volta pro index
        if (checkSession()){
            $this->index();
            return;
        }



        $data = [];
        // checkn if there are errors after the login submit
        if (!empty($_SESSION['validation_errors'])){
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        // check if there was an invalid login
        if (!empty($_SESSION['server_error'])){
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        //displayLoginForm
        $this->view('layouts/html_header');
        $this->view('login_frm', $data);
        $this->view('layouts/html_footer');

    }

    //activated by the action button on the form
    public function loginSubmit()
    {
        if (checkSession()){
            $this->index();
            return;
        }

        if($_SERVER['REQUEST_METHOD'] != 'POST'){
            $this->index();
            return;
        }

        //formValidation
        $validation_errors = [];
        if(empty($_POST['text_username']) || empty($_POST['text_password'])){
            $validation_errors[] = "Please fill the username and password";
        }

        //get form data
        $username =  $_POST['text_username'];
        $password = $_POST['text_password'];

        //filter validade email
        if(!filter_var($username, FILTER_VALIDATE_EMAIL)){
            $validation_errors[] = "Username has to be a valid email";
        }
        //email entre 5 - 50 chars
        if (strlen($username) < 5 || strlen($username) > 50){
            $validation_errors[] = "Username should have between 5 and 50 charcters";
        }

        if (strlen($password) < 6 || strlen($password) > 12){
            $validation_errors[] = "Password should have between 6 and 12 charcters";
        }

        //check if there is validation errors
        if(!empty($validation_errors)){
            $_SESSION['validation_errors'] = $validation_errors;
            $this->loginForm();
            return;
        }

        $model = new Agents();
        $result = $model->checkLogin($username, $password);

        if (!$result['status']) {

            //logger
            loggerRegister("$username - Invalid login", 'error');

            //invalid login
            $_SESSION['server_error'] = 'Invalid login.';
            $this->loginForm();
            return;
        }

        loggerRegister($username . " - logged in successfully.");

        //load user information
        $results = $model->getUserData($username);

        //add user to the session
        $_SESSION['user'] = $results['data'];

        //update the last login
        $model->setUserLastLogin($_SESSION['user']->id);

        //go to main page
        $this->index();
    }

    public function logout()
    {
        //disable direct acess to the logout
        if(!checkSession()){
            $this->index();
            return;
        }

        loggerRegister($_SESSION['user']->name . "  logged out.");
        unset($_SESSION['user']);
        $this->index();
    }


}
