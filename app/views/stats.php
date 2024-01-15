<div class="container-fluid mb-5 bg-white">
    <div class="row justify-content-center pb-5">
        <div class="col-12 p-4">

            <div class="row">
                <div class="col">
                    <h4><strong>Dados estatísticos</strong></h4>
                </div>
                <div class="col text-end">
                    <a href="?ct=main&mt=index" class="btn btn-secondary px-4"><i class="fa-solid fa-chevron-left me-2"></i>Voltar</a>
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-sm-6 col-12 p-1">
                    <div class="card p-3">
                        <h4><i class="fa-solid fa-users me-2"></i>Clientes dos agentes</h4>
                        <?php if (count($agents) == 0): ?>
                            <p class="my-4 text-center opacity-75">Não existem clientes registados.</p>
                        <?php else: ?>
                        <table class="table table-striped table-bordered" id="table_clients">
                            <thead class="table-dark">
                            <tr>
                                <th>Agente</th>
                                <th class="text-center">Clientes registrados</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($agents as $agent):?>
                                <tr>
                                    <td><?= $agent->agente ?></td>
                                    <td class="text-center"><?= $agent->total_clientes ?></td>
                                </tr>
                            <?php endforeach;?>
                            </tbody>
                        </table>
                        <?php endif;?>
                    </div>
                </div>
                <div class="col-sm-6 col-12 p-1">
                    <div class="card p-3">
                        <h4><i class="fa-solid fa-users me-2"></i>Gráfico</h4>
                        <div id="chart"></div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col p-1">
                    
                    <div class="card p-3">
                        <h4><i class="fa-solid fa-list-ul me-2"></i>Dados estatísticos globais</h4>
                        <table class="table table-striped table-bordered">
                            <tr>
                                <td class="text-start">Número total de agentes:</td>
                                <td class="text-start"><strong><?= $global_stats['total_agents']->value ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-start">Número total de clientes:</td>
                                <td class="text-start"><strong><?= $global_stats['total_clients']->value ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-start">Total de clientes inativos:</td>
                                <td class="text-start"><strong><?= $global_stats['total_deleted_clients']->value ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-start">Média de clientes por agente:</td>
                                <td class="text-start"><strong><?= $global_stats['average_clients_per_agent']->value ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-start">Idade do cliente mais novo:</td>
                                <td class="text-start"><strong><?= $global_stats['younger_client']->value ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-start">Idade do cliente mais velho:</td>
                                <td class="text-start"><strong><?= $global_stats['oldest_client']->value ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-start">Média de idade dos clientes:</td>
                                <td class="text-start"><strong><?= sprintf("%.2f", $global_stats['age_average']->value) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-start">Porcentagem de clientes mulheres: </td>
                                <td class="text-start"><strong><?= sprintf("%.2f", $global_stats['percentage_males']->value) . '%' ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-start">Porcentagem de clientes homens: </td>
                                <td class="text-start"><strong><?= sprintf("%.2f", $global_stats['percentage_females']->value) . '%' ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="text-center">
                    <a href="?ct=Admin&mt=createPdfReport" target="_blank" class="btn btn-secondary px-4">
                        <i class="fa-solid fa-file-pdf me-2"></i>Criar relatório em pdf</a>
                </div>

            </div>

            <div class="row mb-3">
                <div class="col text-center">
                    <a href="?ct=main&mt=index" class="btn btn-secondary px-4"><i class="fa-solid fa-chevron-left me-2"></i>Voltar</a>
                </div>
            </div>
                
            </div>
        </div>
    </div>
</div>
<script>
    //Datatables.net
    $(document).ready(function (){
        $('#table_clients').DataTable();
    })

    <?php if(count($agents) != 0): ?>

        //ApexCharts
        var options = {
            series: [{
                name: '',
                data: [<?= $chart_totals ?>]
            }],
            chart: {
                height: 350,
                type: 'bar',
            },
            plotOptions: {
                bar: {
                    borderRadius: 10,
                    dataLabels: {
                        position: 'top', // top, center, bottom
                    },
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val + (val > 1 ? ' clientes' : ' cliente');
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ["#304758"]
                }
            },

            xaxis: {
                categories: [<?= $chart_labels ?>],
                position: 'top',
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                crosshairs: {
                    fill: {
                        type: 'gradient',
                        gradient: {
                            colorFrom: '#D8E3F0',
                            colorTo: '#BED1E6',
                            stops: [0, 100],
                            opacityFrom: 0.4,
                            opacityTo: 0.5,
                        }
                    }
                },
                tooltip: {
                    enabled: true,
                }
            },
            yaxis: {
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false,
                },
                labels: {
                    show: false,
                    formatter: function (val) {
                        return val + (val > 1 ? ' clientes' : ' cliente');
                    },
                }

            },
            title: {
                text: 'Total de clientes por agente',
                floating: true,
                offsetY: 330,
                align: 'center',
                style: {
                    color: '#444'
                }
            }
        };

        var chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();

    <?php endif; ?>
</script>