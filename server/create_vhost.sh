#!/bin/bash

# Check for root access first.
if [ "$(whoami)" != 'root' ]; then
  echo "** You have to execute this script as root."
  exit 1;
fi

# Ask host and file related questions
echo "*** THIS CREATES VERY SIMPLE VHOSTS. Please set up complex ones manually."
read -p "~ Enter the host name (ex: test.drinkcaffeine.com): " servn
read -p "~ Enter the directory name (ex: test): " dir

# Attempt to set up skel (TODO: add skel)
echo "* Creating the web directory..."
if ! mkdir -p /home/websites/$dir; then
  echo "** The web directory already exists."
else
  echo "** Successfully created the web directory."
fi

# Fake skel
echo "* Creating the basic index file..."
echo "<?php echo '<h1>$servn</h1>'; ?>" > /home/websites/$dir/index.php
if ! echo -e /home/websites/$dir/index.php; then
  echo "** Unable to create the basic index file."
else
  echo "** Successfully created the basic index file."
fi

# Set proper web permissions
echo "* Changing permissions..."
chown -Rf apache:apache /home/websites/$dir
chmod -Rf '755' /home/websites/$dir
echo "** Completed changing permissions."

# Attempt to set up vhost info
echo "* Creating the virtual host config file..."
echo "# Dynamically created vhost file for $servn

<VirtualHost *:80>

  DocumentRoot \"/home/websites/$dir\"
  ServerName $servn
  ErrorLog logs/$dir-error_log
  CustomLog logs/$dir-access_log combined

  <Directory \"/home/websites/$dir\">
    Options -Indexes +FollowSymLinks -MultiViews
    AllowOverride All
    Require all granted
  </Directory>

</VirtualHost>" > /etc/httpd/vhosts.d/vhost-$dir.conf
if ! echo -e /etc/httpd/vhosts.d/vhost-$dir.conf; then
  echo "** Unable to create the virtual host config file."
else
  echo "** Successfully created the virtual host config file."
fi

# If SSL is needed, set that up too
echo "~ Create an SSL config for the host? [y/n] "
read q
if [[ "${q}" == "yes" ]] || [[ "${q}" == "y" ]]; then
  ## Create generic SSL (admin can alter)
  echo "* Creating the certificate key and file..."
  openssl req -new -newkey rsa:4096 -days 3650 -nodes -x509 -subj "/C=US/ST=Connecticut/L=Madison/O=DC/CN=$servn" -keyout /etc/httpd/ssl/$dir.key -out /etc/httpd/ssl/$dir.crt
  if ! echo -e /etc/httpd/ssl/$dir.key; then
    echo "** Unable to create the certificate key."
  else
    echo "** Successfully created the certificate key."
  fi
  if ! echo -e /etc/httpd/ssl/$dir.crt; then
    echo "** Unable to create the certificate file."
  else
    echo "** Successfully created the certificate file."
  fi
  ## Attempt to set up SSL vhost info
  echo "* Creating the virtual host SSL config file..."
  echo "# Dynamically created vhost-ssl file for $servn

<VirtualHost *:443>

  SSLEngine on
  SSLCertificateFile /etc/httpd/ssl/$dir.crt
  SSLCertificateKeyFile /etc/httpd/ssl/$dir.key

  DocumentRoot \"/home/websites/$dir\"
  ServerName $servn
  ErrorLog logs/$dir-error_log
  CustomLog logs/$dir-access_log combined

  <Directory \"/home/websites/$dir\">
    Options -Indexes +FollowSymLinks -MultiViews
    AllowOverride All
    Require all granted
  </Directory>

</VirtualHost>" > /etc/httpd/vhosts.d/vhost-$dir-ssl.conf
  if ! echo -e /etc/httpd/vhosts.d/vhost-$dir-ssl.conf; then
    echo "** Unable to create the virtual host SSL config file."
  else
    echo "** Successfully created the virtual host SSL config file."
  fi
fi

# Update the hosts file (for Drupal sites mainly, but still good practice)
echo "127.0.0.1 $servn" >> /etc/hosts
echo "* Updated hosts file for server."

# Test Apache2
echo "** Testing Apache configuration..."
apachectl configtest

# Restart Apache2
echo "~ Restart Apache (only do this if there were no errors)? [y/n] "
read q
if [[ "${q}" == "yes" ]] || [[ "${q}" == "y" ]]; then
  systemctl restart httpd
fi

echo "** Done. Don't forget to update the hosts file on your computer to use 10.1.0.19 for the name you picked."
