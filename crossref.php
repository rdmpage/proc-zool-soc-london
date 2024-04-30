<?php

$sheet1 = "BHL Proceedings of the Zoological Society of London - Downloaded from CrossRef 18 April 2024.tsv";
$sheet1_keys = array(
'Journal Title',
'Article Title',
'Volume',
'Issue',
'Published Date',
'Authors',
'Start Page',
'End Page',
'DOI'
);

$sheet1_key_map = array(
'Journal Title' => 'journal',
'issn' => 'issn',
'year' => 'year',
'Volume' => 'volume',
//'Part',
'Issue' => 'issue',
'Article Title' => 'title',
'Authors' => 'authors',
'Start Page' => 'spage',
'eEnd Page' => 'epage',
//'BHL URL: start page',
'date' => 'date',
'DOI' => 'doi',
'biostor' => 'biostor',
//'pageid',
// 'Published Date'
);

$std_keys = array_values($sheet1_key_map);
$std_keys[] = 'year';
array_unshift($std_keys, 'id');

echo join("\t", $std_keys) . "\n";

$headings = array();

$row_count = 0;

$filename = $sheet1;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	$go = is_array($row) && count($row) > 1;
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			$obj = new stdclass;
			
			$std_obj = new stdclass;
			$std_obj->id = 'crossref' . str_pad($row_count, 5, '0', STR_PAD_LEFT);
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
		
			//print_r($obj);	
			
			foreach ($obj as $k => $v)
			{
				switch ($k)
				{
					case 'BHL URL: start page':
						$std_obj->bhl = preg_replace('/https?:\/\/(www.)?biodiversitylibrary.org\/page\//', '', $v);
						break;
						

					case 'Published Date':
						$std_obj->year = substr($v, 0, 4);
						break;
						
					default:
						if (isset($sheet1_key_map[$k]))
						{
							$std_obj->{$sheet1_key_map[$k]} = $v;
						}
						break;
					
				
				}
			
			}
			
			
			//print_r($std_obj);
			
			// filter
			if (isset($std_obj->doi) && preg_match('/-\d{4}\.([0-9]{4})\.tb/', $std_obj->doi, $m))
			{
				$year = (Integer)$m[1];
				
				if ($year <= 1923)
				{
					if (!isset($std_obj->year))
					{
						$std_obj->year = $year;
					}
				
				
					$output = array();	
			
					foreach ($std_keys as $k)
					{
						if (isset($std_obj->{$k}))
						{
							$output[] = $std_obj->{$k};
						}
						else
						{
							$output[] = '';
						}
					}
					echo join("\t", $output) . "\n";
				}
			}
			
		}
	}	
	$row_count++;	
	
}	

?>
