#!/bin/bash
source functions.sh

install_db
install_data
cd $MOODLEDIR

$MOOSHCMD role-update-capability-by-category-id student mod/forumn:viewrating allow 3 40

if echo "SELECT * FROM mdl_role_capabilities rc inner join mdl_context ctx on ctx.id=rc.contextid  WHERE contextlevel=40 and  contextid='3' \
        AND capability='mod/forumn:viewrating' AND permission='1' AND roleid='5'"  \
    | mysql -u "$DBUSER" -p"$DBPASSWORD" "$DBNAME" ; then
  exit 0
else
  exit 1
fi

