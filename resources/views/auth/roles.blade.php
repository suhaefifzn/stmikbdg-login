<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="author" content="STMIK Bandung">
    <title>STMIK Bandung - Select Role</title>

    {{-- Favicons --}}
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
</head>
    <body>
        <main>
            <div class="container pt-3">
                <p>
                    Akun Anda memiliki beberapa role aktif. Silahkan pilih satu diantara beberapa role berikut untuk mengakses <i>{{ $site }}</i>. Jika setelah dipilih muncul pesan "...Forbidden", maka web tersebut tidak menyediakan role yang Anda inginkan.
                </p>
                @php
                    $setRoles = [];

                    foreach ($roles as $role) {
                        switch ($role) {
                            case 'is_mhs':
                                $setRoles['Mahasiswa'] = $role;
                                break;
                            case 'is_dosen':
                                $setRoles['Dosen'] = $role;
                                break;
                            case 'is_doswal':
                                $setRoles['Dosen Wali'] = $role;
                                break;
                            case 'is_prodi':
                                $setRoles['Prodi'] = $role;
                                break;
                            case 'is_admin':
                                $setRoles['Admin'] = $role;
                                break;
                            case 'is_dev':
                                $setRoles['Developer'] = $role;
                                break;

                            default:
                                break;
                        }
                    }
                @endphp
                <ul>
                    @foreach ($setRoles as $key => $role)
                        <li>
                            <a
                                href="/verify?site={{ $site }}&role={{ $role }}"
                                class="text-decoration-none"
                            >
                                {{ $key === 'Developer' ? 'Developer (Belum Difungsikan)' : $key }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </main>

        {{-- Bootstrap --}}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        {{-- Feather Icons --}}
        <script>
            feather.replace()
        </script>
    </body>
</html>
