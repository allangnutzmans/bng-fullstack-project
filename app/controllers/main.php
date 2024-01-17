<?php

namespace bng\Controllers;

use bng\Models\Agents;
use Mpdf\Tag\A;

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

    public function changePasswordForm()
    {
        if(!checkSession()){
            $this->index();
            return;
        }

        $data['user'] = $_SESSION['user'];

        // check for server errors
        if (!empty($_SESSION['validation_errors'])){
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        // check for server errors
        if (!empty($_SESSION['server_error'])){
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('profile_change_password_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
        
    }
    public function changePasswordSubmit()
    {

        if(!checkSession()){
            $this->index();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST'){
            $this->index();
            return;
        }

        if (empty($_POST['text_current_password'])){
            $validation_errors[] = "Please fill the current password";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->changePasswordForm();
        }

        if (empty($_POST['text_new_password'])){
            $validation_errors[] = "Please fill the new password";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->changePasswordForm();
            return;
        }

        if (empty($_POST['text_repeat_new_password'])){
            $validation_errors[] = "Please fill the again new password";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->changePasswordForm();
            return;
        }

        //get input values
        $current_password = $_POST['text_current_password'];
        $new_password = $_POST['text_new_password'];
        $repeat_new_password = $_POST['text_repeat_new_password'];

        if (strlen($current_password) < 6 || strlen($current_password) > 12){
                $validation_errors[] = "Current password should have between 6-12 characters";
                $_SESSION['validation_errors'] = $validation_errors;
                $this->changePasswordForm();
                return;
        }

        if (strlen($new_password) < 6 || strlen($new_password) > 12){
                $validation_errors[] = "New password should have between 6-12 characters";
                $_SESSION['validation_errors'] = $validation_errors;
                $this->changePasswordForm();
                return;
        }

        if (strlen($repeat_new_password) < 6 || strlen($repeat_new_password) > 12){
                $validation_errors[] = "New password should have between 6-12 characters";
                $_SESSION['validation_errors'] = $validation_errors;
                $this->changePasswordForm();
                return;
        }


        //check if all passwords have at least, one upper, one lower and one last digit
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $current_password)){
            $validation_errors[] = "Current password have at least, one upper, one lower and one last digit";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->changePasswordForm();
            return;
        }
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $new_password)){
            $validation_errors[] = "New password have at least, one upper, one lower and one last digit";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->changePasswordForm();
            return;
        }
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $repeat_new_password)){
            $validation_errors[] = "Repeat new password have at least, one upper, one lower and one last digit";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->changePasswordForm();
            return;
        }

        if ($new_password != $repeat_new_password){
            $validation_errors[] = "New password and repeat new password don't match";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->changePasswordForm();
            return;
        }

        //check if current password matches the pass on db
        $model = new Agents();
        $result = $model->checkCurrentPassword($current_password);

        //Check if current pass match to the pass on db
        if (!$result['status']) {
            $_SESSION['server_error'] = 'Wrong current password';
            $this->changePasswordForm();
            return;
        }

        //form data is ok
        $model->updateAgentPassword($new_password);

        //logger
        loggerRegister(getActiveUsername() . " - UPATED the password at the user profile section.");

        //show view
        $data['user'] = $_SESSION['user'];
        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('profile_change_password_success');
        $this->view('footer');
        $this->view('layouts/html_footer');

    }

    public function definePassword($purl = '')
    {
        if (checkSession()) {
            $this->index();
            return;
        }

        if(empty($purl) || strlen($purl) != 20){
            die('Erro nas credenciais de acesso.');
        }

        $model = new Agents();
        $results = $model->checkNewAgentPurl($purl);

        if (!$results['status']){
            die('Erro nas credenciais de acesso.');
        }

        if (!empty($_SESSION['validation_errors'])){
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        $data['purl'] = $purl;
        $data['id'] = $results['id'];

        $this->view('layouts/html_header');
        $this->view('new_agent_define_password', $data);
        $this->view('layouts/html_footer');
    }

    public function definePasswordSubmit()
    {

        if (checkSession()) {
            $this->index();
            return;
        }

        if($_SERVER['REQUEST_METHOD'] != 'POST'){
            $this->index();
            return;
        }


        if(empty($_POST['purl']) || empty($_POST['id']) || strlen($_POST['purl']) != 20){
            $this->index();
            return;

        }

        $id = aes_decrypt($_POST['id']);
        $purl = $_POST['purl'];


        if(!$id){
            $this->index();
            return;
        }

        if (empty($_POST['text_password'])){
            $validation_errors[] = "Please fill the password field";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->definePassword($purl);
        }

        if (empty($_POST['text_repeat_password'])){
            $validation_errors[] = "Please fill the repeat password field";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->definePassword($purl);
        }

        $password = $_POST['text_password'];
        $repeat_password = $_POST['text_repeat_password'];

        if (strlen($password) < 6 || strlen($password) > 12){
            $validation_errors[] = "Password should have between 6-12 characters";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->definePassword($purl);
            return;
        }

        if (strlen($repeat_password) < 6 || strlen($repeat_password) > 12){
            $validation_errors[] = "Repeat password should have between 6-12 characters";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->definePassword($purl);
            return;
        }

        //check regex
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $password)){
            $validation_errors[] = "Password have at least, one upper, one lower and one last digit";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->definePassword($purl);
            return;
        }
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $repeat_password)){
            $validation_errors[] = "Repeat password have at least, one upper, one lower and one last digit";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->definePassword($purl);
            return;
        }

        if ($password != $repeat_password){
            $validation_errors[] = "Password and repeat password don't match";
            $_SESSION['validation_errors'] = $validation_errors;
            $this->definePassword($purl);
            return;
        }

        $model = new Agents();
        $model->newAgentPassword($id, $password);

        //logger
        loggerRegister("Successful password definition for agent ID = {$id}, purl = {$purl}");

        $this->view('layouts/html_header');
        $this->view('reset_password_define_password_success');
        $this->view('layouts/html_footer');
    }

}




















