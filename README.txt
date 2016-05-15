===============================================
The Admin database field for Moodle >= 2.3
===============================================

   The Admin database field acts as an extra API layer to restrict view
   and edit access to any other type of field in a database activity.

   Users with "mod/data:managetemplates" capability can always view and edit
   an Admin field, but access for other users can be restricted to "Hidden",
   "Visible (and not editable)" or "Visible and editable".

   Additionally the following special "admin" fields are available:

   (a) setdefaultvalues

       The presence of this field will insert values from the user profile
       as default values for the following fields:
            firstname, lastname, middlename, alternatename,
            lastnamephonetic, firstnamephonetic,
            institution, department, address, city, country,
            email, phone1, phone2, url, icq, skype, yahoo, aim, msn

       Additionally, the following aliases are available:
            firstname_english    => firstname
            name_english_given   => firstname
            lastname_english     => lastname
            name_english_surname => lastname
            affiliation_english  => institution

   (b) fixdisabledfields

       The presence of this field will fix "missing property" errors generated
       when the form has both disabled fields and required fields, but some of
       the required fields are not filled in.

   (c) unapprove

       The presence of this field will force any newly added record to be
       "unapproved", and therefore "hidden" from other users. This field
       overrides the default behavior of the database module, which
       automatically sets records added by teachers/admins as "approved"
       and therefore "visible" by all other users.

    When creating any of the above special fields, set "Field type" to
    "Number" and "Accessibility" to "Hidden from non-managers".

    In the template for adding and editing records, the "fixdisabledfields" field
    should appear on the FIRST line, and the "setdefaultvalues" and "unapprove"
    fields should be on the LAST line.

=================================================
To INSTALL this plugin
=================================================

    ----------------
    Using GIT
    ----------------

    1. Clone this plugin to your server

       cd /PATH/TO/MOODLE
       git clone -q https://github.com/gbateson/moodle-datafield_admin.git mod/data/field/admin

    2. Add this plugin to the GIT exclude file

       cd /PATH/TO/MOODLE
       echo '/mod/data/field/admin/' >> '.git/info/exclude'

    3. continue with steps 3 and 4 below

    ----------------
    Using ZIP
    ----------------

    1. download the zip file from one of the following locations

        * https://github.com/gbateson/moodle-datafield_admin/archive/master.zip
        * http://bateson.kanazawa-gu.ac.jp/moodle/zip/plugins_datafield_admin.zip

    2. Unzip the zip file - if necessary renaming the resulting folder to "admin".
       Then upload, or move, the "admin" folder into the "mod/data/field" folder on
       your Moodle >= 2.3 site, to create a new folder at "mod/data/field/admin"

    3. continue with steps 3 and 4 below

    ----------------
    Using GIT or ZIP
    ----------------

    3. Currently database plugin strings aren't fully modularised, so the following
       two strings need be added manually to the language pack for the Database
       activity module, in file "/PATH/TO/MOODLE/mod/data/lang/en/data.php"

          $string['admin'] = 'Admin';
          $string['nameadmin'] = 'Admin field';

    4. Log in to Moodle as administrator to initiate the install/update

       If the install/update does not begin automatically, you can initiate it
       manually by navigating to the following Moodle administration page:

          Settings -> Site administration -> Notifications

    ----------------
    Troubleshooting
    ----------------

    If you have a white screen when trying to view your Moodle site
    after having installed this plugin, then you should remove the
    plugin folder, enable Moodle debugging, and try the install again.

    With Moodle debugging enabled you should get a somewhat meaningful
    message about what the problem is.

    The most common issues with installing this plugin are:

    (a) the "admin" folder is put in the wrong place
        SOLUTION: make sure the folder is at "mod/data/field/admin"
                  under your main Moodle folder, and that the file
                  "mod/data/field/admin/field.class.php" exists

    (b) permissions are set incorrectly on the "mod/data/field/admin" folder
        SOLUTION: set the permissions to be the same as those of other folders
                  within the "mod/data/field" folder

    (c) there is a syntax error in the Database language file
        SOLUTION: remove your previous edits, and then copy and paste
                  the language strings from this README file

    (d) the PHP cache is old
        SOLUTION: refresh the cache, for example by restarting the web server,
                  or the PHP accelerator, or both

=================================================
To UPDATE this plugin
=================================================

    ----------------
    Using GIT
    ----------------

    1. Get the latest version of this plugin

       cd /PATH/TO/MOODLE/mod/data/field/admin
       git pull

    2. Log in to Moodle as administrator to initiate the update

    ----------------
    Using ZIP
    ----------------

    Repeat steps 1, 2 and 4 of the ZIP install procedure (see above)


===============================================
To ADD an Admin field to a database activity
===============================================

    1. Login to Moodle, and navigate to a course page in which you are a teacher (or admin)

    2. Locate, or create, the Database activity to which you wish to add an Admin field

    4. click the link to view the Database activity, and then click the "Fields" tab

    5. From the "Field type" menu at the bottom of the page, select "Admin"

    6. Enter values for "Field name" and "Field description"

    7. Select the subtype of this field

    8. If required, enter conditions for disabling this field in the input form

       Syntax for UNARY operators:
       ('fieldname', 'checked')
       ('fieldname', 'notchecked')
       ('fieldname', 'noitemselected')

       Syntax for BINARY operators:
       ('fieldname', 'eq',  'value')
       ('fieldname', 'neq', 'value')
       ('fieldname', 'in',  'value1,value2,value3')

    9. Click the "Save changes" button at the bottom of the page.

    10. If necessary, you may need to further edit the field in order to add settings
        that are specific to the selected subtype
