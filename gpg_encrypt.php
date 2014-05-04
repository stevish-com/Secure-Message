<?php
/////////////////////////////////////////////////////
// gpg_encrypt v1.3.s                              //
// http://Business-PHP.com/opensource/gpg_encrypt/ //
//                                                 //
// This version (.s) has been edited to remove     //
// deprecated functions ereg() and eregi_replace() //
//                                                 //
// this PHP function provides a way to easily      //
// encrypt messages using GnuPG. See the above URL //
// for more information.                           //
//                                                 //
// should run on PHP 4.3.0 or later                //
//                                                 //
// Copyright (C) 15 Jan 2004 Atom Emet             //
// Copyright (C) 17 Feb 2006 Atom Emet             //
// Atom {at} Business-PHP.com                      //
//                                                 //
// Distributed under the terms of the              //
// GNU General Public License                      //
//                                                 //
// Other licenses available on request             //
//                                                 //
// This program is free software; you can          //
// redistribute it and/or modify it under the      //
// terms of the GNU General Public License as      //
// published by the Free Software Foundation;      //
// either version 2 of the License, or             //   
// (at your option) any later version.             //
//                                                 //
// This program is distributed in the hope         //
// that it will be useful, but WITHOUT ANY         //
// WARRANTY; without even the implied warranty     //
// of MERCHANTABILITY or FITNESS FOR A             //
// PARTICULAR PURPOSE. See the GNU General         //
// Public License for more details.                //
//                                                 //
// You should have received a copy of the          //
// GNU General Public License along with this      //
// program; if not, write to                       //
//    the Free Software Foundation, Inc.           //
//    59 Temple Place - Suite 330                  //
//    Boston, MA  02111-1307, USA                  //
/////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////
// usage - see: http://business-php.com/opensource/gpg_encrypt/
// array gpg_gpg_encrypt(secret-message, /path/to/gpg, /path/to/.gnupg/, 0x123456);

//////////////////////
// define the function
function gpg_encrypt() {

    //////////////////////////////////////////////////////////
    // sanity check - make sure there are at least 4 arguments
    // any extra arguments are considered to be additional key IDs
    if(func_num_args() < 4) {
        trigger_error("gpg_encrypt.php requires at least 4 arguments", E_USER_ERROR);
        // if an error message directs you to the line above please
        // double check that you are providing at least 4 arguments
        die();
    }

    ////////////////////////////////
    // assign arguments to variables
    $gpg_encrypt_args = func_get_args();
    $gpg_encrypt_secret_message = array_shift($gpg_encrypt_args);    // 1st argument - secret message
    $gpg_encrypt_gpg_path = array_shift($gpg_encrypt_args);        // 2nd argument - full path to gpg
    $gpg_encrypt_gpg_home = array_shift($gpg_encrypt_args);        // 3rd argument - keyring directory

    ///////////////////////////////////////////////////////////////////////
    // make sure that each recipient has the message encrypted to their key
    // the 4th argument, and any subsequent arguments, are key IDs
    foreach($gpg_encrypt_args as $gpg_encrypt_recipient) {
        $gpg_encrypt_recipient_list .= " -r ${gpg_encrypt_recipient}";
    }

    //////////////////////////////////////////////////////////////////////////////
    // sanity check - make sure "$gpg_encrypt_gpg_home" is pointing to a directory
    if(!is_dir($gpg_encrypt_gpg_home)) {
        trigger_error("gpg homedir is not a directory: \"${gpg_encrypt_gpg_home}\"", E_USER_ERROR);
        // if an error message directs you to the line above please
        // double check that your full path to the .gnupg directory is correct
        die();
    }

    ////////////////////////////////////////////////////////////////////////////////////////
    // sanity check - make sure "$gpg_encrypt_gpg_path" is pointing to an executable program
    if(!is_executable($gpg_encrypt_gpg_path)) {
        trigger_error("gpg is not executable: \"${gpg_encrypt_gpg_path}\" :: or you may need to comment out this sanity check - see the source", E_USER_ERROR);
        // if an error message directs you to the line above please
        // double check that your full path to gpg is correct
        // ////////////////////////////////////////////////////////////////////////////////////////////
        // it has been reported that some (older) configurations of php will choke on this sanity check
        // if this is causing an error, try to comment out this test
        die();
    }

    //////////////////////////////////////////
    // find which version of GnuPG we're using
    //////////////////////////////////////////

    ///////////////////////////////////////////
    // first we'll set up a pipe to read STDOUT
    $gpg_encrypt_version_descriptorspec = array(
        1 => array("pipe", "w")  // STDOUT is a pipe that GnuPG will write to
    );

    ////////////////////////////////////////////////////////
    // open a process for gpg to tell us which version it is
    $gpg_encrypt_version_process = proc_open("${gpg_encrypt_gpg_path} --version",
        $gpg_encrypt_version_descriptorspec,
        $gpg_encrypt_version_pipes);

    /////////////////////////////////////////////////////
    // we're only concerned with the first line of output
    $gpg_encrypt_version_output = fgets($gpg_encrypt_version_pipes[1], 1024);

    /////////////////////////////////////////
    // close the $gpg_encrypt_version_process
    proc_close($gpg_encrypt_version_process);

    ///////////////////////////////////////////////
    // sanity check - see if we're working with gpg
    if(!preg_match('/^gpg /', $gpg_encrypt_version_output)) {
    trigger_error("gpg executable is not GnuPG: \"${gpg_encrypt_gpg_path}\"", E_USER_ERROR);
        // if an error message directs you to the line above please
        // double check that your path to gpg is really GnuPG
        die();
    }

    /////////////////////////////////////////////////////////////
    // pick the version number out of $gpg_encrypt_version_output
    // we'll need this so we can determine the correct
    // way to tell GnuPG how to "always trust"
    $gpg_encrypt_gpg_version = preg_replace('/^.* /i', '', $gpg_encrypt_version_output);

    ////////////////////////////////////////////////////////
    // depending on which version of GnuPG we're using there
    // are two different ways to specify "always trust"
    if("$gpg_encrypt_gpg_version" < '1.2.3') {
        $gpg_encrypt_trust = '--always-trust';        // the old way
    } else {
        $gpg_encrypt_trust = '--trust-model always';    // the new way
    }

    /////////////////////////////////////////////
    // unset variables that we don't need anymore
    unset($gpg_encrypt_version_output,
        $gpg_encrypt_gpg_version);

    ////////////////////////////////////////
    // we're done checking the GnuPG version
    ////////////////////////////////////////

    //////////////////////////////////////////////
    // set up pipes for handling I/O to/from GnuPG
    $gpg_encrypt_descriptorspec = array(
        0 => array("pipe", "r"),  // STDIN is a pipe that GnuPG will read from
        1 => array("pipe", "w"),  // STDOUT is a pipe that GnuPG will write to
        2 => array("pipe", "w")   // STDERR is a pipe that GnuPG will write to
    );

    ///////////////////////////////
    // this opens the GnuPG process
    $gpg_encrypt_gpg_process = proc_open("${gpg_encrypt_gpg_path} --no-random-seed-file --lock-never --homedir ${gpg_encrypt_gpg_home} ${gpg_encrypt_trust} -ea ${gpg_encrypt_recipient_list}",
        $gpg_encrypt_descriptorspec,
        $gpg_encrypt_pipes);

    //////////////////////////////////////////////////////////////////
    // this writes the "$gpg_encrypt_secret_message" to GnuPG on STDIN
    if(is_resource($gpg_encrypt_gpg_process)) {
        fwrite($gpg_encrypt_pipes[0], ${gpg_encrypt_secret_message});
        fclose($gpg_encrypt_pipes[0]);

    /////////////////////////////////////////////////////////
    // this reads the encrypted output from GnuPG from STDOUT
    while(!feof($gpg_encrypt_pipes[1])) {
        $gpg_encrypt_encrypted_message .= fgets($gpg_encrypt_pipes[1], 1024);
    }
    fclose($gpg_encrypt_pipes[1]);

    /////////////////////////////////////////////////////////
    // this reads warnings and notices from GnuPG from STDERR
    while(!feof($gpg_encrypt_pipes[2])) {
        $gpg_encrypt_error_message .= fgets($gpg_encrypt_pipes[2], 1024);
    }
    fclose($gpg_encrypt_pipes[2]);

    /////////////////////////////////////////
    // this collects the exit status of GnuPG
    $gpg_encrypt_exit_status = proc_close($gpg_encrypt_gpg_process);

    ////////////////////////////////////////////
    // unset variables that are no longer needed
    // and can only cause trouble
    unset($gpg_encrypt_args,
        $gpg_encrypt_secret_message,
        $gpg_encrypt_recipient_list,
        $gpg_encrypt_recipient,
        $gpg_encrypt_gpg_path,
        $gpg_encrypt_gpg_home,
        $gpg_encrypt_trust);

    ////////////////////////////////////
    // this returns an array containing:
    // [0] encrypted output (STDOUT)
    // [1] warnings and notices (STDERR)
    // [2] exit status
    return array($gpg_encrypt_encrypted_message, $gpg_encrypt_error_message, $gpg_encrypt_exit_status);
    }
}

?>
