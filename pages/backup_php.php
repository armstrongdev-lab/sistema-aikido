<?php
// Configurações dos bancos
$host = "10.132.36.3";
$user = "cmwclini7b57831c_admin";
$pass = "Arms@060788"; // substitua
$dbs = ["cmwclini7b57831c_aikido_db", "cmwclini7b57831c_agenda_terapia"];

// Pasta onde o backup será salvo
$backup_dir = "/home/cmwclini7b57831c/backups/";
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

// Apagar backups com mais de 30 dias
foreach (glob($backup_dir . "*.sql") as $file) {
    if (time() - filemtime($file) > 30*24*60*60) { // 30 dias em segundos
        unlink($file);
        echo "Backup antigo removido: " . basename($file) . "<br>";
    }
}
?>
