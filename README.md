
## All shortname that have to be changed

- [left side navigation panel] you can go to admin/settings.php?section=navigation to check the "Show course full names" checkbox

- [question bank->questions->Select category course name](https://github.com/yuenpaklam/moodle_shortname/tree/master/question/classes/bank/search/category_condition.php) function question_category_list

- [question bank->import->import category and question bank->export->export category](https://github.com/yuenpaklam/moodle_shortname/blob/master/lib/questionlib.php) question_category_select_menu_jstree

- [course->calendar->page title](https://github.com/yuenpaklam/moodle_shortname/blob/master/calendar/view.php) $PAGE->set_title("$course->fullname: $strcalendar: $pagetitle");

- [Calendar left top corner selector] doing 


