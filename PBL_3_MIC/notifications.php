<?php
session_start();
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authenticator/auth-login-basic.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$notifications = [];

if ($role == 1) { // Admin
  // Pending companies
  $stmt = $pdo->query("SELECT * FROM perusahaan WHERE status = 'pending'");
  $notifications = $stmt->fetchAll();
} elseif ($role == 2) { // Mahasiswa
  $stmt = $pdo->prepare("SELECT pm.*, p.nama as perusahaan FROM pengajuan_magang pm JOIN perusahaan p ON pm.perusahaan_id = p.id WHERE pm.mahasiswa_id = (SELECT id FROM mahasiswa WHERE user_id = ?) AND pm.status != 'pengajuan'");
  $stmt->execute([$user_id]);
  $notifications = $stmt->fetchAll();
} elseif ($role == 3) { // Perusahaan
  // Pengajuan magang
  $stmt = $pdo->prepare("SELECT 'pengajuan' as type, pm.id, m.nama as nama FROM pengajuan_magang pm JOIN mahasiswa m ON pm.mahasiswa_id = m.id WHERE pm.perusahaan_id = (SELECT id FROM perusahaan WHERE user_id = ?) AND pm.status = 'permohonan'");
  $stmt->execute([$user_id]);
  $notifications = array_merge($notifications, $stmt->fetchAll());
  // Kerja sama proposals
  $stmt2 = $pdo->prepare("SELECT 'kerja_sama' as type, ks.id, j.nama as nama FROM kerja_sama ks JOIN jurusan j ON ks.jurusan_id = j.id WHERE ks.perusahaan_id = (SELECT id FROM perusahaan WHERE user_id = ?) AND ks.status = 'proposed'");
  $stmt2->execute([$user_id]);
  $notifications = array_merge($notifications, $stmt2->fetchAll());
} elseif ($role == 4) { // Jurusan
  $stmt = $pdo->prepare("SELECT DISTINCT pm.*, m.nama as mahasiswa, p.nama as perusahaan FROM pengajuan_magang pm JOIN mahasiswa m ON pm.mahasiswa_id = m.id JOIN perusahaan p ON pm.perusahaan_id = p.id WHERE m.jurusan_id = (SELECT id FROM jurusan WHERE user_id = ?)");
  $stmt->execute([$user_id]);
  $notifications = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>Notifications</title>
  <meta name="description" content="" />
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
  <!-- Icons -->
  <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
  <!-- Core CSS -->
  <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="../assets/css/demo.css" />
  <!-- Vendors CSS -->
  <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
  <!-- Helpers -->
  <script src="../assets/vendor/js/helpers.js"></script>
  <script src="../assets/js/config.js"></script>
</head>
<body>
  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      <!-- Menu -->
      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
          <a href="index.html" class="app-brand-link">
            <span class="app-brand-logo demo">
              <svg width="25" viewBox="0 0 25 42" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <!-- SVG content -->
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
          <li class="menu-item">
            <a href="<?php echo ($role == 1 ? 'index.php' : ($role == 2 ? 'mahasiswa_dashboard.php' : ($role == 3 ? 'perusahaan_dashboard.php' : 'jurusan_dashboard.php'))); ?>" class="menu-link">
              <i class="menu-icon tf-icons bx bx-home-circle"></i>
              <div>Dashboard</div>
            </a>
          </li>
          <!-- Other menu items based on role -->
        </ul>
      </aside>
      <!-- / Menu -->
      <!-- Layout container -->
      <div class="layout-page">
        <!-- Navbar -->
        <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
          <!-- Navbar content -->
        </nav>
        <!-- / Navbar -->
        <!-- Content wrapper -->
        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            <h4>Notifications</h4>
            <div class="row">
              <?php if (empty($notifications)): ?>
                <p>No new notifications.</p>
              <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                  <div class="col-md-12 mb-3">
                    <div class="card">
                      <div class="card-body">
                        <?php if ($role == 1): ?>
                          <h5>New Company Registration: <?php echo htmlspecialchars($notif['nama']); ?></h5>
                          <p><?php echo htmlspecialchars($notif['deskripsi']); ?></p>
                        <?php elseif ($role == 2): ?>
                          <h5>Application Update for <?php echo htmlspecialchars($notif['perusahaan']); ?></h5>
                          <p>Status: <?php echo ucfirst($notif['status']); ?></p>
                        <?php elseif ($role == 3): ?>
                          <?php if ($notif['type'] == 'pengajuan'): ?>
                            <h5>New Application from <?php echo htmlspecialchars($notif['nama']); ?></h5>
                          <?php elseif ($notif['type'] == 'kerja_sama'): ?>
                            <h5>Proposal Kerja Sama dari <?php echo htmlspecialchars($notif['nama']); ?></h5>
                          <?php endif; ?>
                        <?php elseif ($role == 4): ?>
                          <h5>New Application: <?php echo htmlspecialchars($notif['mahasiswa']); ?> to <?php echo htmlspecialchars($notif['perusahaan']); ?></h5>
                          <p>Status: <?php echo ucfirst($notif['status']); ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Core JS -->
  <script src="../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../assets/vendor/libs/popper/popper.js"></script>
  <script src="../assets/vendor/js/bootstrap.js"></script>
  <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="../assets/vendor/js/menu.js"></script>
  <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
  <script src="../assets/js/main.js"></script>
  <script src="../assets/js/dashboards-analytics.js"></script>
</body>
</html>
