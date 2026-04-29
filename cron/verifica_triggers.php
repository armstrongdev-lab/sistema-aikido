<?php
require_once '../includes/db.php'; // ajuste o caminho se necessário

try {
    // Verificar triggers com DEFINER errado
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $definerInvalido = false;
    foreach ($triggers as $trigger) {
        if (strpos($trigger['Definer'], 'root@') !== false) {
            $definerInvalido = true;
            break;
        }
    }

    if ($definerInvalido) {
        // Dropa triggers antigas
        $pdo->exec("DROP TRIGGER IF EXISTS trg_incrementa_treinos");
        $pdo->exec("DROP TRIGGER IF EXISTS trg_decrementa_treinos");

        // Recria sem DEFINER
        $pdo->exec("
        CREATE TRIGGER trg_incrementa_treinos AFTER INSERT ON presencas
        FOR EACH ROW
        BEGIN
            DECLARE faixa_max INT;
            SELECT MAX(id_faixa) INTO faixa_max FROM faixas_treinos WHERE aluno_id = NEW.aluno_id;
            IF faixa_max IS NOT NULL THEN
                UPDATE faixas_treinos SET numero_treinos = numero_treinos + 1
                WHERE aluno_id = NEW.aluno_id AND id_faixa = faixa_max;
            END IF;
        END
        ");

        $pdo->exec("
        CREATE TRIGGER trg_decrementa_treinos AFTER DELETE ON presencas
        FOR EACH ROW
        BEGIN
            DECLARE faixa_max INT;
            SELECT MAX(id_faixa) INTO faixa_max FROM faixas_treinos WHERE aluno_id = OLD.aluno_id;
            IF faixa_max IS NOT NULL THEN
                UPDATE faixas_treinos SET numero_treinos = GREATEST(0, numero_treinos - 1)
                WHERE aluno_id = OLD.aluno_id AND id_faixa = faixa_max;
            END IF;
        END
        ");

        echo "✅ Triggers recriadas automaticamente.";
    } else {
        echo "ℹ️ Triggers já estão corretas.";
    }
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
