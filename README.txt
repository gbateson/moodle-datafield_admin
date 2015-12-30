===============================================
The Admin database field for Moodle >= 2.3
===============================================

   The Admin database field acts as an extra API layer to restrict view
   and edit access to any other type of field in a database activity.

   Users with "mod/data:managetemplates" capability can always view and edit
   an Admin field, but access for other users can be restricted to "Hidden",
   "Visible (and not editable)" or "Visible and editable".

===============================================
To INSTALL or UPDATE the Admin database field
===============================================

    1. download the zip file from one of the following locations

        * https://github.com/gbateson/moodle-datafield_admin/archive/master.zip
        * http://bateson.kanazawa-gu.ac.jp/moodle/zip/plugins_datafield_admin.zip

    2. unzip the zip file - creates folder called "admin"

    3. upload the "admin" folder into the "mod/data/field" folder on your Moodle >= 2.3 site, to create a new folder at "mod/data/field/admin"

    4. log in to Moodle as administrator to initiate install/upgrade

        if install/upgrade does not begin automatically, you can initiate it manually by navigating to the following link:
        Settings -> Site administration -> Notifications

===============================================
To add an Admin field to a database activity
===============================================

    1. Login to Moodle, and navigate to a course page

    2. Locate the Database activity to which you wish to add an Admin field

    4. click the link to view the Database activity, and then click the Fields tab

    5. From the field type menu at the bottom of the page, select "Admin"

    6. Enter values for "Field name" and "Field description" select the subtype of this field

    7. Click the "Save changes" button at the bottom of the page.

    8. If necessary, you may need to further edit the field in order to complete further settings for selected subtype
