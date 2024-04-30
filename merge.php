<?php


require_once (dirname(__FILE__) . '/compare.php');

// merge

//----------------------------------------------------------------------------------------
// Disjoint-set data structure

// https://en.wikipedia.org/wiki/Disjoint-set_data_structure
$parents = array();

// initialise element to be a member of its own set
function makeset($x) {
	global $parents;
	
	$parents[$x] = $x;
}

// find with path compression
function find($x) {
	global $parents;
	
	if ($parents[$x] != $x) 
	{
		$parents[$x] = find($parents[$x]);
		return $parents[$x];
	} else {
		return $x;
	}
}

// make two elements belong to same set
function union($x, $y) {
	global $parents;
	
	$x_root = find($x);
	$y_root = find($y);
	$parents[$x_root] = $y_root;
	
}

function dumpset()
{
	global $parents;
	
	echo "Disjoint set forest\n";
	foreach ($parents as $x => $parent)
	{
		echo $x .  ' -> ' . $parent . "\n";
	}
}

//----------------------------------------------------------------------------------------
function sheet_filename_from_id($id)
{
	$filename = "unknown.tsv";
	
	if (preg_match('/^([a-z]+)/', $id, $m))
	{
		$filename = $m[1] . '.tsv';
	}
	
	return $filename;
}


				
				
//----------------------------------------------------------------------------------------

				

$sheet_names = array('bobheidi.tsv', 'bhl.tsv', 'crossref.tsv');

$sheet_by_filename = array();

$sheets = array();

foreach ($sheet_names as $index => $filename)
{
	$sheet_by_filename[$filename] = $index;

	$sheets[$index] = array();
	$row_count = 0;

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
		
				foreach ($row as $k => $v)
				{
					if (trim($v) != '')
					{
						$obj->{$headings[$k]} = trim($v);
					}
				}
				
				$sheets[$index][$obj->id] = $obj;
				
				makeset($obj->id);
			}
		}
		
		$row_count++;	
	}	
}

//rint_r($sheets);

//exit();

// block by various features

if (1)
{
	// Do they share DOIs?
	$dois = array();
	foreach ($sheets as $index => $sheet)
	{
		foreach ($sheet as $id => $obj)
		{
			if (isset($obj->doi))
			{
				if (!isset($dois[$obj->doi]))
				{
					$dois[$obj->doi] = array();
				}
				$dois[$obj->doi][] = $obj->id;
			}
		}
	}

	// merge with same DOI
	foreach ($dois as $doi => $members)
	{
		$n = count($members);
	
		if ($n > 1)
		{
			for ($i = 0; $i < $n - 1; $i++)
			{
				union($members[$i], $members[$n-1]);
			}
		}
	}
}


if (0)
{
	// Do they share page IDs, doesn't work as used different items :O
	$page_ids = array();
	
	foreach ($sheets as $index => $sheet)
	{
		foreach ($sheet as $id => $obj)
		{
			if (isset($obj->bhl))
			{
				if (!isset($dois[$obj->bhl]))
				{
					$page_ids[$obj->bhl] = array();
				}
				$page_ids[$obj->bhl][] = $obj->id;
			}
		}
	}

	print_r($page_ids);

	// merge with same PageID
	// to do: sanity check based on text similarity
	foreach ($page_ids as $page_id => $members)
	{
		$n = count($members);
	
		if ($n > 1)
		{
			for ($i = 0; $i < $n - 1; $i++)
			{
				union($members[$i], $members[$n-1]);
			}
		}
	}
}

if (1)
{
	// block by years
	$years = array();
	
	foreach ($sheets as $index => $sheet)
	{
		foreach ($sheet as $id => $obj)
		{
			if (isset($obj->year))
			{
				if (!isset($years[$obj->year]))
				{
					$years[$obj->year] = array();
				}
				$years[$obj->year][] = $obj->id;
			}
		}
	}
	
	ksort($years);

	//print_r($years);
	
	// cluster titles 
	
	foreach ($years as $year => $members)
	{
		// echo "year=$year\n";
		
		//print_r($members);
		
		$spages = array();
		
		$titles = array();
		foreach ($members as $item)
		{
			$sheet = sheet_filename_from_id($item);
				
			$text = $sheets[$sheet_by_filename[$sheet]][$item]->title;
			
			$spage = -1;
			if (isset($sheets[$sheet_by_filename[$sheet]][$item]->spage))
			{
				$spage = $sheets[$sheet_by_filename[$sheet]][$item]->spage;
			}
							
			$spages[$item] = $spage;
			
			$text = strip_tags($text);
			
			// cleaning
			$text = preg_replace('/^\d+\s*\.\s+/', '', $text);
			$text = preg_replace('/“/u', '"', $text);
			
			$titles[$item] = $text;
		}
		
		// print_r($spages);
				
		asort($titles);
		
		// print_r($titles);
		
		$keys = array_keys($titles);
		$n = count($keys);
		
		for ($i = 0; $i < $n - 1; $i++)
		{
			$j = $i + 1;
			
			$text1 = $titles[$keys[$i]];
			$text2 = $titles[$keys[$j]];
			
			//echo $text1 . "\n";
			//echo $text2 . "\n";
			
			$result = compare_levenshtein($text1, $text2);
			
			//print_r($result);
			
			$go = false;
			
			if ($result->normalised > 0.95)
			{
				$go = true;
			}
			
			// sanity check
			
			// starting pages must match
			$go = $spages[$keys[$i]] == $spages[$keys[$j]];
				
			if ($go)
			{
				// echo $keys[$i] . '=' . $keys[$j] . "\n";
			
				union($keys[$i], $keys[$j]);
			}
			
		
		}
	}	
	
	

	/*
	// merge with same PageID
	foreach ($page_ids as $page_id => $members)
	{
		$n = count($members);
	
		if ($n > 1)
		{
			for ($i = 0; $i < $n - 1; $i++)
			{
				union($members[$i], $members[$n-1]);
			}
		}
	}
	*/

}

// Dump clusters
$clusters = array();

foreach ($parents as $x => $parent)
{
	if (!isset($clusters[$parent]))
	{
		$clusters[$parent] = array();
	}
	$clusters[$parent][] = $x;
}

if (0)
{
	echo "Clusters\n";
	print_r($clusters);
}

if (0)
{
	$count_items = 0;
	$count_clustered = 0;

	foreach ($clusters as $p => $elements)
	{
		$count_items++;
	
		if (count($elements) > 1)
		{
			$count_clustered ++;
		}
	
		if (count($elements) > 1)
		{
			echo "\n$p:\n";
			foreach ($elements as $item)
			{
				$sheet = sheet_filename_from_id($item);
				$sheet = $m[1] . '.tsv';
				echo "   $item: " . $sheets[$sheet_by_filename[$sheet]][$item]->title . "\n";
			}
		}
	}


	echo "$count_items items, of which $count_clustered are clustered with at least one other item\n";
}

// nice dump to explore....

$years = array();
foreach ($clusters as $p => $elements)
{
	$sheet = sheet_filename_from_id($p);
	
	$obj = $sheets[$sheet_by_filename[$sheet]][$p];
	
	$year = 1000;
	$spage = -1;
	
	if (isset($obj->year))
	{
		$year = $obj->year;
	}

	if (isset($obj->spage))
	{
		$spage = $obj->spage;
	}
	
	if (!isset($years[$year]))
	{
		$years[$year] = array();
	}
	if (!isset($years[$year][$spage]))
	{
		$years[$year][$spage] = array();
	}
	
	$set_obj = new stdclass;
	
	$set_obj->title = $obj->title;
	
	$set_obj->sources = array();
	
	foreach ($elements as $item)
	{
		$sheet = sheet_filename_from_id($item);
		$e = $sheets[$sheet_by_filename[$sheet]][$item];
		
		$kv = array('doi', 'year', 'volume', 'issue', 'spage', 'epage', 'authors');
		
		foreach ($kv as $k)
		{
			if (isset($e->{$k}))
			{
				$set_obj->{$k}[] = $e->{$k};
			}
		}
		
		if (isset($e->bhl))
		{
			$set_obj->bhl[] = $e->bhl;
		}
		
		$set_obj->sources[] =  $item;
	}
	
	foreach ($set_obj as $k => $v)
	{
		if (is_array($v))
		{
			$set_obj->{$k} = array_unique($v);
		}
	}
	
	$years[$year][$spage][] = $set_obj;
}


ksort($years);

foreach ($years as $year => &$articles)
{
	ksort($articles, SORT_NATURAL);
}

// print_r($years);

echo '<html>';
foreach ($years as $year => $articles)
{
	echo '<h2>' . $year . '</h2>' . "\n";
	
	echo '<ul>';
	
	foreach ($articles as $spage => $parts)
	{
		echo '<li>';
		echo "[" . $spage . "]";
		
		echo '<ul>';
		
		foreach ($parts as $part)
		{
			$colour = 'white';
			
			
			if ($colour == 'white')
			{
				foreach ($part->sources as $id)
				{
					if ($colour == 'white')
					{
						if (preg_match('/^bhl/', $id))
						{
							$colour = "#73FA79";
						}
					}
 				}
 			}	
 			
			if ($colour == 'white')
			{
				if (isset($part->bhl) && count($part->bhl) > 1)
				{
					$colour = "#FF9300";
				}
			}
 				
		
			echo '<li style="';
			
			echo "background:$colour;";
			
			echo '">';
			echo $part->title;
			
			echo ' ' . join(" | ", $part->sources);
			
			/*
			if (isset($part->bhl) && count($part->bhl) > 1)
			{
				echo " danger!";
			}
			*/
			
			if (isset($part->doi))
			{
				echo '<br>';
				foreach ($part->doi as $doi)
				{
					echo '<a href="https://doi.org/' . $doi . '">' . $doi . '</a>';
				}			
			}
			
			echo '</li>';
		}
		echo '</ul>';
		echo '</i>';
	}
	
	echo '</ul>';

}
echo '</html>';







?>
