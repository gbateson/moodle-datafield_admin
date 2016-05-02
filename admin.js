// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod/data/field/admin/admin.js
 *
 * @package    mod_data
 * @subpackage datafield_admin
 * @copyright  2015 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.datafield_admin = {

    /**
     * setup_field
     *
     * @param object
     * @param string id1   : the id of the element to be disabled
     * @param string id2   : the id of the condition element
     * @param string op    : the condition operator
     * @param string value : the condition value
     * @return void, but may add event handler(s) to the id2 element(s)
     */
    setup_field : function (Y, id1, id2, op, value) {

        var elm = document.getElementById(id2);
        if (elm==null) {
            return;
        }
        var node = null;
        var t = elm.type;
        if (t=="text" || t=="textarea" || t=="checkbox" || t=="radio" || t=="select-one" || t=="select-multiple") {
            node = Y.one(elm);
        }
        if (t=="checkbox" || t=="radio") {
            node = Y.all(elm.form.elements[elm.name]);
        }
        if (typeof(t)=="undefined") {
            node = Y.all(elm);
        }
        if (node) {
            node.on("change", function(e){
                M.datafield_admin.onchange_field(e.target, id1, op, value);
            });
            M.datafield_admin.onchange_field(Y.one(elm), id1, op, value);
        }
    },

    /**
     * onchange_field
     *
     * @param object node  : the YUI node of the condition element
     * @param string id    : the id of the element to be disabled
     * @param string op    : the condition operator
     * @param string value : the condition value
     * @return void, but may change the CSS disabled setting of the id element
     */
    onchange_field : function (node, id, op, value) {

        switch (op) {
            case "eq":
                var v = M.datafield_admin.get_value(node);
                var disabled = (v==value ? true : false);
                break;
            case "neq":
                var v = M.datafield_admin.get_value(node);
                var disabled = (v==value ? false : true);
                break;
            case "in":
                var v = M.datafield_admin.get_value(node);
                var disabled = (value.split(",").indexOf(v)>=0);
                break;
            case "checked":
                var checked = node.get("checked");
                var disabled = (checked==true);
                break;
            case "notchecked":
                var checked = node.get("checked");
                var disabled = (checked==false);
                break;
            case "noitemselected":
                var i = node.get("selectedIndex");
                var disabled = (i==-1);
                break;
            default:
                var disabled = false; // shouldn't happen !!
        }
        Y.one("#" + id).set("disabled", disabled);
    },

    /**
     * getFormElementValue
     *
     * @param  object a YUI node representing a form element
     * @return string representing the value of the YUI node
     */
    get_value : function (node) {

        var elm = Y.Node.getDOMNode(node);
        var t = elm.type;

        if (t=="text" || t=="textarea" || t=="password" || t=="hidden") {
            return elm.value;
        }

        // the standard string used by the Database module
        // to join groups of radio/checkbox elements
        var joiner = "##";

        if (t=="select-one" || t=="select-multiple") {
            var i_max = elm.options.length;
            for (var v="", i=0; i<i_max; i++) {
                if (elm.options[i].selected) {
                    v += (v=="" ? "" : joiner) + elm.options[i].value;
                }
            }
            return v;
        }

        if (t=="button" || t=="reset" || t=="submit") {
            return "";
        }

        if (t=="radio" || t=="checkbox") {
            // get ALL radio/checkbox items with this name
            elm = elm.form.elements[elm.name];
        }

        // radio or checkbox groups
        var i_max = elm.length || 0;
        for (var v="", i=0; i<i_max; i++) {
            if (elm[i].checked) {
                v += (v=="" ? "" : joiner) + elm[i].value;
            }
        }
        return v;
    },

    /**
     * setup_field
     *
     * @param object
     * @param string id    : the id of the element to be disabled
     * @param string value : the condition value
     * @return void, but may set value of node with the specified id
     */
    set_default_value : function (Y, id, value) {

        var elm = document.getElementById(id);
        if (elm==null) {
            return;
        }
        var node = null;
        var t = elm.type;
        if (t=="text" || t=="textarea" || t=="checkbox" || t=="radio" || t=="select-one" || t=="select-multiple") {
            node = Y.one(elm);
        }
        if (t=="checkbox" || t=="radio") {
            node = Y.all(elm.form.elements[elm.name]);
        }
        if (typeof(t)=="undefined") {
            node = Y.all(elm);
        }
        if (node) {
            if (M.datafield_admin.get_value(node)=='') {
                Y.one("#" + id).set("value", value);
            }
        }
    }
};
