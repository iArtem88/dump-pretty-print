<?php
	/**
	 * PHP pretty print is a php class for printing php-variables in a easy way. 
	 * Supports embedded arrays etc. Good for debugging.
	 * 
	 * Initial version by Eugeny Pavlenko
	 * Homepage: http://EugenyPavlenko.com
	 * Date: 19.08.2013
	 * License: MIT
	 */
	class PP {
		// some settings, you can easily change
		private
			//$printTime = true,
			//$printBackTraces = true,
			$doAlignKeys                    = true,
			$doCollapseLongStrings          = true,
			// if string's length exceeds that number, the string is then considered "long"
			$longStringsLength              = 100,
			$doCollapseKeys                 = true,
			$keysCountToLeaveWhenCollapsing = 5,
			// do apply key collapsing only if array contains certain number of keys
			$applyKeyCollapseKeysLimit      = 10;
			
		private
			// to be unique in a DOM
			$libPrefix          = 'phpPrettyPrint_',
			$ulMarginLeft       = 20,
			$ulMarginLeftLevels = 20,
			$tablesStyle        = 'border-collapse: collapse;font-size: 14px;',
			$collapseSpanStyle  = 'border: 1px solid #dc143c; color: #dc143c; cursor: pointer; font-size: 11px; padding: 0px;',
			$collapseSpanText   = '...',
			$manySeparator      = "<br><br>";
		
		private function isAssocArr($arr)	{
			return (bool)count(array_filter(array_keys($arr), 'is_string'));
		}

		private function genUniqueStr() {
			return uniqid($this->libPrefix) . str_replace(".", "_", strval(microtime(true)));
		}
		
		private function printKey($k, $keysMaxLen, $assoc) {
			// 2 means two quotes (")
			$plus = $assoc ? 2 : 0;
			
			if (is_string($k)) {
				
				$k = "\"{$k}\"";
				
				if ($this->doAlignKeys) {
					$k = str_pad($k, $keysMaxLen+$plus);
					$k = str_replace(' ', '&nbsp;', $k);
				}
				
				return "<span style='color: #8b0000'>{$k}</span>";
				
			} else {
				
				if ($this->doAlignKeys) {
					$k = str_pad(strval($k), $keysMaxLen+$plus);
					$k = str_replace(' ', '&nbsp;', $k);
				}
				
				return "<span style='color: #8b0000'>{$k}</span>";
			}
		}

		private function printValue($v) {
			if (is_string($v)) {
				if ($this->doCollapseLongStrings && (mb_strlen($v) > $this->longStringsLength)) {
					$v      = htmlspecialchars($v, null, 'UTF-8');
					$tmpStr = '';
					$tmpArr = explode(' ', $v);
					$i      = 0;
					
					while (mb_strlen($tmpStr) < $this->longStringsLength) {
						$tmpStr .= $tmpArr[$i] . ' ';
						$i++;
					}
					
					$spanTmpId      = $this->genUniqueStr()."_spanTmp";
					$spanTmpIdMore  = $this->genUniqueStr()."_spanTmpMore";
					$spanFullId     = $this->genUniqueStr()."_spanFull";
					
					$spanTmp = "<span id='{$spanTmpId}' style='color: #8b0000'>\"{$tmpStr}";
					$spanTmp .= "<span id='{$spanTmpIdMore}' onclick='document.getElementById(\"{$spanTmpIdMore}\").style.display=\"none\";document.getElementById(\"{$spanTmpId}\").style.display=\"none\";document.getElementById(\"{$spanFullId}\").style.display=\"inline\";' style='{$this->collapseSpanStyle}'>{$this->collapseSpanText}</span>";
					$spanTmp .= "\"</span>";
					
					//$v = $tmpStr;
					$spanFull = "<span id='{$spanFullId}' style='display: none; color: #8b0000'>\"{$v}\"</span>";
					
					return $spanFull . $spanTmp;
				} else {
					$v = htmlspecialchars($v, null, 'UTF-8');
					return "<span style='color: #8b0000'>\"{$v}\"</span>";	
				}
			} elseif (is_array($v)) {
				$v = "[]";
				return "<span style='font-weight: bold'>{$v}</span>";
			} elseif (is_bool($v)) {
				$v = ($v) ? "true" : "false";
				return "<span style='font-weight: bold'>{$v}</span>";
			} elseif (is_null($v)) {
				$v = "null";
				return "<span style='color: #00008b; font-weight: bold'>{$v}</span>";
			} elseif (is_object($v)) {
				$v = trim(print_r($v, true));
				return "<span style='color: #000'><pre style='font-size: 13px; padding: 0px; margin: 0px; display: inline'>{$v}</pre></span>";
			} else {
				$v = print_r($v, true);
				return "<span style='color: #00008b'>{$v}</span>";
			}
		}
		
		private function getArrayMaxKeyLength($arr) {
			$keyLen = 0;
			foreach ($arr as $key => $val) {
				$t = (string)$key;
				if (mb_strlen($t) > $keyLen)
					$keyLen = mb_strlen($t);
			}
			
			return $keyLen;
		}
		
		private function doRecursion($someVar, $firstCall, $level= 0, $uniqueId) {
			$isArray      = is_array($someVar);
			$isAssocArray = $isArray ? $this->isAssocArr($someVar) : false;
			$keysCount    = $isArray ? count($someVar) : 0;
			$keyLen       = $isArray ? $this->getArrayMaxKeyLength($someVar) : 0;
			
			if ($firstCall) {
				$mrg     = ($isArray && $keysCount) ? '0 0 0 '.$this->ulMarginLeft.'px' : '0';
				
				$retStr  = ($isArray && $keysCount ? ($isAssocArray ? '{' : '[') : '');
				$retStr .= '<ul style="list-style-type: none; padding: 0px; margin: '.$mrg.'">';
			} else {
				$retStr  = '<ul style="list-style-type: none; padding: 0px; margin-left: '.$this->ulMarginLeftLevels.'px;">';
			}

			
			if ($isArray && ($keysCount > 0)) {
				
				$i = 0;

				foreach ($someVar as $key => $val) {
					
					$comma        = ($i == $keysCount-1) ? '' : ',';
					$bracketOpen  = (is_array($val) && count($val)) ? ($this->isAssocArr($val) ? '{' : '[') : '';
					$bracketClose = ($bracketOpen == '') ? '' : (($bracketOpen == '{') ? '}' : ']');
					$pKey         = $this->printKey($key, $keyLen, $isAssocArray);
					$pVal         = (is_array($val) && count($val)) ? $this->doRecursion($val, false, $level+1, $uniqueId) :  $this->printValue($val);
					$liVisibility = ($this->doCollapseKeys && ($keysCount >= $this->applyKeyCollapseKeysLimit) && (($i+1) > $this->keysCountToLeaveWhenCollapsing)) ? 'none' : 'inline';
					
					$retStr      .= "<li style='display: {$liVisibility}' class='{$this->libPrefix}le{$level}'><table class='{$this->libPrefix}table' style='{$this->tablesStyle}'><tr valign='top'>";
					$retStr      .= "<td>{$pKey}: </td>";
					$retStr      .= "<td>{$bracketOpen}{$pVal}{$bracketClose}{$comma}</td>";
					$retStr      .= "</tr></table></li>";

					if ($this->doCollapseKeys && ($keysCount >= $this->applyKeyCollapseKeysLimit) && (($i+1) == $this->keysCountToLeaveWhenCollapsing)) {
						$spanId       = $this->genUniqueStr().'_span'; 
						$retStr      .= "<span id='{$spanId}' onclick='{$uniqueId}_showCollapsedKeys({$level}); (function(x){x.parentNode.removeChild(x);})(document.getElementById(\"{$spanId}\"))' style='{$this->collapseSpanStyle}'>{$this->collapseSpanText}</span>";	
					}
					
					$i++;
				}
				
			} else {
				$retStr .= '<li>' . $this->printValue($someVar) . '</li>';
			}

			$retStr .= '</ul>';
			
			if ($firstCall && $isArray && $keysCount) {
				$retStr .= $isAssocArray ? '}' : ']';
			}

			return $retStr;
		}
		
		private function doPrettyPrint($someVar, $comment = '') {
			$uniqueId = $this->genUniqueStr();
			
			$r = $this->doRecursion($someVar, true, 0 , $uniqueId);

			$o = <<<EOF
<script>
	function {$uniqueId}_showCollapsedKeys(lev) {
		var c = '{$this->libPrefix}le'+lev;
		var elems = document.getElementById('{$uniqueId}_div').getElementsByClassName(c);
		for (var i = 0; i != elems.length; ++i) {
			elems[i].style.display = "inline";
		}
	}
</script>
<div id="{$uniqueId}_div">
	<div>{$comment}</div>
	<div style="border: 1px solid #d3d3d3; border-radius: 4px; padding: 10px; font-size: 14px; font-family: 'Courier New', Arial; background-color: #eee;">{$r}</div>
</div>
EOF;

			return $o;
		}


		// echoes
		public static function dump() {
			$args       = func_get_args();
			$argsCount  = count($args);
			
			$tmp = new self();

			foreach ($args as $i => $v) {
				echo $tmp->doPrettyPrint($v);
				
				if ($i+1 < $argsCount)
					echo $tmp->manySeparator;
			}
			
		}
		
		// returns
		public static function one($v, $comment = '') {
			$tmp = new self();
			return $tmp->doPrettyPrint($v, $comment);
		}
	}
