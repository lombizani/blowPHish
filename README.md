blowPHish.php
=============

A naïve Blowfish implementation in PHP
by Carlos De Bernardis


## How to run
You need PHP-CLI > 5.5 to run this program.

$ php -f blowPHish.php -- <args>

Command-line arguments:
-e - Flags encryption. Must be accompanied by -k, -i and -o.
-d - Flags decryption. Must be accompanied by -k, -i and -o.
-g <size> - Flags key generation, must be followed by desired key size and -k.
-k <keyfile> - Specifies which file to read/write key from/to.
-i <infile> - Specifies file from which data to encrypt/decrypt shall be read.
-o <outfile> - Specifies file to which decrypted/encrypted data will be written.

## Implementation details
Keys are randomly generated using Blowfish itself (in counter mode) and stored
encrypted with Blowfish by using a SHA1 hash of a user-provided passphrase as
key. The passphrase must always be supplied for both encryption and decryption.

I have also chosen to encrypt data in CBC mode to protect it from being
rearranged. The encrypted file includes the initialization vector in the
beginning (it is obviously stripped after decryption).


## Issues
It sometimes appends a few null characters to the end of the decrypted file
because I haven't yet implemented a way to save the original file's length.
