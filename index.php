<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="keywords" content="stop words, stopwords, stopword, strip stop words, text analysis, word cloud, strip stopwords, natural language processing" />

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Stop-Word Stripper - IanMonroe.com</title>
<style type="text/css">
<!--
@import url(https://fonts.googleapis.com/css?family=Copse);
@import url(https://fonts.googleapis.com/css?family=Puritan);
@import url(https://fonts.googleapis.com/css?family=Permanent+Marker);

body {

  background: #fff url('https://www.ianmonroe.com/sentiment/images/7820parchment.jpg') center center fixed no-repeat;
  -moz-background-size: cover;
  background-size: cover;
  font-family: 'Puritan', arial, serif;
  font-size:14px;
}
.content_box {
	-webkit-transition-duration: 0.2s, 0.2s;
	-webkit-transition-property: background-color, color;
	-webkit-transition-timing-function: cubic-bezier(0.42, 0, 1, 1), cubic-bezier(0.42, 0, 1, 1);
	
	background-attachment: scroll;
	background-clip: border-box;
	background-color: rgba(0, 0, 0, 0.0976562);
	background-image: none;
	background-origin: padding-box;
	padding: 10px;
	border: 1px solid #C0C0C0;
	border-top-left-radius: 4px;
	border-bottom-left-radius: 4px;
	border-top-right-radius: 4px;
	border-bottom-left-radius: 4px;
}

h1, h2, h3, h4 {
font-family: 'Permanent Marker', arial, serif;
}

h1{
font-size:24px;
}

h2{
font-size:16px;
}

h3{
font-size:14px;
}

h4{
font-size:12px;
}


-->
</style>
</head>

<body>
<div class="content_box">
<p><a href="https://www.ianmonroe.com/"><img src="http://www.ianmonroe.com/wp-content/themes/im_enterprises/images/logo.png" alt="IanMonroe.com" width="286" height="60" align="left" /></a><br /></p>
<h1><br />Stop-Word Stripper 1.0<br />
  by Ian Monroe</h1>
  <!-- AddToAny BEGIN -->
<div class="a2a_kit a2a_default_style">
<a class="a2a_dd" href="http://www.addtoany.com/share_save?linkurl=http%3A%2F%2Fwww.ianmonroe.com%2Fstopstrip%2F&amp;linkname=Stop-Word Stripper%20-%20IanMonroe.com">Share</a>
<span class="a2a_divider"></span>
<a class="a2a_button_facebook"></a>
<a class="a2a_button_twitter"></a>
<a class="a2a_button_email"></a>
</div>
<script type="text/javascript">
var a2a_config = a2a_config || {};
a2a_config.linkname = "Stop-word Stripper - IanMonroe.com";
a2a_config.linkurl = "https://www.ianmonroe.com/stopstrip/";
</script>
<script type="text/javascript" src="https://static.addtoany.com/menu/page.js"></script>
<!-- AddToAny END -->
  </div>
<div class="content_box" style="width=600px;">

<?php 
ini_set("memory_limit","128M");

function fixEncoding($in_str) 
{ 
  $cur_encoding = mb_detect_encoding($in_str) ; 
  if($cur_encoding == "UTF-8" && mb_check_encoding($in_str,"UTF-8")) 
    return $in_str; 
  else 
    return utf8_encode($in_str); 
} // fixEncoding 


/**
 * Strip punctuation from text.
 */
function strip_punctuation( $text )
{
    $urlbrackets    = '\[\]\(\)';
    $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
    $urlspaceafter  = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
    $urlall         = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;
 
    $specialquotes  = '\'"\*<>';
 
    $fullstop       = '\x{002E}\x{FE52}\x{FF0E}';
    $comma          = '\x{002C}\x{FE50}\x{FF0C}';
    $arabsep        = '\x{066B}\x{066C}';
    $numseparators  = $fullstop . $comma . $arabsep;
 
    $numbersign     = '\x{0023}\x{FE5F}\x{FF03}';
    $percent        = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
    $prime          = '\x{2032}\x{2033}\x{2034}\x{2057}';
    $nummodifiers   = $numbersign . $percent . $prime;
 
    return preg_replace(
        array(
        // Remove separator, control, formatting, surrogate,
        // open/close quotes.
            '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
        // Remove other punctuation except special cases
            '/\p{Po}(?<![' . $specialquotes .
                $numseparators . $urlall . $nummodifiers . '])/u',
        // Remove non-URL open/close brackets, except URL brackets.
            '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
        // Remove special quotes, dashes, connectors, number
        // separators, and URL characters followed by a space
            '/[' . $specialquotes . $numseparators . $urlspaceafter .
                '\p{Pd}\p{Pc}]+((?= )|$)/u',
        // Remove special quotes, connectors, and URL characters
        // preceded by a space
            '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
        // Remove dashes preceded by a space, but not followed by a number
            '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
        // Remove consecutive spaces
            '/ +/',
        ),
        ' ',
        $text );
} // end strip_punctuation


//We get into the meat of the script here

//Now, first things first, let's upload the file and parse it.
// define a directory to store the uploaded files in .. 
$feed_cache_dir = 'uploaded/'; 
// delete cached files older than this many days 
$days = "15";  
$new_count = 0;
$updated_count = 0;

if (isset($_POST["submission"])){
	     
		$buffer = file_get_contents("./stopwords.txt");

		$buffer = fixEncoding($buffer);
		$DictionaryArray = explode("~", $buffer);	
		$finished_dictionary = $DictionaryArray;
			
			$buffer = $_POST["mash_text"];
			$buffer = fixEncoding($buffer);
			$buffer = strip_tags($buffer);
			$buffer = preg_replace('/-/', ' ', $buffer);
			$MashArray = preg_split("/[\s,]+/", $buffer);	
			
		$reduced_mash = array_unique($MashArray);
		foreach($reduced_mash as &$dic_word){
			$dic_word = strip_punctuation($dic_word);
			$dic_word = strtolower($dic_word);
			$dic_word = trim($dic_word);
			
		}
		$finished_mash = array_unique($reduced_mash);
		
		$bad_words = array_diff($finished_mash, $finished_dictionary);
		
	$censor_string = " ";	
	if (true){	
		
		echo "<p>Here's your text, stripped of stop-words.</p><hr />";
		
			$linebuffer = $_POST["mash_text"];
			$linebuffer = strtolower($linebuffer);
			$input_text_array = strip_punctuation($linebuffer);
			$input_text_array = explode(" ", $input_text_array);
			$output_text_array = array_diff($input_text_array, $finished_dictionary);

			$finaloutput = "";
			foreach ($output_text_array as $output_word){
				if ( strlen($output_word)>=2 ){
					$finaloutput = $finaloutput." ".$output_word;
				}
			}
			echo $finaloutput;
			echo '<br /><hr />';

			

			?>
            <p><a href="https://www.ianmonroe.com/stopstrip">Try again? Click here</a></p>
            <table width="100%" border="0" align="center">
  <tr>
    <td align="center"></td>
  </tr>
</table>
            <?
		//}
	}
	   

} else { 
 

?> 

<p>Are you doing some impromptu text analysis, and need a way to see only the most important words in a blob of text?</p>
<p>Creating a <a href="http://www.wordle.com" target="_blank" >word cloud</a> from expressive text, and need to get down to the nitty gritty?</p>
<p>Wondering what a piece of text would look like, if you remove all the unnecessary bits?</p>

<p>Try Ian's (un)patented Stop-Word Stripper!</p>

<p>Go on, paste your blob of text in the box below, and see what happens.</p>

<form enctype="multipart/form-data" action="https://www.ianmonroe.com/stopstrip/index.php" method="POST"> 
  <p>Paste your text here:</p> <p><textarea name="mash_text" cols="80" rows="10">Paste text here!</textarea></p>
  <input name="submission" type="hidden" value="true" />
  <!--<p>Upload a file to "correct": <input name="mash" type="file" /></p> -->
  <p>
   <input type=submit value=Upload />   
    </p>
</form> 
<?php 
} 
?> 




<br /><br />
<p><a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/"><img class="alignleft" style="border-width: 0;" src="http://i.creativecommons.org/l/by-nc-sa/3.0/us/88x31.png" alt="Creative Commons License" width="88" height="31" /></a> <br /> By Ian Monroe, 2011</p>
</div>

</body>
</html>
