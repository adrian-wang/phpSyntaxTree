<?php

// phpSyntaxTree - A syntax tree graph generator writtn in PHP.
// Copyright (c) 2003-2005 Andre Eisenbach <andre@ironcreek.net>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id: index.php,v 1.14 2005/08/22 23:31:16 int2str Exp $

define( 'VERSION', '2.0-BRANCH' );

define( 'LOG_PHRASE', 0 );
define( 'LOG_LANG', 1 );

require_once( 'lib/class.template.php' );
require_once( 'src/counter.php' );
require_once( 'src/log.php' );
require_once( 'src/lang.php' );

define( 'HTML_CHECKED',   'checked="checked"' );
define( 'HTML_UNCHECKED', '' );
define( 'HTML_SELECTED', 'selected="selected"' );

// Start a session which is used to pass form data
//   to the image generating PHP.

session_start();

$page = NULL;

// Check if a new form was submitted and store
//   the data in session variables accordingly

if( isset( $_POST['data'] ) )
{
    // Initialize the main page template

    $page = new CTemplate( GetLocalizedFName( 'tpl/main.html' ));

    $_SESSION['data']      = trim( $_POST['data'] );

    $_SESSION['color']     = ( isset( $_POST['color'] )     ? TRUE : FALSE ); 
    $_SESSION['antialias'] = ( isset( $_POST['antialias'] ) ? TRUE : FALSE ); 
    $_SESSION['autosub']   = ( isset( $_POST['autosub'] )   ? TRUE : FALSE ); 
    $_SESSION['triangles'] = ( isset( $_POST['triangles'] ) ? TRUE : FALSE ); 

    if ( isset( $_POST['font'] ) )
    {
        $font = 'lsansuni.ttf';
        switch( $_POST['font'] )
        {
            case 'vera_sans':
                $font = 'Vera.ttf';
                break;
            
            case 'vera_serif':
                $font = 'VeraSe.ttf';
                break;
                
            default:
                break;
        }
        
        $_SESSION['fontsel'] = $_POST['font'];
        $_SESSION['font']    = $font;
    }
    
    $_SESSION['fontsize'] = isset( $_POST['fontsize'] ) 
        ? intval( $_POST['fontsize'] ) : 8;
    
    // Increment phrase counter (displayed on the bottom of the page)
    AddCounter();

    // Log phrase
    if ( LOG_PHRASE )
        LogPhrase( $_SESSION['data'] );

    // Log language settings, so we get a feeling for
    // which translations we'll need...
    if ( LOG_LANG )
        LogLangSettings();
} else {
    // If no phrase was submitted, show 
    //   usage information, news, etc. .

    $page = new CTemplate( GetLocalizedFname( 'tpl/intro.html' ));
}

// If we don't have data from the form or a
//   previous phrase stored in the sessions,
//   load the example data.

if ( !isset( $_SESSION['data'] ) )
{
    $_SESSION['data']      = file_get_contents( GetLocalizedFname( "var/sample.phrase" )); 
    $_SESSION['color']     = TRUE; 
    $_SESSION['antialias'] = TRUE; 
    $_SESSION['autosub']   = TRUE;
    $_SESSION['triangles'] = TRUE;
    $_SESSION['fontsel']   = "vera_sans";
    $_SESSION['font']      = "Vera.ttf";
    $_SESSION['fontsize']  = 8;
}

// Render the page

$phrase = htmlentities( $_SESSION['data'] );

$img    = sprintf( "<img src=\"stgraph.png.php?%s\" alt=\"\" title=\"%s\"/>", SID, $phrase );
$graph  = sprintf( "<a href=\"dnlgraph.php?%s\">%s</a>", SID, $img );
$icon   = "<img src=\"img/vectorgfx.png\" alt=\"SVG\" />";
$svg    = sprintf( "<div id=\"svg\"><a href=\"stgraph.svg.php?%s\">%s</a></div>", SID, $icon );

$fontoption = sprintf( "SELECT_%s", $_SESSION['fontsel'] );
$sizeoption = sprintf( "SELECT_size_%d", $_SESSION['fontsize'] );

$keepdata = "";
if( isset( $_POST['keepdata'] ) && strlen($_POST['keepdata']) > 1) {
    $keepdata = $_POST['keepdata'];
} else {
    $keepdata = $phrase;
}

$page->SetValues( array(
    'VERSION'       => VERSION,
    'FORM_ACTION'   => sprintf( "?%s", strip_tags( SID )),
    'GRAPH'         => $graph, 
    'SVG'           => $svg,
    'PHRASE'        => $phrase,
    'DATA_VAL'      => $phrase,
    'DATA_CHANGE'   => process($keepdata),
    'KEEP_DATA'     => $keepdata,
    'COLOR_VAL'     => $_SESSION['color']     ? HTML_CHECKED : HTML_UNCHECKED,
    'ANTIALIAS_VAL' => $_SESSION['antialias'] ? HTML_CHECKED : HTML_UNCHECKED,
    'AUTOSUB_VAL'   => $_SESSION['autosub']   ? HTML_CHECKED : HTML_UNCHECKED,
    'TRIANGLES_VAL' => $_SESSION['triangles'] ? HTML_CHECKED : HTML_UNCHECKED,
    'COUNTER'       => GetCounter(),
    $fontoption     => HTML_SELECTED,
    $sizeoption     => HTML_SELECTED
));

$page->Render();

?>
<?PHP
function process($data)
{
	$ret = "";
	if (strlen($data) !== 0) {
		$count = 0;
		$prefix1 = "<label id=\"label";
		$prefix2 = "\" name=\"";
		$prefix3 = "\" onclick=\"colorfunction(";
		$prefix4 = ")\">";
		$postfix = "</label>";
		// echo "<h1>try click on the bracket!<br></h1>";
		$stack = new mystack();
		for ($i=0; $i<strlen($data); $i++) {
			$out = $data[$i];
			if ($out !== '[' && $out !== ']' && $out !== '(' && $out !== ')') {
				$ret = $ret.$out;
			} else if ($out === '[' || $out === '(') {
				$stack->push($count);
				$ret = $ret.$prefix1."+".$count.$prefix2.$i.$prefix3.$count.$prefix4.$out.$postfix;
				$count++;
			} else if ($out === ']' || $out == ')') {
				if ($stack->isEmpty() === 1) {
					$ret = "<h2>not match!</h2>";
					return $ret;
				}
				$match = $stack->pop();
				$ret = $ret.$prefix1."-".$match.$prefix2.$i.$prefix3.$match.$prefix4.$out.$postfix;
			}
		}
		if ($stack->isEmpty() === 0) {
			$ret = "<h2>not match!!</h2>";
			return $ret;
		}
		$count = 0;
	}
	return $ret;
}
class mystack{
	private $top=-1;
	private $stack=array();
	public function isEmpty() {
		if ($this->top==-1)
			return 1;
		else
			return 0;
	}
	public function push($data) {
		$this->stack[++$this->top]=$data;
	}
	public function pop() {
		$ret = $this->stack[$this->top];
		unset($this->stack[$this->top--]);
		return $ret;
	}
}
?>
