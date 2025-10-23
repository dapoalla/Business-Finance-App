<?php
require_once 'db.php';
include 'header.php';

// Only admin should manage users
if (($_SESSION['role'] ?? 'admin') !== 'admin') {
    echo '<div class="container"><p>Access denied.</p></div>';
    require_once 'footer.php';
    exit;
}

$msg = '';

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, name, email, role, password_hash) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss', $username, $name, $email, $role, $hash);
        if ($stmt->execute()) $msg = 'User created.'; else $msg = 'Error: ' . $conn->error;
        $stmt->close();
    } else {
        $msg = 'Username and password required.';
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $uid");
    $msg = 'User deleted.';
}

// List users
$users = $conn->query("SELECT id, username, name, email, role, created_at FROM users ORDER BY created_at DESC");
?>
<div class="container">
    <h2>Users</h2>
    <?php if ($msg): ?><p style="color:#b7ffb7;"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <h3>Create User</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <label>Username<input type="text" name="username" required></label>
        <label>Name<input type="text" name="name"></label>
        <label>Email<input type="email" name="email"></label>
        <label>Role
            <select name="role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </label>
        <label>Password<input type="password" name="password" required></label>
        <button type="submit">Create</button>
    </form>

    <h3>Existing Users</h3>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($users && $users->num_rows > 0): ?>
            <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td><?= htmlspecialchars($u['created_at']) ?></td>
                    <td>
                        <a href="users.php?delete=<?= (int)$u['id'] ?>" onclick="return confirm('Delete user?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once 'footer.php'; ?>