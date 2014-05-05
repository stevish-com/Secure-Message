Stevish Secure Message
======================
Version 1.0

PHP script to allow the public to email you securely with no software for them to install. Possible uses include giving clients a way to securely transmit desired passwords, or giving family members a way to send you their credit card number to help them sign up for stuff. This script is perfect for paranoid crypto-geeks who can't convince friends family or clients to install and set up encryption software and public/private keypairs.

This is accomplished in 4 main steps:

1. **SSL.** SSL is assumed! If there is no SSL connection (HTTPS) to this script, it will not run because the message would not be secure

2. **Encryption.** The submitted message and optional file are encrypted using Gnu Privacy Guard (GPG). In order for this to work, you must provide at least one public PGP key. If more than one is provided, message senders will have the option of which person (or name-email-key) to send the message to.

3. **Transmission.** The encrypted message and/or file is then attached to an email and sent to the email address on file for the key used.

4. **Decryption.** This script does not handle decryption. You are responsible to have your own decryption software and a copy of your secret PGP key (which should obviously match the public key you gave the server)

Open Source
===========
As all security software should be (in my humble opinion), this software is open source. If you know anything about php, I encourage you to read through the entirety of the code (index.php and all the files it includes... It's really not that long), and understand the logic behind it. You are always responsible for your own security, so OWN it, and double-check my work. If you find a security hole, make it public by posting an issue or, better yet, submit a fix. 

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
