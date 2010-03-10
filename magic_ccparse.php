<?php
/** Author: Mark Harviston
 *  Purpose: normalize (sort, reduce) Magic: The Gathering color codes
 *
 *  (c) 2010 Mark Harviston <infinull@gmail.com> Some Rights Reserved
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

#color wheel node
class cwn {
	public $next;
	public $data;
	public function __construct($data){$this->data = $data;}
}

#Magic Colorcode parser
function ccparse($str='', $html=false){
	/* normalize $str or turn into html
	 * if $html set to true, output <img> tags to the gatherer
	 * returns normalized mana cost string or HTML (if $html is true)
	 */
	$str = strtoupper($str);

	//accept the gatherer style strings (2/B) instead of {2B}
	//also turn brackets([]) into curlies just 'coz
	$str = str_replace('/','',$str); //remove '/'
	$str = strtr($str, '()[]','{}{}'); //normalize parentheticals

	assert(preg_match('/^([0-9WUBRGTX]+|\{[0-9WUBRG]+})*$/','{BG}'));
	//must contain only digit, space, color code or {}, X or T(tap)
	//X and/or Tap must not be w/i {}
	//There must be only one nested level of {}

	$colorless = 0;
	$colors = array();
	$colors[0] = array();

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
	$Ts = 0;
	if (isset($colors[1]['T'])){
		$Ts = $colors[1]['T'];
		unset($colors[1]['T']);
	}

	if(isset($colors[5]['WUBRG'])){
		$colorless += $colors[5]['WUBRG']; //All color mana is the same thing as
		//colorless mana. or is it?
		unset($colors[5]['WUBRG']);
	}

	//render colorless and X
	if($html){
		$out = str_repeat(clrtoimg('T'), $Ts);
		$out .= str_repeat(clrtoimg('X'), $Xs);
	}else{
		$out = str_repeat('T', $Ts);
		$out .= str_repeat('X', $Xs);
	}
	//var_dump( empty($colors[0]) );
	if(!empty($colorless) || empty($colors[0])){
		if($html){
			$out .= clrtoimg($colorless);
		}else{
			$out .= $colorless;
		}
	}

	//Render multi-colors first
	if(false){
		//Render multi-colors
		for($i = 4; $i > 1; $i--){
			if(isset($colors[$i]) && is_array($colors[$i])) {
				uksort($colors[$i], 'magic_cc_sort_cmp');
				
				foreach($colors[$i] as $color => $qty){
					if($html){
						$out .= str_repeat(clrtoimg($color), $qty);
					}else{
						$out .= str_repeat('{' . $color . '}', $qty);
					}
				}
			}
		}
		//Render single colors
		if (isset($colors[1]) && is_array($colors[1])){
			uksort($colors[$i],'magic_cc_sort_cmp');
			foreach ($colors[1] as $color => $qty){
				if($html){
					$out .= str_repeat(clrtoimg($color), $qty);
				}else{
					$out .= str_repeat($color, $qty);
				}
			}
		}
	}else{
		//Render all colors at once (sort "in-between" instead of "multis-first")
		uksort($colors[0], 'magic_cc_sort_cmp');
		foreach($colors[0] as $color => $qty){
			if(strlen($color) > 1){
				if ($html){
					$out .= str_repeat(clrtoimg($color), $qty);
				}else{
					$out .= str_repeat('{' . $color . '}', $qty);
				}
			}else{
				if ($html){
					$out .= str_repeat(clrtoimg($color), $qty);
				}else{
					$out .= str_repeat($color, $qty);
				}
			}
		}
	}

	return $out;
}

function clrtoimg($clr){
	$clr = strtr($clr, array('T' => 'tap', 'X' => 'x'));
	return "<img alt=\"$clr\" src=\"http://gatherer.wizards.com/handlers/image.ashx?size=medium&name=$clr&type=symbol\"/>";
}

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

function test_magic(){
	assert(ccparse('3W') == '3W');
	assert(ccparse('34W') == '34W');
	assert(ccparse('3 4W') == '7W');
	//assert(ccparse('5WGB {GB} {BG} {3B} {RGU}') == '5{3B}WB{BG}{BG}{RGU}{G}'); //if you use the other sorting method this will fail
	//echo ccparse('5WGB {GB} {BG} {3B} {RGU}');
	ccparse("{GB}{BG}{WU}{UW}{UB}{BU}{BR}{RB}{GW}{WG}{WB}{UR}{RU}{RW}{WR}{GU}{UG}");
	ccparse("{WUBRG}{GRBUW}");

	ccparse("XR");
	ccparse("");
	ccparse("{RG}RG");
	ccparse('{2G}{BG}{RG}');
	ccparse('W{BU}');
	ccparse('{2BU}{2BU}{2BU}');
	echo ccparse('T5WGB {GB} {BG} {2B} {WBR}', true);
}
test_magic();
