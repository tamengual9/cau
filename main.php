<?php
//Mira si la cookie no ha estat establerta o si no ha caducat. En els dos casos, torna a LOGIN.
if ( !isset ($_SESSION['myusername']) ) {
    session_destroy();
    header("location:index.php");
    die();
}
else
    $username = $_SESSION['myusername'];

require_once '_basic.php';

//Function that loads a Class File needed, in case we forgot to put the require_once clause.
function my_autoloader($class) {
    require_once 'model/' . $class . '.php';
}
spl_autoload_register('my_autoloader');

$tables = new Tables();

//Carrega fitxer de configuració si no disposam de maxrows com a parametre.
if (isset($_GET['maxrows']))
    $maxRows = $_GET['maxrows'];
else {
    $xml = loadConfigFile("_config.xml");
    $maxRows = $xml->params->param[0]->value;
}

$id =  (isset($_GET['id'])) ?   htmlentities($_GET['id'])  :  0;
$pg =  (isset($_GET['pg'])) ?   $_GET['pg']                :  "issues";

//Get member identification.
$rowmember = $tables->getFirstRow("SELECT id FROM members WHERE username='$username'");

/* Controllers */

switch ($pg) {
    case "added":
        include 'issues_added.php';
        break;
    default: /* issues */
        $rowmember = $tables->getFirstRow("SELECT id FROM members WHERE username='$username'");
        $memberid = $rowmember['id'];
        //Si es un User no Admin, seleccionar Issues per user.
        $sqlMember = (!isUserAdmin($rowmember['id'])) ? " AND fkey_member=$memberid "  :  "";

        //Tipus de Issues que he demostrar= obertes o tancades
        $class = (isset($_GET['class'])) ? (   ($_GET['class']=='0') ? 0 : 1   ) : 0;
        $classOfIssues = ($class==0) ? "fkey_state='1'" : "(fkey_state='2' OR fkey_state='3')";

        //Selecciona Files
        $sql =  "SELECT iss.id, iss.name, iss.descripcio, iss.date_start, iss.fkey_member AS fkey_member, "
                . "iss.bool_checked, me.username "
                . "FROM issues iss INNER JOIN members me ON iss.fkey_member=me.id "
                . "WHERE $classOfIssues "
                . "$sqlMember ORDER BY id DESC";
        $rows = $tables->executaQuery($sql);
        
        //Get the Issues that have Comments by another user unchecked by 
        $sql2 = "SELECT iss.id"
                ." FROM issues iss"
                        ." LEFT OUTER JOIN comments co ON iss.id = co.fkey_issue"
                ." WHERE co.bool_checked=0"
                       . ( ($username!="admin") ? " AND iss.fkey_member='$memberid'" : "" )
                       . " AND co.fkey_member <> $memberid"
                ." GROUP BY iss.id";
        $prov = $tables->executaQuery($sql2);
        $issuesWithComments = array();
        foreach ($prov as $p) {
            array_push($issuesWithComments, $p['id']);
        }
        $count=0;
        break;
}        

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <!-- Ajustam el contingut a la pantalla, tal i com és desitjable en disp mòbils -->
    <meta id="meta" name="viewport" content="width=device-width; initial-scale=1.0" />
    <title>CAU - EASDIB 1.0</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script type="text/javascript" src="js/main.js"></script>
    <link rel="stylesheet" type="text/css" href="css/main.css"/>

<!-- Twitter BootStrap 3.0 -->
<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css"/>
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>    
    
    <!-- DATE PICKER JQUERY -->
    <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>        
</head>
<body>
<?php                                
        /* "Generate" Template */
        switch ($pg) {
            case "issues":
                include 'templates/issues.php';
                break;
            
            case "added":
                if (isUserAdmin($rowmember['id']))
                    include 'templates/issues_added_bk_tpl.php';
                else
                    if (!isset($row))   //Add a New Issue
                        include 'templates/issues_add_tpl.php';
                    else        //Edit an Issue
                        include 'templates/issues_ed_tpl.php';         
                break;
            
            default:
                include 'issues.php';
                break;
        }        
?>
        <!--   To show Messages or Error to the User  xtoni  -->
    <div id="error_or_message">
<?php       if (isset($_GET['errm'])) echo $_GET['errm']; ?>
    </div>

<?php include 'templates/footer.php';   ?>   
</body>
</html>