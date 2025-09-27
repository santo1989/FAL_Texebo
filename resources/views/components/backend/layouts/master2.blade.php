<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Order Tracking Software from NTG, MIS Department" />
    <meta name="author" content="Engr. Md. Hasibul Islam Santo, MIS, NTG" />
    <title>{{ $pageTitle ?? 'FAL' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
    
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js" crossorigin="anonymous"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Simple DataTables -->
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link href="{{ asset('ui/backend/css/styles.css') }}" rel="stylesheet" />
    
    <style>
        :root {
            --primary-bg: #e6eefe;
            --card-bg: #f5f7ff;
            --accent-color: #2563eb;
        }

        body {
            background-color: var(--primary-bg);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
        }

        .container-fluid {
            padding: 0.5rem;
        }

        .main-card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 0.5rem;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        @media (min-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
            
            .main-card {
                margin: 1rem;
                padding: 1.5rem;
            }
        }

        /* .select2-container .select2-selection--single {
            height: 38px;
            border-color: var(--accent-color);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        } */
    </style>
</head>
<body>
    <div class="container-fluid">
         {!! $breadCrumb ?? '' !!}
        <div class="main-card">
           
            {{ $slot ?? '' }}
        </div>
    </div>

    <!-- Scripts -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
            crossorigin="anonymous"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.js"></script>
    
    <!-- Simple DataTables -->
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.js" 
            crossorigin="anonymous"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom Scripts -->
    <script src="{{ asset('ui/backend/js/scripts.js') }}"></script>
    <script src="{{ asset('ui/backend/js/datatables-simple-demo.js') }}"></script>
    
    {{-- <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
            
            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => 
                new bootstrap.Tooltip(tooltipTriggerEl)
            );
        });
    </script> --}}
</body>
</html>