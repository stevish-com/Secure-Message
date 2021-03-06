<?php
/*
Version 1.0
See Readme for description

    Copyright (C) 2014 Stephen Narwold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

if ( $_SERVER['HTTPS'] != "on" ) {
	die("This page cannot be viewed without a secure connection. Please go to <a href='https://s.stevish.com/message'>https://s.stevish.com/message</a>.");
}
if ( !file_exists("options.php") ) {
	die("Options.php is missing! Please edit options_example.php, set the options, and save it as options.php");
}
require_once("gpg_encrypt.php");
require_once("options.php");

$message = false;
$starttime = microtime(true);
if (!empty($_POST['message'])) {
	
	//VARSEC
	if (!array_key_exists($_POST['to'], $to_array)) {
		$to = 1;
	} else {
		$to = $_POST['to'];
	}
	$from = preg_replace("/[^a-zA-Z0-9\.\-\_\@]/", "", $_POST['email']);
	$subject = preg_replace("/[^a-zA-Z0-9\.\-\_\@\'\"\:\;\,\.\?\/\\\[\]\{\}\+\=\&\(\)\!\#\$\%\^\*\s]/", "?", $_POST['subject']);
	//END VARSEC
	
	$rawmessage = $_POST[message];
	$gpg = gpg_encrypt($rawmessage, '/usr/bin/gpg' , '/home/stevish/.gnupg', $to_array[$to]['key']);
	// $gpg is an array containing
	// $gpg[0] encrypted output (STDOUT)
	// $gpg[1] warnings and notices (STDERR)
	// $gpg[2] exit status from gpg

	// test gpg's exit status
	if ($gpg[0]) {
		$message = $gpg[0];
	} else {
		// if the gpg command returned non-zero
		// then display gpg's diagnostic output
		$messagerr = "<pre>\n" . $gpg[1] . '</pre>';
	}
}
$filedata = false;
if (!$messagerr && $_FILES['attfile']['name']) {
	$filename = $_FILES['attfile']['name'] . ".gpg";
	
	//Open the uploaded file into a variable and destroy the file
	$rawfiledata = file_get_contents($_FILES['attfile']['tmp_name']);
	unlink($_FILES['attfile']['tmp_name']);
	
	//Encrypt file content
	if ($rawfiledata) {
		$gpg = gpg_encrypt($rawfiledata, '/usr/bin/gpg' , '/home/stevish/.gnupg', $to_array[$to]['key']);
	} else {
		$filerr = true;
	}
	
	unset($rawfiledata);
	
	if ($gpg[2] == '0') {
		$filedata = $gpg[0];
	} else {
		// if the gpg command returned non-zero
		// then display gpg's diagnostic output
		$filerr = "<pre>\n" . $gpg[1] . '</pre>';
	}
}

if (!$messagerr && !$filerr && ($filedata || $message)) {
	$id = intval(str_replace(array("\r", "\n"), "", file_get_contents('id.php')));
	file_put_contents('id.php', $id+1);

	$endtime = microtime(true);
	$totaltime = $endtime - $starttime;
	
	if ($filedata) {
		send_email($to_array[$to]['email'], "From s.stevish.com/message\nMessage ID: $id\nContains attachment\n\n" . $message, $from, $subject, $filename, $filedata, "application/gnupg");
		echo "Message (id#<strong>$id</strong>) and file successfully encrypted and sent to {$to_array[$to]['name']} in $totaltime seconds. Make a note of the id# if you need delivery confirmation.<hr/><a href='/message/'>Send another message</a>";
		$success = true;
	} else {
		send_email($to_array[$to]['email'], "From s.stevish.com/message\nMessage ID: $id\n\n" . $message, $from, $subject);
		echo "Message (id#<strong>$id</strong>) successfully encrypted and sent to {$to_array[$to]['name']} in $totaltime seconds. Make a note of the id# if you need delivery confirmation.<hr/><a href='/message/'>Send another message</a>";
		$success = true;
	}
} elseif (!$messagerr && !$filerr && !$filedata && !$message) {
	echo $welcome_message;
} else {
	echo "No message was sent. <br/>";
	if ($filerr) {
		echo "<br/><br/>File Error:<br/><pre>$filerr</pre>";
	}
	if ($messagerr) {
		echo "<br/><br/>Message error: <br/><pre>$messagerr</pre>";
	}
	echo "<hr/>";
}
if (!$success) {
	?>


	<html><body>
	<form method="post" enctype="multipart/form-data">
	<input type="hidden" name="set" value="1" />
	<div style="background-color: #f88; padding: 10px;"><strong><em>WARNING! These three fields will not be encrypted. Please do not put any sensitive information in these fields.</em></strong><br/>
	To: <select name="to">
		<?php
		foreach($to_array as $k => $a)
			echo "<option value='$k'>{$a['name']}</option>\n";
		?>
		</select><br/>
	Your E-mail, So I can contact you: <input type="text" name="email" /><br/>
	Subject: <input type="text" name="subject" size="60" /><br/></div><hr/>
	Message:<br/>
	<textarea name="message" rows="10" cols="70"></textarea><br/><br/>
	File: <input type="file" name="attfile" /><br/><br/>
	<input type="submit" value="Encrypt and send your message/file">
	</form>
	<br/><br/><a href="http://www.OptimumSSL.com" title="SSL Certificate Authority" style="font-family: arial; font-size: 10px; text-decoration: none;"><img src="optimumSSL_tl_white2.gif" alt="SSL Certificate Authority" title="SSL Certificate Authority" border="0" /><br /></a>
	</body></html>
	<?php
} 

function send_email($to, $textmessage, $from, $subject, $filename = false, $filedata = false, $filetype = false) {
	$headers = "From: $from";
	$subject = $subject;
	
	if (!$filename) {
		return @mail($to, $subject, $textmessage, $headers);
	}
	
	$semi_rand = md5( microtime(true) ); 
	$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

	$headers .= "\nMIME-Version: 1.0\n" . 
		"Content-Type: multipart/mixed;\n" . 
		" boundary=\"{$mime_boundary}\"";

	$message = "This is a multi-part message in MIME format.\n\n" . 
		"--{$mime_boundary}\n" . 
		"Content-Type: text/plain; charset=\"iso-8859-1\"\n" . 
		"Content-Transfer-Encoding: 7bit\n\n" . 
		$textmessage . "\n\n";

	$filedata = chunk_split( base64_encode($filedata) );
			 
	$message .= "--{$mime_boundary}\n" . 
		"Content-Type: {$filetype};\n" . 
		" name=\"{$filename}\"\n" . 
		"Content-Disposition: attachment;\n" . 
		" filename=\"{$filename}\"\n" . 
		"Content-Transfer-Encoding: base64\n\n" . 
		$filedata . "\n\n" . 
		"--{$mime_boundary}--\n"; 

	return @mail( $to, $subject, $message, $headers );
}

?>
