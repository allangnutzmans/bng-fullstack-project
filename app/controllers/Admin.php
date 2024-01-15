<?php

namespace bng\Controllers;

use bng\Models\AdminModel;
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

}
