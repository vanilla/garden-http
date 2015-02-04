server {
    listen 80 default_server;
    listen 8080 default_server;

    root {DOCUMENT_ROOT};

    autoindex            off;
    client_max_body_size 100M;
    fastcgi_read_timeout 1800;

    # This is the php handler for all garden requests.
    location ~* "^/_index\.php(/|$)" {
        internal;
        include                   fastcgi.conf;
        fastcgi_param             SCRIPT_NAME /index.php;
        fastcgi_param             PHP_SELF $fastcgi_script_name;
        fastcgi_param             SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param             X_REWRITE $request_rewrite;

        fastcgi_pass              php-fpm;
    }

    # Rewrite all php files through the framework.
    location ~* "\.php(/|$)" {
        set $request_rewrite 1;
        rewrite ^ /_index.php$uri last;
    }

    # Default location.
    location ~* ^ {
        try_files $uri @garden;
    }

    # Rewrite all files that aren't found to the base php handler.
    location @garden {
        set $request_rewrite 1;
        rewrite ^ /_index.php/$uri last;
    }

    # Deny internal locations.
    location ~ /\.          { access_log off; log_not_found off; deny all; }
    location ~ ~$           { access_log off; log_not_found off; deny all; }
    location ^~ /favicon.ico { access_log off; log_not_found off; return 404; }
}

upstream php-fpm {
    server {SERVER};
}
