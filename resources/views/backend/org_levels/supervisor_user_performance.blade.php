<x-backend.layouts.master>
    <x-slot name="pageTitle">
        KPI User Performance
    </x-slot>

    <x-slot name='breadCrumb'>
        <x-backend.layouts.elements.breadcrumb>
            <x-slot name="pageHeader"> User Performance </x-slot>

            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('supervisor_assigns.index') }}">User Performance </a></li>
        </x-backend.layouts.elements.breadcrumb>
    </x-slot>

    @if ($results == null)
        <div class="alert alert-danger">
            <strong>Sorry!</strong> No Data Found.
        </div>
    @else
        <section class="content">
            <div class="container-fluid">

                @if (session('message'))
                    <div class="alert alert-success">
                        <span class="close" data-dismiss="alert">&times;</span>
                        <strong>{{ session('message') }}.</strong>
                    </div>
                @endif

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row justify-content-between">
                                    <div class="col-md-6">
                                        <div class="accordion" id="accordionExample">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingThree">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseThree"
                                                        aria-expanded="false" aria-controls="collapseThree">
                                                        <h3 class="card-title text-center">User Performance Measurement
                                                            Table</h3>
                                                    </button>
                                                </h2>
                                                <div id="collapseThree" class="accordion-collapse collapse"
                                                    aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <table class="table table-bordered table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th>Marks</th>
                                                                    <th>Performance Title</th>
                                                                    <th>Total in Range</th>
                                                                    <th>Average</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($MesurementTable as $result)
                                                                    {{-- @dd($result); --}}
                                                                    <tr>
                                                                        <td>{{ $result['marks'] }}</td>
                                                                        <td>{{ $result['title'] }}</td>
                                                                        <td>{{ $result['Total_ID'] }}</td>
                                                                        <td>{{ $result['Percentage_of_Total_ID'] }}</td>
                                                                    </tr>
                                                                @endforeach

                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>



                                    </div>
                                    <div class="col-md-6 text-center">
                                        <div class="accordion" id="accordionExample">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingTw">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                                        aria-expanded="false" aria-controls="collapseTwo">
                                                        <div class="text-center">
                                                            <h3 class="card-title">User Performance Analysis Curve
                                                                {{ date('Y') }}
                                                            </h3>
                                                        </div>
                                                    </button>
                                                </h2>
                                                <div id="collapseTwo" class="accordion-collapse collapse"
                                                    aria-labelledby="headingTw" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">

                                                        <div class="text-center">
                                                            <div class="chart">
                                                                <canvas id="barChart" style="height:110px;"></canvas>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">

                                    <table id="datatablesSimple" class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Emp ID</th>
                                                <th>Name</th>
                                                <th>KPI Period</th>
                                                <th>KPI Quantitative</th>
                                                <th>KPI Qualitative</th>
                                                <th>Average Quantitative</th>
                                                <th>Average Qualitative</th>
                                                <th>Total Average</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($results as $result)
                                                <tr>
                                                    <td><button type="button"
                                                            class="btn btn-outline-dark bg-gradient btn-sm"
                                                            data-bs-toggle="modal" data-bs-target="#showEmployeeDetails"
                                                            data-item="{{ $result['user'] }}">
                                                            {{ $result['emp_id'] }}
                                                        </button>
                                                    </td>
                                                    <td>{{ $result['name'] }}</td>
                                                    <td>
                                                        @foreach ($result['kpi_period'] as $kpiLate)
                                                            <div>{{ $kpiLate }}</div>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        @foreach ($result['kpiLate'] as $kpiLate)
                                                            <div>{{ $kpiLate }}</div>
                                                        @endforeach
                                                    </td>
                                                    <td>
                                                        @foreach ($result['kpiNormal'] as $kpiNormal)
                                                            <div>{{ $kpiNormal }}</div>
                                                        @endforeach
                                                    </td>
                                                    <td>{{ $result['averageLate'] }}</td>
                                                    <td>{{ $result['averageNormal'] }}</td>
                                                    <td>{{ $result['totalAverage'] }}</td>
                                                </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                            <!-- /.card -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
        </section>
        {{-- open modal for show employee details --}}
        <div class="modal fade" id="showEmployeeDetails" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Employee Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table">
                                    <table class="table table-bordered">

                                        <tr>
                                            <th>Employee ID</th>
                                            <td id="emp_id"></td>
                                        </tr>
                                        <tr>
                                            <th>Employee Name</th>
                                            <td id="name"></td>
                                        </tr>
                                        <tr>
                                            <th>Company</th>
                                            <td id="company"></td>
                                        </tr>
                                        <tr>
                                            <th>Department</th>
                                            <td id="department"></td>
                                        </tr>
                                        <tr>
                                            <th>Designation</th>
                                            <td id="designation"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table">
                                    <table class="table">
                                        <tr>
                                            <td id="picture">

                                            </td>
                                        </tr>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>




    <script>
        $(document).on('click', '.btn-outline-dark', function() {
            var user = $(this).data('item');
            for (var i = 0; i < user.length; i++) {
                var id = user[i];
            }
            // var id = user[0];



            console.log(user);
            console.log(id);
            $(`#picture`).empty();
            $('#emp_id').text(id.emp_id);
            $('#name').text(id.name);
            $('#picture').append(
                `<img src="{{ asset('images/users/${id.picture}') }}" alt="" width="200px" height="200px">`);

            $.ajax({
                url: "{{ route('getEmployeeDetails') }}",
                type: "GET",
                data: {
                    id: id.id
                },
                success: function(data) {
                    // console.log(data);
                    $('#company').text(data.company_name);
                    $('#department').text(data.department_name);
                    $('#designation').text(data.designation_name);
                }
            })


        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('barChart');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Not Completed', 'Development Needed', 'Below Expectation', 'Good Performer',
                    'Excellent Performer', 'Outstanding Performer'
                ],
                datasets: [{
                    label: 'Annual Performance Curve - NTG',
                    data: [<?php echo $MesurementTable[0]['Total_ID']; ?>, <?php echo $MesurementTable[1]['Total_ID']; ?>, <?php echo $MesurementTable[2]['Total_ID']; ?>,
                        <?php echo $MesurementTable[3]['Total_ID']; ?>, <?php echo $MesurementTable[4]['Total_ID']; ?>, <?php echo $MesurementTable[5]['Total_ID']; ?>
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    // borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>




</x-backend.layouts.master>
