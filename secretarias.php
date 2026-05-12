<?php
require_once "conexao.php";
include 'includes/header.php';

$result = $mysqli->query("SELECT Sec_codigo, Sec_descricao FROM secretaria ORDER BY Sec_descricao");
$secretarias = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $secretarias[] = $row;
    }
}
$total = count($secretarias);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretarias</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    
</head>
<body>
<div class="container">

    <div class="header">
        <div class="header-tag">// sistema de gestão</div>
        <h1>Secre<span>tarias</span></h1>
        <p class="subtitle">Listagem completa de secretarias cadastradas no sistema.</p>
    </div>

    <div class="stats">
        <div class="stats">
            <div class="stat">
                <span class="stat-value"><?= $total ?></span>
                <span class="stat-label">Total cadastradas</span>
            </div>
        </div>        
        <div class="stats">
            <div class="stat">       
                <a href="dashboard.php" style="text-decoration:none; color:inherit;">         
                <div class="stat-value" style="color:#azul">↩</div>
                </a>
            </div>
        </div>
    </div>
    <div class="search-wrap">
        <form method="GET" class="filters">
        <input
            type="text"
            class="search-input"
            id="busca"
            placeholder="Filtrar secretarias..."
            onkeyup="filtrar()"
            autocomplete="off"
        >
        </form>
    </div>
    <div class="table-wrap" >
        <?php if ($total > 0): ?>
        <table id="tabela">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($secretarias as $i => $s): ?>
                <tr>
                    <td class="row-index"><?= $i + 1 ?></td>
                    <td class="td-code"><?= htmlspecialchars($s['Sec_codigo']) ?></td>
                    <td class="td-nome"><?= htmlspecialchars($s['Sec_descricao']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="no-results" id="noResults">Nenhuma secretaria encontrada.</div>
        <?php else: ?>
        <div class="empty">
            <div class="empty-icon">🗂️</div>
            <div class="empty-text">Nenhuma secretaria cadastrada.</div>
        </div>
        <?php endif; ?>
    </div>

    <div class="footer">baugal &mdash; <?= date('Y') ?></div>

</div>

<script>
function filtrar() {
    const termo = document.getElementById('busca').value.toLowerCase();
    const linhas = document.querySelectorAll('#tabela tbody tr');
    let visiveis = 0;

    linhas.forEach(tr => {
        const texto = tr.innerText.toLowerCase();
        const mostrar = texto.includes(termo);
        tr.style.display = mostrar ? '' : 'none';
        if (mostrar) visiveis++;
    });

    document.getElementById('noResults').style.display = visiveis === 0 ? 'block' : 'none';
}
</script>
</body>
</html>
