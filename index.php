<?php
require 'config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// Поиск
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Получение услуг
$services = [];
try {
    $sql = "SELECT s.*, u.username, u.avatar_path 
            FROM services s
            JOIN users u ON s.created_by = u.id
            WHERE s.is_deleted = 0 AND u.is_banned = 0";
    
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    Logger::logError("Ошибка загрузки услуг: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Наши услуги</title>
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
            cursor: pointer;
        }
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .auth-buttons button, .auth-buttons a {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #cc0000;
            color: white;
        }
        .btn-primary:hover {
            background-color: #990000;
        }
        .btn-danger {
            background-color: #333;
            color: white;
            border: 1px solid #cc0000;
        }
        .btn-danger:hover {
            background-color: #222;
        }
        .btn-success {
            background-color: #990000;
            color: white;
        }
        .user-info {
            display: flex;
            align-items: center;
            margin-right: 15px;
            color: white;
        }
        .user-avatar-sm {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 8px;
            object-fit: cover;
        }
        main {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: #fff;
            margin-bottom: 20px;
        }
        .search-container {
            margin: 20px 0;
            text-align: center;
        }
        .search-container input {
            padding: 10px;
            width: 300px;
            max-width: 100%;
            border-radius: 4px;
            border: 1px solid #333;
            background-color: #222;
            color: #fff;
        }
        .search-container button {
            padding: 10px 20px;
            background-color: #cc0000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .services {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .service {
            background-color: #222;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #333;
        }
        .service:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(204,0,0,0.3);
            border-color: #cc0000;
        }
        .service-image-container {
            height: 200px;
            overflow: hidden;
            position: relative;
            background: #333;
        }
        .service-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .service:hover .service-thumbnail {
            transform: scale(1.05);
        }
        .no-image {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #999;
        }
        .service-details {
            padding: 15px;
        }
        .service-author {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .service-author a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        .service h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
            color: #fff;
        }
        .service-price {
            font-size: 20px;
            font-weight: bold;
            color: #ff0000;
            margin: 10px 0;
        }
        .service p {
            color: #ccc;
            line-height: 1.4;
            margin: 10px 0;
            font-size: 14px;
        }
        .view-more {
            display: inline-block;
            margin-top: 10px;
            color: #cc0000;
            text-decoration: none;
            font-weight: bold;
        }
        .delete-btn {
            background-color: #cc0000;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
        }
        .admin-badge {
            background-color: #990000;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 8px;
        }
        .message-btn {
            background-color: #990000;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
            display: inline-block;
            margin-right: 10px;
            transform: none !important;
        }
        .message-btn:hover {
            background-color: #770000;
        }
        .quick-message-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #333;
            border-radius: 8px;
        }
        .quick-message-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            resize: vertical;
            min-height: 80px;
            margin-bottom: 10px;
            background-color: #222;
            color: #fff;
        }
        .quick-message-form button {
            padding: 8px 15px;
            background-color: #cc0000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        #addServiceForm {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #222;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.5);
            z-index: 1000;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid #cc0000;
        }
        #addServiceForm h2 {
            margin-top: 0;
            color: #fff;
            text-align: center;
        }
        #addServiceForm .form-group {
            margin-bottom: 20px;
        }
        #addServiceForm label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #ccc;
        }
        #addServiceForm input[type="text"],
        #addServiceForm input[type="number"],
        #addServiceForm textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            background-color: #333;
            color: #fff;
        }
        #addServiceForm textarea {
            min-height: 120px;
            resize: vertical;
        }
        #addServiceForm input[type="file"] {
            width: 100%;
            padding: 10px;
            background: #333;
            border: 1px dashed #666;
            border-radius: 6px;
            color: #fff;
        }
        #addServiceForm .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #cc0000;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        #addServiceForm .submit-btn:hover {
            background-color: #990000;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.8);
            z-index: 999;
        }
        .close-form {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #ccc;
        }
        .close-form:hover {
            color: #fff;
        }
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #222;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.5);
            z-index: 1000;
            width: 90%;
            max-width: 400px;
            border: 1px solid #cc0000;
        }
        .modal h2 {
            margin-top: 0;
            color: #fff;
            text-align: center;
        }
        .modal .form-group {
            margin-bottom: 15px;
        }
        .modal input {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: #333;
            color: #fff;
        }
        .modal .submit-btn {
            width: 100%;
            padding: 10px;
            background-color: #cc0000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 22px;
            cursor: pointer;
            color: #ccc;
        }
        .modal .close:hover {
            color: #fff;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            opacity: 0;
            transition: opacity 0.5s;
            z-index: 1001;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        .notification.success {
            background-color: #009900;
        }
        .notification.error {
            background-color: #cc0000;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="btn-home">Домой</a>
        <h1>Наши услуги</h1>
        <div class="auth-buttons">
            <?php if ($currentUser): ?>
                <div class="user-info">
                    <?php if ($currentUser['avatar_path']): ?>
                        <img src="<?= htmlspecialchars($currentUser['avatar_path']) ?>" alt="Аватар" class="user-avatar-sm">
                    <?php endif; ?>
                    <span>
                        <?= htmlspecialchars($currentUser['username']) ?>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <span class="admin-badge">ADMIN</span>
                        <?php endif; ?>
                    </span>
                </div>
                <a href="profile.php" class="btn-primary">Профиль</a>
                <a href="messages.php" class="btn-primary">Сообщения</a>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn-success">Админ-панель</a>
                <?php endif; ?>
                <button id="logoutBtn" class="btn-danger">Выход</button>
                <button id="addServiceBtn" class="btn-primary">Добавить услугу</button>
            <?php else: ?>
                <button id="registerBtn" class="btn-primary">Регистрация</button>
                <button id="loginBtn" class="btn-primary">Вход</button>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <h1>Наши услуги</h1>
        
        <div class="search-container">
            <form method="GET" action="index.php">
                <input type="text" name="search" placeholder="Поиск услуг..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Найти</button>
            </form>
        </div>
        
        <div class="services">
            <?php if (empty($services)): ?>
                <p style="text-align:center;grid-column:1/-1;color:#ccc;">Услуги пока не добавлены</p>
            <?php else: ?>
                <?php foreach ($services as $service): ?>
                    <div class="service">
                        <div class="service-image-container">
                            <?php 
                            $imgStmt = $pdo->prepare("SELECT file_path FROM service_images WHERE service_id = ? LIMIT 1");
                            $imgStmt->execute([$service['id']]);
                            $image = $imgStmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <?php if ($image && file_exists($image['file_path'])): ?>
                                <img src="<?= htmlspecialchars($image['file_path']) ?>" class="service-thumbnail">
                            <?php else: ?>
                                <div class="no-image">Нет фото</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="service-details">
                            <div class="service-author">
                                <a href="profile.php?id=<?= $service['created_by'] ?>">
                                    <?php if ($service['avatar_path']): ?>
                                        <img src="<?= htmlspecialchars($service['avatar_path']) ?>" class="user-avatar-sm">
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($service['username']) ?></span>
                                </a>
                            </div>
                            <h2><?= htmlspecialchars($service['title']) ?></h2>
                            <div class="service-price"><?= htmlspecialchars($service['price']) ?> руб.</div>
                            <p><?= nl2br(htmlspecialchars(substr($service['description'], 0, 100))) ?>...</p>
                            <a href="service.php?id=<?= $service['id'] ?>" class="view-more">Подробнее</a>
                            
                            <?php if ($currentUser): ?>
                                <?php if ($currentUser['id'] != $service['created_by']): ?>
                                    <button class="message-btn" 
                                            onclick="event.preventDefault(); event.stopPropagation(); toggleMessageForm(<?= $service['created_by'] ?>, <?= $service['id'] ?>)">
                                        Написать сообщение
                                    </button>
                                    
                                    <div id="messageForm_<?= $service['created_by'] ?>" class="quick-message-form">
                                        <form onsubmit="sendQuickMessage(event, <?= $service['created_by'] ?>, <?= $service['id'] ?>)">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <textarea name="message" placeholder="Введите ваше сообщение..." required></textarea>
                                            <button type="submit">Отправить</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($currentUser && ($currentUser['id'] == $service['created_by'] || $currentUser['role'] == 'admin')): ?>
                                <form action="delete_service.php" method="POST" onsubmit="return confirm('Вы уверены?')">
                                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="delete-btn">Удалить</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Модальное окно регистрации -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeRegister">&times;</span>
            <h2>Регистрация</h2>
            <form id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-group">
                    <input type="text" id="regUsername" name="username" placeholder="Имя пользователя" required>
                </div>
                <div class="form-group">
                    <input type="email" id="regEmail" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" id="regPassword" name="password" placeholder="Пароль" required>
                </div>
                <button type="submit" class="submit-btn">Зарегистрироваться</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно входа -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeLogin">&times;</span>
            <h2>Вход</h2>
            <form id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-group">
                    <input type="text" id="loginUsername" name="username" placeholder="Имя пользователя" required>
                </div>
                <div class="form-group">
                    <input type="password" id="loginPassword" name="password" placeholder="Пароль" required>
                </div>
                <button type="submit" class="submit-btn">Войти</button>
            </form>
        </div>
    </div>

    <!-- Форма добавления услуги -->
    <div class="modal-overlay" id="serviceModalOverlay"></div>
    <div id="addServiceForm">
        <span class="close-form" id="closeServiceForm">&times;</span>
        <h2>Добавить услугу</h2>
        <form id="serviceForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="form-group">
                <label>Название услуги:</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Цена (руб.):</label>
                <input type="number" name="price" min="0" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Описание:</label>
                <textarea name="description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label>Фотографии (до 5 файлов):</label>
                <input type="file" name="photos[]" accept="image/*" multiple>
            </div>
            <button type="submit" class="submit-btn">Добавить услугу</button>
        </form>
    </div>

    <!-- Уведомления -->
    <div id="notification" class="notification"></div>

    <script>
        // Глобальные переменные
        const csrfToken = "<?= $_SESSION['csrf_token'] ?>";

        // Элементы управления
        const registerBtn = document.getElementById('registerBtn');
        const loginBtn = document.getElementById('loginBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        const addServiceBtn = document.getElementById('addServiceBtn');
        const registerModal = document.getElementById('registerModal');
        const loginModal = document.getElementById('loginModal');
        const closeRegister = document.getElementById('closeRegister');
        const closeLogin = document.getElementById('closeLogin');
        const notification = document.getElementById('notification');
        const serviceModalOverlay = document.getElementById('serviceModalOverlay');
        const addServiceForm = document.getElementById('addServiceForm');
        const closeServiceForm = document.getElementById('closeServiceForm');

        // Открытие/закрытие модальных окон
        if (registerBtn) registerBtn.onclick = () => registerModal.style.display = 'block';
        if (loginBtn) loginBtn.onclick = () => loginModal.style.display = 'block';
        if (closeRegister) closeRegister.onclick = () => registerModal.style.display = 'none';
        if (closeLogin) closeLogin.onclick = () => loginModal.style.display = 'none';
        
        window.onclick = (event) => {
            if (event.target === registerModal) registerModal.style.display = 'none';
            if (event.target === loginModal) loginModal.style.display = 'none';
        }

        // Управление формой добавления услуги
        if (addServiceBtn) {
            addServiceBtn.onclick = () => {
                addServiceForm.style.display = 'block';
                serviceModalOverlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            };
        }

        if (closeServiceForm) {
            closeServiceForm.onclick = () => {
                addServiceForm.style.display = 'none';
                serviceModalOverlay.style.display = 'none';
                document.body.style.overflow = '';
            };
        }

        if (serviceModalOverlay) {
            serviceModalOverlay.onclick = () => {
                addServiceForm.style.display = 'none';
                serviceModalOverlay.style.display = 'none';
                document.body.style.overflow = '';
            };
        }

        // Показать уведомление
        function showNotification(message, isSuccess = true) {
            notification.textContent = message;
            notification.className = isSuccess ? 'notification success' : 'notification error';
            notification.style.opacity = 1;
            
            setTimeout(() => {
                notification.style.opacity = 0;
            }, 3000);
        }

        // Функция для показа/скрытия формы сообщения
        function toggleMessageForm(userId, serviceId) {
            const form = document.getElementById(`messageForm_${userId}`);
            const allForms = document.querySelectorAll('.quick-message-form');
            
            // Скрываем все другие формы
            allForms.forEach(f => {
                if (f.id !== `messageForm_${userId}`) {
                    f.style.display = 'none';
                }
            });
            
            // Переключаем текущую форму
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
                        csrf_token: csrfToken
                    })
                });
                
                if (response.ok) {
                    showNotification('Сообщение отправлено!');
                    form.message.value = '';
                    document.getElementById(`messageForm_${recipientId}`).style.display = 'none';
                } else {
                    const error = await response.text();
                    showNotification('Ошибка: ' + error, false);
                }
            } catch (error) {
                showNotification('Ошибка сети', false);
            }
        }

        // Регистрация
        if (document.getElementById('registerForm')) {
            document.getElementById('registerForm').onsubmit = async function(e) {
                e.preventDefault();
                
                const formData = {
                    username: this.regUsername.value,
                    email: this.regEmail.value,
                    password: this.regPassword.value,
                    csrf_token: csrfToken
                };
                
                try {
                    const response = await fetch('register.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(formData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification('Регистрация успешна! Авторизуйтесь');
                        registerModal.style.display = 'none';
                    } else {
                        showNotification('Ошибка: ' + data.message, false);
                    }
                } catch (error) {
                    showNotification('Ошибка сети', false);
                }
            };
        }

        // Вход
        if (document.getElementById('loginForm')) {
            document.getElementById('loginForm').onsubmit = async function(e) {
                e.preventDefault();
                
                const formData = {
                    username: this.loginUsername.value,
                    password: this.loginPassword.value,
                    csrf_token: csrfToken
                };
                
                try {
                    const response = await fetch('login.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(formData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification('Вход выполнен успешно!');
                        loginModal.style.display = 'none';
                        
                        // Обновляем страницу через 1 секунду
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification('Ошибка: ' + data.message, false);
                    }
                } catch (error) {
                    showNotification('Ошибка сети', false);
                }
            };
        }

        // Выход
        if (logoutBtn) {
            logoutBtn.onclick = async () => {
                try {
                    const response = await fetch('logout.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ csrf_token: csrfToken })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.reload();
                    }
                } catch (error) {
                    showNotification('Ошибка выхода', false);
                }
            };
        }

        // Добавление услуги
        if (document.getElementById('serviceForm')) {
            document.getElementById('serviceForm').onsubmit = async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                try {
                    const response = await fetch('add_service.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification('Услуга успешно добавлена!');
                        addServiceForm.style.display = 'none';
                        serviceModalOverlay.style.display = 'none';
                        document.body.style.overflow = '';
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification('Ошибка: ' + data.message, false);
                    }
                } catch (error) {
                    showNotification('Ошибка сети', false);
                }
            };
        }
    </script>
</body>
</html>