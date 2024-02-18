<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="author" content="STMIK Bandung">
    <title>STMIK Bandung - Login</title>

    {{-- Favicomns --}}
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicons/favicon-16x16.png">
    <meta name="theme-color" content="#ffffff">

    {{-- Bootstrap --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap.login.css">

    {{-- Feather Icons --}}
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

    {{-- JQuery --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    {{-- Sweet Alert --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
            font-size: 3.5rem;
            }
        }

        .b-example-divider {
            width: 100%;
            height: 3rem;
            background-color: rgba(0, 0, 0, .1);
            border: solid rgba(0, 0, 0, .15);
            border-width: 1px 0;
            box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
        }

        .b-example-vr {
            flex-shrink: 0;
            width: 1.5rem;
            height: 100vh;
        }

        .bi {
            vertical-align: -.125em;
            fill: currentColor;
        }

        .nav-scroller {
            position: relative;
            z-index: 2;
            height: 2.75rem;
            overflow-y: hidden;
        }

        .nav-scroller .nav {
            display: flex;
            flex-wrap: nowrap;
            padding-bottom: 1rem;
            margin-top: -1px;
            overflow-x: auto;
            text-align: center;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .btn-bd-primary {
            --bd-violet-bg: #712cf9;
            --bd-violet-rgb: 112.520718, 44.062154, 249.437846;

            --bs-btn-font-weight: 600;
            --bs-btn-color: var(--bs-white);
            --bs-btn-bg: var(--bd-violet-bg);
            --bs-btn-border-color: var(--bd-violet-bg);
            --bs-btn-hover-color: var(--bs-white);
            --bs-btn-hover-bg: #6528e0;
            --bs-btn-hover-border-color: #6528e0;
            --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
            --bs-btn-active-color: var(--bs-btn-hover-color);
            --bs-btn-active-bg: #5a23c8;
            --bs-btn-active-border-color: #5a23c8;
        }

        .bd-mode-toggle {
            z-index: 1500;
        }

        .bd-mode-toggle .dropdown-menu .active .bi {
            display: block !important;
        }
    </style>
</head>
    <body class="d-flex align-items-center py-4 bg-body-tertiary">
        <main class="form-signin w-100 m-auto">

            <div class="mb-3 d-flex justify-content-center">
                <img
                    class="mb-2"
                    src="/images/stmikbdg_logo.png"
                    alt="STMIK Bandung Logo"
                    width="200"
                >
            </div>

            <form action="/authenticate" method="post" id="formLogin">
                @csrf
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" autocomplete="off" value="{{ old('email') }}" required>
                    <label for="email">Email</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>
                <button class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-1" type="submit">
                <i data-feather="log-in" style="width: 1.1em;"></i>
                Login
                </button>
            </form>
        </main>

        {{-- Bootstrap --}}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        {{-- Feather Icons --}}
        <script>
            feather.replace()
        </script>

        <script>
            $(document).ready(() => {
                $('#formLogin').submit((e) => {
                    e.preventDefault();

                    const url = new URL(window.location.href);
                    const siteDst = new URL(url.searchParams.get('site')).hostname;

                    $.ajax({
                        url: '/authenticate',
                        type: 'POST',
                        data: {
                            _token: $('input[name="_token"]').val(),
                            email: $('#email').val(),
                            password: $('#password').val(),
                        },
                        success: (response, status, xhr) => {
                            if (xhr.status === 201) {
                                return window.location = `/verify?site=http://${siteDst}`;
                            }
                        },
                        error: (xhr, status) => {
                            if (xhr.status === 401) {
                                const { responseJSON: { message } } = xhr;

                                Swal.fire({
                                    icon: 'error',
                                    html: message,
                                    toast: true,
                                    timer: 5000,
                                    position: 'top-right',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                })
                            } else if (xhr.status === 429) {
                                const { responseJSON: { message } } = xhr;

                                Swal.fire({
                                    icon: 'warning',
                                    html: message,
                                    toast: true,
                                    timer: 5000,
                                    position: 'top-right',
                                    showConfirmButton: false,
                                    timerProgressBar: true,
                                });
                            } else {
                                console.log(xhr);
                            }
                        }
                    })
                });
            });
        </script>
    </body>
</html>
