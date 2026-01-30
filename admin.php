<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $db = new PDO("sqlite:" . __DIR__ . "/database.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("Error: " . $e->getMessage()); }

session_start();
$menu = ["GB", "GBC", "GBA", "NDS", "3DS", "SNES", "N64", "GC", "Wii", "WiiU", "Switch", "SEGA", "SONY", "OTROS"];

function limpiarSistema($string) {
    return str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $string);
}

// LÓGICA DE BORRADO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] != 'guardar') {
    $ids = [];
    if ($_POST['accion'] == 'eliminar_masivo' && !empty($_POST['juegos_ids'])) { $ids = $_POST['juegos_ids']; }
    elseif ($_POST['accion'] == 'eliminar_unico' && isset($_POST['id'])) { $ids = [$_POST['id']]; }

    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt_files = $db->prepare("SELECT imagen, partida FROM juegos WHERE id IN ($placeholders)");
        $stmt_files->execute($ids);
        foreach ($stmt_files->fetchAll(PDO::FETCH_ASSOC) as $f) {
            if (file_exists(__DIR__ . "/images/" . $f['imagen'])) @unlink(__DIR__ . "/images/" . $f['imagen']);
            if (file_exists(__DIR__ . "/savegames/" . $f['partida'])) @unlink(__DIR__ . "/savegames/" . $f['partida']);
        }
        $db->prepare("DELETE FROM juegos WHERE id IN ($placeholders)")->execute($ids);
    }
    header("Location: admin.php?deleted=1"); exit();
}

// LÓGICA DE GUARDADO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'guardar') {
    try {
        $nombre = $_POST['nombre'];
        $consola = $_POST['consola'];
        $_SESSION['ultima_consola'] = $consola;
        
        $img_dir = __DIR__ . '/images/';
        $sav_dir = __DIR__ . '/savegames/';
        
        $ext_img = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nuevo_img = limpiarSistema($nombre) . " (" . $consola . ")." . $ext_img;
        $nom_partida = limpiarSistema($_FILES['partida']['name']);

        @move_uploaded_file($_FILES['imagen']['tmp_name'], $img_dir . $nuevo_img);
        @move_uploaded_file($_FILES['partida']['tmp_name'], $sav_dir . $nom_partida);

        $stmt = $db->prepare("INSERT INTO juegos (nombre, consola, imagen, partida, notas, completado, logros) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nombre, $consola, $nuevo_img, $nom_partida, $_POST['notas'], 
            isset($_POST['completado']) ? 1 : 0, isset($_POST['logros']) ? 1 : 0
        ]);
        
        header("Location: admin.php?success=1"); exit();
    } catch (Exception $e) { die("Error: " . $e->getMessage()); }
}

$juegos_registrados = $db->query("SELECT * FROM juegos ORDER BY consola, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$ultima_consola = $_SESSION['ultima_consola'] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - La Bóveda de Alejandro</title>
    <style>
        :root { --bg-color: #050505; --card-bg: rgba(20, 20, 20, 0.7); --accent: #ff8c00; --danger: #d32f2f; --border: rgba(255,255,255,0.15); }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-color: var(--bg-color); 
            color: #f4f1ea; 
            margin: 0; 
            padding: 40px 20px;
            /* Nueva imagen de fondo con estrellas y cuadrícula para notar el parallax */
            background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://w0.peakpx.com/wallpaper/241/653/HD-wallpaper-grid-landscape-retro-futuristic-scifi-digital-art.jpg');
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
        }

        .container { max-width: 950px; margin: 0 auto; position: relative; }
        
        /* Efecto Esmerilado (Glassmorphism) */
        .form-container, .system-group { 
            background: var(--card-bg); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px); 
            border: 1px solid var(--border); 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.6);
            margin-bottom: 30px;
        }

        .form-container { padding: 20px; }
        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        input, select, textarea { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: rgba(0,0,0,0.4); color: white; box-sizing: border-box; font-size: 0.9rem; }
        textarea { grid-column: span 2; height: 60px; }
        
        .status-checks { grid-column: span 2; display: flex; gap: 25px; padding: 5px; }
        .check-item { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .check-item input { width: 16px; height: 16px; margin: 0; accent-color: var(--accent); }
        .svg-icon { width: 18px; height: 18px; vertical-align: middle; }

        .btn-add { grid-column: span 2; padding: 12px; background: var(--accent); border: none; font-weight: bold; cursor: pointer; border-radius: 6px; text-transform: uppercase; color: #000; transition: 0.3s; }
        .btn-add:hover { background: #e67e00; transform: translateY(-2px); }

        #floating-delete {
            position: fixed; left: 40px; top: 50%; transform: translateY(-50%); 
            background: var(--danger); color: white; border: 2px solid rgba(255,255,255,0.1); 
            width: 80px; height: 80px; border-radius: 50%; cursor: pointer; display: none; 
            flex-direction: column; align-items: center; justify-content: center; 
            box-shadow: 0 10px 30px rgba(211, 47, 47, 0.4); z-index: 999;
        }

        .system-group { overflow: hidden; }
        .system-title { background: rgba(0,0,0,0.4); padding: 10px; text-align: center; border-bottom: 1px solid var(--border); }
        .system-title img { max-height: 25px; filter: grayscale(1) brightness(1.5); }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(0,0,0,0.2); color: var(--accent); font-size: 0.7rem; text-transform: uppercase; padding: 10px 12px; text-align: left; }
        td { padding: 8px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; font-size: 0.95rem; }
        
        .td-actions { display: flex; align-items: center; justify-content: center; gap: 15px; }
        .btn-x { background: none; border: none; color: var(--danger); font-size: 1.7rem; cursor: pointer; padding: 0; margin-top: -3px; line-height: 1; }
        .check-borrar { width: 18px; height: 18px; margin: 0; cursor: pointer; accent-color: var(--accent); }

        .img-td img { width: 50px; height: 50px; object-fit: contain; background: #000; border-radius: 5px; border: 1px solid rgba(255,255,255,0.1); }
        
        .status-icons { display: flex; gap: 12px; align-items: center; }
        .icon-off { opacity: 0.1; filter: grayscale(1); }
    </style>
</head>
<body>

<svg width="0" height="0" style="position:absolute;">
    <defs>
        <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#fff2ad;" />
            <stop offset="50%" style="stop-color:#ffa500;" />
            <stop offset="100%" style="stop-color:#b8860b;" />
        </linearGradient>
    </defs>
</svg>

<button type="button" id="floating-delete" onclick="if(confirm('¿Borrar seleccionados?')) document.getElementById('form-masivo').submit();">
    <span>🗑️</span><small>BORRAR</small>
</button>

<div class="container">
    <div class="form-container">
        <h3 style="text-align:center; color:var(--accent); margin:0 0 15px; font-size:1.1rem; letter-spacing: 2px;">NUEVA ENTRADA</h3>
        <form action="admin.php" method="POST" enctype="multipart/form-data" class="grid-form">
            <input type="hidden" name="accion" value="guardar">
            <input type="text" name="nombre" placeholder="Nombre del Juego" required>
            <select name="consola" required>
                <option value="" disabled <?= $ultima_consola == "" ? "selected" : "" ?>>Consola</option>
                <?php foreach($menu as $s): ?>
                    <option value="<?= $s ?>" <?= $ultima_consola == $s ? "selected" : "" ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <input type="file" name="imagen" accept="image/*" required>
            <input type="file" name="partida" required>
            <textarea name="notas" placeholder="Notas opcionales..."></textarea>
            
            <div class="status-checks">
                <label class="check-item">
                    <input type="checkbox" name="completado"> 
                    <span style="color:var(--accent); font-weight:bold; font-size:1.1rem;">✔</span>
                </label>
                <label class="check-item">
                    <input type="checkbox" name="logros"> 
                    <svg class="svg-icon" viewBox="0 0 576 512"><path fill="url(#goldGrad)" d="M552 64H448V24c0-13.3-10.7-24-24-24H152c-13.3 0-24 10.7-24 24v40H24C10.7 64 0 74.7 0 88v56c0 35.7 22.5 72.4 61.9 100.7 31.5 22.7 69.8 37.1 110 41.7C203.3 338.5 240 360 240 360v72h-48c-35.3 0-64 20.7-64 56v12c0 6.6 5.4 12 12 12h296c6.6 0 12-5.4 12-12v-12c0-35.3-28.7-56-64-56h-48v-72s36.7-21.5 68.1-73.6c40.3-4.6 78.6-19 110-41.7 39.3-28.3 61.9-65 61.9-100.7V88c0-13.3-10.7-24-24-24zM99.3 192.8C74.9 175.2 64 155.6 64 144v-16h64.2c1 32.6 5.8 61.2 12.8 86.2-15.1-5.2-29.2-12.4-41.7-21.4zM512 144c0 16.1-17.7 36.1-35.3 48.8-12.5 9-26.7 16.2-41.8 21.4 7-25 11.8-53.6 12.8-86.2H512v16z"></path></svg>
                </label>
            </div>
            <button type="submit" class="btn-add">Añadir Juego</button>
        </form>
    </div>

    <form id="form-masivo" action="admin.php" method="POST">
        <input type="hidden" name="accion" value="eliminar_masivo">
        <?php $current_sys = ""; foreach($juegos_registrados as $j): 
            if($current_sys != $j['consola']): 
                if($current_sys != "") echo "</tbody></table></div>";
                $current_sys = $j['consola']; 
                $logo_name = ($current_sys == 'OTROS') ? 'other' : strtolower($current_sys);
        ?>
            <div class="system-group">
                <div class="system-title">
                    <img src="logos/<?= $logo_name ?>.png" alt="<?= $current_sys ?>">
                </div>
                <table>
                    <thead><tr><th style="width:90px; text-align:center;">Acción</th><th style="width:70px;">Img</th><th>Nombre</th><th style="width:100px;">Estado</th></tr></thead>
                    <tbody>
            <?php endif; ?>
                <tr>
                    <td>
                        <div class="td-actions">
                            <input type="checkbox" name="juegos_ids[]" value="<?= $j['id'] ?>" class="check-borrar" onchange="toggleFloating()">
                            <button type="button" class="btn-x" onclick="eliminarUnico(<?= $j['id'] ?>)">×</button>
                        </div>
                    </td>
                    <td class="img-td"><img src="images/<?= htmlspecialchars($j['imagen']) ?>"></td>
                    <td><strong><?= htmlspecialchars($j['nombre']) ?></strong></td>
                    <td>
                        <div class="status-icons">
                            <span class="<?= $j['completado'] ? '' : 'icon-off' ?>" style="color:var(--accent); font-weight:bold; font-size:1.1rem;">✔</span>
                            <svg class="svg-icon <?= $j['logros'] ? '' : 'icon-off' ?>" viewBox="0 0 576 512"><path fill="url(#goldGrad)" d="M552 64H448V24c0-13.3-10.7-24-24-24H152c-13.3 0-24 10.7-24 24v40H24C10.7 64 0 74.7 0 88v56c0 35.7 22.5 72.4 61.9 100.7 31.5 22.7 69.8 37.1 110 41.7C203.3 338.5 240 360 240 360v72h-48c-35.3 0-64 20.7-64 56v12c0 6.6 5.4 12 12 12h296c6.6 0 12-5.4 12-12v-12c0-35.3-28.7-56-64-56h-48v-72s36.7-21.5 68.1-73.6c40.3-4.6 78.6-19 110-41.7 39.3-28.3 61.9-65 61.9-100.7V88c0-13.3-10.7-24-24-24zM99.3 192.8C74.9 175.2 64 155.6 64 144v-16h64.2c1 32.6 5.8 61.2 12.8 86.2-15.1-5.2-29.2-12.4-41.7-21.4zM512 144c0 16.1-17.7 36.1-35.3 48.8-12.5 9-26.7 16.2-41.8 21.4 7-25 11.8-53.6 12.8-86.2H512v16z"></path></svg>
                        </div>
                    </td>
                </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </form>
</div>

<form id="form-unico" action="admin.php" method="POST">
    <input type="hidden" name="accion" value="eliminar_unico">
    <input type="hidden" name="id" id="id-unico">
</form>

<script>
    // Parallax: El fondo se mueve un 50% respecto al scroll
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        document.body.style.backgroundPositionY = -(scrolled * 0.5) + 'px';
    });

    function toggleFloating() {
        const btn = document.getElementById('floating-delete');
        btn.style.display = document.querySelectorAll('.check-borrar:checked').length > 0 ? 'flex' : 'none';
    }
    function eliminarUnico(id) {
        if(confirm('¿Eliminar juego?')) {
            document.getElementById('id-unico').value = id;
            document.getElementById('form-unico').submit();
        }
    }
</script>
</body>
</html>