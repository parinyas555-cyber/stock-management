<?php
require_once __DIR__ . '/../src/partials.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'save') {
            $username = trim($_POST['username'] ?? '');
            $name = trim($_POST['full_name'] ?? '');
            $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'staff';
            $active = isset($_POST['active']);
            $password = (string)($_POST['password'] ?? '');

            if ($username === '' || $name === '') {
                throw new Exception('กรุณากรอก Username และชื่อ-นามสกุล');
            }
            if ($password !== '' && strlen($password) < 6) {
                throw new Exception('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
            }

            if ($id) {
                $s = $pdo->prepare('UPDATE users SET username=?,full_name=?,role=?,active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
                $s->execute([$username, $name, $role, $active, $id]);
                if ($password !== '') {
                    $s = $pdo->prepare('UPDATE users SET password_hash=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
                    $s->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
                }
                flash('success', $password !== '' ? 'แก้ไขผู้ใช้งานและเปลี่ยนรหัสผ่านแล้ว' : 'แก้ไขผู้ใช้งานแล้ว');
            } else {
                if ($password === '') {
                    throw new Exception('กรุณากำหนดรหัสผ่าน');
                }
                $s = $pdo->prepare('INSERT INTO users(username,password_hash,full_name,role,active) VALUES(?,?,?,?,?)');
                $s->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $role, $active]);
                flash('success', 'เพิ่มผู้ใช้งานแล้ว');
            }
        } elseif ($action === 'delete') {
            if ($id === current_user_id()) {
                throw new Exception('ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่');
            }
            $s = $pdo->prepare('DELETE FROM users WHERE id=?');
            $s->execute([$id]);
            flash('success', 'ลบผู้ใช้งานแล้ว');
        }

        header('Location:/users.php');
        exit;
    } catch (Throwable $e) {
        flash('danger', 'ดำเนินการไม่สำเร็จ: ' . $e->getMessage());
        header('Location:/users.php');
        exit;
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT id,username,full_name,role,active FROM users WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
$users = $pdo->query('SELECT id,username,full_name,role,active,created_at FROM users ORDER BY id')->fetchAll();

page_start('การจัดการผู้ใช้งาน');
?>
<div class="card form-card">
    <h3><?= $edit ? 'แก้ไขผู้ใช้งาน' : 'เพิ่มผู้ใช้งาน' ?></h3>
    <div class="muted" style="margin-bottom:12px">
        ระบบจัดเก็บรหัสผ่านแบบเข้ารหัส จึงไม่สามารถเรียกดูรหัสผ่านเดิมของผู้ใช้งานได้ หากต้องการเปลี่ยนรหัสผ่านให้กรอกรหัสใหม่ด้านล่าง
    </div>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h($edit['id'] ?? 0) ?>">
        <div class="form-grid">
            <div><label>Username</label><input name="username" value="<?= h($edit['username'] ?? '') ?>" required></div>
            <div><label>ชื่อ-นามสกุล</label><input name="full_name" value="<?= h($edit['full_name'] ?? '') ?>" required></div>
            <div>
                <label>รหัสผ่าน <?= $edit ? '<span class="muted">(กรอกเฉพาะเมื่อต้องการเปลี่ยน)</span>' : '' ?></label>
                <div class="password-wrap">
                    <input id="userPassword" type="password" name="password" minlength="6" <?= $edit ? '' : 'required' ?> autocomplete="new-password">
                    <button type="button" class="button secondary" onclick="togglePassword('userPassword',this)">แสดง</button>
                </div>
            </div>
            <div><label>สิทธิ์</label><select name="role"><option value="staff" <?= ($edit['role'] ?? '') === 'staff' ? 'selected' : '' ?>>พนักงาน</option><option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ</option></select></div>
        </div>
        <label class="check"><input type="checkbox" name="active" <?= ($edit['active'] ?? true) ? 'checked' : '' ?>> เปิดใช้งานบัญชี</label>
        <button class="primary"><?= $edit ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้งาน' ?></button>
        <?php if ($edit): ?><a class="button secondary" href="/users.php">ยกเลิก</a><?php endif; ?>
    </form>
</div>

<div class="table-wrap">
<table>
    <tr><th>Username</th><th>ชื่อ</th><th>รหัสผ่าน</th><th>สิทธิ์</th><th>สถานะ</th><th>สร้างเมื่อ</th><th>จัดการ</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= h($u['username']) ?></td>
        <td><?= h($u['full_name']) ?></td>
        <td><span class="muted">ไม่สามารถแสดงรหัสผ่านเดิมได้</span></td>
        <td><?= h($u['role']) ?></td>
        <td><?= $u['active'] ? '<span class="badge success-bg">ใช้งาน</span>' : '<span class="badge danger-bg">ปิดใช้งาน</span>' ?></td>
        <td><?= h($u['created_at']) ?></td>
        <td class="actions">
            <a class="button secondary" href="?edit=<?= h($u['id']) ?>">แก้ไข / เปลี่ยนรหัสผ่าน</a>
            <?php if ($u['id'] !== current_user_id()): ?>
            <form method="post" onsubmit="return confirm('ยืนยันการลบผู้ใช้งาน?')">
                <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= h($u['id']) ?>">
                <button class="button danger">ลบ</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    btn.textContent = visible ? 'แสดง' : 'ซ่อน';
}
</script>
<?php page_end();
