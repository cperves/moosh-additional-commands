#!/bin/bash
source functions.sh
#FIXME TODO
install_db
install_data
cd $MOODLEDIR


$MOOSHCMD mymoodle-reset-u 2

if echo "SELECT * FROM mdl_block_instances bi inner join mdl_context ctx on ctx.id=bi.contextid WHERE ctx.instanceid = 2 and ctx.contextlevel=30" \
    | mysql -u "$DBUSER" -p"$DBPASSWORD" "$DBNAME" | grep "userid"; then
  exit 0
else
  exit 1
fi