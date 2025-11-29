<?php
include 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Adicionar coluna cor na tabela medicos
    // $sql = "ALTER TABLE medicos ADD COLUMN cor VARCHAR(7) DEFAULT '#6c757d'";
    // $stmt = $db->prepare($sql);
    // $stmt->execute();
    
    echo "Coluna 'cor' adicionada com sucesso na tabela medicos!";
    
    // Atualizar médicos existentes com cores
    $cores = ['#2c7fb8', '#7fcdbb', '#edf8b1', '#253237', '#28a745', '#ffc107', '#dc3545'];
    
    $query = "SELECT id FROM medicos";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $index = 0;
    foreach ($medicos as $medico) {
        $cor = $cores[$index % count($cores)];
        $update = "UPDATE medicos SET cor = :cor WHERE id = :id";
        $stmt_update = $db->prepare($update);
        $stmt_update->bindParam(':cor', $cor);
        $stmt_update->bindParam(':id', $medico['id']);
        $stmt_update->execute();
        $index++;
    }
    
    echo "<br>Cores atribuídas aos médicos existentes!";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>