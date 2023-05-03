Summary
=======

I created a backup of my entire Xonotic server config, including all pk3 files, [on Github](https://github.com/ballerburg9005/xonotic.us.to).

It includes complete instructions how you can set up this server by yourself, using a new (clean) VPS.
 
My server also displays those instructions on http://xonotic.us.to .

<br>

*Check out [Oracle Cloud Forever Free Tier](https://www.youtube.com/watch?v=_m21FxvuQ4c)! You get 4 super beefy dedicated Ampere/ARM cores with 24GB RAM on a 600Mbit connection in an undercrowded ARM-exclusive datacenter for $0 (no hidden cost!). After lots of playstesting, everything checks out A+++ . Supports more than 32 players on only one core. Sounds unbelievable, but true. Don't pick the x86 option, it sometimes sucks really bad especially on weekends. Make sure upon registration to pick a "region" with a [large (maybe with Azure) datacenter](https://www.oracle.com/cloud/public-cloud-regions/), because some regions don't have ARM and right now you can't change region afterwards (I picked Germany/NRW/Frankfurt). Hint: In Oracle Control Center, you need to go to Networking -> Virtual cloud networks -> vcn -> Security List Details -> Ingress Rules to switch off external UDP firewall and allow TCP on port 80. UPDATE WARNING: Apparently there is a small chance, that [Oracle will just delete your account and data without reason and warning at random](https://old.reddit.com/r/oraclecloud/comments/pfachp/oracle_account_terminated_after_free_tier_ended/). This happened to someone else recently, not us or others. It seems to have little if anything to do with the free trial, and perhaps not even the free tier. Don't trust them with your data. For just a game server, you can do rsync backups, restore it somewhere else in 30 minutes, it might be ok since it is free. But professionally, avoid it at all cost.*


<p><br>

Instructions
============

Prepare the system. Install packages, setup xonotic user, use zsh:
```
# disable IPv6 (it always causes issues at random)
echo -en "net.ipv6.conf.all.disable_ipv6=1\nnet.ipv6.conf.default.disable_ipv6=1\nnet.ipv6.conf.lo.disable_ipv6=1\n" >> /etc/sysctl.conf && sysctl -p

apt install zip unzip zsh curl wget screen lighttpd vim php php-cgi php-mysql git rsync

# Oh my Zsh
sh -c "$(curl -fsSL https://raw.github.com/ohmyzsh/ohmyzsh/master/tools/install.sh)"

# --- > put your id_rsa.pub into /root/.ssh/authorized_keys
# also add your root account's id_rsa.pub, if you want to run cronjob backups

# keeps things all in one place 
rm -r /var/www/html
ln -s /home/xonotic/www /var/www/html
 
useradd -u 9005 -m xonotic -G root,audio -g www-data -s `which zsh`

su xonotic

unset ZSH; cd "$HOME"
sh -c "$(curl -fsSL https://raw.github.com/ohmyzsh/ohmyzsh/master/tools/install.sh)"
```
<br>

Download the latest Xonotic autobuild ZIP and unzip:
```
cd /home/xonotic
wget https://beta.xonotic.org/autobuild/Xonotic-`date +%Y%m%d  --date="3 days ago"`.zip
unzip Xonotic-`date +%Y%m%d  --date="3 days ago"`.zip
rm Xonotic-`date +%Y%m%d  --date="3 days ago"`.zip
```
<br>

Download the maps and server config from my github repository:
```
git clone --depth=1 https://github.com/ballerburg9005/xonotic.us.to
 
rsync -ra xonotic.us.to/ ./
mkdir /home/xonotic/.xonotic/data/data
chmod 700 /home/xonotic/.xonotic/data/data
rm -rf xonotic.us.to .ssh/authorized_keys
```
<bR>

Special speedup for csprogs download. If you do this, you need to repeat it each time you update your Xonotic installation!
```
mkdir -p /tmp/csprogs; OLDWD="$PWD" 
cd /tmp/csprogs/ 
rm /home/xonotic/.xonotic/data/csprogs-xonotic-*.pk3
export MYDATE="$(command ls /home/xonotic/Xonotic/data/xonotic-*-data.pk3 | sed -En "s/.*-([0-9]*)-data.pk3$/\1/p")"
unzip /home/xonotic/Xonotic/data/xonotic-*-data.pk3 'csprogs*'
echo "csprogs" > csprogs-xonotic-$MYDATE-autobuild.pk3.serverpackage
zip /home/xonotic/.xonotic/data/csprogs-xonotic-$MYDATE-autobuild.pk3 csprogs*
rm csprogs*
cd "$OLDWD"
```
<br>

**Edit /home/xonotic/.xonotic/data/server.cfg!**:
```
export MYSERVER="example.com" # (or ip address like 15.26.37.48) THIS NEEDS TO BE CORRECT
sed "s/xonotic\.us\.to/$MYSERVER/g" -i /home/xonotic/.xonotic/data/server.cfg

# you can control the server if connected inside Xonotic as a player with this password
echo '//rcon_password "SuperSecret"' >  /home/xonotic/.xonotic/data/secret.cfg # remove //
chmod 700 /home/xonotic/.xonotic/data/secret.cfg
```
<bR>

Seriously ... you have to edit the server.cfg by hand:
```
vim /home/xonotic/.xonotic/data/server.cfg
```
<br>

And the final steps:
```
exit
exit # to root shell

chown root:root /home/xonotic/xonotic*.service
ln /home/xonotic/xonotic.service /etc/systemd/system/xonotic.service
ln /home/xonotic/xonotic-stats.service /etc/systemd/system/xonotic-stats.service

lighty-enable-mod fastcgi fastcgi-php dir-listing rewrite

systemctl daemon-reload
systemctl enable xonotic lighttpd xonotic-stats
systemctl start lighttpd xonotic xonotic-stats
systemctl restart lighttpd

```
<br><p>

Probably smart to put a restart into crontab ( cmd: crontab -e ):
```
0 7 * * * systemctl restart xonotic
```
<br><p>

Useful commands
===============

Backup command for cronjob (on remote machine):
```
rsync --exclude 'Xonotic' -ra root@xonotic.us.to:/home/xonotic/ /mnt/1/BACKUP/XONOTIC_SERVER/
```
<br>

SSH directly into interactive Xonotic server console (from remote machine):
```
XONOTICSERVER=; ssh -t root@xonotic.us.to "su xonotic -c 'screen -r xonotic-server'"
```
<br><p> 

Troubleshooting
===============
Disable firewall or selinux:
```
iptables -I INPUT -j ACCEPT
setenforce 0 # you might need to set SELINUX=disabled in /etc/selinux/config and reboot
```
<br><p> 

 
Running on ARM architecture
===========================

```
# type into root console:
apt install autoconf build-essential curl git libtool libgmp-dev libjpeg-turbo8-dev libsdl2-dev libxpm-dev xserver-xorg-dev zlib1g-dev unzip zip wget
su xonotic
cd ~/Xonotic
make server

exit
systemctl restart xonotic
```
<p><br>
 
 
Github
======
If you are curious: To mirror my server files on Github, I simply created a new account there, added an 
access token to it and then on my main account I granted that user access 
to an empty repo. Then I logged in as xonotic on my server and edited the
.git/config to include the user:accesstoken@ before the URL and changed it to my empty repo. I update the repo with a cronjob for the user xonotic: 

```
GIT=;cd /home/xonotic/; git config --global http.postBuffer 1048576000; git pull; git submodule update --remote --recursive; git add -u; git add  * .*; git clean -f; git commit -m "."; git push
```

For additional servers, I use this abomination:
```
DUELGIT=;cd /home/xonotic/; rsync -ra --progress /home/xonotic/.xonotic/data/*.pk3 xonotic@xonotic.us.to:/home/xonotic/.xonotic/data/; ssh -t xonotic@xonotic.us.to "cd /home/xonotic/; git config pull.rebase false; git config --global http.postBuffer 1048576000; git pull; git submodule update --remote --recursive; git add -u; git add  * .*; git clean -f; git commit -m '.'; git push"; echo "done updating remote"; cp ~/.xonotic/data/server.cfg /tmp/server.cfg; cp ~/www/stats-`hostname`.csv /tmp/; git add -u; git add  * .*; git stash; git pull;  cp /tmp/server.cfg ~/.xonotic/data/server.cfg_`hostname`; cp /tmp/stats-`hostname`.csv ~/www/; git stash drop; git config --global http.postBuffer 1048576000; git pull; git submodule update --remote --recursive; git add -u; git add  * .*; git clean -f; git commit -m "."; git push;  cp /tmp/server.cfg ~/.xonotic/data/; cp /tmp/stats-`hostname`.csv ~/www/ # discards everything on server but new pk3s, server config and statistics
```
<br>

Note that the command needs to be tested first. Uploading the home directory to Github ordinarily is to be considered insecure, for good reasons that do not apply to my case.
<p><br>
