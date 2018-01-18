<?php

require('includes/blowfish.php');

// Just in case you didn't read the README
function print_usage()
{
    global $argv;
    
    echo "Incorrect syntax.\n";
    echo 'Usage: php -f ' . $argv[0] . " -- <[-e -i plainfile -o cipheredfile -k keyfile] |\n\t[-d -i cipheredfile -o plainfile -k keyfile] |\n\t[-g keysize -k keyfile]>\n";
}

function error($msg)
{
    fwrite(STDERR, 'ERROR: ' . $msg . "\n");
    exit(1);
}

function ask_for_passphrase()
{
    $handle = fopen ("php://stdin", "r");
    echo "Enter your passphrase for this secret key and press ENTER: ";
    $passphrase = chop(fgets($handle));
    fclose($handle);
    return $passphrase;
}

$opts = getopt('edi:o:k:g:');

// This just makes sure the parameters are okay
if((array_key_exists('g', $opts) && (array_key_exists('e', $opts)
                                    || array_key_exists('d', $opts)
                                    || !array_key_exists('k', $opts)))
    || (array_key_exists('e', $opts) && array_key_exists('d', $opts))
    || ((array_key_exists('e', $opts) || array_key_exists('d', $opts))
        && (!array_key_exists('i', $opts) || !array_key_exists('o', $opts) || !array_key_exists('k', $opts)))
    || empty($opts)) { // If they are not, print usage and exit
        print_usage();
        exit(1);
    }
else { // If they are, proceed to do something useful
    if(array_key_exists('g', $opts)) { // Generate a new random key and store it
        $keysize = $opts['g'];
        $keyfile = $opts['k'];
        
        $newkey = '';
        
        try {
            $newkey = Blowfish::generate_key($keysize);
        } catch (WrongKeySizeException $exc) {
            error($exc->getMessage());
        }
        
        $passphrase = ask_for_passphrase();
        
        $keytothekey = sha1($passphrase, true);
        
        $bf = new Blowfish($keytothekey);
        $key = $bf->cbc_encrypt($newkey);

        $file = fopen($keyfile, "wb");
        if($file === false) error("Couldn't open " . $keyfile);
        if(fwrite($file, $key) === false) error("Couldn't write to " . $keyfile);
        fclose($file);
        
        echo "Key written to $keyfile successfully!\n";
        
        exit(0);
    }
    if(array_key_exists('e', $opts)) { // Encrypt something with an existing key
        $inputfile = $opts['i'];
        $outputfile = $opts['o'];
        $keyfile = $opts['k'];
        
        $encryptedkey = file_get_contents($keyfile);
        if($encryptedkey === false) error("Couldn't open " . $keyfile);
        
        $passphrase = ask_for_passphrase();
        
        $bf = new Blowfish(sha1($passphrase, true));
        $key = $bf->cbc_decrypt($encryptedkey);
        
        $plaindata = file_get_contents($inputfile);
        if($plaindata === false) error("Couldn't open " . $inputfile);
        
        $bf->setKey($key);
        $encrypteddata = $bf->cbc_encrypt($plaindata);
        
        $file = fopen($outputfile, "wb");
        if($file === false) error("Couldn't open " . $outputfile);
        if(fwrite($file, $encrypteddata) === false) error("Couldn't write to " . $outputfile);
        fclose($file);
        
        echo "Encrypted $inputfile with key $keyfile to $outputfile successfully!\n";
        
        exit(0);
    }
    if(array_key_exists('d', $opts)) { // Decrypt with a key
        $inputfile = $opts['i'];
        $outputfile = $opts['o'];
        $keyfile = $opts['k'];
        
        $encryptedkey = file_get_contents($keyfile);
        if($encryptedkey === false) error("Couldn't open " . $keyfile);
        
        $passphrase = ask_for_passphrase();
        
        $bf = new Blowfish(sha1($passphrase, true));
        $key = $bf->cbc_decrypt($encryptedkey);
        
        $encrypteddata = file_get_contents($inputfile);
        if($encrypteddata === false) error("Couldn't open " . $inputfile);
        
        $bf->setKey($key);
        $plaindata = $bf->cbc_decrypt($encrypteddata);
        
        $file = fopen($outputfile, "wb");
        if($file === false) error("Couldn't open " . $outputfile);
        if(fwrite($file, $plaindata) === false) error("Couldn't write to " . $outputfile);
        fclose($file);
        
        echo "Decrypted $inputfile with key $keyfile to $outputfile successfully!\n";
        
        exit(0);
    }
}

?>
