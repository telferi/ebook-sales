<?php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Sender Setup</title>
</head>
<body>
    <h1>Email Sender Setup</h1>
    <form method="post" action="process_email_settings.php">
        <label for="emailServerHost">Email server host:</label>
        <input type="text" id="emailServerHost" name="emailServerHost"><br><br>
        
        <label for="smtpPort">SMTP Port:</label>
        <input type="text" id="smtpPort" name="smtpPort"><br><br>
        
        <label for="imapPort">IMAP Port:</label>
        <input type="text" id="imapPort" name="imapPort"><br><br>
        
        <label for="pop3Port">POP3 Port:</label>
        <input type="text" id="pop3Port" name="pop3Port"><br><br>
        
        <label for="senderEmail">Sender e-mail address:</label>
        <input type="email" id="senderEmail" name="senderEmail"><br><br>
        
        <label for="senderPassword">Sender password:</label>
        <input type="password" id="senderPassword" name="senderPassword"><br><br>
        
        <input type="submit" value="Submit">
    </form>
</body>
</html>