<?php

$sheet1 = "BHL Proceedings of the Zoological Society of London - Bob & Heidi data from 2020.tsv";
$sheet1_keys = array(
'journal',
'issn',
'year',
'volume',
'Part',
'issue',
'title',
'authors',
'start page',
'end page',
'BHL URL: start page',
'date',
'doi',
'biostor',
'pageid'
);

$sheet1_key_map = array(
'journal' => 'journal',
'issn' => 'issn',
'year' => 'year',
'volume' => 'volume',
//'Part',
'issue' => 'issue',
'title' => 'title',
'authors' => 'authors',
'start page' => 'spage',
'end page' => 'epage',
//'BHL URL: start page',
'date' => 'date',
'doi' => 'doi',
'biostor' => 'biostor',
//'pageid',
);

$std_keys = array_values($sheet1_key_map);
$std_keys[] = 'bhl';
array_unshift($std_keys, 'id');

echo join("\t", $std_keys) . "\n";


$headings = array();

$row_count = 0;

$filename = "BHL Proceedings of the Zoological Society of London - Bob & Heidi data from 2020.tsv";

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
			$std_obj->id = 'bobheidi' . str_pad($row_count, 5, '0', STR_PAD_LEFT);
		
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
