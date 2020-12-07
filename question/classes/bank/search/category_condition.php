<?php
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
 * A search class to control from which category questions are listed.
 *
 * @package   core_question
 * @copyright 2013 Ray Morris
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\bank\search;
defined('MOODLE_INTERNAL') || die();

/**
 *  This class controls from which category questions are listed.
 *
 * @copyright 2013 Ray Morris
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_condition extends condition {
    /** @var \stdClass The course record. */
    protected $course;

    /** @var \stdClass The category record. */
    protected $category;

    /** @var array of contexts. */
    protected $contexts;

    /** @var bool Whether to include questions from sub-categories. */
    protected $recurse;

    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    /** @var array query param used in where. */
    protected $params;

    /** @var string categoryID,contextID as used with question_bank_view->display(). */
    protected $cat;

    /** @var int The maximum displayed length of the category info. */
    protected $maxinfolength;

    /**
     * Constructor
     * @param string     $cat           categoryID,contextID as used with question_bank_view->display()
     * @param bool       $recurse       Whether to include questions from sub-categories
     * @param array      $contexts      Context objects as used by question_category_options()
     * @param \moodle_url $baseurl       The URL the form is submitted to
     * @param \stdClass   $course        Course record
     * @param integer    $maxinfolength The maximum displayed length of the category info.
     */
    public function __construct($cat = null, $con = null, $recurse = false, $contexts, $baseurl, $course, $maxinfolength = null) {
        $this->cat = $cat;
        $this->con = $con;
        $this->recurse = $recurse;
        $this->contexts = $contexts;
        $this->baseurl = $baseurl;
        $this->course = $course;
        $this->init();
        $this->maxinfolength = $maxinfolength;
    }

    /**
     * Initialize the object so it will be ready to return where() and params()
     */
    private function init() {
        global $DB;
        //CORE HACK: Turn category into array to enable multiple cats search
        //Recursive search disabled as it is redundant under tree view with checkbox
        /*
        if (!$this->category = $this->get_current_category($this->cat)) {
            return;
        }
        if ($this->recurse) {
            $categoryids = question_categorylist($this->category->id);
        } else {
            $categoryids = array($this->category->id);
        }
        list($catidtest, $this->params) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
        $this->where = 'q.category ' . $catidtest;
        */
        if (!$this->categories = $this->get_current_categories($this->cat, $this->con)) {
            return;
        }
        list($catidtest, $this->params) = $DB->get_in_or_equal(explode(',', $this->cat), SQL_PARAMS_NAMED, 'cat');
        $this->where = 'q.category ' . $catidtest;    
    }

    public function where() {
        return  $this->where;
    }

    public function params() {
        return $this->params;
    }

    /**
     * Called by question_bank_view to display the GUI for selecting a category
     */
    public function display_options() {
        $this->display_category_form($this->contexts, $this->baseurl, $this->cat);
        //CORE HACK: Category info is not needed
        //$this->print_category_info($this->category);
    }

    /**
     * Displays the recursion checkbox GUI.
     * question_bank_view places this within the section that is hidden by default
     */
    public function display_options_adv() {
        echo \html_writer::start_div();
        echo \html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'recurse',
                                               'value' => 0, 'id' => 'recurse_off'));
        echo \html_writer::checkbox('recurse', '1', $this->recurse, get_string('includesubcategories', 'question'),
                                       array('id' => 'recurse_on', 'class' => 'searchoptions'/*,'onclick' => 'document.getElementById("displayoptions").submit()'*/));
        echo \html_writer::end_div() . "\n";
    }

    /**
     * Display the drop down to select the category.
     *
     * @param array $contexts of contexts that can be accessed from here.
     * @param \moodle_url $pageurl the URL of this page.
     * @param string $current 'categoryID,contextID'.
     */
    
    protected function display_category_form($contexts, $pageurl, $current) {
        global $OUTPUT;
        
        echo \html_writer::start_div('choosecategory mb-10');
        echo \html_writer::start_tag('button', array('class' => 'btn btn-secondary dropdown-toggle mr-5', 
                                                    'type' => 'button', 
                                                    'id' => 'dropdownMenuButton',
                                                    'data-toggle' => 'dropdown',
                                                    'aria-haspopup' => 'true', 
                                                    'aria-expanded' => 'false'));
        echo "Select categories ";
        echo \html_writer::end_tag('button');
        echo \html_writer::start_tag('button', array('class' => 'btn btn-secondary', 
                                                    'type' => 'submit',
                                                    'value' => 'submit',
                                                    'id' => 'categorySubmitButton'));
        echo "Search";
        echo \html_writer::end_tag('button');
        echo \html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'cat', 'value' => '', 'id' => 'selectedcategories'));
        echo \html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'con', 'value' => '', 'id' => 'selectedcontexts'));

        echo \html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'lastchanged', 'value' => null, 'id' => 'lastchanged'));
        
        echo \html_writer::start_div('categorytree dropdown-menu', array('aria-labelledby' => 'dropdownMenuButton'));
        $this->question_category_list($contexts);
        echo \html_writer::end_div() . "\n";
        echo \html_writer::end_div() . "\n";
    }
    
    protected function question_category_list($contexts) {
        echo \html_writer::start_tag('ul');
        foreach ($contexts as $context){
            $categories = get_categories_for_contexts($context->id, 'parent, sortorder, name ASC', false);   
            if (!empty($categories)){
                echo \html_writer::start_tag('li');
                echo $context->get_context_name(true, false);

                foreach (array_keys($categories) as $catid) {
                    $categories[$catid]->childids = array();
                }
                
                $toplevelcategoryids = array();
                
                foreach (array_keys($categories) as $catid) {
                    if (!empty($categories[$catid]->parent) &&
                            array_key_exists($categories[$catid]->parent, $categories)) {
                        $categories[$categories[$catid]->parent]->childids[] = $catid;
                    } else {
                        $toplevelcategoryids[] = $catid;
                    }
                }
                
                echo \html_writer::start_tag('ul');
                
                foreach($toplevelcategoryids as $topcatid){
                    echo \html_writer::start_tag('li', array('id' =>  $topcatid . '|' . $categories[$topcatid]->contextid));
                    echo $categories[$topcatid]->name;
                    if (!empty($countstring = $categories[$topcatid]->questioncount)){
                        echo " ($countstring)";
                    }
                    $this->build_sub_list($categories,$categories[$topcatid]->childids);
                    echo \html_writer::end_tag('li');
                }
                
                echo \html_writer::end_tag('ul');     
                echo \html_writer::end_tag('li');
                
            }
        }
        echo \html_writer::end_tag('ul');        
    }
    
    protected function build_sub_list($categories, $childids) {
        if (!empty($childids)){
            echo \html_writer::start_tag('ul');
            foreach($childids as $id){
                echo \html_writer::start_tag('li', array('id' =>  $id . '|' . $categories[$id]->contextid));
                echo $categories[$id]->name;
                if (!empty($countstring = $categories[$id]->questioncount)){
                    echo " ($countstring)";
                }
                $this->build_sub_list($categories,$categories[$id]->childids);
                echo \html_writer::end_tag('li');
            }
            echo \html_writer::end_tag('ul'); 
        }
    }
    /*
    protected function display_category_form($contexts, $pageurl, $current) {
        global $OUTPUT;

        echo \html_writer::start_div('choosecategory');
        $catmenu = question_category_options($contexts, false, 0, true);
        echo \html_writer::label(get_string('selectacategory', 'question'), 'id_selectacategory');
        // Edited by Label @ 2018-09-06
        //
        //
        // echo \html_writer::select($catmenu, 'category', $current, array(), array('class' => 'searchoptions custom-select', 'id' => 'id_selectacategory'));
        echo "<input type='hidden' name='selectedcat' id='selectedcat' value='" . $_POST['selectedcat'] . "' />";

        $selectedcat = explode('|', $_POST['selectedcat']);
        echo "<div id='id_selectacategory'>";
        $pcount = 0;
        $count = 0;
        $openul = false;
        foreach ($catmenu as $cat) {
            echo "<ul>";
            foreach ($cat as $catparent => $catparentitem) {
                $count++;
                $pid = 'j1_' . $count;

                if (in_array($pid, $selectedcat)) {
                    echo "<li data-checkstate=\"checked\">" . $catparent;
                } else {
                    echo "<li>" . $catparent;
                }
                echo "<ul>";

                $catlevel = -1;

                foreach ($catparentitem as $catid => $catname) {
                    $count++;
                    $cid = $catid;

                    if (substr_count($catname, "&nbsp;") > 0) {
                        $checkcatlevel = substr_count($catname, '&nbsp;') / 2;

                        // var_dump($checkcatlevel);
                        // var_dump($catlevel);

                        if ($checkcatlevel < $catlevel) {
                            $catlevel = $checkcatlevel;
                            echo "</li></ul></li>";
                            $openul = false;
                        } else if ($checkcatlevel > $catlevel) {
                            if ($catlevel > -1) {
                                echo "<ul>";
                            }
                            $catlevel = $checkcatlevel;
                        } else {
                            echo "</li>";
                        }
                    }

                    $catname = substr($catname, strrpos($catname, '&nbsp;'));

                    if (in_array($cid, $selectedcat)) {
                        echo "<li data-jstree='{ \"checked\" : true, \"selected\" : true, \"opened\" : true }' data-checkstate=\"checked\" id='cat_" . $catid . "'>" . $catname . "";
                    } else {
                        echo "<li id='cat_" . $catid . "'>" . $catname . "";
                    }

                    if (strpos($catname, 'Default for') !== false) {
                        $catlevel = -1;
                        echo "<ul>";
                        $openul = true;
                    }
                }

                if ($openul) {
                    echo "</ul>";
                    echo "</li>";    
                }
                
                echo "</ul>";
                echo "</li>";
            }
            echo "</ul></ul>";
        }
        echo "</div>";
        echo "<input type='button' id='SearchCategoryBtn' Value='Search' />";
        echo \html_writer::end_div() . "\n";
    }
    */
    
    //CORE HACK: New function - validates category array and context array and returns categories as an array of records
    //Look up the category record based on cateogry ID and context
    //@param string $cat, $con as used with question_bank_view->display()
    //@return \stdClass The categories array (of records)
     
    protected function get_current_categories($cat, $con) {
        global $DB, $OUTPUT;
        $catarr = explode(',', $cat);
        $conarr = explode(',', $con);
        if (!$catarr[0]) {
            $this->print_choose_category_message($categoryandcontext);
            return false;
        }
        $categories = [];
        foreach ($catarr as $key => $catid){
            if (!$category = $DB->get_record('question_categories',
                    array('id' => $catid, 'contextid' => $conarr[$key]))) {
                echo $OUTPUT->box_start('generalbox questionbank');
                echo $OUTPUT->notification('Category not found!');
                echo $OUTPUT->box_end();
                return false;
            } else {
                $categories[] = $category;
            }
        } 
        return $categories;
    }
    
    /**
     * Look up the category record based on cateogry ID and context
     * @param string $categoryandcontext categoryID,contextID as used with question_bank_view->display()
     * @return \stdClass The category record
     */
    /*protected function get_current_category($categoryandcontext) {
        global $DB, $OUTPUT;
        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        if (!$categoryid) {
            $this->print_choose_category_message($categoryandcontext);
            return false;
        }

        if (!$category = $DB->get_record('question_categories',
                array('id' => $categoryid, 'contextid' => $contextid))) {
            echo $OUTPUT->box_start('generalbox questionbank');
            echo $OUTPUT->notification('Category not found!');
            echo $OUTPUT->box_end();
            return false;
        }

        return $category;
    }*/
    
    //CORE HACK: Category description is not needed
    /**
     * Print the category description
     * @param stdClass $category the category information form the database.
     */
    //CORE HACK: Category description is not needed
    /*
    protected function print_category_info($category) {
        $formatoptions = new \stdClass();
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        echo \html_writer::start_div('boxaligncenter categoryinfo');
        if (isset($this->maxinfolength)) {
            echo shorten_text(format_text($category->info, $category->infoformat, $formatoptions, $this->course->id),
                                     $this->maxinfolength);
        } else {
            echo format_text($category->info, $category->infoformat, $formatoptions, $this->course->id);
        }
        echo \html_writer::end_div() . "\n";
    }*/
}
