### BEGIN INIT INFO
# Provides:          example
# Required-Start:
# Required-Stop:
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Arcade1Up Audio Key Bindings
# Description:
# This script binds Joy buttons to events for arcade1up ###
# PREPERATION
# Make the proper updates before running. You will need a keyboard
# This can all be performed via SSH
#
# To enable this functionality you will need to find your device
# > ls /dev/input/by-id
#
# Test the device you think is your proper controller
# > evtest /dev/input/by-id/usb-Device-Name-event-joystick
#
# You will see the following prompt and you can press Ctrl-c at any time to exit
# Testing ... (interrupt to exit)
#
# Press any button on your controller
# ! If you do not get a response then you've selected the wrong ID device, exit and try another !
# Once you get a proper response and you've found the right controller you will see:
#
# Event: time 1558730438.587386, type 4 (EV_MSC), code 4 (MSC_SCAN), value 90001
# Event: time 1558730438.587386, type 1 (EV_KEY), code 288 (BTN_TRIGGER), value 1
# Event: time 1558730438.587386, -------------- SYN_REPORT ------------
# Event: time 1558730438.667388, type 4 (EV_MSC), code 4 (MSC_SCAN), value 90001
# Event: time 1558730438.667388, type 1 (EV_KEY), code 288 (BTN_TRIGGER), value 0
# Event: time 1558730438.667388, -------------- SYN_REPORT ------------
#
# Switch between the left and right volume switch settings and use the values returned
#
# This example means button press/switch left (value 1)
# type 1 (EV_KEY), code 288 (BTN_TRIGGER), value 1
#
# This example means button release/switch middle (value 0)
# type 1 (EV_KEY), code 288 (BTN_TRIGGER), value 0
#
# This example means button press/switch right (value 1)
# type 1 (EV_KEY), code 289 (BTN_TRIGGER), value 1
#
# This example means button release/switch middle (value 0)
# type 1 (EV_KEY), code 289 (BTN_TRIGGER), value 0
#
# Below you will replace the proper values
# The middle switch selection for the volume control has no binding, it is an off setting
# Because of this we will bind both triggers' off event to the same volume
#
#
# Testing
#
# Run this file manually in your home directory to verify it works
# sh arcade1up-audio-bindings.sh
#
# Run the following to test running in background
# sh arcade1up-audio-bindings.sh &>/dev/null
#
# to run on boot, add script to /etc/init.d/
# ex: nano /etc/init.d/arcade1up-audio-bindings.sh
#
#
# Run this to set the script to run on startup
# sudo update-rc.d ~/arcade1up-audio-bindings.sh defaults
#
# make sure it is executable
# chmod +x /etc/init.d/arcade1up-audio-bindings.sh
### END INIT INFO
echo "Listen for button events for system functions..."
# this is your device designated by ID
device='/dev/input/by-path/platform-3f980000.usb-usb-0:1.2:1.0-event-joystick'
echo "Listening on:"
echo $device
event_right_on='*type 1 (EV_KEY), code 294 (BTN_BASE), value 1' # Read for button switch on right
event_right_off='*type 1 (EV_KEY), code 294 (BTN_BASE), value 0' # Read for button switch off right
event_left_on='*type 1 (EV_KEY), code 296 (BTN_BASE3), value 1' # Read for button switch on left
event_left_off='*type 1 (EV_KEY), code 296 (BTN_BASE3), value 0' # Read for button switch off left

# switch to decide function
evtest "$device" | while read line; do
  case $line in
    ($event_left_on) amixer set 'PCM' 0% ;;
    ($event_left_off) amixer set 'PCM' 76% ;;
    ($event_right_off) amixer set 'PCM' 76% ;;
    ($event_right_on) amixer set 'PCM' 85% ;;
    ($event_right_on) amixer set 'PCM' 85% ;;
  esac
done
