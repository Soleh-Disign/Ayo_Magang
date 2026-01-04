<?php
session_start();
include 'config.php';

$error = '';
$success = '';
$jurusanList = [];
// Load jurusan if exists
try {
    $stmt = $pdo->query("SELECT id, nama FROM jurusan");
    $jurusanList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore; jurusan may not exist yet
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $role = intval($_POST['role'] ?? 2);
    $nama = trim($_POST['nama'] ?? '');

    if ($username === '' || $password === '' || $email === '' || $nama === '') {
        $error = 'Lengkapi semua field yang wajib.';
    } else {
        // check username uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username sudah digunakan.';
        } else {
            try {
                $pdo->beginTransaction();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
                $ins->execute([$username, $hash, $role, $email]);
                $user_id = $pdo->lastInsertId();

                if ($role === 2) {
                    // mahasiswa
                    $jurusan_id = intval($_POST['jurusan_id'] ?? 0);
                    $nim = trim($_POST['nim'] ?? '');
                    $alamat = trim($_POST['alamat'] ?? '');
                    $ins2 = $pdo->prepare("INSERT INTO mahasiswa (user_id, nama, jurusan_id, nim, alamat) VALUES (?, ?, ?, ?, ?)");
                    $ins2->execute([$user_id, $nama, $jurusan_id, $nim, $alamat]);
                    $success = 'Registrasi mahasiswa berhasil. Silakan login.';
                } elseif ($role === 3) {
                    // perusahaan -> pending
                    $alamatp = trim($_POST['alamat_perusahaan'] ?? '');
                    $NPWP = trim($_POST['NPWP'] ?? '');
                    $tahun = trim($_POST['tahun'] ?? '');
                    $telp = trim($_POST['telp'] ?? '');
                    $pos = trim($_POST['pos'] ?? '');
                    $ins2 = $pdo->prepare("INSERT INTO perusahaan (user_id, nama, ,NPWP, tahun_berdiri, alamat, telp, kode_pos,  deskripsi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $ins2->execute([$user_id, $nama, $alamatp, $deskripsi]);
                    $success = 'Registrasi perusahaan berhasil. Akun menunggu persetujuan admin.';
                } else {
                    $success = 'Registrasi berhasil.';
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style">

<head>
    <meta charset="utf-8" />
    <title>Register - Ayo Magang</title>
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
</head>

<body>
    <div class="container-xxl">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Register</h5>
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                        <form method="post" id="registerForm" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-control" onchange="toggleFields(this.value)">
                                        <option value="2" <?php if (isset($_POST['role']) && $_POST['role'] == 2) echo 'selected'; ?>>Mahasiswa</option>
                                        <option value="3" <?php if (isset($_POST['role']) && $_POST['role'] == 3) echo 'selected'; ?>>Perusahaan</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Nama</label>
                                    <input type="text" name="nama" class="form-control" required value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
                                </div>
                            </div>

                            <div id="mahasiswa-fields">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jurusan</label>
                                        <select name="jurusan_id" class="form-control">
                                            <?php if ($jurusanList): foreach ($jurusanList as $j): ?>
                                                    <option value="<?php echo $j['id']; ?>" <?php if (isset($_POST['jurusan_id']) && $_POST['jurusan_id'] == $j['id']) echo 'selected'; ?>><?php echo htmlspecialchars($j['nama']); ?></option>
                                                <?php endforeach;
                                            else: ?>
                                                <option value="0">-- Tidak ada jurusan --</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NIM</label>
                                        <input type="text" name="nim" class="form-control" value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Alamat</label>
                                        <textarea name="alamat" class="form-control"><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div id="perusahaan-fields" style="display:none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NPWP</label>
                                        <input type="text" name="NPWP" class="form-control" value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tahun Berdiri</label>
                                        <input type="text" name="tahun" class="form-control" value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">No Telepon</label>
                                        <input type="text" name="telp" class="form-control" value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kode Pos</label>
                                        <input type="text" name="pos" class="form-control" value="<?php echo htmlspecialchars($_POST['nim'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Alamat Perusahaan</label>
                                        <textarea name="alamat_perusahaan" class="form-control"><?php echo htmlspecialchars($_POST['alamat_perusahaan'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control"><?php echo htmlspecialchars($_POST['deskripsi'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Register</button>
                            <a href="auth-login-basic.php" class="btn btn-link">Login</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script>
        function toggleFields(role) {
            document.getElementById('mahasiswa-fields').style.display = role == 2 ? 'block' : 'none';
            document.getElementById('perusahaan-fields').style.display = role == 3 ? 'block' : 'none';
        }
        (function() {
            var role = '<?php echo intval($_POST['role'] ?? 2); ?>';
            toggleFields(role);
        })();
    </script>
</body>

</html>