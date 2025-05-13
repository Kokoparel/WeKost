<?php
// Start session and include configuration
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Validate required parameters
if (!isset($_GET['room']) || empty($_GET['room']) || 
    !isset($_GET['user']) || empty($_GET['user']) || 
    !isset($_GET['kost']) || empty($_GET['kost'])) {
    header("Location: chat_list.php");
    exit;
}

$room_id = mysqli_real_escape_string($conn, $_GET['room']);
$other_user_id = mysqli_real_escape_string($conn, $_GET['user']);
$kost_id = mysqli_real_escape_string($conn, $_GET['kost']);

// Verify the chat room exists and the user is authorized to access it
$query_check_room = "SELECT * FROM chat_rooms WHERE id_room = '$room_id'";
$result_check_room = mysqli_query($conn, $query_check_room);

if (mysqli_num_rows($result_check_room) == 0) {
    header("Location: chat_list.php");
    exit;
}

$room_data = mysqli_fetch_assoc($result_check_room);

// Make sure current user is part of this chat room
if ($user_role == 'mahasiswa' && $room_data['id_mahasiswa'] != $user_id) {
    header("Location: chat_list.php");
    exit;
}
if ($user_role == 'mitra' && $room_data['id_mitra'] != $user_id) {
    header("Location: chat_list.php");
    exit;
}

// Get information about the kost
$query_kost = "SELECT * FROM kost WHERE id_kost = '$kost_id'";
$result_kost = mysqli_query($conn, $query_kost);
$kost_data = mysqli_fetch_assoc($result_kost);

// Get information about the other user
$query_other_user = "SELECT * FROM users WHERE id_user = '$other_user_id'";
$result_other_user = mysqli_query($conn, $query_other_user);
$other_user = mysqli_fetch_assoc($result_other_user);

// Get chat history
$query_chat_history = "SELECT c.*, 
                       u_sender.nama_lengkap as sender_name, 
                       u_receiver.nama_lengkap as receiver_name
                       FROM chat c
                       JOIN users u_sender ON c.id_pengirim = u_sender.id_user
                       JOIN users u_receiver ON c.id_penerima = u_receiver.id_user
                       WHERE (c.id_pengirim = '$user_id' AND c.id_penerima = '$other_user_id' AND c.id_kost = '$kost_id')
                       OR (c.id_pengirim = '$other_user_id' AND c.id_penerima = '$user_id' AND c.id_kost = '$kost_id')
                       ORDER BY c.waktu_kirim ASC";
$result_chat_history = mysqli_query($conn, $query_chat_history);

// Mark all unread messages as read
if ($user_role == 'mahasiswa') {
    $update_read_status = "UPDATE chat SET status_baca = 'dibaca' 
                           WHERE id_penerima = '$user_id' 
                           AND id_pengirim = '$other_user_id' 
                           AND id_kost = '$kost_id' 
                           AND status_baca = 'belum_dibaca'";
    mysqli_query($conn, $update_read_status);
    
    // Update the unread count in chat_rooms table
    $update_unread = "UPDATE chat_rooms SET unread_count = 0 
                      WHERE id_room = '$room_id' AND id_mahasiswa = '$user_id'";
    mysqli_query($conn, $update_unread);
} else if ($user_role == 'mitra') {
    $update_read_status = "UPDATE chat SET status_baca = 'dibaca' 
                           WHERE id_penerima = '$user_id' 
                           AND id_pengirim = '$other_user_id' 
                           AND id_kost = '$kost_id' 
                           AND status_baca = 'belum_dibaca'";
    mysqli_query($conn, $update_read_status);
    
    // Update the unread count in chat_rooms table
    $update_unread = "UPDATE chat_rooms SET unread_count = 0 
                      WHERE id_room = '$room_id' AND id_mitra = '$user_id'";
    mysqli_query($conn, $update_unread);
}

// Process new message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && !empty($_POST['message'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Insert the new message
    $query_new_message = "INSERT INTO chat (id_pengirim, id_penerima, id_kost, pesan, status_baca) 
                          VALUES ('$user_id', '$other_user_id', '$kost_id', '$message', 'belum_dibaca')";
    
    if (mysqli_query($conn, $query_new_message)) {
        // Update the chat room with the latest message
        $update_room = "UPDATE chat_rooms SET 
                       last_message = '$message', 
                       last_update = NOW(), 
                       unread_count = unread_count + 1 
                       WHERE id_room = '$room_id'";
        mysqli_query($conn, $update_room);
        
        // Redirect to avoid form resubmission
        header("Location: chat_detail.php?room=$room_id&user=$other_user_id&kost=$kost_id");
        exit;
    }
}

// Determine redirect path based on user role
$back_link = ($user_role == 'mahasiswa') ? "chat_list.php" : "dashboard_mitra.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - WeKost</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            position: relative;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 15px;
            background-color: #0074E4;
            color: white;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .back-button {
            margin-right: 15px;
            color: white;
            text-decoration: none;
            font-size: 18px;
        }
        .chat-info {
            flex: 1;
            display: flex;
            align-items: center;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-avatar i {
            font-size: 20px;
            color: #0074E4;
        }
        .user-details {
            display: flex;
            flex-direction: column;
        }
        .user-name {
            font-weight: bold;
            font-size: 16px;
        }
        .kost-name {
            font-size: 12px;
            opacity: 0.9;
        }
        .kost-info {
            margin: 10px 15px;
            padding: 10px;
            background-color: #f1f8ff;
            border-radius: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        .kost-thumbnail {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            margin-right: 10px;
            object-fit: cover;
        }
        .kost-details {
            flex: 1;
        }
        .kost-title {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .kost-address {
            font-size: 12px;
            color: #666;
            margin-bottom: 2px;
        }
        .kost-price {
            font-weight: bold;
            color: #0074E4;
        }
        .view-details {
            color: #0074E4;
            text-decoration: none;
            font-size: 12px;
            padding: 5px 10px;
            border: 1px solid #0074E4;
            border-radius: 15px;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-bottom: 80px; /* Space for the input area */
        }
        .message {
            max-width: 75%;
            padding: 10px 15px;
            border-radius: 15px;
            font-size: 14px;
            position: relative;
        }
        .message.sent {
            align-self: flex-end;
            background-color: #0074E4;
            color: white;
            border-bottom-right-radius: 5px;
        }
        .message.received {
            align-self: flex-start;
            background-color: #e9e9eb;
            color: #333;
            border-bottom-left-radius: 5px;
        }
        .message-time {
            font-size: 10px;
            margin-top: 5px;
            opacity: 0.8;
            text-align: right;
        }
        .message.sent .message-time {
            color: rgba(255, 255, 255, 0.8);
        }
        .message.received .message-time {
            color: #777;
        }
        .message-status {
            font-size: 10px;
            margin-top: 2px;
            text-align: right;
        }
        .message.sent .message-status {
            color: rgba(255, 255, 255, 0.8);
        }
        .date-divider {
            text-align: center;
            margin: 10px 0;
            position: relative;
        }
        .date-divider:before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        .date-text {
            background-color: #fff;
            padding: 0 10px;
            position: relative;
            z-index: 2;
            font-size: 12px;
            color: #777;
        }
        .chat-input {
            position: fixed;
            bottom: 0;
            width: 100%;
            max-width: 480px;
            padding: 15px;
            background-color: #fff;
            border-top: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message-input {
            flex: 1;
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid #e0e0e0;
            outline: none;
            font-size: 14px;
        }
        .send-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #0074E4;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .send-button:disabled {
            background-color: #ccc;
        }
        .empty-state {
            text-align: center;
            padding: 30px 15px;
            color: #777;
        }
        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #ccc;
        }
        .empty-state p {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .empty-state small {
            font-size: 14px;
            opacity: 0.8;
        }
        /* Auto-scroll to bottom button */
        .scroll-bottom {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(0, 116, 228, 0.8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 5;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .scroll-bottom.visible {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Chat Header -->
        <div class="chat-header">
            <a href="<?php echo $back_link; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="chat-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo $other_user['nama_lengkap']; ?></div>
                    <div class="kost-name"><?php echo $kost_data['nama_kost']; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Kost Information Banner -->
        <div class="kost-info">
            <?php if(!empty($kost_data['foto_kost']) && file_exists($kost_data['foto_kost'])): ?>
                <img src="<?php echo $kost_data['foto_kost']; ?>" class="kost-thumbnail" alt="<?php echo $kost_data['nama_kost']; ?>">
            <?php else: ?>
                <img src="images/default_kost.jpg" class="kost-thumbnail" alt="Default Kost Image">
            <?php endif; ?>
            <div class="kost-details">
                <div class="kost-title"><?php echo $kost_data['nama_kost']; ?></div>
                <div class="kost-address"><?php echo substr($kost_data['alamat'], 0, 40) . (strlen($kost_data['alamat']) > 40 ? '...' : ''); ?></div>
                <div class="kost-price">Rp <?php echo number_format($kost_data['harga_sewa'], 0, ',', '.'); ?>/bulan</div>
            </div>
            <a href="detail_kost_mhs.php?id=<?php echo $kost_id; ?>" class="view-details">Detail</a>
        </div>
        
        <!-- Chat Messages Area -->
        <div class="chat-messages" id="chatMessages">
            <?php 
            $current_date = '';
            
            if (mysqli_num_rows($result_chat_history) > 0) {
                while ($chat = mysqli_fetch_assoc($result_chat_history)) {
                    // Add date divider if the date changes
                    $message_date = date('Y-m-d', strtotime($chat['waktu_kirim']));
                    if ($message_date != $current_date) {
                        $current_date = $message_date;
                        $display_date = date('d F Y', strtotime($chat['waktu_kirim']));
                        
                        // Check if it's today
                        if ($message_date == date('Y-m-d')) {
                            $display_date = 'Hari Ini';
                        } 
                        // Check if it's yesterday
                        else if ($message_date == date('Y-m-d', strtotime('-1 day'))) {
                            $display_date = 'Kemarin';
                        }
                        
                        echo '<div class="date-divider"><span class="date-text">' . $display_date . '</span></div>';
                    }
                    
                    // Determine if this message was sent by the current user
                    $is_sent = ($chat['id_pengirim'] == $user_id);
                    $message_class = $is_sent ? 'sent' : 'received';
                    $status_text = ($chat['status_baca'] == 'dibaca') ? 'Dibaca' : 'Terkirim';
            ?>
                <div class="message <?php echo $message_class; ?>">
                    <?php echo htmlspecialchars($chat['pesan']); ?>
                    <div class="message-time">
                        <?php echo date('H:i', strtotime($chat['waktu_kirim'])); ?>
                    </div>
                    <?php if ($is_sent): ?>
                    <div class="message-status">
                        <?php echo $status_text; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php 
                }
            } else {
            ?>
                <div class="empty-state">
                    <i class="far fa-comments"></i>
                    <p>Belum ada percakapan</p>
                    <small>Mulai chat dengan mengirim pesan</small>
                </div>
            <?php 
            }
            ?>
        </div>
        
        <!-- Scroll to Bottom Button -->
        <div class="scroll-bottom" id="scrollBottom" onclick="scrollToBottom()">
            <i class="fas fa-chevron-down"></i>
        </div>
        
        <!-- Message Input Area -->
        <form method="POST" class="chat-input" id="messageForm">
            <input type="text" name="message" id="messageInput" class="message-input" placeholder="Ketik pesan..." autocomplete="off" required>
            <button type="submit" class="send-button" id="sendButton">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>

    <script>
        // Function to scroll to bottom of messages
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Scroll to bottom when page loads
        window.onload = function() {
            scrollToBottom();
            
            // Show/hide scroll button based on scroll position
            const chatMessages = document.getElementById('chatMessages');
            const scrollButton = document.getElementById('scrollBottom');
            
            chatMessages.addEventListener('scroll', function() {
                const scrollPosition = chatMessages.scrollTop + chatMessages.clientHeight;
                const scrollHeight = chatMessages.scrollHeight;
                
                // Show button if not at bottom
                if (scrollHeight - scrollPosition > 100) {
                    scrollButton.classList.add('visible');
                } else {
                    scrollButton.classList.remove('visible');
                }
            });
            
            // Disable send button when empty
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            
            messageInput.addEventListener('input', function() {
                sendButton.disabled = this.value.trim() === '';
            });
            
            // Initial check
            sendButton.disabled = messageInput.value.trim() === '';
        };
        
        // Auto refresh chat every 30 seconds to check for new messages
        setInterval(function() {
            // Get current scroll position
            const chatMessages = document.getElementById('chatMessages');
            const isAtBottom = (chatMessages.scrollHeight - chatMessages.scrollTop) <= chatMessages.clientHeight + 50;
            
            // Store the URL with parameters
            const currentURL = window.location.href;
            
            // Reload the page
            window.location.href = currentURL;
            
            // After reload, scroll back to position or bottom if needed
            if (isAtBottom) {
                scrollToBottom();
            }
        }, 30000); // 30 seconds
    </script>
</body>
</html>