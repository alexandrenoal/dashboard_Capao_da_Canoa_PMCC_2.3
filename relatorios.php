<?php
require_once "conexao.php";
include 'includes/header.php';

// ── Por ano ──────────────────────────────────────────────
$r = $mysqli->query("
    SELECT Ods_ano AS label, COUNT(*) AS total
    FROM ordemservic
    WHERE Ods_ano IS NOT NULL
    GROUP BY Ods_ano
    ORDER BY Ods_ano ASC
");
$por_ano = $r->fetch_all(MYSQLI_ASSOC);

// ── Por mês (ano selecionado) ─────────────────────────────
$ano_sel = $_GET['ano'] ?? date('Y');
$stmt = $mysqli->prepare("
    SELECT DATE_FORMAT(Ods_entrada, '%m') AS mes_num,
           DATE_FORMAT(Ods_entrada, '%b') AS label,
           COUNT(*) AS total
    FROM ordemservic
    WHERE Ods_ano = ? AND Ods_entrada IS NOT NULL
    GROUP BY mes_num, label
    ORDER BY mes_num ASC
");
$stmt->bind_param('s', $ano_sel);
$stmt->execute();
$por_mes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Nomes dos meses em PT
$meses_pt = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
foreach ($por_mes as &$m) {
    $m['label'] = $meses_pt[(int)$m['mes_num'] - 1];
}
unset($m);

// ── Por dia (mês selecionado) ─────────────────────────────
$mes_sel = $_GET['mes'] ?? date('m');
$stmt2 = $mysqli->prepare("
    SELECT DAY(Ods_entrada) AS label, COUNT(*) AS total
    FROM ordemservic
    WHERE Ods_ano = ? AND MONTH(Ods_entrada) = ? AND Ods_entrada IS NOT NULL
    GROUP BY DAY(Ods_entrada)
    ORDER BY DAY(Ods_entrada) ASC
");
$stmt2->bind_param('ss', $ano_sel, $mes_sel);
$stmt2->execute();
$por_dia = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Anos disponíveis ──────────────────────────────────────
$anos_res = $mysqli->query("SELECT DISTINCT Ods_ano FROM ordemservic WHERE Ods_ano IS NOT NULL ORDER BY Ods_ano DESC");
$anos_disp = $anos_res->fetch_all(MYSQLI_ASSOC);

// ── Totais rápidos ────────────────────────────────────────
$tot_geral = array_sum(array_column($por_ano, 'total'));
$tot_ano   = array_sum(array_column($por_mes, 'total'));
$tot_mes   = array_sum(array_column($por_dia, 'total'));

$meses_pt_full = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatórios — OS</title>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@400;700;900&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link rel="stylesheet" href="css/stylerelatorios.css">
</head>
<body>
    
<div class="container">

<div class="header">
    <div class="eyebrow">// dashboard · ti</div>
    <h1>Relatório de <em>OS</em></h1>
    <p class="subtitle">Ordens de serviço agrupadas por período</p>
</div>

<form method="GET" class="filters">
    <select name="ano">
        <?php foreach($anos_disp as $a): ?>
        <option value="<?=$a['Ods_ano']?>" <?=$ano_sel==$a['Ods_ano']?'selected':''?>><?=$a['Ods_ano']?></option>
        <?php endforeach; ?>
    </select>
    <select name="mes">
        <?php for($i=1;$i<=12;$i++): ?>
        <option value="<?=str_pad($i,2,'0',STR_PAD_LEFT)?>" <?=(int)$mes_sel===$i?'selected':''?>><?=$meses_pt_full[$i]?></option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="btn-go">APLICAR</button>
    <div class="stat">
        <a href="dashboard.php" style="text-decoration:none; color:inherit;">
        <div class="stat-value" style="color:#azul">↩</div>
        
        </a>
    </div>
</form>

<!-- KPIs -->
<div class="kpis">
    <div class="kpi k1">
        <div class="kpi-val"><?=$tot_geral?></div>
        <div class="kpi-label">Total geral</div>
    </div>
    <div class="kpi k2">
        <div class="kpi-val"><?=$tot_ano?></div>
        <div class="kpi-label">Em <?=$ano_sel?></div>
    </div>
    <div class="kpi k3">
        <div class="kpi-val"><?=$tot_mes?></div>
        <div class="kpi-label"><?=$meses_pt_full[(int)$mes_sel]?> / <?=$ano_sel?></div>
    </div>
</div>

<!-- Gráfico por Ano -->
<div class="section-title">Por Ano</div>
<div class="chart-card tall">
    <div class="chart-head">
        <span class="chart-title">OS por Ano</span>
        <span class="chart-sub">todos os anos cadastrados</span>
    </div>
    <div class="chart-wrap"><canvas id="chartAno" height="100"></canvas></div>
</div>

<!-- Gráficos por Mês e Dia lado a lado -->
<div class="section-title">Por Mês · <?=$ano_sel?></div>
<div class="grid2">
    <div class="chart-card tall">
        <div class="chart-head">
            <span class="chart-title">OS por Mês</span>
            <span class="chart-sub"><?=$ano_sel?></span>
        </div>
        <div class="chart-wrap"><canvas id="chartMes" height="200"></canvas></div>
    </div>
    <div class="chart-card tall">
        <div class="chart-head">
            <span class="chart-title">OS por Dia</span>
            <span class="chart-sub"><?=$meses_pt_full[(int)$mes_sel]?> / <?=$ano_sel?></span>
        </div>
        <div class="chart-wrap"><canvas id="chartDia" height="200"></canvas></div>
    </div>
</div>

<!-- Tabela resumo mensal -->
<div class="section-title">Resumo Mensal · <?=$ano_sel?></div>
<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>Mês</th>
                <th class="td-bar-wrap"></th>
                <th style="text-align:right">Quantidade</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $max_mes = $por_mes ? max(array_column($por_mes,'total')) : 1;
        foreach($por_mes as $m):
            $pct = round($m['total']/$max_mes*100);
        ?>
        <tr>
            <td class="td-label"><?=$m['label']?></td>
            <td class="td-bar-wrap">
                <div class="td-bar"><div class="td-bar-fill" style="width:<?=$pct?>%"></div></div>
            </td>
            <td class="td-num"><?=$m['total']?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$por_mes): ?>
        <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:32px">Sem dados para <?=$ano_sel?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</div><!-- /container -->

<script>
Chart.defaults.color = '#4a5270';
Chart.defaults.font.family = "'IBM Plex Mono', monospace";
Chart.defaults.font.size = 11;

const gridColor = 'rgba(26,32,53,0.8)';
const tickColor = '#4a5270';

function baseOpts(label) {
    return {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#111620',
                borderColor: '#1a2035',
                borderWidth: 1,
                titleColor: '#dde2f0',
                bodyColor: '#e8f020',
                padding: 12,
                callbacks: { label: ctx => `  ${ctx.parsed.y} OS` }
            }
        },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: tickColor } },
            y: { grid: { color: gridColor }, ticks: { color: tickColor }, beginAtZero: true }
        }
    };
}

// Dados PHP → JS
const dAno = {
    labels: <?=json_encode(array_column($por_ano,'label'))?>,
    data:   <?=json_encode(array_map('intval', array_column($por_ano,'total')))?>
};
const dMes = {
    labels: <?=json_encode(array_column($por_mes,'label'))?>,
    data:   <?=json_encode(array_map('intval', array_column($por_mes,'total')))?>
};
const dDia = {
    labels: <?=json_encode(array_map(fn($d)=>'Dia '.$d['label'], $por_dia))?>,
    data:   <?=json_encode(array_map('intval', array_column($por_dia,'total')))?>
};

// ── Gráfico Ano (barras) ──────────────────────────────────
new Chart(document.getElementById('chartAno'), {
    type: 'bar',
    data: {
        labels: dAno.labels,
        datasets: [{
            data: dAno.data,
            backgroundColor: dAno.data.map((v,i) => i === dAno.data.indexOf(Math.max(...dAno.data)) ? '#e8f020' : 'rgba(232,240,32,0.25)'),
            borderColor: '#e8f020',
            borderWidth: 1,
            borderRadius: 4,
            hoverBackgroundColor: '#e8f020',
        }]
    },
    options: {
        ...baseOpts('ano'),
        plugins: {
            ...baseOpts('ano').plugins,
            tooltip: { ...baseOpts('ano').plugins.tooltip, callbacks: { label: ctx => `  ${ctx.parsed.y} OS` } }
        }
    }
});

// ── Gráfico Mês (linha) ───────────────────────────────────
new Chart(document.getElementById('chartMes'), {
    type: 'line',
    data: {
        labels: dMes.labels,
        datasets: [{
            data: dMes.data,
            borderColor: '#20f0b8',
            backgroundColor: 'rgba(32,240,184,0.08)',
            pointBackgroundColor: '#20f0b8',
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.3,
            fill: true,
        }]
    },
    options: baseOpts('mes')
});

// ── Gráfico Dia (barras) ──────────────────────────────────
new Chart(document.getElementById('chartDia'), {
    type: 'bar',
    data: {
        labels: dDia.labels,
        datasets: [{
            data: dDia.data,
            backgroundColor: 'rgba(240,96,32,0.35)',
            borderColor: '#f06020',
            borderWidth: 1,
            borderRadius: 3,
            hoverBackgroundColor: '#f06020',
        }]
    },
    options: {
        ...baseOpts('dia'),
        scales: {
            x: { grid:{color:gridColor}, ticks:{color:tickColor, maxRotation:60} },
            y: { grid:{color:gridColor}, ticks:{color:tickColor}, beginAtZero:true }
        }
    }
});
</script>
</body>
</html>
