#!/usr/bin/perl -w
use Cwd qw(realpath);

############################################################
## Gets full path for use with a cronjob
############################################################
my $path = $0;
@words = split('photo_rets.pl', $path);
my $full_path = "@words";

##############################################################
# Config for daily and photos
# Each config is separated for the photo and daily pairs
# These are to be named by the database name in PM_main->board
##############################################################
my $db =$ARGV[0];
###################################
## Does not change
my $local_dir = realpath($0);
$local_dir =~ s/_dataset.*//g;
###################################

############################################################
## setup our enviroment. this will allow us to use this script 
## no matter where it resides 
## we want the $homedir value so we can use $homedir/tmp instead of the system /tmp.
my $myName = `whoami`;
        chomp($myName);
        my  ($name,$passwd,$uid,$gid,$quota,$comment,$gcos,$homedir,$shell,$expire) = getpwnam($myName);
## make sure $homedir/tmp exits if not make it
`mkdir -p $homedir/tmp`;

use POSIX qw(strftime);
my $mmdd = strftime "%m%e", localtime;


### Photos directories
my $media_dir= $homedir.'/public_html/media/'.$db.'/x/';
my $media_dir_extras= $homedir.'/public_html/media/'.$db.'_extras/x/';
my $photo_script_dir= $homedir.'/public_html/_dataset/scripts/generic/';
my $get_images=$homedir.'_dataset/scripts/generic/photo/'.$db.'.php';
############################################################
## run php scripts
############################################################

print `mkdir -p $homedir/tmp/$db/media/x`;
print `mkdir -p $homedir/tmp/$db/media/extras`;

print "retrieving pictures...\n";
print `/usr/bin/php $get_images`;

print "moving files to proper place...\n";
print $media_dir."\n";
print `/usr/bin/rsync --archive $homedir/tmp/$db/media/x/ $media_dir 2>&1`;
print `/usr/bin/rsync --archive $homedir/tmp/$db/media/extras/ $media_dir_extras 2>&1`;

print "resizing images...\n";
print `/usr/bin/php "$photo_script_dir/image_resizer.php" $db`;
############################################################