<?php

require_once('../config.php');

$results = array();
//get all courses
$courses = $DB->get_records('course',array());

//get courses data
foreach ($courses as $course){
	$view_num = $DB->get_record_sql('SELECT COUNT(*) AS view_num FROM mdl_log WHERE course='.$course->id.' AND action="view"')->view_num;
	$post_num = $DB->get_record_sql('SELECT COUNT(*) AS post_num FROM mdl_log WHERE course='.$course->id.' AND action LIKE"add%"')->post_num;
	$score = $view_num + $post_num * 10;
	$result = array('id'=>$course->id,'fullname'=>$course->fullname,'view_num'=>$view_num,'post_num'=>$post_num,'score'=>$score);
	$results[] = $result;
}

//sort by score
foreach ($results as $key => $row){
	$score_a[$key] = $row['score'];
}
array_multisort($score_a,SORT_DESC,$results);

//print table header
echo '<table border=1>';
if(count($results) > 0){
	echo '<tr>';
	foreach ($results[0] as $key => $value){
		echo '<td>'.$key.'</td>';
	}
	echo '</tr>';
}
//print result table
foreach ($results as $result){
	echo '<tr>'.
		'<td>'.$result['id'].'</td>'.
		'<td>'.$result['fullname'].'</td>'.
		'<td>'.$result['view_num'].'</td>'.
		'<td>'.$result['post_num'].'</td>'.
		'<td>'.$result['score'].'</td>'.
		'<tr>';
}
echo '</table>';