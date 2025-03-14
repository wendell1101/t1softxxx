
#upgrade nginx to 1.10
# sudo apt-add-repository ppa:nginx/stable
# sudo apt-get update
# sudo apt-get upgrade

# cd ~/Code
# tar xzf nginx_sites.tar.gz
# sudo cp -f nginx_sites/* /etc/nginx/sites-available/
# cd /etc/nginx/sites-enabled
# sudo ln -sf ../sites-available/admin.og.local admin.og.local
# sudo ln -sf ../sites-available/aff.og.local aff.og.local
# sudo ln -sf ../sites-available/player.og.local player.og.local
# sudo ln -sf ../sites-available/www.og.local www.og.local
# ls -l
# cd ~/Code

# tar xzf localca.tar.gz
# ls ssh_keys/localca

# sudo service nginx restart


