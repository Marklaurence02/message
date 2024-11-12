const MessageBox = document.getElementById('message-box');
let latestMessageTimestamp = null;  // Track the latest message timestamp
let selectedUserId = null;  // Track the selected user ID

document.addEventListener('DOMContentLoaded', () => {
    loadUserRecentMessages();
});

function loadUserRecentMessages() {
    const userItems = document.querySelectorAll('.user-item');

    userItems.forEach(userItem => {
        const userId = userItem.getAttribute('data-user-id');
        const recentMessageElement = userItem.querySelector('.recent-message');
        const blueDotElement = userItem.querySelector('.blue-dot');
        const messageDateElement = userItem.querySelector('.message-date'); // Element for the date
        const usernameElement = userItem.querySelector('.username'); // Element for the username

        // Fetch the most recent message and unread count for each user
        fetch(`/messagepage/get_recent_message.php?user_id=${userId}`)
            .then(response => {
                if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
                return response.json();
            })
            .then(data => {
                // Check if the data is available and display the most recent message
                if (data && data.recent_message) {
                    recentMessageElement.textContent = data.recent_message; // Update the recent message text

                    // Format the timestamp (assuming it's in `YYYY-MM-DD HH:MM:SS` format)
                    const messageDate = new Date(data.timestamp);
                    const formattedDate = messageDate.toLocaleString(); // Format the date as per local timezone

                    // Display the formatted date
                    messageDateElement.textContent = formattedDate;

                    // Update the username with the role in the format username (role)
                    if (data.name && data.role) {
                        usernameElement.innerHTML = `${data.name} <span class="role">(${data.role})</span>`;
                    }

                    // Check if there are unread messages and display the blue dot
                    if (data.unread_count > 0) {
                        blueDotElement.classList.remove('d-none');
                        userItem.classList.add('border-blue');  // Add blue border for unread messages
                    } else {
                        blueDotElement.classList.add('d-none');
                        userItem.classList.remove('border-blue');
                    }
                } else {
                    // Fallback when no message data is available
                    recentMessageElement.textContent = "No messages yet";
                    messageDateElement.textContent = ""; // Clear the date if no message
                    blueDotElement.classList.add('d-none');
                    userItem.classList.remove('border-blue');
                }
            })
            .catch(error => {
                console.error("Error loading recent message :", error);
                recentMessageElement.textContent = "No messages yet";
                messageDateElement.textContent = ""; // Clear the date if error
                blueDotElement.classList.add('d-none');
                userItem.classList.remove('border-blue');
            });
    });
}

function openConversation(userId, username) {
    selectedUserId = userId;  // Corrected variable name
    latestMessageTimestamp = null;
    document.getElementById('conversation-username').textContent = username;

    // Hide the user list and show the conversation view
    document.getElementById('user-list').classList.add('d-none');
    document.getElementById('conversation-view').classList.remove('d-none');

    // Clear unread indicator
    const userItem = document.querySelector(`.user-item[data-user-id="${userId}"]`);
    const blueDotElement = userItem.querySelector('.blue-dot');
    blueDotElement.classList.add('d-none');  // Hide blue dot
    userItem.classList.remove('border-blue');  // Remove blue border

    // Clear the message box before loading new messages
    MessageBox.innerHTML = ''; // Clear previous messages

    loadMessages();
}

function loadMessages() {
    if (!selectedUserId) {  // Corrected variable name
        console.error("No user selected.");
        return;
    }

    const isAtBottom = MessageBox.scrollTop >= (MessageBox.scrollHeight - MessageBox.clientHeight - 20);
    const url = `/messagepage/get_messages.php?receiver=${selectedUserId}` +  // Corrected variable name
                (latestMessageTimestamp ? `&after=${latestMessageTimestamp}` : '');

    let lastMessageMinute = null;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.statusText}`);
            }
            return response.json();
        })
        .then(messages => {
            if (!messages.length) return;

            messages.forEach(msg => {
                const msgDate = new Date(msg.timestamp);
                const msgTimeString = msgDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const currentMessageMinute = msgDate.getHours() * 60 + msgDate.getMinutes();

                // Check if the message is in a new minute compared to the last message
                if (lastMessageMinute !== currentMessageMinute) {
                    const separatorElement = document.createElement('div');
                    separatorElement.classList.add('separator', 'separator-centered');  // Add centered class
                    separatorElement.innerHTML = `<small class="timestamp">${msgTimeString}</small>`;
                    MessageBox.appendChild(separatorElement);
                }

                // Create a new message bubble
                const messageElement = document.createElement('div');
                messageElement.classList.add('message', msg.sender_id === selectedUserId ? 'received' : 'sent');  // Corrected variable name
                
                messageElement.innerHTML = `
                    <p><strong>${msg.first_name}:</strong> ${msg.message}</p>
                `;
                MessageBox.appendChild(messageElement);

                lastMessageMinute = currentMessageMinute;
            });

            if (messages.length > 0) {
                latestMessageTimestamp = messages[messages.length - 1].timestamp;
            }

            if (isAtBottom) MessageBox.scrollTop = MessageBox.scrollHeight;
        })
        .catch(error => console.error("Error loading messages:", error));
}

// Send a message
function sendMessage(event) {
    event.preventDefault();
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();

    if (!message || !selectedUserId) {  // Corrected variable name
        alert("Please enter a message.");
        return;
    }

    fetch("/messagepage/post_message.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `receiver=${selectedUserId}&message=${encodeURIComponent(message)}`  // Corrected variable name
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            messageInput.value = '';  // Clear input field
            loadMessages();  // Reload messages after sending
        } else {
            console.error("Error sending message:", result.message);
        }
    })
    .catch(error => console.error("Error sending message:", error));
}

// Switch back to user list view
function backToUserList() {
    document.getElementById('user-list').classList.remove('d-none');
    document.getElementById('conversation-view').classList.add('d-none');
    selectedUserId = null;  // Reset selected user
}
