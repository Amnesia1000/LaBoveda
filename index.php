<?php
$db = new PDO("sqlite:database.db");
$sistema = isset($_GET['sys']) ? $_GET['sys'] : 'TODOS';

if ($sistema == 'TODOS') {
    $query = $db->query("SELECT * FROM juegos ORDER BY consola, nombre ASC");
} else {
    $stmt = $db->prepare("SELECT * FROM juegos WHERE consola = ? ORDER BY nombre ASC");
    $stmt->execute([$sistema]);
    $juegos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$juegos = ($sistema == 'TODOS') ? $query->fetchAll(PDO::FETCH_ASSOC) : $juegos;

$menu = ["GB", "GBC", "GBA", "NDS", "3DS", "SNES", "N64", "GC", "Wii", "WiiU", "Switch", "SEGA", "SONY", "OTROS"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Bóveda de Alejandro</title>
    <style>
        :root {
            --bg-color: #0f0f0f; --card-bg: #181818; --text-main: #f4f1ea; --accent: #ff8c00;
            --nav-bg: rgba(15, 15, 15, 0.88); --img-bg: #121212;
        }
        body.light-mode {
            --bg-color: #d8d8d8; --card-bg: #e2e2e2; --text-main: #222222;
            --nav-bg: rgba(220, 220, 220, 0.88); --img-bg: #cfcfcf;
        }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background-color: var(--bg-color); color: var(--text-main); transition: 0.4s; padding-top: 145px; overflow-x: hidden; }
        body::before { content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: url('https://www.transparenttextures.com/patterns/carbon-fibre.png'); opacity: 0.8; z-index: -1; pointer-events: none; }
        
        .top-bar { position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; background: var(--nav-bg); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(128,128,128,0.2); }
        header { padding: 22px 0 8px; text-align: center; }
        header h1 { font-size: 1.6rem; text-transform: uppercase; letter-spacing: 6px; margin: 0; font-weight: 700; }
        nav { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 15px; padding: 12px 20px; }
        nav a { color: #888; text-decoration: none; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; transition: 0.3s; }
        nav a:hover, nav a.active { color: var(--accent); }
        #buscador { padding: 7px 15px; border-radius: 20px; border: 1px solid rgba(128,128,128,0.3); background: rgba(0,0,0,0.1); color: var(--text-main); font-size: 0.75rem; width: 160px; outline: none; }

        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 35px; margin-bottom: 50px; }
        .card { position: relative; background: var(--card-bg); border-radius: 10px; padding: 8px; transition: 0.3s ease; border: 1px solid rgba(128,128,128,0.15); }
        
        .img-container { width: 100%; aspect-ratio: 1 / 1; position: relative; border-radius: 6px; background: var(--img-bg); display: flex; align-items: center; justify-content: center; z-index: 1; overflow: visible; }
        .img-container img { max-width: 100%; max-height: 100%; object-fit: contain; transition: 0.3s ease-out; border-radius: 6px; }
        
        /* ICONOS DE ESTADO (Copa y Tilde) */
        .status-badge {
            position: absolute; top: -10px; width: 22px; height: 22px; z-index: 20;
            filter: drop-shadow(0 0 1px #000) drop-shadow(0 0 2px #000);
        }
        .badge-left { left: -10px; color: var(--accent); font-size: 18px; font-weight: bold; display: flex; align-items: center; justify-content: center; }
        .badge-right { right: -10px; }

        .tooltip {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 92%; background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(12px);
            color: #000; padding: 12px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            opacity: 0; visibility: hidden; z-index: 30; transition: 0.3s;
            text-align: center; border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .card:hover .tooltip { opacity: 1; visibility: visible; transform: translate(-50%, -52%); }
        
        .dl-btn { background: #000; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.65rem; font-weight: bold; text-decoration: none; display: inline-block; }
        .dl-btn:hover { background: var(--accent); color: #000; }

        .game-info { margin-top: 12px; text-align: center; font-size: 0.85rem; font-weight: bold; }
        .system-header { text-align: center; margin: 45px 0 25px; }
        .system-header img { max-height: 45px; filter: grayscale(1) brightness(1.8); }

        .theme-switch { position: relative; display: inline-block; width: 36px; height: 19px; }
        .theme-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #555; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 13px; width: 13px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .4s; }
        input:checked + .slider { background-color: var(--accent); }
        input:checked + .slider:before { transform: translateX(17px); }
    </style>
</head>
<body id="body">

<svg width="0" height="0" style="position:absolute;">
    <defs>
        <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#fff2ad;" />
            <stop offset="50%" style="stop-color:#ffa500;" />
            <stop offset="100%" style="stop-color:#b8860b;" />
        </linearGradient>
    </defs>
</svg>

<div class="top-bar">
    <header><h1>La Bóveda de Alejandro</h1></header>
    <nav>
        <a href="index.php?sys=TODOS" class="<?= $sistema == 'TODOS' ? 'active' : '' ?>">TODOS</a>
        <?php foreach($menu as $m): ?><a href="index.php?sys=<?= $m ?>" class="<?= $sistema == $m ? 'active' : '' ?>"><?= $m ?></a><?php endforeach; ?>
        <input type="text" id="buscador" placeholder="Buscar..." onkeyup="filtrarJuegos()">
        <label class="theme-switch"><input type="checkbox" id="theme-toggle" onclick="toggleTheme()"><span class="slider"></span></label>
    </nav>
</div>

<div class="container">
    <?php $current_system = ""; $first_grid = true;
    foreach ($juegos as $j):
        $logo_name = ($j['consola'] == 'OTROS') ? 'other' : strtolower($j['consola']);
        if ($sistema == 'TODOS' && $current_system != $j['consola']):
            if (!$first_grid) echo "</div>";
            $current_system = $j['consola']; $first_grid = false;
            echo "<div class='system-section'><div class='system-header'><img src='logos/$logo_name.png'></div><div class='grid'>";
        elseif ($sistema != 'TODOS' && $first_grid):
            $first_grid = false;
            echo "<div class='system-section'><div class='system-header'><img src='logos/$logo_name.png'></div><div class='grid'>";
        endif;
    ?>
        <div class="card" data-name="<?= strtolower(htmlspecialchars($j['nombre'])) ?>">
            <div class="tooltip">
                <div style="font-size:0.8rem; font-weight:700; margin-bottom:8px;"><?= nl2br(htmlspecialchars($j['notas'])) ?></div>
                <a href="savegames/<?= htmlspecialchars($j['partida']) ?>" class="dl-btn" download>DESCARGAR</a>
            </div>
            <div class="img-container">
                <img src="images/<?= htmlspecialchars($j['imagen']) ?>">
                
                <?php if($j['completado'] == 1): ?>
                    <div class="status-badge badge-left">✔</div>
                <?php endif; ?>

                <?php if($j['logros'] == 1): ?>
                    <svg class="status-badge badge-right" viewBox="0 0 576 512"><path fill="url(#goldGrad)" d="M552 64H448V24c0-13.3-10.7-24-24-24H152c-13.3 0-24 10.7-24 24v40H24C10.7 64 0 74.7 0 88v56c0 35.7 22.5 72.4 61.9 100.7 31.5 22.7 69.8 37.1 110 41.7C203.3 338.5 240 360 240 360v72h-48c-35.3 0-64 20.7-64 56v12c0 6.6 5.4 12 12 12h296c6.6 0 12-5.4 12-12v-12c0-35.3-28.7-56-64-56h-48v-72s36.7-21.5 68.1-73.6c40.3-4.6 78.6-19 110-41.7 39.3-28.3 61.9-65 61.9-100.7V88c0-13.3-10.7-24-24-24zM99.3 192.8C74.9 175.2 64 155.6 64 144v-16h64.2c1 32.6 5.8 61.2 12.8 86.2-15.1-5.2-29.2-12.4-41.7-21.4zM512 144c0 16.1-17.7 36.1-35.3 48.8-12.5 9-26.7 16.2-41.8 21.4 7-25 11.8-53.6 12.8-86.2H512v16z"></path></svg>
                <?php endif; ?>
            </div>
            <div class="game-info">
                <span><?= htmlspecialchars($j['nombre']) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$first_grid) echo "</div></div>"; ?>
</div>

<script>
function filtrarJuegos() {
    let input = document.getElementById('buscador').value.toLowerCase();
    let cards = document.getElementsByClassName('card');
    for (let card of cards) {
        let name = card.getAttribute('data-name');
        card.style.display = name.includes(input) ? "block" : "none";
    }
}
function toggleTheme() {
    const body = document.getElementById('body');
    const toggle = document.getElementById('theme-toggle');
    if (toggle.checked) { body.classList.add('light-mode'); localStorage.setItem('theme', 'light'); }
    else { body.classList.remove('light-mode'); localStorage.setItem('theme', 'dark'); }
}
window.onload = function() {
    if (localStorage.getItem('theme') === 'light') {
        document.getElementById('body').classList.add('light-mode');
        document.getElementById('theme-toggle').checked = true;
    }
};
</script>
</body>
</html>