<?php
//Array of possible recipients of the message
//
// key is the ID of the public key installed on the server with which to encrypt the message.
//   Public keys must already be installed on the server.
// email is the address to send the encrypted message to (not shown publicly)
// name is what is displayed in the "To" dropdown of the public page
$to_array = array(
	1 => array('key' => 'D5E42749', 'email' => 'stephen@example.com', 'name' => 'Stephen'),
	2 => array('key' => '5F8E0A09', 'email' => 'tom@example.com', 'name' => 'Tom'),
);
$welcome_message = "<strong style='font-size: 1.3em'>Welcome to Steve's message center. Simply input your message in the box below, or upload a file (or both) and click send, and it will be encrypted and sent to Steve. You may now also use this form to send a message to Tom.</strong><hr/>";
?>