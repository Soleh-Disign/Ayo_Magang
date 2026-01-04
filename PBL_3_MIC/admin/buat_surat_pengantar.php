<?php
session_start();
include 'config.php';

// jika tidak login -> ke login
if (!isset($_SESSION['user_id'])) {
header("Location: ../authenticator/auth-login-basic.php");
exit;
}

// jika bukan admin -> redirect sesuai role
if ($_SESSION['role'] != 1) {
switch ($_SESSION['role']) {
    case 2:
    header("Location: mahasiswa_dashboard.php");
    break;
    case 3:
    header("Location: perusahaan_dashboard.php");
    break;
    case 4:
    header("Location: jurusan_dashboard.php");
    break;
    default:
    header("Location: auth-login-basic.php");
    break;
}
exit;
}

// --- ambil id pengajuan dari URL ---
$id_pengajuan = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// --- ambil data dari database (join pengajuan_magang, mahasiswa, jurusan, perusahaan) ---
$sql = "
    SELECT 
        pm.id,
        pm.tanggal_pengajuan,
        m.nama        AS nama_mhs,
        m.nim,
        j.nama        AS prodi,
        p.nama        AS nama_perusahaan,
        p.alamat      AS alamat_perusahaan
    FROM pengajuan_magang pm
    JOIN mahasiswa m ON pm.mahasiswa_id = m.id
    JOIN perusahaan p ON pm.perusahaan_id = p.id
    JOIN jurusan j    ON m.jurusan_id = j.id
    WHERE pm.id = :id
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id_pengajuan]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// default waktu PKL bisa diisi manual atau nanti dari kolom lain
$default_waktu_pkl = '...............................';

// --- handle form override (jika admin ingin edit isi langsung di halaman ini) ---
$nama_mhs   = $_POST['nama_mhs']   ?? ($data['nama_mhs'] ?? '');
$nim        = $_POST['nim']        ?? ($data['nim'] ?? '');
$prodi      = $_POST['prodi']      ?? ($data['prodi'] ?? '');
$waktu_pkl  = $_POST['waktu_pkl']  ?? $default_waktu_pkl;
$dosen      = $_POST['dosen']      ?? '........................................';
$perusahaan = $_POST['perusahaan'] ?? ($data['nama_perusahaan'] ?? '');

// tanggal surat otomatis hari ini
$tanggal_surat = date('d F Y');

// Add query for notification count (pending companies)
$stmtNotif   = $pdo->query("SELECT COUNT(*) as total FROM perusahaan WHERE status = 'pending'");
$notif_count = $stmtNotif->fetch()['total'];
?>
<!DOCTYPE html>
<html
    lang="en"
    class="light-style layout-menu-fixed"
    dir="ltr"
    data-theme="theme-default"
    data-assets-path="../assets/"
    data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Dashboard</title>

    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />

    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>

    <!-- STYLE KHUSUS PRINT A4 -->
    <style>
        /* area surat tampak seperti kertas di layar */
        #area-print {
            font-family: "Times New Roman", serif;
            color: #000;
            background: #fff;
            padding: 20mm;
            margin: 0 auto;
        }

        @media (min-width: 992px) {
            #area-print {
                border: 1px solid #ddd;
            }
        }

        /* style khusus saat print */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
                background: #fff !important;
            }

            /* hanya area surat yang terlihat dan memenuhi A4 */
            #area-print {
                width: 210mm;
                min-height: 297mm;
                padding: 20mm;
                box-sizing: border-box;
                margin: 0 auto;
                border: none !important;
            }

            /* sembunyikan seluruh elemen lain */
            aside,
            nav,
            .layout-menu,
            .layout-navbar,
            .layout-overlay,
            .content-wrapper> :not(.container-xxl),
            .container-xxl .row>.col-lg-4,
            .btn-print-wrapper,
            .btn,
            form {
                display: none !important;
            }

            html,
            body,
            .layout-wrapper,
            .layout-container,
            .layout-page,
            .content-wrapper,
            .container-xxl {
                width: 100%;
                height: auto;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->

            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="index.html" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <svg
                                width="25"
                                viewBox="0 0 25 42"
                                version="1.1"
                                xmlns="http://www.w3.org/2000/svg"
                                xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <path
                                        d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                                        id="path-1"></path>
                                    <path
                                        d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                                        id="path-3"></path>
                                    <path
                                        d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                                        id="path-4"></path>
                                    <path
                                        d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                                        id="path-5"></path>
                                </defs>
                                <g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                                        <g id="Icon" transform="translate(27.000000, 15.000000)">
                                            <g id="Mask" transform="translate(0.000000, 8.000000)">
                                                <mask id="mask-2" fill="white">
                                                    <use xlink:href="#path-1"></use>
                                                </mask>
                                                <use fill="#696cff" xlink:href="#path-1"></use>
                                                <g id="Path-3" mask="url(#mask-2)">
                                                    <use fill="#696cff" xlink:href="#path-3"></use>
                                                    <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use>
                                                </g>
                                                <g id="Path-4" mask="url(#mask-2)">
                                                    <use fill="#696cff" xlink:href="#path-4"></use>
                                                    <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use>
                                                </g>
                                            </g>
                                            <g
                                                id="Triangle"
                                                transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) ">
                                                <use fill="#696cff" xlink:href="#path-5"></use>
                                                <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                                            </g>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bolder ms-2">Ayo Magang</span>
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <!-- Dashboard -->
                    <li class="menu-item active">
                        <a href="index.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Analytics">Dashboard</div>
                        </a>
                    </li>
                    <!-- New Menus -->
                    <li class="menu-item">
                        <a href="approve_perusahaan.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-check-circle"></i>
                            <div>Perusahaan Mendaftar</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="create_jurusan.php" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-plus-circle"></i>
                            <div>Buat Akun Jurusan</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#perusahaan" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-building"></i>
                            <div>Perusahaan</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#mahasiswa" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div>Mahasiswa</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#jurusan" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-graduation-cap"></i>
                            <div>Jurusan</div>
                        </a>
                    </li>
                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->

                <nav
                    class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="bx bx-search fs-4 lh-0"></i>
                                <input
                                    type="text"
                                    class="form-control border-0 shadow-none"
                                    placeholder="Search..."
                                    aria-label="Search..." />
                            </div>
                        </div>
                        <!-- /Search -->

                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- Place this tag where you want the button to render. -->
                            <li class="nav-item lh-1 me-3">
                                <a
                                    class="github-button"
                                    href="https://github.com/themeselection/sneat-html-admin-template-free"
                                    data-icon="octicon-star"
                                    data-size="large"
                                    aria-label="Star themeselection/sneat-html-admin-template-free on GitHub">Admin</a>
                            </li>

                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block">Admin</span>
                                                    <small class="text-muted">Admin</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="profile.php">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">Settings</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="notifications.php">
                                            <span class="d-flex align-items-center align-middle">
                                                <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                                                <span class="flex-grow-1 align-middle">Notifikasi</span>
                                                <span class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20"><?php echo $notif_count; ?></span>
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="logout.php">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span class="align-middle">Log Out</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>
                </nav>

                <!-- / Navbar -->

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row g-4">

                            <!-- FORM EDIT DATA -->
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Data Surat Pengantar</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" id="formSurat">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Mahasiswa</label>
                                                <input type="text" name="nama_mhs" class="form-control"
                                                    value="<?= htmlspecialchars($nama_mhs) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">NIM</label>
                                                <input type="text" name="nim" class="form-control"
                                                    value="<?= htmlspecialchars($nim) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Program Studi</label>
                                                <input type="text" name="prodi" class="form-control"
                                                    value="<?= htmlspecialchars($prodi) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Waktu PKL</label>
                                                <input type="text" name="waktu_pkl" class="form-control"
                                                    placeholder="contoh: 1 Januari 2026 s/d 31 Maret 2026"
                                                    value="<?= htmlspecialchars($waktu_pkl) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Dosen Pembimbing</label>
                                                <input type="text" name="dosen" class="form-control"
                                                    value="<?= htmlspecialchars($dosen) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Perusahaan Tujuan</label>
                                                <input type="text" name="perusahaan" class="form-control"
                                                    value="<?= htmlspecialchars($perusahaan) ?>">
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">Perbarui Isi Surat</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- PREVIEW + AREA PRINT -->
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <div id="area-print">
                                            <!-- HEADER SURAT -->
                                            <div class="text-center mb-2">
                                                <div style="font-size:11pt;">KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN</div>
                                                <div style="font-size:11pt;">TEKNOLOGI</div>
                                                <div style="font-size:14pt; font-weight:bold;">POLITEKNIK NEGERI PADANG</div>
                                                <div style="font-size:10pt;">
                                                    Kampus Politeknik Negeri Padang, Limau Manis, Padang, Sumatera Barat<br>
                                                    Telepon: (0751) 72590 | Laman: https://www.pnp.ac.id
                                                </div>
                                            </div>

                                            <hr class="my-2">

                                            <!-- TANGGAL -->
                                            <div class="d-flex justify-content-between mb-3" style="font-size:11pt;">
                                                <div></div>
                                                <div>Padang, <?= $tanggal_surat ?></div>
                                            </div>

                                            <!-- NOMOR / LAMPIRAN / PERIHAL -->
                                            <table style="font-size:11pt; width:100%; margin-bottom:10px;">
                                                <tr>
                                                    <td style="width:80px;">Nomor</td>
                                                    <td style="width:10px;">:</td>
                                                    <td>...................................................</td>
                                                </tr>
                                                <tr>
                                                    <td>Lampiran</td>
                                                    <td>:</td>
                                                    <td>-</td>
                                                </tr>
                                                <tr>
                                                    <td>Perihal</td>
                                                    <td>:</td>
                                                    <td><strong>Pengantar Praktik Kerja Lapangan (PKL)</strong></td>
                                                </tr>
                                            </table>

                                            <!-- TUJUAN -->
                                            <p style="font-size:11pt; margin-bottom:0;">Yth. Pimpinan / HRD</p>
                                            <p style="font-size:11pt; margin-bottom:0; font-weight:bold;">
                                                <?= htmlspecialchars($perusahaan) ?>
                                            </p>
                                            <p style="font-size:11pt; margin-bottom:10px;">
                                                di Jl. .............................................
                                            </p>

                                            <!-- PARAGRAF 1 -->
                                            <p style="font-size:11pt; text-align:justify;">
                                                Menindaklanjuti surat balasan dari perusahaan Bapak/Ibu perihal kesediaan
                                                menerima mahasiswa kami untuk melaksanakan Praktik Kerja Lapangan (PKL),
                                                maka dengan ini kami menyerahkan mahasiswa:
                                            </p>

                                            <!-- DATA MAHASISWA -->
                                            <table style="font-size:11pt; margin-left:20px; margin-bottom:10px;">
                                                <tr>
                                                    <td style="width:120px;">Nama</td>
                                                    <td style="width:10px;">:</td>
                                                    <td><strong><?= htmlspecialchars($nama_mhs) ?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td>NIM</td>
                                                    <td>:</td>
                                                    <td><?= htmlspecialchars($nim) ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Program Studi</td>
                                                    <td>:</td>
                                                    <td><?= htmlspecialchars($prodi) ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Waktu PKL</td>
                                                    <td>:</td>
                                                    <td><?= htmlspecialchars($waktu_pkl) ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Dosen Pembimbing</td>
                                                    <td>:</td>
                                                    <td><?= htmlspecialchars($dosen) ?></td>
                                                </tr>
                                            </table>

                                            <!-- PARAGRAF 2 -->
                                            <p style="font-size:11pt; text-align:justify;">
                                                Untuk melaksanakan kegiatan PKL di instansi yang Bapak/Ibu pimpin sesuai
                                                dengan jadwal tersebut di atas. Selama kegiatan berlangsung, mahasiswa
                                                diwajibkan mematuhi segala peraturan dan tata tertib yang berlaku
                                                di perusahaan.
                                            </p>

                                            <!-- PARAGRAF 3 -->
                                            <p style="font-size:11pt; text-align:justify;">
                                                Demikian surat pengantar ini kami sampaikan. Atas kerja sama dan bantuan
                                                yang Bapak/Ibu berikan dalam menunjang pendidikan mahasiswa kami,
                                                kami ucapkan terima kasih.
                                            </p>

                                            <!-- TTD -->
                                            <div class="mt-4" style="font-size:11pt; text-align:right;">
                                                Hormat Kami,<br>
                                                Kepala Unit Kerja Sama,<br><br><br>
                                                <span style="font-weight:bold; text-decoration:underline;">
                                                    Nama Jurusan
                                                </span><br>
                                                NIP. 198XXXXXXXXXXXXX
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- TOMBOL ACTION -->
                    <div class="card-header d-flex justify-content-between align-items-center btn-print-wrapper">
                        <h5 class="mb-0"></h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary btn-sm" id="btnPrint">
                                <i class="bx bx-printer"></i> Print A4
                            </button>
                        </div>
                    </div>

                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <!-- PRINT & PDF JS -->
    <script>
        // Print langsung area-print dengan CSS @media print
        document.getElementById('btnPrint').addEventListener('click', function() {
            window.print();
        });
    </script>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboards-analytics.js"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>