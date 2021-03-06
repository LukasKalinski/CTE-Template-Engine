<?php
/**
 * Error presentation in HTML.
 * 
 * - Assumes $error to be set and contain all data-keys, such as
 *   php_file, message, etc.
 * - Assumes that CTE.php is included.
 * - Defines INCLUDED_CTE_MISC, which may be used for verification.
 *
 * @package CTE.misc
 * @since 2007-01-04
 * @version 2007-01-27
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

define('INCLUDED_CTE_MISC_ERRORPRES', true);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>CTE Error</title>
<style type="text/css">
body {
  font-family:    Verdana;
  background:     #A8B7A9
}

#error {
  background:     #6688A0;
  font-size:      10px;
  color:          #FFFFFF;
  padding:        8px;
  border:         1px dashed #FFFFFF
}

#mainTitle {
  font-size:      17px;
  font-weight:    bold;
  margin-bottom:  20px;
}

td.dataRow {
  padding:        2px;
}

td.entryTitle {
  width:          120px;
  font-weight:    bold;
  padding:        2px
}

span.hlight {
  color:            #FEFFA9;
  font-family:      Arial;
  font-size:        14px;
  letter-spacing:   1px
}

#version {
  margin-top:   20px;
  font-size:    10px;
  font-style:   italic
}
</style>
<script type="text/javascript">
<!--

//-->
</script>
</head>
<body>
  <div id="error">
    <div id="mainTitle">
      An Error Occured
    </div>
<?php
if (!isset($error) || !is_array($error)) {
  exit('A CTE error occured but failed to be displayed due to non-followed specifications.');
}

echo '<table>';
foreach ($error as $title => $value) {
  // Interpret formatted text in message:
  if ($title == 'message') {
    $value = preg_replace(
      '!//(.*?)//!',
      '<span class="hlight">\1</span>',
      $value
    );
  }
  
  $title = ucwords(str_replace('_', ' ', $title));
  echo '<tr>';
    echo '<td class="entryTitle" valign="top">';
      echo "$title:";
    echo '</td>';
    echo '<td class="dataRow">';
      echo nl2br($value);
    echo '</td>';
  echo '</tr>';
}
echo '</table>';
?>
  <div id="version">CTE Version <?php echo CTE::VERSION; ?></div>
  </div>
</body>
</html>