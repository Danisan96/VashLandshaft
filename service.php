<?php
require 'config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$service_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.avatar_path, u.id as user_id 
        FROM services s
        JOIN users u ON s.created_by = u.id
        WHERE s.id = ? AND s.is_deleted = 0 AND u.is_banned = 0
    ");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        header("Location: index.php");
        exit;
    }

    $imgStmt = $pdo->prepare("SELECT id, file_path FROM service_images WHERE service_id = ? ORDER BY id ASC");
    $imgStmt->execute([$service_id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    Logger::logError("Service view error: " . $e->getMessage());
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($service['title']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #111;
            color: #fff;
        }
        header {
            background-color: #000;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #cc0000;
        }
        .btn-home {
            padding: 8px 16px;
            background-color: #cc0000;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            border: none;
        }
        .service-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #222;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            border: 1px solid #333;
        }
        .service-image-main {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .service-header {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }
        .service-author {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .service-author a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        .service-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .service-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .service-image:hover {
            transform: scale(1.03);
        }
        .price {
            font-size: 24px;
            font-weight: bold;
            color: #ff0000;
            margin: 15px 0;
        }
        .service-description {
            line-height: 1.6;
            margin-bottom: 20px;
            color: #eee;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            background-color: #333;
            color: white;
            border: 1px solid #cc0000;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #444;
        }
        .delete-btn {
            background-color: #cc0000;
            color: white;
            border: none;
        }
        .delete-btn:hover {
            background-color: #990000;
        }
        .message-btn {
            background-color: #990000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .message-btn:hover {
            background-color: #770000;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-image {
            max-width: 90%;
            max-height: 90%;
        }
        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
        }
        .quick-message-form {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #333;
            border-radius: 8px;
            width: 100%;
        }
        .quick-message-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 6px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 15px;
            background-color: #222;
            color: #fff;
            font-size: 16px;
        }
        .quick-message-form button {
            padding: 10px 20px;
            background-color: #cc0000;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="btn-home">Домой</a>
        <h1><?= htmlspecialchars($service['title']) ?></h1>
        <div></div>
    </header>

    <div class="service-container">
        <?php if (!empty($images)): ?>
            <img src="<?= htmlspecialchars($images[0]['file_path']) ?>" class="service-image-main" onclick="openModal('<?= htmlspecialchars($images[0]['file_path']) ?>')">
            
            <?php if (count($images) > 1): ?>
                <div class="service-images">
                    <?php for ($i = 1; $i < count($images); $i++): ?>
                        <img src="<?= htmlspecialchars($images[$i]['file_path']) ?>" class="service-image" onclick="openModal('<?= htmlspecialchars($images[$i]['file_path']) ?>')">
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="service-header">
            <div class="service-author">
                <a href="profile.php?id=<?= $service['user_id'] ?>">
                    <?php if ($service['avatar_path']): ?>
                        <img src="<?= htmlspecialchars($service['avatar_path']) ?>" class="author-avatar">
                    <?php else: ?>
                        <div class="author-avatar" style="background:#333;display:flex;align-items:center;justify-content:center;">
                            <span>👤</span>
                        </div>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($service['username']) ?></span>
                </a>
            </div>
            <div class="price"><?= htmlspecialchars($service['price']) ?> руб.</div>
        </div>

        <div class="service-description">
            <?= nl2br(htmlspecialchars($service['description'])) ?>
        </div>

        <div class="action-buttons">
            <a href="index.php" class="btn">Назад к услугам</a>
            
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['id'] != $service['user_id']): ?>
                <button class="message-btn" onclick="event.preventDefault(); event.stopPropagation(); toggleMessageForm(<?= $service['user_id'] ?>, <?= $service['id'] ?>)">
                    Написать сообщение
                </button>
                
                <div id="messageForm_<?= $service['user_id'] ?>" class="quick-message-form">
                    <form onsubmit="sendQuickMessage(event, <?= $service['user_id'] ?>, <?= $service['id'] ?>)">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <textarea name="message" placeholder="Введите ваше сообщение..." required></textarea>
                        <button type="submit">Отправить</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user']) && ($_SESSION['user']['id'] == $service['user_id'] || $_SESSION['user']['role'] == 'admin')): ?>
                <form action="delete_service.php" method="POST" onsubmit="return confirm('Вы уверены?')">
                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="btn delete-btn">Удалить услугу</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Модальное окно для просмотра изображений -->
    <div id="imageModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img id="modalImage" class="modal-image">
    </div>

    <script>
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = imageSrc;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('imageModal')) {
                closeModal();
            }
        }

        // Функция для показа/скрытия формы сообщения
        function toggleMessageForm(userId, serviceId) {
            const form = document.getElementById(`messageForm_${userId}`);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
            
            // Если форма показана, фокусируемся на текстовом поле
            if (form.style.display === 'block') {
                form.querySelector('textarea').focus();
            }
        }
        
        // Функция отправки быстрого сообщения
        async function sendQuickMessage(event, recipientId, serviceId) {
            event.preventDefault();
            
            const form = event.target;
            const message = form.message.value.trim();
            
            if (!message) {
                alert('Введите текст сообщения');
                return;
            }
            
            try {
                const response = await fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        recipient_id: recipientId,
                        service_id: serviceId,
                        message: message,
                        csrf_token: "<?= $_SESSION['csrf_token'] ?>"
                    })
                });
                
                if (response.ok) {
                    alert('Сообщение отправлено!');
                    form.message.value = '';
                    document.getElementById(`messageForm_${recipientId}`).style.display = 'none';
                } else {
                    const error = await response.text();
                    alert('Ошибка: ' + error);
                }
            } catch (error) {
                alert('Ошибка сети');
            }
        }
    </script>
</body>
</html>