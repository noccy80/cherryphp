<?php
/**
 * This is a working example of how to use the Request and Response methods to
 * parse HTTP requests and output HTTP responses directly over the socket, as
 * well as generation of self-signed certificates using OpenSSL.
 *
 * The Certificate class is used to generate the context for the SSL sockets.
 *
 *
 */

require_once "cherryphp";

use Cherry\Cli\CliUtils;

// Check if the certificate file exists, and otherwise generate it.
if (!file_exists("server.pem")) {
    // Create the CSR
    $cr = new Cherry\Crypto\OpenSSL\CSR([
        "countryName"           => "XX",
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
    $client = @stream_socket_accept($server);
    if( $client ) {

        // Set up the request and prepare to read it from socket
        $req = new Cherry\Web\Request();
        $host = stream_socket_get_name($client, true);
        $req->setRemoteIp($host);
        
        // Read until we got the whole request. The isRequestComplete() method
        // will return true once it has detected a full request.
        while(!$req->isRequestComplete())
            $req->createFromString(fread($client, 24000),true);

        // If we don't have a request method this was probably just a ping to
        // get the certificate, leaving us with an empty request which doesn't
        // need a response.
        if ($req->getRequestMethod()) {
        
            // Create an appropriate response based on the request.
            $rsp = $req->createResponse();
            
            // Set up our response. The camel case is translated into the proper
            // headers, so contentType becomes Content-Type.
            $rsp->contentType = "text/html";
            $rsp->server = "Cherry HTTPS Example Server";
            $rsp->connection = "close";
            $rsp->setContent($req->asHtml());
            
            // Respond to client
            fwrite($client,  $rsp->asHttpResponse(true));
            
            // Output the request and the response as text (for debugging)
            echo "\033[33m".CliUtils::numberLines($req->asText(),"\033[7m%3d \033[27m")."\n\033[0m";
            echo "\033[36m".CliUtils::numberLines($rsp->asText(),"\033[7m%3d \033[27m")."\n\033[0m";
            
        }
        
        // Close the connection
        fclose($client);

    }
}
