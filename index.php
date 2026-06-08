<?php
$dataFile = 'data.json';

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode(array()));
}
$data = json_decode(file_get_contents($dataFile), true);
if (!is_array($data)) {
    $data = array();
}

// --- تابع آیکون ---
function getIconByType($type) {
    switch ($type) {
        case 'urgent': return '🔴';
        case 'important': return '🟡';
        case 'routine': return '🔄';
        case 'future': return '☁️';
        default: return '•';
    }
}

// --- افزودن آیتم (در ابتدای لیست اصلی) ---
if (!empty($_POST['title'])) {
    $title = trim($_POST['title']);
    if ($title !== '') {
        $validTypes = array('urgent', 'important', 'routine', 'future');
        $type = in_array($_POST['type'], $validTypes) ? $_POST['type'] : 'future';
        $newItem = array(
            'id' => uniqid(),
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'type' => $type,
            'for_tomorrow' => false
        );
        array_unshift($data, $newItem);
        file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: index.php");
        exit;
    }
}

// --- ارسال به فردا ---
if (isset($_GET['add_tomorrow'])) {
    $id = $_GET['add_tomorrow'];
    foreach ($data as &$item) {
        if ($item['id'] === $id) {
            $item['for_tomorrow'] = true;
            break;
        }
    }
    file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit;
}

// --- حذف از فردا (بازگشت به لیست اصلی) ---
if (isset($_GET['remove_tomorrow'])) {
    $id = $_GET['remove_tomorrow'];
    foreach ($data as &$item) {
        if ($item['id'] === $id) {
            $item['for_tomorrow'] = false;
            break;
        }
    }
    file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit;
}

// --- حذف کامل ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $newData = array();
    foreach ($data as $item) {
        if ($item['id'] !== $id) {
            $newData[] = $item;
        }
    }
    $data = $newData;
    file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header("Location: index.php");
    exit;
}

// --- جدا کردن آیتم‌ها ---
$visibleUrgent = $visibleImportant = $visibleRoutine = $visibleFuture = array();
$tomorrowItems = array();

foreach ($data as $item) {
    if (!empty($item['for_tomorrow'])) {
        $tomorrowItems[] = $item;
    } else {
        if ($item['type'] === 'urgent') $visibleUrgent[] = $item;
        elseif ($item['type'] === 'important') $visibleImportant[] = $item;
        elseif ($item['type'] === 'routine') $visibleRoutine[] = $item;
        elseif ($item['type'] === 'future') $visibleFuture[] = $item;
    }
}

// --- معکوس کردن لیست فردا تا آخرین اضافه‌شده بالا باشد ---
$tomorrowItems = array_reverse($tomorrowItems);

// --- تابع نمایش کارت ---
function renderCard($item, $inTomorrow = false) {
    $id = $item['id'];
    $title = $item['title'];
    $icon = getIconByType($item['type']);
    echo '<div class="card">';
    if ($inTomorrow) {
        echo "<div class=\"card-text\"><strong>{$icon} {$title}</strong></div>";
    } else {
        echo "<div class=\"card-text\"><strong>{$title}</strong></div>";
    }
    echo '<div class="card-actions">';
    if ($inTomorrow) {
        echo "<a href='?remove_tomorrow={$id}' class='btn-done'>✓ انجام شد</a>";
    } else {
        echo "<a href='?add_tomorrow={$id}' class='btn-tomorrow'>→ فردا</a>";
    }
    echo "<a href='?delete={$id}' class='btn-delete'>✕</a>";
    echo '</div>';
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مدیریت ذهن</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Vazirmatn', 'Segoe UI', Tahoma, sans-serif;
        }
        body {
            background: #fafcfc;
            color: #333;
            padding: 16px;
            line-height: 1.5;
        }
        .container {
            max-width: 520px;
            margin: auto;
        }
        h1 {
            text-align: center;
            font-size: 20px;
            margin: 20px 0 24px;
            color: #2c3e50;
            font-weight: 600;
        }
        .section {
            margin-bottom: 28px;
        }
        h2 {
            font-size: 16px;
            margin-bottom: 14px;
            color: #2c3e50;
            font-weight: 600;
        }
        .card {
            background: white;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .card:hover {
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }
        .card-text {
            flex: 1;
            min-width: 0;
            padding-left: 12px;
        }
        .card-text strong {
            font-size: 16px;
            line-height: 1.4;
            word-break: break-word;
        }
        .card-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }
        .btn-tomorrow,
        .btn-done,
        .btn-delete {
            text-decoration: none;
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
            white-space: nowrap;
        }
        .btn-tomorrow { background: #e3f2fd; color: #1976d2; }
        .btn-done { background: #e8f5e9; color: #2e7d32; }
        .btn-delete { background: #ffebee; color: #c62828; }

        .empty {
            text-align: center;
            color: #999;
            padding: 20px 0;
            font-size: 14px;
        }

        form.add-form {
            background: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 28px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        }
        input, select, button {
            width: 100%;
            padding: 14px;
            margin: 8px 0;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            background: #fafafa;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #90a4ae;
            background: white;
        }
        button {
            background: #2e7d32;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
            margin-top: 12px;
        }
        button:hover {
            background: #1b5e20;
        }

        @media (max-width: 480px) {
            .card {
                padding: 14px;
            }
            .card-text strong {
                font-size: 15px;
            }
            .btn-tomorrow,
            .btn-done,
            .btn-delete {
                font-size: 11px;
                padding: 4px 8px;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <h1>مدیریت ذهن 🧠 </h1>

    <form class="add-form" method="post">
        <input type="text" name="title" placeholder="چیزی که نباید فراموش بشه..." required autofocus>
        <select name="type">
            <option value="urgent">🔴 کارهای مهم فوری</option>
            <option value="important">🟡 کارهای مهم ولی غیر فوری</option>
            <option value="routine">🔄 روال هفتگی</option>
            <option value="future">☁️ آینده (ایده‌ها و بلندمدت)</option>
        </select>
        <button>ذخیره</button>
    </form>

    <!-- فردا -->
    <div class="section">
        <h2>✅ فردا</h2>
        <?php if (count($tomorrowItems) === 0): ?>
            <div class="empty">چیزی برای فردا انتخاب نشده.</div>
        <?php else: ?>
            <?php foreach ($tomorrowItems as $item): ?>
                <?php renderCard($item, true); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- فوری -->
    <div class="section">
        <h2>🔴 فوری</h2>
        <?php if (count($visibleUrgent) === 0): ?>
            <div class="empty">کار فوری‌ای نداری.</div>
        <?php else: ?>
            <?php foreach ($visibleUrgent as $item): ?>
                <?php renderCard($item); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- مهم -->
    <div class="section">
        <h2>🟡 مهم</h2>
        <?php if (count($visibleImportant) === 0): ?>
            <div class="empty">کار مهمی ثبت نشده.</div>
        <?php else: ?>
            <?php foreach ($visibleImportant as $item): ?>
                <?php renderCard($item); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- روال -->
    <div class="section">
        <h2>🔄 روال هفتگی</h2>
        <?php if (count($visibleRoutine) === 0): ?>
            <div class="empty">روالی ثبت نشده.</div>
        <?php else: ?>
            <?php foreach ($visibleRoutine as $item): ?>
                <?php renderCard($item); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- آینده -->
    <div class="section">
        <h2>☁️ آینده</h2>
        <?php if (count($visibleFuture) === 0): ?>
            <div class="empty">ایده‌ای برای آینده نداری!</div>
        <?php else: ?>
            <?php foreach ($visibleFuture as $item): ?>
                <?php renderCard($item); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>