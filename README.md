
## All shortname that have to be changed

- [left side navigation panel] you can go to admin/settings.php?section=navigation to check the "Show course full names" checkbox

- [question bank->questions->Select category course name](https://github.com/yuenpaklam/moodle_shortname/tree/master/question/classes/bank/search) function question_category_list

question bank->import->import category
question bank->export->export category function  question_category_select_menu_jstree in lib/questionlib.php

Calendar left top corner 

course->calendar->title $PAGE->set_title("$course->fullname: $strcalendar: $pagetitle"); in calendar/view.php
