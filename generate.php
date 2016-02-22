<?php
	function GetMainUrl($object){
		//cleans url's http:// part
		$pos=strpos($object->url->loc,GetProjectName($object));
		return substr_replace($object->url->loc,"",0,$pos);
	}
	//gets main url of site
	function GetProjectName($object){
		$name=$object->url->loc;
		$pos=strpos($name,"://")+3;
		$name=substr_replace($name,"",0,$pos);
		$from_end=strlen($name)-strpos($name, ".co");
		return substr_replace($name, "",-$from_end);
	}
  	function PutLinksIntoGraph($object,$gv){
  		foreach ($object as $url){
			$loc=$url->loc;
			$pos=strpos($loc,".co")+3;					
			$node=substr_replace($loc,"",0,$pos);
			if(strlen($node)>1){//if link is exists
				//delete "/" tags 
				$node=substr_replace($node,"",0,1);//baştaki / i sil
				$node=substr_replace($node,"",-1);//sondaki / i sil
				$gv->addEdge(array('http://'.GetMainUrl($object) => $node));
			}		
		}
		return $gv;
	}

	function PutLinksIntoArray($object){
		$arr=array();
		$site=$object->url->loc;
		foreach ($object as $url){
			$loc=$url->loc;
			$pos=strpos($loc,".co")+3;					
			$node=substr_replace($loc,"",0,$pos);
			if(strlen($node)>1){//if link is exists
				//delete "/" tags 
				$node=substr_replace($node,"",0,1);//baştaki / i sil
				$node=substr_replace($node,"",-1);//sondaki / i sil
				array_push($arr,$node);
				
				}		
		}
		return $arr;
	}
	function CreatePNG($gv,$object){
		$file = fopen(GetProjectName($object).".png", "wb");
		$raw_data = $gv->fetch('png');
		fwrite ($file, $raw_data);
		fclose($file);
	}

	function CreateJSON($object){
		//decode the template for the new json file
		$json=json_decode("template.json",true);
		//extend this template with adding new values
		$json->project=GetProjectName($object);//add site name
		$json->url=GetMainUrl($object);//add sites main url.for example "google.com"
		$arr_pushed=array();
		$arr=PutLinksIntoArray($object);//add child links of site into an array
		foreach ($arr as  $value) {
			//check if this child link was not added before
			if(IF_NOT_EXISTS($arr_pushed,$value)){
				$json->sitemap[]=$value;//push into sitemap
				array_push($arr_pushed,$value);//add into pusheds
			}
		}
		//finally create new json file for the specific site
		file_put_contents(GetProjectName($object).".json", json_encode($json));
	}


	function IF_NOT_EXISTS($arr_pushed,$value){
		$do_push=true;
		foreach ($arr_pushed as $pushed){
			if($value==$pushed){
				$do_push=false;
				break;
			}
		}
		return $do_push;
	}

	$shell_command="linkchecker -r1 -ositemap ";
	//get additional parameters
	foreach ($argv as $key) {
		# code...
		if($key!==$argv[0]){
			$shell_command=$shell_command." ".$key;
		}
		
	}
	$shell_command=$shell_command.">site.xml";
	//run linkchecker
	exec($shell_command);
	//get sitemap's xml
	$object=simplexml_load_file("site.xml") or die("Error: Cannot create object\n");
	require_once 'GraphViz.php';
	$gv = new Image_GraphViz();
	$gv=PutLinksIntoGraph($object,$gv);
	//creating png image of sitemap
	CreatePNG($gv,$object);
	//creating json file
	CreateJSON($object);
?>