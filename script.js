
function closeNotification() {
    document.getElementById("notificationBar").style.display = "none";
}

// Show the notification bar if there's a message
document.addEventListener("DOMContentLoaded", function() {
    var notificationBar = document.getElementById("notificationBar");
    if (notificationBar) {
        notificationBar.style.display = "block";
        setTimeout(closeNotification, 5000); // Auto-hide after 5 seconds
    }
});
