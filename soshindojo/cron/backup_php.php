<?php
// Configurações dos bancos
$host = "127.0.0.1";
$user = "admin";
$pass = "admin"; // substitua
$dbs = ["aikido_db"];

// Pasta onde o backup será salvo
$backup_dir = "/home/aikido_db/backups/";
if(!is_dir($backup_dir)) mkdir($backup_dir, 0777, true);

// Função para gerar backup de um banco via PHP puro
function backup_database($host, $user, $pass, $dbname, $backup_dir){
    $conn = new mysqli($host, $user, $pass, $dbname);
    if($conn->connect_error){
        return "Erro ao conectar no banco $dbname: " . $conn->connect_error;
    }

    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while($row = $result->fetch_array()){
        $tables[] = $row[0];
    }

    $sql_dump = "";
    foreach($tables as $table){
        $result = $conn->query("SELECT * FROM `$table`");
        $num_fields = $result->field_count;

        $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
        $row2 = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
        $sql_dump .= $row2[1] . ";\n\n";

        while($row_data = $result->fetch_assoc()){
            $sql_dump .= "INSERT INTO `$table` VALUES(";
            $vals = [];
            foreach($row_data as $value){
                $vals[] = isset($value) ? "'" . addslashes($value) . "'" : "NULL";
            }
            $sql_dump .= implode(", ", $vals);
            $sql_dump .= ");\n";
        }
        $sql_dump .= "\n\n";
    }

    $filename = $backup_dir . $dbname . "_" . date("Ymd_His") . ".sql";
    file_put_contents($filename, $sql_dump);

    $conn->close();
    return $filename;
}

// Criar backups
foreach($dbs as $db){
    $arquivo = backup_database($host, $user, $pass, $db, $backup_dir);
    if(file_exists($arquivo)){
        echo "Backup do banco $db criado com sucesso.<br>";
    } else {
        echo $arquivo . "<br>";
    }
}
?>
