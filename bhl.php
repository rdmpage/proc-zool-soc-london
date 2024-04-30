<?php

$sheet1 = "BHL Proceedings of the Zoological Society of London - Downloaded from BHL 18 April 2024.tsv";

$sheet1 = "SegmentsForTitle44963-20240427194947.txt";

$sheet1_keys = array(
'Segment ID',
'Title',
'Translated Title',
'Item ID',
'Volume',
'Issue',
'Series',
'Date',
'Language',
'Author IDs',
'Author Names',
'Start Page',
'End Page',
'Start Page BHL ID',
'End Page BHL ID',
'Additional Page IDs',
'Article DOI',
'Contributors',
);

$sheet1_key_map = array(
'Segment ID' => 'part',
'Title' => 'title',
//'Translated Title',
//'Item ID',
'Volume' => 'volume',
'Issue' => 'issue',
//'Series',
//'Date',
//'Language',
//'Author IDs',
//'Author Names' => 'authors',
'Start Page' => 'spage',
'End Page' => 'epage',
//'Start Page BHL ID',
//'End Page BHL ID',
//'Additional Page IDs',
'Article DOI' => 'doi',
//'Contributors',


);

$std_keys = array_values($sheet1_key_map);
$std_keys[] = 'year';
$std_keys[] = 'bhl';
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
			$std_obj->id = 'bhl' . str_pad($row_count, 5, '0', STR_PAD_LEFT);
		
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
					case 'Start Page BHL ID':
						$std_obj->bhl = preg_replace('/https?:\/\/(www.)?biodiversitylibrary.org\/page\//', '', $v);
						break;
						
					case 'Date':
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
	$row_count++;	
	
}	

?>
