<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;

class Agent extends BaseController
{
    public function myClients()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'agent') {
            header("Location: index.php");
        }

            $id_agent = $_SESSION['user']->id;
            $model = new Agents();
            $results = $model->getAgentClients($id_agent);

            $data['user'] = $_SESSION['user'];
            $data['clients'] = $results['data'];


            $this->view('layouts/html_header');
            $this->view('navbar', $data);
            $this->view('agent_clients', $data);
            $this->view('footer');
            $this->view('layouts/html_footer');

    }

    public function newClientForm()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'agent') {
            header("Location: index.php");
        }

        $data['user'] = $_SESSION['user'];
        $data['flatpickr'] = true;

        //check if there are validation errors
        if (!empty($_SESSION['validation_errors'])){
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        //check is there a server error
        if (!empty($_SESSION['server_error'])){
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('insert_client_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');

    }

    public function newClientSubmit()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'agent' || $_SERVER['REQUEST_METHOD'] != 'POST') {
            header("Location: index.php");
        }

        //formValidation
        $validation_errors = [];

        //text_name
        if(empty($_POST['text_name'])){
            $validation_errors[] = 'Name field is required.';
        } else {
            if (strlen($_POST['text_name']) < 3 || strlen($_POST['text_name']) > 50){
                $validation_errors[] = 'Name field should have between 3 - 50 characters.';
            }
        }
        //gender
        if(empty($_POST['radio_gender'])){
            $validation_errors[] = 'Gender field is required.';
        }
        //text_birthdate
        if(empty($_POST['text_birthdate'])){
            $validation_errors[] = 'Birthdate field is required.';
        } else {
            //check if birthdate is valid and older than today
            $birthdate = \DateTime::createFromFormat('d-m-Y', $_POST['text_birthdate']);
            if (!$birthdate){
                $validation_errors[] = "Wrong birthdate format";
            } else {
                $today = new \DateTime();
                if ($birthdate >= $today)
                $validation_errors[] = "Birthdate has to be at least a day before today.";
            }
        }
        //filter email
        if (empty($_POST['text_email'])){
            $validation_errors[] = 'Email field is required.';
        } else {
            if (!filter_var($_POST['text_email'], FILTER_VALIDATE_EMAIL)){
                $validation_errors[] = "Invalid email address.";
            }
        }

        //phone
        if (empty($_POST['text_phone'])){
            $validation_errors[] = 'Phone field is required.';
        } else {
            if (!preg_match("/^9{1}\d{8}$/", $_POST['text_phone'])){
                $validation_errors[] = "Phone number should start with 9 an have 9 a total of digits.";
            }
        }

        //check if there is any validation error to return
        if (!empty($validation_errors)){
            $_SESSION['validation_errors'] = $validation_errors;
            $this->newClientForm();
            return;
        }

        $model = new Agents();
        $results = $model->checkIfClientExists($_POST);

        if ($results['status']){
            //person already exists
            $_SESSION['server_error'] = "This name it's already registered in another client.";
            $this->newClientForm();
            return;
        }

        //ADD new client
        $model->addNewClientToDB($_POST);

        //loger
        loggerRegister(getActiveUsername() . " added a new client " .  $_POST['text_name'] ." | " . $_POST['text_email']);

        //retrurn to clients page
        $this->myClients();

    }

    public function editClient($id){

        if (!checkSession() || $_SESSION['user']->profile != 'agent'){
            header('Location:index.php');
        }

         $client_id = aes_decrypt($id);

        if (!$client_id){
            header('Location:index.php');
        }

        //loads all the client data
        $model = new Agents();
        $results = $model->getClientData($client_id);

        //check if data exists
        if($results['status'] == 'error'){
            header('Location:index.php');
        }

        $data['client'] =  $results['data'];

        //display edit client form
        $data['user'] = $_SESSION['user'];
        $data['flatpickr'] = true;

        //check for validation errors
        if (!empty($_SESSION['validation_errors'])){
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        //check is there a server error
        if (!empty($_SESSION['server_error'])){
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }
        var_dump($data);
        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('edit_client_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }


    public function editClientSubmit()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'agent' || $_SERVER['REQUEST_METHOD'] != 'POST') {
            header("Location: index.php");
        }

        //formValidation
        $validation_errors = [];

        //text_name
        if(empty($_POST['text_name'])){
            $validation_errors[] = 'Name field is required.';
        } else {
            if (strlen($_POST['text_name']) < 3 || strlen($_POST['text_name']) > 50){
                $validation_errors[] = 'Name field should have between 3 - 50 characters.';
            }
        }
        //gender
        if(empty($_POST['radio_gender'])){
            $validation_errors[] = 'Gender field is required.';
        }
        //text_birthdate
        if(empty($_POST['text_birthdate'])){
            $validation_errors[] = 'Birthdate field is required.';
        } else {
            //check if birthdate is valid and older than today
            $birthdate = \DateTime::createFromFormat('Y-m-d', $_POST['text_birthdate']);
            if (!$birthdate){
                $validation_errors[] = "Wrong birthdate format";
            } else {
                $today = new \DateTime();
                if ($birthdate >= $today)
                    $validation_errors[] = "Birthdate has to be at least a day before today.";
            }
        }
        //filter email
        if (empty($_POST['text_email'])){
            $validation_errors[] = 'Email field is required.';
        } else {
            if (!filter_var($_POST['text_email'], FILTER_VALIDATE_EMAIL)){
                $validation_errors[] = "Invalid email address.";
            }
        }

        //phone
        if (empty($_POST['text_phone'])){
            $validation_errors[] = 'Phone field is required.';
        } else {
            if (!preg_match("/^9{1}\d{8}$/", $_POST['text_phone'])){
                $validation_errors[] = "Phone number should start with 9 an have 9 a total of digits.";
            }
        }

        //check if id_client is present in POST and is valid
        if(empty($_POST['id_client'])) {
            header("Location:index.php");
        }
        $id_client = aes_decrypt($_POST['id_client']);
        if (!$id_client) {
            header("Location:index.php");
        }

        if (!empty($validation_errors)){
            $_SESSION['validation_errors'] = $validation_errors;
            $this->editClient(aes_encrypt($id_client));
            return;
        }

        $model = new Agents();
        $results = $model->checkOtherClientWithSameName($id_client, $_POST['text_name']);

        if ($results['status']){
            $_SESSION['server_error'] = "There is already another client with the same name";
            $this->editClient(aes_encrypt($id_client));
            return;
        }

        //update client data
        $model->updateClientData($id_client, $_POST);

        //Logs
        loggerRegister(getActiveUsername() . " - updated the data of the client ID: " . $id_client);

        $this->myClients();
    }

    public function deleteClient($id){
        if(!checkSession() || $_SESSION['user']->profile != 'agent') {
            header("Location: index.php");
        }

        //check if the id is valid
        $id_client = aes_decrypt($id);
        if(!$id_client){
            //client dont exists
            header('Location:index.php');
        }

        $model = new Agents();
        $results = $model->getClientData($id_client);
        //validacao dos dados vazios

        //display the view
        $data['user'] = $_SESSION['user'];
        $data['client'] = $results['data'];

        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('delete_client_confirmation', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function deleteClientConfirm($id)
    {
        if(!checkSession() || $_SESSION['user']->profile != 'agent') {
            header("Location: index.php");
        }

        //check if id is valid
        $id_client = aes_decrypt($id);
        if(!$id_client){
            //client dont exists
            header('Location:index.php');
        }

        //load model to delete
        $model = new Agents();
        $model->deleteClient($id_client);

        //Logs
        loggerRegister(getActiveUsername() . " - deleted the data client ID: " . $id_client);

        $this->myClients();
    }

    public function uploadFileForm()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'agent') {
            header("Location: index.php");
        }

        $data['user'] = $_SESSION['user'];

        //check is there a server error
        if (!empty($_SESSION['server_error'])){
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        if (!empty($_SESSION['report'])){
            $data['report'] = $_SESSION['report'];
            unset($_SESSION['report']);
        }

        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('upload_file_with_clients_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function uploadFileSubmit(){

        if(!checkSession() || $_SESSION['user']->profile != 'agent') {
            header("Location: index.php");
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST' ) {
            header("Location: index.php");
        }

        if(empty($_FILES) || empty($_FILES['clients_file']['name'])){
            $_SESSION['server_error'] = "Please load a CSV or XSLX file";
            $this->uploadFileForm();
            return;
        }

        //check if ext is valid
        //clients file -> nome do input
        $valid_extensions = ['xlsx', 'csv'];
        $tmp = explode('.', $_FILES['clients_file']['name']);
        $extension = end($tmp);
        if (!in_array($extension, $valid_extensions)){

            //logger
            loggerRegister(getActiveUsername() . " - attempt to load invalid file: " . $_FILES['clients_file']['name'], "error");

            $_SESSION['server_error'] = 'Please provide a CSV or XSLX file.';
            $this->uploadFileForm();
            return;
        }

        if ($_FILES['clients_file']['size'] > 2000000) {

            //logger
            loggerRegister(getActiveUsername() . " - attempt to load invalid file: " . $_FILES['clients_file']['name'] . " maximum size exceeded", "error");

            $_SESSION['server_error'] = "File size exceeded. Max allowed: 2MB.";
            $this->uploadFileForm();
            return;
        }

        $file_path = __DIR__ . '/../uploads/data_'. time() . '.' . $extension;
        if (move_uploaded_file($_FILES['clients_file']['tmp_name'], $file_path)){

            $result = $this->validateHeader($file_path);
            if ($result) {

                $this->uploadFileToDatabase($file_path);

            } else {
                //header not ok

                //
                loggerRegister(getActiveUsername() . " - attempt to load invalid file: " . $_FILES['clients_file']['name'] . ", with a  mismatching header", "error");

                $_SESSION['server_error'] = 'Mismatching header.';
                $this->uploadFileForm();
                return;

            }

        } else {
            //logger
            loggerRegister(getActiveUsername() . " Unexpected error occurred while loading the file: " . $_FILES['clients_file']['name'], "error");


            $_SESSION['server_error'] = 'Unexpected error occurred. Try again.';
            $this->uploadFileForm();
            return;
        }

    }

    private function validateHeader($file_path)
    {
        $data = [];
        $file_info = pathinfo($file_path);

        if ($file_info['extension'] == 'csv'){

            //open csv file
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(';');
            $reader->setEnclosure('');
            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray()[0];
        }

        elseif ($file_info['extension'] == 'xlsx'){
            //open xslx file
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray()[0];
        }

        //cheeck if header content is valid
        $valid_header = 'name,gender,birthdate,email,phone,interests';
        return implode(',', $data) == $valid_header ? true : false;


    }

    private function uploadFileToDatabase($file_path)
    {
        $data = [];
        $file_info = pathinfo($file_path);

        if ($file_info['extension'] == 'csv') {

            //open csv file
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(';');
            $reader->setEnclosure('');
            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray();
        } elseif ($file_info['extension'] == 'xlsx') {
            //open xslx file
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray();
        }

        $model = new Agents();

        $report = [
            'total' => 0,
            'total_loaded' => 0,
            'total_unloaded'=> 0
        ];

        array_shift($data); // retira o primeiro elemento (no caso, o array do array)


        foreach ($data as $client) {

            //check if row contains data or not
            if (empty($client[0])) continue;

            $report['total']++;

            $exists = $model->checkIfClientExists(['text_name' => $client[0]]);

            if (!$exists['status']){
                $post_data = [
                    'text_name' => $client[0],
                    'radio_gender' => $client[1],
                    'text_birthdate' => $client[2],
                    'text_email' => $client[3],
                    'text_phone' => $client[4],
                    'text_interests' => $client[5]
                ];

                $model->addNewClientToDB($post_data);

                $report['total_loaded']++;
            } else {

                $report['total_unloaded']++;

            }

        }

        //logger
        loggerRegister(getActiveUsername() . " - file loaded: " . $_FILES['clients_file']['name']);
        loggerRegister(getActiveUsername() . " - report: " . json_encode($report));


        $report['filename'] = $_FILES['clients_file']['name'];
        $_SESSION['report'] = $report;

        $this->uploadFileForm();

    }

    public function exportClientsXLSX()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'agent') {
            header("Location: index.php");
        }

        //getAllClients
        $model = new Agents();
        $results = $model->getAgentClients($_SESSION['user']->id);

        //header
        $data[] = ['name', 'gender', 'birthdate', 'email', 'phone', 'interests', 'created_at', 'updated_at'];

        foreach ($results['data'] as $client){

            unset($client->id);

            $data[] = (array)$client;

        }

        //store data into XSLX
        $filename = "output" . time() . ".xlsx";
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'data');
        $spreadsheet->addSheet($worksheet);
        $worksheet->fromArray($data);


        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($filename) . '"');
        $writer->save('php://output');

        loggerRegister(getActiveUsername() . " - downloaded the list of clients to: " . $filename . " | total: " .
        count($data) -1 . " registers");


    }


}