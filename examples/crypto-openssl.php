<?php

require_once "xenon/xenon.php";
xenon\frameworks\cherryphp::bootstrap(__DIR__.'/..');

if (!file_exists("server.pem")) {
    // Create the CSR
    $cr = new Cherry\Crypto\OpenSSL\CSR([
        "countryName"           => "IN",
        "stateOrProvinceName"   => "World",
        "localityName"          => "Somewhere",
        "organizationName"      => "Organization Inc.",
        "organizationalUnitName"=> "Unit",
        "commonName"            => "example.com",
    ]);

    // Create a new private key
    $pk = new Cherry\Crypto\OpenSSL\KeyPair(2048);
    $cr->setPrivateKey($pk);
    $cr->setSerial(2);
    // Sign the certificate
    $cr->signCertificate();
    // Export the certificate
    $cr->exportCertificatePem("server.pem");
    // If you also want a PKCS12 file, try this:
    // $cr->exportCertificatePkcs12("server.p12");
}

// Grab the certificate and get the context
$cert = new Cherry\Crypto\OpenSSL\Certificate("server.pem");
$ctx = $cert->getStreamContext();

// Create the server socket with the assigned context
$server = stream_socket_server('ssl://0.0.0.0:9000', $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $ctx);

while($server) {
    $buffer = null;
    $client = stream_socket_accept($server);
    print stream_socket_get_name( $client, true) . "\n";
    if( $client ) {
        // Read until double CRLF
        while( !preg_match('/\r?\n\r?\n/', $buffer) ) {
            $buffer .= fread($client, 24000);
            if (empty($buffer))
                break;
        }

        if ($buffer) {
            // Respond to client
            $dat = "Hello World! " . microtime(true). "<pre>{$buffer}</pre>";
            $len = strlen($dat);
            echo "Writing response...";
            fwrite($client,  "HTTP/1.1 200 OK\r\n"
                             . "Connection: close\r\n"
                             . "Content-Type: text/html\r\n"
                             . "Content-Length: {$len}\r\n"
                             . "\r\n"
                             . $dat);
            fclose($client);
        }
        echo "Done!\n";
    } else {
        print "error.\n";
    }
}