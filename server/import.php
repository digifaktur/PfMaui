<?php

// set working dir
chdir('/path/to/script');

// delete old files
unlink('basic.ics');

// download ics files: foreach calendar ...
$googlecal1 = file_get_contents('https://calendar.google.com/FROM-GOOGLE.ics');
file_put_contents('basic.ics', $googlecal1);

// connect to database
$mysqli = new mysqli('HOST', 'USER', 'PASS', 'DB');
// delete old entries
$mysqli->query('DELETE FROM entry');

// insert from files
$files = array('basic' => 'basic.ics', 'second' => 'second.ics', 'third' => 'third.ics');

foreach ($files as $group => $file) {
        $start = '';
        $end = '';
        $subject = '';
        $repeat = '';
        $isevent = false;
		// a crude and faulty ICS parser
        foreach(file($file) as $line) {
                $values = explode(':', $line, 2);
                if (count($values) > 1) {
                        $values[1] = trim($values[1]);
                        if ($values[0] == 'BEGIN' && $values[1] != 'VEVENT') {
                                $isevent = false;
                        }
                        else if ($values[0] == 'BEGIN' && $values[1] == 'VEVENT') {
                                $isevent = true;
                        }
						// skip all non-event entries
                        if (!$isevent) continue;
						
                        else {
							// start a new entry
							// start date
							if(str_starts_with($values[0], 'DTSTART')) {
									if (strlen($values[1]) > 9) {
											$start = DateTime::createFromFormat('Ymd\THis\Z', $values[1], new DateTimeZone('UTC'));
											if ($start === false) {
													$start = DateTime::createFromFormat('Ymd\THis', $values[1], new DateTimeZone('UTC'));
											}
											
											$start->setTimeZone(new DateTimeZone('Europe/Vienna'));
									}
									else {
											$start = DateTime::createFromFormat('Ymd', $values[1]);
											$repeat .= ';GDAY;';
									}
									$end = $start;
							}
							// end date
							else if(str_starts_with($values[0], 'DTEND')) {
									if (strlen($values[1]) > 9) {
											$end = DateTime::createFromFormat('Ymd\THis*', $values[1], new DateTimeZone('UTC'));
											if ($end === false) {
													$end = DateTime::createFromFormat('Ymd\THis', $values[1], new DateTimeZone('UTC'));
											}
											$end->setTimeZone(new DateTimeZone('Europe/Vienna'));
									}
									else {
											$end = DateTime::createFromFormat('Ymd', $values[1]);
									}
							}
							
							// subject
							else if(str_starts_with($values[0], 'SUMM')) {
									$subject = mysqli_real_escape_string($mysqli, $values[1]);
							}
							
							// repetition rule
							else if(str_starts_with($values[0], 'RRUL')) {
									$arr_repeat = explode('=', $values[1]);
									for($i = 0; $i < count($arr_repeat); $i++) {
											if ($arr_repeat[$i] == 'FREQ') {
													$repeat .= mysqli_real_escape_string($mysqli, $arr_repeat[$i + 1]);
													break;
											}
									}
							}
							// insert into DB when END tag is found
							else if(str_starts_with($values[0], 'END')) {
									if ($values[1] == 'VEVENT') {
											$sql = 'INSERT INTO entry(start, end, subject, repeats, calendar)
											VALUES (\''.$start->format('Y-m-d H:i:s').'\', \''.$end->format('Y-m-d H:i:s').'\', \''.$subject.'\', \''.$repeat.'\', \''.$group.'\')';
											$mysqli->query($sql);
									}
									$start = '';
									$end = '';
									$subject = '';
									$repeat = '';
							}
                        }
                }
        }
}

?>
