<?php

/* MYSQL variables*/
/*  $dbhost     = '10.1.0.184';
 $dbuser     = 'glpi';
 $dbpassword = 'glpi.*2017';
 $dbdefault  = 'GLPI';
 $conn       = null; */
 $dbhost     = '10.1.1.1';
 $dbuser     = 'glpi';
 $dbpassword = 'Glpi.2018';
 $dbdefault  = 'glpi';
 $conn       = null;


 $host='10.1.0.113:1521/PAS8';
 $usuario='UTIC';
 $password='UT1C9090*';

$rootCode=0;

function searchEntitie($entitie_name) {
     
        // Create connection
        $GLOBALS['conn'] = new mysqli($GLOBALS['dbhost'], $GLOBALS['dbuser'], $GLOBALS['dbpassword'], $GLOBALS['dbdefault']);
        // Check connection
        if ($GLOBALS['conn']->connect_error) {
            die("Connection failed: " . $GLOBALS['conn']->connect_error);
        } 
        $sql = "SELECT `id`, `name`,`entities_id`, `completename`, `comment`, `level`, `sons_cache`, `ancestors_cache` FROM glpi_entities where name ='".$entitie_name."'";
        $result = $GLOBALS['conn']->query($sql);

        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()) {
                return $row;
            }
        } else {
           return null;
        }
        
}
function updateRoot($code) {
     
    // Create connection
    $GLOBALS['conn'] = new mysqli($GLOBALS['dbhost'], $GLOBALS['dbuser'], $GLOBALS['dbpassword'], $GLOBALS['dbdefault']);
    // Check connection
    if ($GLOBALS['conn']->connect_error) {
        die("Connection failed: " . $GLOBALS['conn']->connect_error);
    } 
    $sql = "UPDATE glpi_entities SET id ='".$code."' WHERE id=12 ";
    $result = $GLOBALS['conn']->query($sql);

    if ( $GLOBALS['conn'] ->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" .  $GLOBALS['conn'] ->error;
    }

}
function insertEntity($id,$name,$ancestor,$level,$completename,$antecesor_cache) {
     
    // Create connection
    $GLOBALS['conn'] = new mysqli($GLOBALS['dbhost'], $GLOBALS['dbuser'], $GLOBALS['dbpassword'], $GLOBALS['dbdefault']);
    // Check connection
    if ($GLOBALS['conn']->connect_error) {
        die("Connection failed: " . $GLOBALS['conn']->connect_error);
    } 
    $sql = "INSERT INTO glpi.glpi_entities (id,`name`,entities_id,completename,`level`,ancestors_cache,date_mod, date_creation)
     VALUES ($id,'".$name."',$ancestor,'".$completename."',$level,'".$antecesor_cache."',NOW(),NOW())";
    $result = $GLOBALS['conn']->query($sql);

    if ( $GLOBALS['conn'] ->query($sql) === TRUE) {
        //echo "New record created successfully";
    } else {
        die($GLOBALS['conn'] ->error);
    }

}
$connOracle=oci_connect($usuario,$password,$host);
$query="SELECT FTVORGN_ORGN_CODE, FTVORGN_TITLE FROM FTVORGN WHERE FTVORGN_ORGN_CODE_PRED IS NULL AND FTVORGN_ORGN_CODE!= '330802'";
$resultado=oci_parse($connOracle,$query);
@oci_execute($resultado);
while (($row = oci_fetch_array($resultado, OCI_BOTH)) != false) {
     $rootCode=$row[0];
     $rootName =$row[1];
}
oci_close($connOracle);
updateRoot($rootCode);
//endConnection();

     $connOracle=oci_connect($usuario,$password,$host);
    $query="SELECT FTVORGN_TITLE, FTVORGN_ORGN_CODE_PRED, FTVORGN_ORGN_CODE, SYS_CONNECT_BY_PATH(FTVORGN_ORGN_CODE,',') recorrido,SYS_CONNECT_BY_PATH(FTVORGN_TITLE,'>') Entidad FROM FTVORGN
    START WITH FTVORGN_ORGN_CODE_PRED IS NULL
    CONNECT BY PRIOR FTVORGN_ORGN_CODE =FTVORGN_ORGN_CODE_PRED";
    $resultado=oci_parse($connOracle,$query);
    @oci_execute($resultado);
    echo "<table border='1'>\n";
    while (($row = oci_fetch_array($resultado, OCI_BOTH)) != false) {
        echo "<tr>\n";
        // Usar nombres de columna en mayúsculas para los índices del array asociativo
        if(searchEntitie($row['FTVORGN_TITLE'])==null){
            echo "<td>". $row['FTVORGN_TITLE']   . "</td>\n";
            echo "<td>". $row['FTVORGN_ORGN_CODE_PRED']   . "</td>\n";
            echo "<td>". $row['FTVORGN_ORGN_CODE']   . "</td>\n";
             // ARRAY OF FATHERS
            $antecesor="[";
            $array=explode(',', $row[3]);
            foreach($array as $pos=>$value)
            {
                if($pos!=0&&$pos!=(count($array)-1))
                {
                    if($pos==1){
                        $antecesor=$antecesor.'"'.$value.'"';
                    }
                    else
                    {
                        $antecesor=$antecesor.','.'"'.$value.'"';
                    }
                    
                }
                
            }
            $antecesor=$antecesor."]";
            echo "<td>". $antecesor. "</td>\n";
            //LEVEL
            $count=0;
            $strArray = count_chars($row[4]."",1);
            foreach ($strArray as $key=>$value)
            {
                if(strcmp(chr($key)."", ">") == 0)
                $count=$value;
                //echo $value." ";
            }
            echo "<td>". $row[4]."". $count."</td>\n";
            insertEntity($row['FTVORGN_ORGN_CODE']+0,$row['FTVORGN_TITLE'],$row['FTVORGN_ORGN_CODE_PRED'],$count,$row[4],$antecesor);
        }
       
       // echo $row[1] . " y " . $row['DEPARTMENT_NAME'] . " son lo mismo<br>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    oci_close($connOracle); 

?>