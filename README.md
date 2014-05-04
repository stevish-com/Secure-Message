Secure-Message
==============

PHP script to allow the public to email you securely with no software for them to install.

This is accomplished in 3 main steps:

1. SSL
SSL is assumed! If there is no SSL connection (HTTPS) to this script, it will not run because the message would not be secure

2. Encryption
The submitted message and optional file are encrypted using Gnu Privacy Guarg (GPG). In order for this to work, you must provide at least one public PGP key. If more than one is provided, message senders will have the option of which person (or name-email-key) to send the message to.

3. Transmission
The encrypted message and/or file is then attached to an email and sent to the email address on file for the key used.

4. Decryption
This script does not handle decryption. You are responsible to have your own decryption software and a copy of your secret PGP key (which should obviously match the public key you gave the server)

Prerequisites
=============
You need to know how to generate and use PGP keys, and understand how to add public keys to your web-server's keyring. Your webserver, then, will obviously need to have gnupg installed.

Instructions
============
To install, first copy the files over to where you want them

Next, copy `options_example.php` to `options.php` and set up the options.
  - The $to_array is a numeric array of associative arrays.
    - The associative arrays each represent a person and have 3 key/value pairs:
      - 'key' - The ID of the public key associated with this person.
      - 'email' - The email address the encrypted message will be sent to (hidden from public)
      - 'name' - The name of the person (to appear in the drop-down list for the message sender)
    - You may have as many or few people as you want in this list, as long as there's at least one
  - The $welcome_message is simply the message displayed at the top of the page

That's it! I recommend an .htaccess file to redirect all requests fom httpto https just for usability.
