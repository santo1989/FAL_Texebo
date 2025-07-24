        <x-backend.layouts.master>

            <x-slot name="pageTitle">
                Dashboard
            </x-slot>

            <x-slot name='breadCrumb'>
                <x-backend.layouts.elements.breadcrumb>
                    <x-slot name="pageHeader"> Dashboard </x-slot>
                    <li class="breadcrumb-item active">Dashboard</li>
                </x-backend.layouts.elements.breadcrumb>
            </x-slot>

            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                       @php
$user = auth()->user();
@endphp

<?php
    $joining_date = date("Y-m-d", strtotime($user->joining_date));
    $dob = date("Y-m-d", strtotime($user->dob));
    $today = date("Y-m-d");

    // Check if today is the work anniversary or birthday
    $is_work_anniversary = (date("m-d", strtotime($joining_date)) === date("m-d", strtotime($today)));
    $is_birthday = (date("m-d", strtotime($dob)) === date("m-d", strtotime($today)));

    if ($is_work_anniversary) {
        // Calculate years between two dates
        $joining_years = date_diff(date_create($joining_date), date_create($today))->y;

        // Show work anniversary message
        echo '<div class="card-header">Happy '.$joining_years.' Year Work Anniversary!</div>
              <div class="card-body"><h5 class="card-title">Wishing you a wonderful time with us</h5></div>
              <canvas id="canvas"></canvas>'; // Include the canvas element here for fireworks animation
    } elseif ($is_birthday) {
        // Calculate years between two dates
        $dob_years = date_diff(date_create($dob), date_create($today))->y;

        // Show birthday message
        echo '<div class="container my-5">
              <div class="card text-center">
              <div class="card-header">Happy '.$dob_years.'th Birthday!</div>
              <div class="card-body">
              <h5 class="card-title">Wishing you a wonderful day</h5>
              <p class="card-text">May all your dreams come true.</p>
              </div></div>
              <canvas id="canvas"></canvas>'; // Include the canvas element here for fireworks animation
    }
?>
                      




                    </div>
                </div>
            </div>
            <script>
                //     const iconPath = '{{ asset('logo.PNG') }}';
                //  Push.create("Hello Shailesh!",{
                //        body: "Welcome to the Dashboard.",
                //        timeout: 5000,
                //        icon: iconPath
                // });
            </script>
            <style>
                canvas {
                    position: absolute;
                    top: 0;
                    left: 0;
                    z-index: -1;
                }
            </style>

            <script>
                // Fireworks animation
                function startFireworks() {
                    // Initialize variables
                    const canvas = document.getElementById("canvas");
                    const ctx = canvas.getContext("2d");
                    const particles = [];
                    const colors = ["#00bcd4", "#4caf50", "#ffeb3b", "#ff5722", "#e91e63"];

                    // Set canvas size
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;

                    // Create particle class
                    class Particle {
                        constructor(x, y, dx, dy, size, color) {
                            this.x = x;
                            this.y = y;
                            this.dx = dx;
                            this.dy = dy;
                            this.size = size;
                            this.color = color;
                            this.life = 50;
                        }

                        // Update particle position and draw it
                        update() {
                            this.x += this.dx;
                            this.y += this.dy;
                            this.life--;
                            ctx.fillStyle = this.color;
                            ctx.beginPath();
                            ctx.arc(this.x, this.y, this.size, 0, 2 * Math.PI);
                            ctx.fill();
                        }
                    }

                    // Create new particles and add them to the array
                    function createParticles(x, y) {
                        for (let i = 0; i < 30; i++) {
                            const angle = Math.random() * 360;
                            const speed = Math.random() * 10 + 5;
                            const size = Math.random() * 3 + 1;
                            const color = colors[Math.floor(Math.random() * colors.length)];
                            const dx = Math.cos(angle) * speed;
                            const dy = Math.sin(angle) * speed;
                            const particle = new Particle(x, y, dx, dy, size, color);
                            particles.push(particle);
                        }
                    }

                    // Animate particles
                    function animate() {
                        requestAnimationFrame(animate);
                        ctx.clearRect(0, 0, canvas.width, canvas.height);

                        for (let i = 0; i < particles.length; i++) {
                            particles[i].update();

                            // Remove particles that are no longer visible
                            if (particles[i].life <= 0) {
                                particles.splice(i, 1);
                            }
                        }
                    }

                    // Create particles at random location
                    createParticles(Math.random() * canvas.width, Math.random() * canvas.height);

                    // Start animation
                    animate();
                }

                // Auto start fireworks on page load
                startFireworks();

                // Stop fireworks after 20 seconds
                setTimeout(function() {
                    cancelAnimationFrame(window.requestAnimationFrame);
                }, 20000);
            </script>
        </x-backend.layouts.master>