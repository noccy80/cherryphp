<?php

// Define the template
$tpl = <<<EOF
<VirtualHost *:80>
    ServerName %s
    DocumentRoot %s
    SetEnv APPLICATION_ENV "%s"
    <Directory %s>
        DirectoryIndex index.php
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>
</VirtualHost>
EOF;

// Return the complete configuration chunk
return sprintf($tpl,
    $this->data->servername,
    $this->data->htmlroot,
    $this->data->environment,
    $this->data->htmlroot
);
