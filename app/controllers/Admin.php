<?php

namespace bng\Controllers;

use bng\Models\AdminModel;
use bng\System\SendEmail;
use Mpdf\Mpdf;

class Admin extends BaseController {

    public function allClients()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'admin') {
            header("Location:index.php");
        }

        $model = new AdminModel();
        $results = $model->getAllClients();

        $data['user'] = $_SESSION['user'];
        $data['clients'] = $results->results;


        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('global_clients', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function exportClientsXLSX()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'admin') {
            header("Location: index.php");
        }

        //getAllClients
        $model = new AdminModel();
        $results = $model->getAllClients();
        $results = $results->results;

        //header
        $data[] = ['name', 'gender', 'birthdate', 'email', 'phone', 'interests','created_at', 'agent'];

        foreach ($results as $client){

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

    public function stats()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'admin') {
            header("Location: index.php");
        }

        $model = new AdminModel();
        $results = $model->getAgentClientsStats();

        $data['user'] = $_SESSION['user'];
        $data['agents'] = $results;

        //prepare data to send to graph
        $data['chart_labels'] = [];
        $data['chart_totals'] = [];
        if (count($data['agents']) != 0){
            $label_tmp = [];
            $totals_tmp = [];
            foreach ($data['agents'] as $agent){
                $labels_tmp[] = $agent->agente;
                $totals_tmp[] = $agent->total_clientes;
            }

            $data['chart_labels'] = '"'. implode( '", "', $labels_tmp) . '"';
            $data['chart_totals'] = implode(',', $totals_tmp);
            $data['apexcharts'] = true;
        }
        $data['flatpickr'] = true;
        $data['global_stats'] = $model->getGlobalStats();


        $this->view('layouts/html_header',$data);
        $this->view('navbar', $data);
        $this->view('stats', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }
    public function createPdfReport()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'admin') {
            header("Location: index.php");
        }

        //logger
        loggerRegister(getActiveUsername() . " - visualized the PDF with the stats report.");

        //get stats from the model
        $model = new AdminModel();
        $agents = $model->getAgentClientsStats();
        $global_stats = $model->getGlobalStats();

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'p'
        ]);

        // set starting coordinates
        $x = 50;    // horizontal
        $y = 50;    // vertical
        $html = "";

        // logo and app name
        $html .= '<div style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px;">';
        $html .= '<img src="assets/images/logo_32.png">';
        $html .= '</div>';
        $html .= '<h2 style="position: absolute; left: ' . ($x + 50) . 'px; top: ' . ($y - 10) . 'px;">' . APP_NAME . '</h2>';

        // separator
        $y += 50;
        $html .= '<div style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px; width: 700px; height: 1px; background-color: rgb(200,200,200);"></div>';

        // report title
        $y += 20;
        $html .= '<h3 style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px; width: 700px; text-align: center;">REPORT DE DADOS DE ' . date('d-m-Y') . '</h4>';

        // -----------------------------------------------------------
        // table agents and totals
        $y += 50;

        $html .= '
        <div style="position: absolute; left: ' . ($x + 90) . 'px; top: ' . $y . 'px; width: 500px;">
            <table style="border: 1px solid black; border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 60%; border: 1px solid black; text-align: left;">Agente</th>
                        <th style="width: 40%; border: 1px solid black;">N.º de Clientes</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($agents as $agent) {
            $html .=
                '<tr style="border: 1px solid black;">
                <td style="border: 1px solid black;">' . $agent->agente . '</td>
                <td style="text-align: center;">' . $agent->total_clientes . '</td>
            </tr>';
            $y += 25;
        }

        $html .= '
        </tbody>
        </table>
        </div>';

        // -----------------------------------------------------------
        // table globals
        $y += 50;

        $html .= '
        <div style="position: absolute; left: ' . ($x + 90) . 'px; top: ' . $y . 'px; width: 500px;">
            <table style="border: 1px solid black; border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 60%; border: 1px solid black; text-align: left;">Item</th>
                        <th style="width: 40%; border: 1px solid black;">Valor</th>
                    </tr>
                </thead>
                <tbody>';

        $html .= '<tr><td>Total agentes:</td><td style="text-align: right;">' . $global_stats['total_agents']->value . '</td></tr>';
        $html .= '<tr><td>Total clientes:</td><td style="text-align: right;">' . $global_stats['total_clients']->value . '</td></tr>';
        $html .= '<tr><td>Total clientes removidos:</td><td style="text-align: right;">' . $global_stats['total_deleted_clients']->value . '</td></tr>';
        $html .= '<tr><td>Média de clientes por agente:</td><td style="text-align: right;">' . sprintf("%.2f", $global_stats['average_clients_per_agent']->value) . '</td></tr>';

        if (empty($global_stats['younger_client']->value)) {
            $html .= '<tr><td>Idade do cliente mais novo:</td><td style="text-align: right;">-</td></tr>';
        } else {
            $html .= '<tr><td>Idade do cliente mais novo:</td><td style="text-align: right;">' . $global_stats['younger_client']->value . ' anos.</td></tr>';
        }
        if (empty($global_stats['oldest_client']->value)) {
            $html .= '<tr><td>Idade do cliente mais velho:</td><td style="text-align: right;">-</td></tr>';
        } else {
            $html .= '<tr><td>Idade do cliente mais velho:</td><td style="text-align: right;">' . $global_stats['oldest_client']->value . ' anos.</td></tr>';
        }

        $html .= '<tr><td>Percentagem de homens:</td><td style="text-align: right;">' . $global_stats['percentage_males']->value . ' %</td></tr>';
        $html .= '<tr><td>Percentagem de mulheres:</td><td style="text-align: right;">' . $global_stats['percentage_females']->value . ' %</td></tr>';

        $html .= '
                </tbody>
            </table>
        </div>';

        // -----------------------------------------------------------

        $pdf->WriteHTML($html);

        $pdf->Output();
    }

    public function agentsManagment()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'admin') {
            header("Location: index.php");
        }

        $model = new AdminModel();
        $results = $model->getAgentsForManagement();

        $data['agents'] = $results->results;
        $data['user'] = $_SESSION['user'];

        $this->view('layouts/html_header',$data);
        $this->view('navbar', $data);
        $this->view('agents_management', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');

    }

    public function newAgentForm()
    {
        if(!checkSession() || $_SESSION['user']->profile != 'admin') {
            header("Location: index.php");
        }

        if (!empty($_SESSION['validation_errors'])){
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        //check is there a server error
        if (!empty($_SESSION['server_error'])){
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $data['user'] = $_SESSION['user'];

        $this->view('layouts/html_header',$data);
        $this->view('navbar', $data);
        $this->view('agents_add_new_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');


    }

    public function newAgentSubmit()
    {
        if (!checkSession() || $_SESSION['user']->profile != 'admin' || $_SERVER['REQUEST_METHOD'] != 'POST') {
            header("Location: index.php");
        }

        //formValidation
        $validation_errors = [];

        //text_name
        if (empty($_POST['text_name']) || !filter_var($_POST['text_name'], FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Name field should be a valid email.";
        }
        //gender
        $valid_profile = ['agent', 'admin'];
        if (empty($_POST['select_profile']) || !in_array($_POST['select_profile'], $valid_profile)) {
            $validation_errors[] = "Invalid selected profile";
        }

        //check if there is any validation error to return
        if (!empty($validation_errors)) {
            $_SESSION['validation_errors'] = $validation_errors;
            $this->newAgentForm();
            return;
        }

        // Check if there is an agent w/ same name
        $model = new AdminModel();
        $results = $model->checkIfAgentExists($_POST['text_name']);

        if ($results) {
            //person already exists
            $_SESSION['server_error'] = "User it's already registered.";
            $this->newAgentForm();
            return;
        }

        //add agent to the db
        $results = $model->addNewAgent($_POST);

        if ($results['status'] == 'error'){

            //logger
            loggerRegister(getActiveUsername() . " - an error occurred during the creation of the new agent");
            header('location:index.php');
        }

        $url = explode('?', $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        $url = $url[0] . '?ct=main&mt=definePassword&purl=' . $results['purl'];
        $email = new SendEmail();
        $data = [
            'to' => $_POST['text_name'],
            'link' => $url
        ];

        $results = $email->sendEmail(APP_NAME . ': Finish registration', 'emailBodyNewAgent', $data);

        if ($results['status'] == 'error'){

            //logger
            loggerRegister(getActiveUsername() . " - Unable to send the email for agent registration:" . $_POST['text_name']);
            die($results['message']);

        }

        //logger
        loggerRegister(getActiveUsername() . " - Email successfully sent! Email: " . $_POST['text_name']);

        $data['user'] = $_SESSION['user'];
        $data['email'] = $_POST['text_name'];

        $this->view('layouts/html_header',$data);
        $this->view('navbar', $data);
        $this->view('agents_email_sent', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function editAgent($id)
    {
        if (!checkSession() || $_SESSION['user']->profile != 'admin'){
            header('Location:index.php');
        }

        $id = aes_decrypt($id);

        if (!$id){
            header('Location:index.php');
        }

        //loads all the client data
        $model = new AdminModel();
        $results = $model->getAgentdata($id);

        //display edit client form
        $data['user'] = $_SESSION['user'];
        $data['agent'] =  $results;
        //$data['flatpickr'] = true;

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

        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('agents_edit_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function editAgentSubmit()
    {
        // check if session has a user with admin profile
        if (!checkSession() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if there was a post
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: index.php');
        }

        // check if id is present and valid
        if (empty($_POST['id'])) {
            header('Location: index.php');
        }

        $id = aes_decrypt($_POST['id']);
        if (!$id) {
            header('Location: index.php');
        }

        // form validation
        $validation_error = null;

        // check if agent is a valid email
        if (empty($_POST['text_name']) || !filter_var($_POST['text_name'], FILTER_VALIDATE_EMAIL)) {
            $validation_error = "Agent name should be a valid email.";
        }

        // check if profile is valid
        $valid_profiles = ['admin', 'agent'];
        if (empty($_POST['select_profile']) || !in_array($_POST['select_profile'], $valid_profiles)) {
            $validation_error = "Invalid selected profile.";
        }

        if (!empty($validation_error)) {
            $_SESSION['validation_error'] = $validation_error;
            $this->editAgent(aes_encrypt($id));
            return;
        }

        // check if there is already another agent with the same username
        $model = new AdminModel();
        $results = $model->checkIfUserExists($id, $_POST['text_name']);

        if ($results) {

            // there is another agent with that name (email)
            $_SESSION['server_error'] = "Já existe outro agente com o mesmo nome.";
            $this->editAgent(aes_encrypt($id));
            return;
        }

        // edit agent in the database
        $results = $model->editAgent($id, $_POST);

        if ($results->status == 'error') {

            // logger
            loggerRegister(getActiveUsername() . " - aconteceu um erro na edição de dados do agente ID: $id", 'error');
            header('Location: index.php');
        } else {

            // logger
            loggerRegister(getActiveUsername() . " - editado com sucesso os dados do agente ID: $id - " . $_POST['text_name']);
        }

        // go to the main admin page
        $this->agentsManagment();
    }


    public function editDelete($id = '')
    {
        // check if session has a user with admin profile
        if (!checkSession() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // get agent data
        $model = new AdminModel();
        $results = $model->getAgentdataAndTotalClients($id);

        // display page for confirmation
        $data['user'] = $_SESSION['user'];
        $data['agent'] = $results;

        // display the edit agent form
        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_delete_confirmation', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }
    public function deleteAgentConfirm($id = '')
    {
        // check if session has a user with admin profile
        if (!checkSession() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // delete agent (soft delete)
        $model = new AdminModel();
        $results = $model->deleteAgent($id);

        if ($results->status == 'success') {

            // logger
            loggerRegister(getActiveUsername() . " - successfully delete agent ID: $id");
        } else {

            // logger
            loggerRegister(getActiveUsername() . " - An error occurred during the deletion of agent ID: $id", 'error');
        }

        // go to the main page
        $this->agentsManagment();
    }

    public function editRecover($id = '')
    {
        // check if session has a user with admin profile
        if (!checkSession() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // get agent data
        $model = new AdminModel();
        $results = $model->getAgentdataAndTotalClients($id);

        // display page for confirmation
        $data['user'] = $_SESSION['user'];
        $data['agent'] = $results;

        // display the edit agent form
        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_recover_confirmation', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }
    public function recoverAgentConfirm($id = '')
    {
        // check if session has a user with admin profile
        if (!checkSession() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        $id = aes_decrypt($id);

        if (!$id) {
            header('Location: index.php');
        }

        // get agent data
        $model = new AdminModel();
        $results = $model->recoverAgent($id);

        if ($results->status == 'success') {

            // logger
            loggerRegister(getActiveUsername() . " - Successfully recovered agent ID: $id");
        } else {

            // logger
            loggerRegister(getActiveUsername() . " - an error occurred during recovery agent ID: $id", 'error');
        }

        // go to the main page
        $this->agentsManagment();
    }
}
