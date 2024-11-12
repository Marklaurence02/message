<?php
session_start();

// Initialize messages array in session if it doesn't exist
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Example user ID
    $message = htmlspecialchars($_POST['message']); // Sanitize user input
    $_SESSION['messages'][] = ['user_id' => $user_id, 'message' => $message]; // Store message

    // Simulate a bot response (you can replace this with actual logic)
    $bot_response = "You said: " . $message; // Simple echo response
    $_SESSION['messages'][] = ['user_id' => 0, 'message' => $bot_response]; // Store bot response
}

// Get the user ID from the session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Example user ID
?>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="chat-container">
        <div class="header">Chat with us (User  ID: <span id="user-id"><?php echo $_SESSION['user_id']; ?></span>)</div>
        <div id="chat-box" class="message">
            <div class="welcome-message">
                <div class="welcome-sent">Welcome to Dine&Watch! I am your Dine&Watch virtual assistant. I'll be happy to answer your questions. For an uninterrupted conversation with us, please ensure that you have a stable internet connection. Please tell me what you would like to know:</div>
            </div>
        </div>
        <div class="options row gx-2 gy-2 p-3">
            <div class="col-12 option" data-question="No Refund Policy?">No Refund Policy?</div>
            <div class="col-12 option" data-question="What time Dine&Watch Open">What time Dine&Watch Open</div>
            <div class="col-12 option" data-question="FAQ">FAQ</div>
        </div>
        <div class="input-container">
            <input type="text" id="message-input" class="form-control me-2" placeholder="Type something..." required>
            <button id="send-btn" class="btn btn-primary">Send</button>
        </div>
        <div id="notification-badge" class="notification-badge"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $(document).ready(function() {
        $('#send-btn').click(function() {
            const message = $('#message-input').val();
            const userId = $('#user-id').text();
            if (message.trim() !== '') {
                // Send user message to the server
                $.ajax({
                    url: '/usermessagecontrol/get_users.php', // The PHP file that handles the chat logic
                    type: 'POST',
                    data: {
                        message: message,
                        user_id: userId
                    },
                    success: function(response) {
                        const jsonResponse = JSON.parse(response);
                        $('#chat-box').append('<div class="user-message">' + message + '</div>');
                        $('#chat-box').append('<div class="assistant-message">' + jsonResponse.response + '</div>');
                        $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight); // Scroll to the bottom
                        $('#message-input').val(''); // Clear the input
                    },
                    error: function() {
                        alert('Error sending message. Please try again.');
                    }
                });
            }
        });

        // Handle option clicks
        $('.option').click(function() {
            const question = $(this).data('question');
            $('#chat-box').append('<div class="user-message">' + question + '</div>'); // Display the user's message
            $('#message-input').val(question); // Set the input value to the question
            
            // Call the chatbot response directly instead of sending to staff
            const botResponse = getBotResponse(question);
            $('#chat-box').append('<div class="assistant-message">' + botResponse + '</div>');
            $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight); // Scroll to the bottom
            $('#message-input').val(''); // Clear the input
        });

        // Function to generate bot responses based on predefined questions
        function getBotResponse(question) {
            switch (question) {
                case "No Refund Policy?":
                    return "Our policy states that all sales are final. Please refer to our terms and conditions for more details.";
                case "What time Dine&Watch Open":
                    return "Dine&Watch opens at 11 AM and closes at 11 PM daily.";
                case "FAQ":
                    return "You can find answers to common questions in our FAQ section on our website.";
                default:
                    return "I'm sorry, I don't have an answer for that.";
            }
        }
    });
</script>



<style>
.chat-container {
    width: 100%;
    max-width: 400px;
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.header {
    background-color: #005bb5;
    color: #ffffff;
    padding: 16px;
    text-align: center;
    font-weight: bold;
}

.message {
    padding: 16px;
    background-color: #e8f4ff; /* Slightly blue background for the message area */
    color: #333;
    font-size: 14px;
    max-height: 300px;
    overflow-y: auto;
}

.welcome-message {
    margin-bottom: 10px;
}

.options .option {
    background-color: #d1e7dd;
    color: #005bb5;
    padding: 10px;
    border-radius: 10px;
    text-align: center;
    font-size: 14px;
    cursor: pointer;
    margin-bottom: 5px;
    border: 1px solid #ddd;
}

.options .option:hover {
    background-color: #005bb5;
    color: #ffffff;
}

.input-container input {
    border: none;
    outline: none;
}

.notification-badge {
    background-color: red;
    color: white;
    border-radius: 50%;
    padding: 5px;
    display: none;
    font-size: 0.8rem;
    position: absolute;
    top: 10px;
    right: 10px;
}

.welcome-sent{
    background-color: #d1e7dd;
    padding: 8px;
    border-radius: 10px;
    margin: 5px 0;
    text-align: left;
    font-size: 14px;
    color: #005bb5;
}

.sent {
    background-color: #d1e7dd;
    padding: 8px;
    border-radius: 10px;
    margin: 5px 0;
    text-align: left;
    font-size: 14px;
    color: #005bb5;
}

.received {
    background-color: #f8d7da;
    padding: 8px;
    border-radius: 10px;
    margin: 5px 0;
    text-align: left;
    font-size: 14px;
    color: #b23b3b;
}

.timestamp {
    font-size: 0.8em;
    color: #888;
    margin-left: 5px;
}
</style>
