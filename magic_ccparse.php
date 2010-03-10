<?php
#ini_set('display_errors', 'off');

#color wheel node
class cwn {
	public $next;
	public $data;
	public function __construct($data){$this->data = $data;}
}

#Magic Colorcode parser
function ccparse($str=''){
	$str = strtoupper($str);

	$colorless = 0;
	$colors = array();
	//var_dump($matches);

	#pass 1 get curly brace code
	preg_match_all('/\{([^}]+)}/', $str, $matches);
	//var_dump($matches);
	foreach ($matches[1] as $match){
		preg_match_all('/\d+/', $match, $matches2);
		$colorless2 = 0; 
		foreach ($matches2[0] as $match2){
			$colorless2 += $match2;
		}

		if(empty($colorless2)){
			$colorless2 = '';
		}
		$match = preg_replace('/\d+/', '', $match);

		//reorder doubles
		$arr = str_split($match);
		usort($arr,'magic_cc_cmp');
		$match = implode($arr);


		$match = $colorless2 . $match;

		if(isset($colors[strlen($match)][$match])){
			$colors[strlen($match)][$match]++;
		}else{
			$colors[strlen($match)][$match] = 1;
		}

		if(isset($colors[0][$match])){
			$colors[0][$match]++;
		}else{
			$colors[0][$match] = 1;
		}
	}
	$str = preg_replace('/{[^}]+\}/', '', $str); //remove curly brace codes

	#pass 2 get colorless
	#get all digits that are next to each other
	preg_match_all('/\d+/', $str, $matches);
	foreach ($matches[0] as $match){
		$colorless += $match;
	}

	#pass 3 parse out regular codes
	$str = preg_replace('/\d+|\s+/', '', $str); //remove digits, whitespace

	$len = strlen($str);
	for($i = 0; $i < $len; $i++){
		if(isset($colors[1][$str[$i]])){
			$colors[1][$str[$i]]++;
		}else{
			$colors[1][$str[$i]] = 1;
		}
		if(isset($colors[0][$str[$i]])){
			$colors[0][$str[$i]]++;
		}else{
			$colors[0][$str[$i]] = 1;
		}
	}

	/*X will be parsed as $colors[0]['X'], should probably check to see if
		that's present*/
	$Xs = 0;
	if (isset($colors[1]['X'])){
		$Xs = $colors[1]['X'];
		unset($colors[1]['X']);
	}

	if(isset($colors[5]['WUBRG'])){
		$colorless += $colors[5]['WUBRG']; //All color mana is the same thing as
		//colorless mana. or is it?
		unset($colors[5]['WUBRG']);
	}

	//render colorless and X
	$out = str_repeat('X', $Xs);
	if(!empty($colorless) || empty($colors)){
		$out .= $colorless;
	}

	/*
	//Render multi-colors
	for($i = 4; $i > 1; $i--){
		if(is_array($colors[$i])) {
			uksort($colors[$i], 'magic_cc_sort_cmp');
			foreach($colors[$i] as $color => $qty){
				$out .= str_repeat('{' . $color . '}', $qty);
			}
		}
	}
	//Render single colors
	if (is_array($colors[1])){
		uksort($colors[$i],'magic_cc_sort_cmp');
		foreach ($colors[1] as $color => $qty){
			$out .= str_repeat($color, $qty);
		}
	}
	 */

	//Render all colors at once (sort "in-between" instead of "multis-first")
	uksort($colors[0], 'magic_cc_sort_cmp');
	foreach($colors[0] as $color => $qty){
		if(strlen($color) > 1){
			$out .= str_repeat('{' . $color . '}', $qty);
		}else{
			$out .= str_repeat($color, $qty);
		}
	}

	echo $out . "\n";
}

//sorts an array (string() => int()) where they key is a colorcode and int is a quantity

//compares two colorcodes (should be same length in order to get proper results)
//should be sorted before input
function magic_cc_sort_cmp($a, $b){
	$a = strtr($a,'WUBRG','abcde');
	$b = strtr($b, 'WUBRG','abcde');
	return strcmp($a,$b);
}

//compare two colors
function magic_cc_cmp($a,$b){
	$colorwheel = $W = new cwn('W');
	$U = new cwn('U');
	$B = new cwn('B');
	$R = new cwn('R');
	$G = new cwn('G');
	$W->next = $U;
	$U->next = $B;
	$B->next = $R;
	$R->next = $G;
	$G->next = $W;

	if($a == $b) return 0;

	$current = $$a;
	for($i = 0; $i < 3; $i++){ //if less than 3 hops away, less than
		if($current->data  == $b){
			return -1;
		}
		$current = $current->next;
	}
	return 1; //else greater than
}

//ccparse('3W');
//ccparse('34W');
//ccparse('3 4W');
//ccparse('5WGB {GB} {BG} {3B} {RGU}');
//ccparse("{GB}{BG}{WU}{UW}{UB}{BU}{BR}{RB}{GW}{WG}{WB}{UR}{RU}{RW}{WR}{GU}{UG}");
//ccparse("{WUBRG}{GRBUW}");

//ccparse("XR");
//ccparse("");
ccparse("{RG}RG");
ccparse('{2G}{BG}{RG}');
ccparse('W{BU}');
ccparse('{2BU}{2BU}{2BU}');
