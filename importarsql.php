<link rel="stylesheet" href="css/styledashboard.css">

<?php
// ===== CONFIG =====
$SENHA = "";  // <-- mude!
$DB_HOST = "localhost";
$DB_NAME = "ti";          // <-- nome do seu banco
$DB_USER = "root";            // <-- seu usuário
$DB_PASS = "";                // <-- sua senha
// ==================

// Proteção por senha (via URL ou formulário)
$senhaInformada = $_GET['senha'] ?? $_POST['senha'] ?? '';
if ($senhaInformada !== $SENHA) {
    http_response_code(403);
    die('Acesso negado. Use ?senha=SUA_SENHA na URL.');
}

// Conexão
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Importação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['sql']['tmp_name'])) {
    $sql = file_get_contents($_FILES['sql']['tmp_name']);
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec($sql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        echo "<h2 style='color:green'>✅ Importado com sucesso!</h2>";
    } catch (Exception $e) {
        echo "<h2 style='color:red'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</h2>";
    }
}
?>
<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html><body style="font-family:sans-serif;max-width:500px;margin:50px auto">
    <div class="header-tag">// gestão de ti</div>
        <h1>Update <span>SQL</span></h1><br />    
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="senha" value="<?= htmlspecialchars($senhaInformada) ?>">
        <p><input type="file" name="sql" accept=".sql" required></p>
        <p><button type="submit">Importar</button></p><br />
    </form>
    <div class="stats">
            <div class="stat">       
                <a href="dashboard.php" style="text-decoration:none; color:inherit;">         
                <div class="stat-value" style="color:#azul">↩</div>
                </a>
            </div>
        </div>
</body>
</html>
